<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class TypeProblem extends AbstractMigration
{
    public function change(): void
    {
       $table = $this->table('type_problem', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'null' => false])
            ->addColumn('descricao', 'text', ['null' => true])
            ->addColumn('ativo', 'boolean', ['null' => true])
            ->addColumn('data_cadastro', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_atualizacao', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->create();
    }
}

