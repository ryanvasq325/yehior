<?php

declare(strict_types=1);

namespace App\Database\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607154858 extends AbstractMigration
{
    # Descrição curta e legível do que esta migration faz
    public function getDescription(): string
    {
        return 'customer';
    }

    # Aplica as alterações no banco (criação ou mudança de estrutura)
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('customer');

        $table->addColumn('id',                 'bigint', ['autoincrement' => true]);
        $table->addColumn('nome',               'string',  ['length' => 255]);
        $table->addColumn('sobrenome',          'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('cpf',                'string',  ['length' => 18]);
        $table->addColumn('telefone',           'string',  ['length' => 18]);
        $table->addColumn('ativo',              'boolean', ['default' => true]);
        $table->addColumn('criado_em',          'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('atualizado_em',      'datetime', ['default' => 'CURRENT_TIMESTAMP']);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['cpf']);
        $table->addIndex(['nome']);
    }
    

    # Desfaz exatamente o que o método up() fez (rollback)
    public function down(Schema $schema): void
    {
        # escreva aqui o rollback do up()
    }
}