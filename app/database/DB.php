<?php

# Ativa a checagem estrita de tipos em todo este arquivo
declare(strict_types=1);

# Namespace da camada de banco de dados (mapeia para a pasta app/database via PSR-4)
namespace App\Database;

# Importa a conexão DBAL para tipar o retorno do método connection()
use Doctrine\DBAL\Connection as DBALConnection;
# Importa o QueryBuilder para tipar o retorno do método select()
use Doctrine\DBAL\Query\QueryBuilder;

# Fachada estática que simplifica o acesso ao banco em todo o sistema
final class DB
{
    # Devolve um QueryBuilder já com o SELECT configurado; sem argumentos seleciona tudo ('*')
    public static function select(string ...$columns): QueryBuilder
    {
        # Cria um novo QueryBuilder a partir da conexão singleton
        $qb = Connection::get()->createQueryBuilder();

        # Se nenhuma coluna foi passada, seleciona todas; caso contrário, seleciona as informadas
        return empty($columns)
            # Sem colunas: faz SELECT *
            ? $qb->select('*')
            # Com colunas: expande a lista no SELECT
            : $qb->select(...$columns);
    }

    # Devolve a conexão DBAL para operações de escrita (insert, update, delete, transação, execute)
    public static function connection(): DBALConnection
    {
        # Retorna sempre a mesma conexão singleton do processo
        return Connection::get();
    }

    # Construtor privado impede 'new DB()' — o uso é exclusivo via métodos estáticos
    private function __construct() {}
}