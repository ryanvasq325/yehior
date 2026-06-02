<?php

# Ativa a checagem estrita de tipos em todo este arquivo
declare(strict_types=1);

# Namespace da camada de banco de dados (mapeia para a pasta app/database via PSR-4)
namespace App\Database;

# Importa a classe base de comandos do Symfony Console
use Symfony\Component\Console\Command\Command;
# Importa o tipo de argumento de linha de comando
use Symfony\Component\Console\Input\InputArgument;
# Importa a interface de entrada (lê argumentos e opções)
use Symfony\Component\Console\Input\InputInterface;
# Importa a interface de saída (escreve mensagens no terminal)
use Symfony\Component\Console\Output\OutputInterface;

# Comando que gera um arquivo de migration no padrão YYYYMMDDHHmmss_nome.php
final class MakeMigrationCommand extends Command
{
    # Expressão que define um nome válido: começa com letra minúscula e usa apenas [a-z0-9_]
    private const NAME_PATTERN = '/^[a-z][a-z0-9_]*$/';

    # Configura o nome do comando, a descrição e os argumentos aceitos
    protected function configure(): void
    {
        # Encadeia a configuração do comando
        $this
            # Define como o comando é chamado no terminal
            ->setName('make:migration')
            # Texto de ajuda exibido na listagem de comandos
            ->setDescription('Cria um arquivo de migration com nome no formato YYYYMMDDHHmmss_nome.php')
            # Declara o argumento obrigatório 'name'
            ->addArgument(
                # Nome do argumento
                'name',
                # Marca o argumento como obrigatório
                InputArgument::REQUIRED,
                # Texto de ajuda do argumento
                'Nome da migration em snake_case (ex: create_users_table)'
            );
    }

    # Executa a lógica do comando quando ele é chamado
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        # Lê o nome informado pelo usuário e remove espaços nas pontas
        $name = trim((string) $input->getArgument('name'));

        # Guard clause: valida o nome contra a expressão e bloqueia caracteres perigosos (ex: ../)
        if (preg_match(self::NAME_PATTERN, $name) !== 1) {
            # Avisa o formato correto e aborta sem criar arquivo
            $output->writeln('<error>Nome inválido. Use snake_case: comece com letra e use apenas a-z, 0-9 e _ (ex: create_users_table).</error>');
            # Retorna código de falha
            return Command::FAILURE;
        }

        # Gera o carimbo de data/hora que serve de identificador único da migration
        $timestamp = date('YmdHis');
        # Monta o nome da classe seguindo o padrão exigido pelo Doctrine
        $className = 'Version' . $timestamp;
        # Monta o nome do arquivo no formato timestamp_nome.php
        $fileName = $timestamp . '_' . $name . '.php';
        # Calcula o diretório das migrations a partir da pasta deste comando
        $dir = __DIR__ . '/migration';
        # Monta o caminho completo do arquivo a ser criado
        $filePath = $dir . '/' . $fileName;

        # Cria o diretório de migrations caso ele ainda não exista
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            # Falha cedo se não conseguir criar a pasta de destino
            $output->writeln("<error>Não foi possível criar o diretório: {$dir}</error>");
            # Retorna código de falha
            return Command::FAILURE;
        }

        # Bloqueia a criação de duas migrations no mesmo segundo (mesmo timestamp = mesma classe)
        if (glob($dir . '/' . $timestamp . '_*.php') !== []) {
            # Pede para aguardar um segundo para evitar colisão de classe Version{timestamp}
            $output->writeln('<error>Já existe uma migration com este timestamp. Aguarde 1 segundo e tente novamente.</error>');
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
        $output->writeln('<info>✔ Migration criada com sucesso!</info>');
        # Mostra o nome do arquivo gerado
        $output->writeln("<comment>  Arquivo : </comment>{$fileName}");
        # Mostra o nome da classe gerada
        $output->writeln("<comment>  Classe  : </comment>{$className}");
        # Mostra o caminho relativo do arquivo gerado
        $output->writeln("<comment>  Caminho : </comment>app/database/migration/{$fileName}");
        # Linha em branco final
        $output->writeln('');

        # Retorna código de sucesso
        return Command::SUCCESS;
    }

    # Monta o conteúdo inicial do arquivo de migration a partir do nome da classe e da descrição
    private function buildTemplate(string $className, string $name): string
    {
        # Heredoc com o esqueleto da migration; \$ escapa variáveis que devem aparecer literais no arquivo
        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace App\Database\Migration;

            use Doctrine\DBAL\Schema\Schema;
            use Doctrine\Migrations\AbstractMigration;

            final class {$className} extends AbstractMigration
            {
                # Descrição curta e legível do que esta migration faz
                public function getDescription(): string
                {
                    return '{$name}';
                }

                # Aplica as alterações no banco (criação ou mudança de estrutura)
                public function up(Schema \$schema): void
                {
                    # escreva aqui as alterações
                }

                # Desfaz exatamente o que o método up() fez (rollback)
                public function down(Schema \$schema): void
                {
                    # escreva aqui o rollback do up()
                }
            }
            PHP;
    }
}