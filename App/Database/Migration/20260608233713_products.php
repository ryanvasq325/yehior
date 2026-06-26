<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Products extends AbstractMigration
{
   
    public function change(): void
    {
        $table = $this->table('products', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'null' => false])
            ->addColumn('id_supplier', 'biginteger', ['null' => true])
            ->addColumn('nome', 'text', ['null' => true])
            ->addColumn('codigo_barra', 'text', ['null' => true])
            ->addColumn('unidade', 'text', ['null' => true])
            ->addColumn('preco_compra', 'decimal', ['null' => true, 'precision' => 10, 'scale' => 2])
            ->addColumn('descricao', 'text', ['null' => true])
            ->addColumn('ativo', 'boolean', ['null' => true])
            ->addColumn('excluido', 'boolean', ['null' => false])
            ->addColumn('data_cadastro', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_atualizacao', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('id_supplier', 'supplier', 'id', ['delete' => 'CASCADE', 'update' => 'NO ACTION'])
            ->create();
    }
}
