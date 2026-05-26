<?php

declare(strict_types=1);

namespace app\database\migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526184108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'reports';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('reports');

        $table->addColumn('id',            'bigint', ['autoincrement' => true]);
        $table->addColumn('id_stock',       'bigint', ['notnull' => true]);
        $table->addColumn('problema',          'string',  ['length' => 255]);
        $table->addColumn('descricao',     'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('resolvido',         'boolean', ['default' => false]);
        $table->addColumn('criado_em',     'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('atualizado_em', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['resolvido']);
        $table->addIndex(['problema']);
        $table->addIndex(['id_stock']);

        $table->addForeignKeyConstraint(
            'stock',
            ['id_stock'],
            ['id'],
            ['onDelete' => 'RESTRICT', 'onUpdate' => 'CASCADE'],
            'fk_reports_stock'
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('reports');
    }
}

