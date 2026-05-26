<?php

declare(strict_types=1);

namespace app\database\migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260525201106 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'users';
    }

     public function up(Schema $schema): void
    {
        $table = $schema->createTable('users');

        $table->addColumn('id',            'bigint',  ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('nome',          'text',  ['length' => 255, 'notnull' => true]);
        $table->addColumn('sobrenome',     'text',  ['length' => 255, 'notnull' => false]);
        $table->addColumn('cpf',           'text',  ['length' => 14,  'notnull' => false]);
        $table->addColumn('rg',            'text',  ['length' => 20,  'notnull' => false]);
        $table->addColumn('senha',         'text',  ['length' => 255,  'notnull' => false]);
        $table->addColumn('ativo',         'boolean', ['default' => false,  'notnull' => true]);
        $table->addColumn('administrador', 'boolean', ['default' => false,  'notnull' => true]);
        $table->addColumn('excluido',      'boolean', ['default' => false, 'notnull' => true]);
        $table->addColumn('criado_em',     'datetime', ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('atualizado_em', 'datetime', ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['cpf']);
        $table->addIndex(['ativo']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('users');
    }
}