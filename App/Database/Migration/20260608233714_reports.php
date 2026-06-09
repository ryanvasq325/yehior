<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Reports extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('reports', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'null' => false])
            ->addColumn('id_customer', 'biginteger', ['null' => true])
            ->addColumn('id_tipo_problema', 'biginteger', ['null' => true])
            ->addColumn('cep', 'text', ['null' => true])
            ->addColumn('descricao', 'text', ['null' => true])
            ->addColumn('resolvido', 'boolean', ['null' => true])
            ->addColumn('data_cadastro', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_atualizacao', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('id_customer', 'customer', 'id', ['delete' => 'CASCADE', 'update' => 'NO ACTION'])
            ->addForeignKey('id_tipo_problema', 'type_problem', 'id', ['delete' => 'CASCADE', 'update' => 'NO ACTION'])
            ->create();
    }
}
