<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Users extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'null' => false])
            ->addColumn('nome', 'text', ['null' => true])
            ->addColumn('sobrenome', 'text', ['null' => true])
            ->addColumn('cpf', 'text', ['null' => true])
            ->addColumn('rg', 'text', ['null' => true])
            ->addColumn('senha', 'text', ['null' => true])
            ->addColumn('ativo', 'boolean', ['null' => true])
            ->addColumn('administrador', 'boolean', ['null' => true])
            ->addColumn('excluido', 'boolean', ['null' => true])
            ->addColumn('data_cadastro', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_atualizacao', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->create();
    }

}

