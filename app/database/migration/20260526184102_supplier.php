<?php

declare(strict_types=1);

namespace App\Database\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526184102 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'supplier';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('supplier');

        $table->addColumn('id',                   'bigint',   ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('nome_fantasia',        'string',   ['length' => 255, 'notnull' => true]);
        $table->addColumn('sobrenome_razao',      'string',   ['length' => 255, 'notnull' => false]);
        $table->addColumn('cpf_cnpj',             'string',   ['length' => 18,  'notnull' => true]);
        $table->addColumn('inscricao_estadual',   'string',   ['length' => 30,  'notnull' => false]);
        $table->addColumn('nascimento_fundacao',  'date',     ['notnull' => false]);
        $table->addColumn('ativo',                'boolean',  ['default' => true,  'notnull' => true]);
        $table->addColumn('criado_em',            'datetime', ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('atualizado_em',        'datetime', ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['cpf_cnpj']);
        $table->addIndex(['nome_fantasia']);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE supplier CASCADE');
    }
}
