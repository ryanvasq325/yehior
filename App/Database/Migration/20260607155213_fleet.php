<?php

declare(strict_types=1);

namespace App\Database\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607155213 extends AbstractMigration
{
    # Descrição curta e legível do que esta migration faz
    public function getDescription(): string
    {
        return 'fleet';
    }

    # Aplica as alterações no banco (criação ou mudança de estrutura)
    public function up(Schema $schema): void
    {
       $table = $schema->createTable('fleet');

        $table->addColumn('id',                'bigint', ['autoincrement' => true]);
        $table->addColumn('numero',            'string',  ['length' => 255]);
        $table->addColumn('placa',             'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('ativo',             'boolean', ['default' => false]);
        $table->addColumn('criado_em',         'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('atualizado_em',     'datetime', ['default' => 'CURRENT_TIMESTAMP']);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['numero']);
        $table->addIndex(['placa']);
    }


    # Desfaz exatamente o que o método up() fez (rollback)
    public function down(Schema $schema): void
    {
        # escreva aqui o rollback do up()
    }
}