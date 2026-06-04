<?php

declare(strict_types=1);

namespace App\Database\Migration;

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

        $table->addColumn('id',                'bigint', ['autoincrement' => true]);
        $table->addColumn('id_customer',       'bigint', ['notnull' => true]);
        $table->addColumn('id_tipo_problema', 'bigint', ['notnull' => true]);
        $table->addColumn('cep',               'string',  ['length' => 255]);
        $table->addColumn('descricao',         'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('resolvido',         'boolean', ['default' => false]);
        $table->addColumn('criado_em',         'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('atualizado_em',     'datetime', ['default' => 'CURRENT_TIMESTAMP']);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['resolvido']);

        $table->addForeignKeyConstraint(
            'customer',
            ['id_customer'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'CASCADE'],
            'fk_reports_customer'
        );
        $table->addForeignKeyConstraint(
            'tipo_problema',
            ['id_tipo_problema'],
            ['id'],
            ['onDelete' => 'RESTRICT', 'onUpdate' => 'CASCADE'],
            'fk_reports_tipo_problema'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE reports CASCADE');
    }
}
