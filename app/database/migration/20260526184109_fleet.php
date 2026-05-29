<?php

declare(strict_types=1);

namespace App\Database\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526184109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'fleet';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('fleet');

        $table->addColumn('id',                'bigint', ['autoincrement' => true]);
        $table->addColumn('numero',            'string',  ['length' => 255]);
        $table->addColumn('placa',             'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('ativo',             'boolean', ['default' => false]);
        $table->addColumn('criado_em',         'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('atualizado_em',     'datetime', ['default' => 'CURRENT_TIMESTAMP']);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['numero']);
        $table->addIndex(['placa']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('fleet');
    }
}
