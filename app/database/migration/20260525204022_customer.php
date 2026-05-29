<?php

declare(strict_types=1);

namespace app\database\migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260525204022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'customer';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('customer');

        $table->addColumn('id',                 'bigint', ['autoincrement' => true]);
        $table->addColumn('nome',               'string',  ['length' => 255]);
        $table->addColumn('sobrenome',          'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('cpf',                'string',  ['length' => 18]);
        $table->addColumn('telefone',           'string',  ['length' => 18]);
        $table->addColumn('ativo',              'boolean', ['default' => true]);
        $table->addColumn('criado_em',          'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('atualizado_em',      'datetime', ['default' => 'CURRENT_TIMESTAMP']);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['cpf']);
        $table->addIndex(['nome']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('customer');
    }
}
