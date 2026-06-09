<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Pole extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('pole', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'null' => false])
            ->addColumn('id_address', 'biginteger', ['null' => true])
            ->addColumn('numero', 'text', ['null' => true])
            ->addColumn('bairro', 'text', ['null' => true])
            ->addColumn('complemento', 'text', ['null' => true])
            ->addColumn('data_cadastro', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_atualizacao', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('id_address', 'address', 'id', ['delete' => 'CASCADE', 'update' => 'NO ACTION'])
            ->create();


    }
}
