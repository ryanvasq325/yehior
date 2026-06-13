<?php

declare(strict_types=1);

# Ativa a checagem estrita de tipos em todo este arquivo

# Namespace da camada de banco de dados (mapeia para a pasta app/database via PSR-4)
namespace App\Database;

# Importa a conexão DBAL usada na execução das seeds
use Doctrine\DBAL\Connection;
# Importa a classe base de comandos do Symfony Console
use Symfony\Component\Console\Command\Command;
# Importa o tipo de argumento de linha de comando
use Symfony\Component\Console\Input\InputArgument;
# Importa o tipo de opção de linha de comando
use Symfony\Component\Console\Input\InputOption;
# Importa a interface de entrada (lê argumentos e opções)
use Symfony\Component\Console\Input\InputInterface;
# Importa a interface de saída (escreve mensagens no terminal)
use Symfony\Component\Console\Output\OutputInterface;

# Classe base de toda seed; as seeds geradas pelo make:seed estendem esta classe
# Fica neste mesmo arquivo de propósito, concentrando todo o mecanismo de seed em um só lugar
abstract class AbstractSeed
{
    # Recebe e guarda a conexão já aberta; readonly impede trocar a referência depois
    public function __construct(
        # Conexão DBAL compartilhada, injetada pelo executor de seeds
        protected readonly Connection $connection
    ) {}

    # Texto curto que descreve o que a seed insere — implementado pela seed concreta
    abstract public function getDescription(): string;

    # Contém os inserts padrão do sistema — implementado pela seed concreta
    abstract public function run(): void;

    # Insere uma linha apenas se ela ainda não existir, deixando a seed segura para reexecução
    # As colunas de conflito precisam ter índice ÚNICO ou serem chave primária na tabela
    protected function insertIfNotExists(
        # Nome da tabela de destino
        string $table,
        # Mapa coluna => valor a ser inserido
        array $data,
        # Colunas que identificam duplicata (a chave única que dispara o ON CONFLICT)
        array $conflictColumns,
        # Tipos DBAL opcionais por coluna (ex: ['ativo' => ParameterType::BOOLEAN])
        array $types = []
    ): void {
        # Guard clause: recusa nome de tabela vazio antes de montar qualquer SQL
        if (trim($table) === '') {
            # Falha cedo com mensagem clara
            throw new \InvalidArgumentException('O nome da tabela não pode ser vazio.');
        }

        # Guard clause: recusa conjunto de dados vazio para não gerar INSERT inválido
        if ($data === []) {
            # Falha cedo com mensagem clara
            throw new \InvalidArgumentException('Os dados do insert não podem estar vazios.');
        }

        # Guard clause: exige ao menos uma coluna de conflito, que é o que garante a idempotência
        if ($conflictColumns === []) {
            # Falha cedo com mensagem clara
            throw new \InvalidArgumentException('Informe ao menos uma coluna de conflito (chave única).');
        }

        # Obtém a plataforma do banco uma única vez para escapar identificadores com segurança
        $platform = $this->connection->getDatabasePlatform();

        # Extrai os nomes das colunas a partir das chaves do array de dados
        $columns = array_keys($data);

        # Cria um placeholder nomeado (:coluna) para cada coluna — protege contra SQL injection
        $placeholders = array_map(static fn(string $c): string => ':' . $c, $columns);

        # Escapa cada identificador de coluna conforme as regras do PostgreSQL
        $quotedColumns = array_map(static fn(string $c): string => $platform->quoteIdentifier($c), $columns);

        # Escapa também cada coluna usada na cláusula ON CONFLICT
        $quotedConflict = array_map(static fn(string $c): string => $platform->quoteIdentifier($c), $conflictColumns);

        # Monta o SQL idempotente: se a chave já existir, o banco simplesmente ignora a linha
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) ON CONFLICT (%s) DO NOTHING',
            # Nome da tabela escapado
            $platform->quoteIdentifier($table),
            # Lista de colunas escapadas
            implode(', ', $quotedColumns),
            # Lista de placeholders nomeados
            implode(', ', $placeholders),
            # Lista de colunas de conflito escapadas
            implode(', ', $quotedConflict)
        );

        # Executa o comando vinculando os valores aos placeholders (e tipos, quando informados)
        $this->connection->executeStatement($sql, $data, $types);
    }
}

