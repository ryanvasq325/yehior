<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Supplier extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('supplier', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'null' => false])
            ->addColumn('nome_fantasia', 'text', ['null' => true])
            ->addColumn('sobrenome_razao', 'text', ['null' => true])
            ->addColumn('cpf_cnpj', 'text', ['null' => true])
            ->addColumn('inscricao_estadual', 'text', ['null' => true])
            ->addColumn('nascimento_fundacao', 'date', ['null' => true,])
            ->addColumn('ativo', 'boolean', ['null' => true])
            ->addColumn('data_cadastro', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_atualizacao', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->create();

    }
}
