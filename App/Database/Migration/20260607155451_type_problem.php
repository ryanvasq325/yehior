<?php

declare(strict_types=1);

namespace App\Database\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607155451 extends AbstractMigration
{
    # Descrição curta e legível do que esta migration faz
    public function getDescription(): string
    {
        return 'type_problem';
    }

    # Aplica as alterações no banco (criação ou mudança de estrutura)
    public function up(Schema $schema): void
    {
         $table = $schema->createTable('type_problem');
 
        $table->addColumn('id',         'bigint', ['autoincrement' => true]);
        $table->addColumn('descricao',  'string',  ['length' => 100, 'notnull' => true]);
        $table->addColumn('ativo',      'boolean', ['default' => true]);
        $table->addColumn('criado_em',  'datetime', ['default' => 'CURRENT_TIMESTAMP']);
 
        $table->setPrimaryKey(['id']);
    }
    

    # Desfaz exatamente o que o método up() fez (rollback)
    public function down(Schema $schema): void
    {
        # escreva aqui o rollback do up()
    }
}