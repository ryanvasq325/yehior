<?php

declare(strict_types=1);

namespace App\Database\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607155703 extends AbstractMigration
{
    # Descrição curta e legível do que esta migration faz
    public function getDescription(): string
    {
        return 'employee';
    }

    # Aplica as alterações no banco (criação ou mudança de estrutura)
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('employee');

        $table->addColumn('id',                 'bigint',  ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('id_fleet',        'bigint',   ['notnull' => true]);
        $table->addColumn('nome',               'text',  ['length' => 255, 'notnull' => true]);
        $table->addColumn('sobrenome',          'text',  ['length' => 255, 'notnull' => false]);
        $table->addColumn('cpf',                'text',  ['length' => 14,  'notnull' => false]);
        $table->addColumn('rg',                 'text',  ['length' => 20,  'notnull' => false]);
        $table->addColumn('ativo',              'boolean', ['default' => false,  'notnull' => true]);
        $table->addColumn('excluido',           'boolean', ['default' => false, 'notnull' => true]);
        $table->addColumn('criado_em',          'datetime', ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('atualizado_em',      'datetime', ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['nome']);
        $table->addUniqueIndex(['cpf']);
        $table->addIndex(['ativo']);
        $table->addIndex(['id_fleet']);

        $table->addForeignKeyConstraint(
            'fleet',
            ['id_fleet'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'CASCADE'],
            'fk_reports_fleet'
        );
    }

    # Desfaz exatamente o que o método up() fez (rollback)
    public function down(Schema $schema): void
    {
        # escreva aqui o rollback do up()
    }
}