# Comando único do mecanismo de seed; o MESMO código atende dois nomes diferentes
# make:seed cria um arquivo de seed; migrations:seed executa as seeds pendentes
final class MakeSeedCommand extends Command
{
    # Nome do comando que cria um arquivo de seed
    public const MAKE_NAME = 'make:seed';
    # Nome do comando que executa as seeds pendentes
    public const RUN_NAME = 'migrations:seed';
    # Tabela que registra quais seeds já foram executadas
    private const VERSIONS_TABLE = 'seed_versions';
    # Prefixo de namespace das classes de seed geradas
    private const SEED_NAMESPACE = 'app\\database\\seed\\';
    # Expressão de nome válido: começa com letra minúscula e usa apenas [a-z0-9_]
    private const NAME_PATTERN = '/^[a-z][a-z0-9_]*$/';

    # Recebe a conexão e o nome com que este comando será registrado no bin/console
    public function __construct(
        # Conexão singleton compartilhada com o restante do sistema
        private readonly Connection $connection,
        # Nome do comando (make:seed ou migrations:seed)
        string $name
    ) {
        # Define o nome na classe base ANTES de o configure() ser chamado pelo Symfony
        parent::__construct($name);
    }

    # Configura descrição e parâmetros conforme o nome com que o comando foi registrado
    protected function configure(): void
    {
        # Se este comando é o de execução, expõe apenas a opção --force
        if ($this->getName() === self::RUN_NAME) {
            # Encadeia a configuração do modo de execução
            $this
                # Texto de ajuda do migrations:seed
                ->setDescription('Executa as seeds pendentes (inserts padrão do sistema)')
                # Opção --force / -f para reexecutar tudo
                ->addOption('force', 'f', InputOption::VALUE_NONE, 'Reexecuta todas as seeds, inclusive as já aplicadas');
            # Encerra: nada mais a configurar neste modo
            return;
        }

        # Caso contrário, este é o comando de criação: expõe o argumento obrigatório 'name'
        $this
            # Texto de ajuda do make:seed
            ->setDescription('Cria um arquivo de seed no formato YYYYMMDDHHmmss_nome.php')
            # Argumento com o nome da seed em snake_case
            ->addArgument('name', InputArgument::REQUIRED, 'Nome da seed em snake_case (ex: usuarios_padrao)');
    }

