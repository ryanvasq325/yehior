<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Contact extends AbstractMigration
{
    
    public function change(): void
    {
        $table = $this->table('contact', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'null' => false])
            ->addColumn('id_users', 'biginteger', ['null' => true])
            ->addColumn('id_customer', 'biginteger', ['null' => true])
            ->addColumn('tipo', 'text', ['null' => true])
            ->addColumn('contato', 'text', ['null' => true])
            ->addColumn('endereco_contato', 'text', ['null' => true])
            ->addForeignKey('id_users', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO ACTION'])
            ->addForeignKey('id_customer', 'customer', 'id', ['delete' => 'CASCADE', 'update' => 'NO ACTION'])
            ->create();
    }
}
