<?php

declare(strict_types=1);

namespace App\Database\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607161619 extends AbstractMigration
{
    # Descrição curta e legível do que esta migration faz
    public function getDescription(): string
    {
        return 'stock';
    }

    # Aplica as alterações no banco (criação ou mudança de estrutura)
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('stock');

        $table->addColumn('id',                   'bigint',   ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('id_supplier',          'bigint',   ['notnull' => true]);
        $table->addColumn('nome',                 'string',   ['length' => 255, 'notnull' => true]);
        $table->addColumn('codigo_barra',         'string',   ['length' => 255, 'notnull' => false]);
        $table->addColumn('unidade',              'string',   ['length' => 18,  'notnull' => true]);
        $table->addColumn('preco_compra',         'decimal',  ['length' => 18, 'precision' => 18, 'scale' => 4, 'notnull' => true, 'default' => 0]);
        $table->addColumn('descricao',            'string',   ['length' => 255, 'notnull' => true]);
        $table->addColumn('ativo',                'boolean',  ['default' => true,  'notnull' => true]);
        $table->addColumn('excluido',             'boolean',  ['default' => false,  'notnull' => true]);
        $table->addColumn('criado_em',            'datetime', ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('atualizado_em',        'datetime', ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['codigo_barra']);
        $table->addIndex(['nome']);
        $table->addIndex(['id_supplier']);


        $table->addForeignKeyConstraint(
            'supplier',
            ['id_supplier'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'CASCADE'],
            'fk_stock_supplier'
        );
    
    }

    # Desfaz exatamente o que o método up() fez (rollback)
    public function down(Schema $schema): void
    {
        # escreva aqui o rollback do up()
    }
}