    # Direciona a execução para o método correto conforme o nome do comando
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        # migrations:seed executa as seeds; make:seed cria o arquivo
        return $this->getName() === self::RUN_NAME
            # Modo execução
            ? $this->runPendingSeeds($input, $output)
            # Modo criação
            : $this->createSeedFile($input, $output);
    }

    # ===================== make:seed =====================

    # Cria o arquivo de uma nova seed a partir do nome informado
    private function createSeedFile(InputInterface $input, OutputInterface $output): int
    {
        # Lê o nome informado pelo usuário e remove espaços nas pontas
        $name = trim((string) $input->getArgument('name'));

        # Guard clause: valida o nome e bloqueia caracteres perigosos (ex: ../ para path traversal)
        if (preg_match(self::NAME_PATTERN, $name) !== 1) {
            # Avisa o formato correto e aborta sem criar arquivo
            $output->writeln('<error>Nome inválido. Use snake_case: comece com letra e use apenas a-z, 0-9 e _ (ex: usuarios_padrao).</error>');
            # Retorna código de falha
            return Command::FAILURE;
        }

        # Gera o carimbo de data/hora que serve de identificador único da seed
        $timestamp = date('YmdHis');
        # Monta o nome da classe seguindo o padrão Seed + timestamp
        $className = 'Seed' . $timestamp;
        # Monta o nome do arquivo no formato timestamp_nome.php
        $fileName = $timestamp . '_' . $name . '.php';
        # Calcula o diretório das seeds a partir da pasta deste arquivo
        $dir = __DIR__ . '/seed';
        # Monta o caminho completo do arquivo a ser criado
        $filePath = $dir . '/' . $fileName;

        # Cria o diretório de seeds caso ele ainda não exista
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            # Falha cedo se não conseguir criar a pasta de destino
            $output->writeln("<error>Não foi possível criar o diretório: {$dir}</error>");
            # Retorna código de falha
            return Command::FAILURE;
        }

        # Bloqueia a criação de duas seeds no mesmo segundo (mesmo timestamp = mesma classe)
        if (glob($dir . '/' . $timestamp . '_*.php') !== []) {
            # Pede para aguardar um segundo para evitar colisão de classe Seed{timestamp}
            $output->writeln('<error>Já existe uma seed com este timestamp. Aguarde 1 segundo e tente novamente.</error>');
            # Retorna código de falha
            return Command::FAILURE;
        }

        # Evita sobrescrever um arquivo já existente com o mesmo caminho
        if (file_exists($filePath)) {
            # Informa o conflito e aborta
            $output->writeln("<error>Arquivo já existe: {$filePath}</error>");
            # Retorna código de falha
            return Command::FAILURE;
        }

        # Grava o conteúdo do template no arquivo e captura quantos bytes foram escritos
        $bytes = file_put_contents($filePath, $this->buildTemplate($className, $name));

        # Guard clause: confirma que a escrita realmente aconteceu
        if ($bytes === false) {
            # Falha cedo se o disco recusar a gravação
            $output->writeln("<error>Falha ao gravar o arquivo: {$filePath}</error>");
            # Retorna código de falha
            return Command::FAILURE;
        }

        # Linha em branco para espaçamento visual
        $output->writeln('');
        # Mensagem de sucesso
        $output->writeln('<info>✔ Seed criada com sucesso!</info>');
        # Mostra o nome do arquivo gerado
        $output->writeln("<comment>  Arquivo : </comment>{$fileName}");
        # Mostra o nome da classe gerada
        $output->writeln("<comment>  Classe  : </comment>{$className}");
        # Mostra o caminho relativo do arquivo gerado
        $output->writeln("<comment>  Caminho : </comment>app/database/seed/{$fileName}");
        # Linha em branco final
        $output->writeln('');

        # Retorna código de sucesso
        return Command::SUCCESS;
    }

    # Monta o conteúdo inicial do arquivo de seed a partir do nome da classe e da descrição
    private function buildTemplate(string $className, string $name): string
    {
        # Heredoc com o esqueleto da seed; \$ escapa variáveis que devem aparecer literais no arquivo
        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace App\Database\Seed;

            use App\Database\AbstractSeed;

            final class {$className} extends AbstractSeed
            {
                # Descrição curta e legível do que esta seed insere
                public function getDescription(): string
                {
                    return '{$name}';
                }

                # Executa os inserts padrão do sistema
                # Use \$this->insertIfNotExists() para que rodar a seed mais de uma vez seja seguro
                public function run(): void
                {
                    # exemplo:
                    # \$this->insertIfNotExists(
                    #     'users',
                    #     ['nome' => 'Admin', 'cpf' => '00000000000', 'administrador' => true],
                    #     ['cpf']
                    # );
                }
            }
            PHP;
    }

    # ===================== migrations:seed =====================

    # Executa, em ordem cronológica, todas as seeds ainda não aplicadas
    private function runPendingSeeds(InputInterface $input, OutputInterface $output): int
    {
        # Lê a opção --force como booleano
        $force = (bool) $input->getOption('force');

        # Garante que a tabela de controle de seeds exista antes de qualquer coisa
        $this->ensureVersionsTable();

        # Calcula o diretório onde as seeds ficam guardadas
        $dir = __DIR__ . '/seed';
        # Lista todos os arquivos .php do diretório de seeds, ou um array vazio se não houver
        $files = glob($dir . '/*.php') ?: [];
        # Ordena os arquivos pelo nome; o prefixo timestamp garante ordem cronológica
        sort($files);

        # Se não há seeds no disco, encerra informando o usuário
        if ($files === []) {
            # Mensagem informativa
            $output->writeln('<comment>Nenhuma seed encontrada em app/database/seed.</comment>');
            # Encerra com sucesso (não há nada a fazer)
            return Command::SUCCESS;
        }

        # Carrega em memória a lista de versões já executadas para consulta rápida
        $executed = $this->loadExecutedVersions();
        # Contador de seeds efetivamente aplicadas nesta execução
        $applied = 0;

        # Percorre cada arquivo de seed na ordem cronológica
        foreach ($files as $file) {
            # Extrai o nome do arquivo sem a extensão (ex: 20260601153144_usuarios_padrao)
            $base = basename($file, '.php');

            # Guard clause: aceita apenas arquivos que começam com 14 dígitos seguidos de '_'
            if (preg_match('/^(\d{14})_/', $base, $matches) !== 1) {
                # Avisa e ignora arquivos fora do padrão de nome
                $output->writeln("<error>Seed ignorada (nome fora do padrão): {$base}</error>");
                # Pula para o próximo arquivo
                continue;
            }

            # O timestamp capturado vira a base do nome da classe
            $timestamp = $matches[1];
            # A versão registrada é o nome completo do arquivo (único mesmo entre seeds do mesmo dia)
            $version = $base;
            # Monta o nome totalmente qualificado da classe (namespace + Seed + timestamp)
            $class = self::SEED_NAMESPACE . 'Seed' . $timestamp;

            # Se não estiver no modo force e a versão já rodou, pula sem reexecutar
            if (!$force && isset($executed[$version])) {
                # Vai para a próxima seed
                continue;
            }

            # Carrega o arquivo da seed (o nome do arquivo não casa com PSR-4, então o require é necessário)
            require_once $file;

            # Guard clause: confirma que a classe esperada realmente existe no arquivo
            if (!class_exists($class)) {
                # Erro claro apontando arquivo e classe esperada
                $output->writeln("<error>Classe '{$class}' não encontrada em {$base}.</error>");
                # Aborta com falha
                return Command::FAILURE;
            }

            # Instancia a seed injetando a conexão compartilhada
            $seed = new $class($this->connection);

            # Guard clause: garante que a classe segue o contrato da AbstractSeed
            if (!$seed instanceof AbstractSeed) {
                # Erro claro exigindo a herança correta
                $output->writeln("<error>'{$class}' precisa estender AbstractSeed.</error>");
                # Aborta com falha
                return Command::FAILURE;
            }

            # Marca o instante inicial para medir o tempo de execução
            $start = microtime(true);

            # Tenta executar a seed e registrar a versão dentro de UMA transação atômica
            try {
                # transactional executa tudo e faz rollback automático se qualquer linha falhar
                $this->connection->transactional(function (Connection $conn) use ($seed, $version): void {
                    # Roda os inserts padrão definidos na seed
                    $seed->run();
                    # Registra a versão como aplicada; se já existir, apenas atualiza o horário
                    $conn->executeStatement(
                        # SQL idempotente de controle de versão
                        'INSERT INTO ' . self::VERSIONS_TABLE . ' (version) VALUES (:version)'
                            . ' ON CONFLICT (version) DO UPDATE SET executed_at = CURRENT_TIMESTAMP',
                        # Valor vinculado ao placeholder
                        ['version' => $version]
                    );
                });
            } catch (\Throwable $e) {
                # Em caso de erro, a transação já foi revertida; informa a falha e aborta
                $output->writeln("<error>Falha ao executar a seed {$version}: {$e->getMessage()}</error>");
                # Aborta com falha
                return Command::FAILURE;
            }

            # Calcula o tempo gasto em milissegundos
            $milliseconds = (int) round((microtime(true) - $start) * 1000);
            # Imprime o sucesso da seed com descrição e tempo
            $output->writeln("<info>✔ {$version}</info> <comment>({$seed->getDescription()} — {$milliseconds}ms)</comment>");
            # Incrementa o contador de seeds aplicadas
            $applied++;
        }

        # Linha em branco para espaçamento visual
        $output->writeln('');
        # Mensagem final conforme o número de seeds aplicadas
        $output->writeln(
            $applied === 0
                # Nada pendente
                ? '<comment>Nenhuma seed pendente — o banco já está atualizado.</comment>'
                # Resumo do que foi aplicado
                : "<info>{$applied} seed(s) aplicada(s) com sucesso.</info>"
        );

        # Retorna código de sucesso
        return Command::SUCCESS;
    }

    # Cria a tabela de controle de seeds se ela ainda não existir (operação idempotente)
    private function ensureVersionsTable(): void
    {
        # Executa um CREATE TABLE IF NOT EXISTS — seguro de rodar quantas vezes for
        $this->connection->executeStatement(
            # version é a chave primária; executed_at guarda quando a seed rodou
            'CREATE TABLE IF NOT EXISTS ' . self::VERSIONS_TABLE . ' ('
                . ' version VARCHAR(191) NOT NULL PRIMARY KEY,'
                . ' executed_at TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP'
                . ')'
        );
    }

    # Lê do banco todas as versões de seed já executadas e devolve um mapa para busca rápida
    private function loadExecutedVersions(): array
    {
        # fetchFirstColumn devolve um array simples com os valores da coluna version
        $rows = $this->connection->fetchFirstColumn('SELECT version FROM ' . self::VERSIONS_TABLE);
        # Transforma a lista em mapa [version => true] para checagem em tempo constante
        return array_fill_keys($rows, true);
    }
}