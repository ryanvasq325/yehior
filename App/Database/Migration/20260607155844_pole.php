<?php

declare(strict_types=1);

namespace App\Database\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607155844 extends AbstractMigration
{
    # Descrição curta e legível do que esta migration faz
    public function getDescription(): string
    {
        return 'pole';
    }

    # Aplica as alterações no banco (criação ou mudança de estrutura)
    public function up(Schema $schema): void
    {
       $table = $schema->createTable('pole');
        $table->addColumn('id',            'bigint',           ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('id_bairro',       'bigint',           [ 'notnull'      => false]);
        $table->addColumn('numero',        'string',           ['length'        => 255,  'notnull' => true]);
        $table->addColumn('criado_em',     'datetime',         ['notnull'       => true, 'default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('atualizado_em', 'datetime',         ['notnull'       => true, 'default' => 'CURRENT_TIMESTAMP']);


        $table->setPrimaryKey(['id']);
        $table->addIndex(['id_bairro']);
        $table->addForeignKeyConstraint('address', ['id_bairro'], ['id'], ['onDelete' => 'RESTRICT', 'onUpdate' => 'CASCADE'], 'fk_pole_bairro');

    }
    

    # Desfaz exatamente o que o método up() fez (rollback)
    public function down(Schema $schema): void
    {
        # escreva aqui o rollback do up()
    }
}