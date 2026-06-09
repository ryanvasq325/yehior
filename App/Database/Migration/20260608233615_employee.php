<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Employee extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('employee', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'null' => false])
            ->addColumn('id_fleet', 'biginteger', ['null' => true])
            ->addColumn('nome', 'text', ['null' => true])
            ->addColumn('sobrenome', 'text', ['null' => true])
            ->addColumn('cpf', 'text', ['null' => true])
            ->addColumn('rg', 'text', ['null' => true])
            ->addColumn('ativo', 'boolean', ['null' => true])
            ->addColumn('excluido', 'boolean', ['null' => false])
            ->addColumn('data_cadastro', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_atualizacao', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('id_fleet', 'fleet', 'id', ['delete' => 'CASCADE', 'update' => 'NO ACTION'])
            ->create();
    }
}
