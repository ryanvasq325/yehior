<?php

declare(strict_types=1);

namespace App\Database\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607155103 extends AbstractMigration
{
    # Descrição curta e legível do que esta migration faz
    public function getDescription(): string
    {
        return 'contact';
    }

    # Aplica as alterações no banco (criação ou mudança de estrutura)
    public function up(Schema $schema): void
    {
       $table = $schema->createTable('contact');

        $table->addColumn('id',            'bigint',   ['autoincrement' => true]);
        $table->addColumn('id_usuario',    'bigint',   ['notnull' => false]);
        $table->addColumn('id_cliente',    'bigint',   ['notnull' => false]);
        $table->addColumn('tipo',          'string',   ['length' => 20, 'notnull' => false]);
        $table->addColumn('contato',       'text',     ['notnull' => false]);
        $table->addColumn('criado_em',     'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('atualizado_em', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['contato']);
        $table->addIndex(['tipo']);
        $table->addForeignKeyConstraint('users', ['id_usuario'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint('customer', ['id_cliente'], ['id'], ['onDelete' => 'CASCADE']);
    }
    

    # Desfaz exatamente o que o método up() fez (rollback)
    public function down(Schema $schema): void
    {
        # escreva aqui o rollback do up()
    }
}