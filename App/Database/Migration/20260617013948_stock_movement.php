<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class StockMovement extends AbstractMigration
{

    public function change(): void
    {
        $table = $this->table('stock_movement', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'null' => false])
            ->addColumn('id_produto', 'biginteger', ['null' => true])
            ->addColumn('quantidade_entrada', 'decimal', ['precision' => 18, 'scale' => 4, 'null' => true])
            ->addColumn('quantidade_saida', 'decimal', ['precision' => 18, 'scale' => 4, 'null' => true])
            ->addColumn('observacao', 'text', ['null' => true])
            ->addColumn('data_cadastro', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_atualizacao', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('id_produto', 'products', 'id', ['delete' => 'CASCADE', 'update' => 'NO ACTION'])
            ->create();
    }
}
