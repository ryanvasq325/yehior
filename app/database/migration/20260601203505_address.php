<?php

declare(strict_types=1);

namespace App\Database\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260601203505 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'address';
    }

    public function up(Schema $schema): void
    {
         $table = $schema->createTable('address');

        $table->addColumn('id',            'bigint',  ['autoincrement' => true,  'notnull' => true]);
        $table->addColumn('id_city',       'bigint',  [ 'notnull' => false]);
        $table->addColumn('id_customer',   'bigint',  [ 'notnull' => false]);
        $table->addColumn('id_supplier',   'bigint',  [ 'notnull' => false]);
        $table->addColumn('id_enterprise', 'bigint',  [ 'notnull' => false]);
        $table->addColumn('logradouro',    'string',  ['length' => 255, 'notnull' => true]);
        $table->addColumn('bairro',        'string',  ['length' => 100, 'notnull' => true]);
        $table->addColumn('cep',           'string',  ['length' => 10,  'notnull' => true]);
        $table->addColumn('numero',        'integer', ['notnull' => true]);
        $table->addColumn('complemento',   'string',  ['length' => 100, 'notnull' => false]);
        $table->addColumn('criado_em',     'datetime', ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('atualizado_em', 'datetime', ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['id_city']);
        $table->addIndex(['id_customer']);
        $table->addIndex(['id_supplier']);
        $table->addIndex(['id_enterprise']);

        $table->addForeignKeyConstraint('city',       ['id_city'],       ['id'], ['onDelete' => 'RESTRICT', 'onUpdate' => 'CASCADE'], 'fk_address_city');
        $table->addForeignKeyConstraint('customer',   ['id_customer'],   ['id'], ['onDelete' => 'RESTRICT', 'onUpdate' => 'CASCADE'], 'fk_address_customer');
        $table->addForeignKeyConstraint('supplier',   ['id_supplier'],   ['id'], ['onDelete' => 'RESTRICT', 'onUpdate' => 'CASCADE'], 'fk_address_supplier');
        $table->addForeignKeyConstraint('enterprise', ['id_enterprise'], ['id'], ['onDelete' => 'RESTRICT', 'onUpdate' => 'CASCADE'], 'fk_address_enterprise');
    }
    

    public function down(Schema $schema): void
    {
        $schema->dropTable('address');
    }
}