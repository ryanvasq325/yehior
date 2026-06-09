<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Address extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('address', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'null' => false])
            ->addColumn('id_customer', 'biginteger', ['null' => true])
            ->addColumn('id_supplier', 'biginteger', ['null' => true])
            ->addColumn('logradouro', 'text', ['null' => true])
            ->addColumn('cep', 'text', ['null' => true])
            ->addColumn('numero', 'text', ['null' => true])
            ->addColumn('bairro', 'text', ['null' => true])
            ->addColumn('complemento', 'text', ['null' => true])
            ->addColumn('data_cadastro', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_atualizacao', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('id_customer', 'customer', 'id', ['delete' => 'CASCADE', 'update' => 'NO ACTION'])
            ->addForeignKey('id_supplier', 'supplier', 'id', ['delete' => 'CASCADE', 'update' => 'NO ACTION'])
            ->create();

    }
}
