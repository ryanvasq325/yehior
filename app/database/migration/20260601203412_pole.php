<?php

declare(strict_types=1);

namespace App\Database\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260601203412 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'pole';
    }

    public function up(Schema $schema): void
    {
      $table = $schema->createTable('pole');
        $table->addColumn('id',            'bigint',           ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('id_bairro',       'bigint',           [ 'notnull'      => false]);
        $table->addColumn('numero',        'string',           ['length'        => 255,  'notnull' => true]);
        $table->addColumn('criado_em',     'datetime',         ['notnull'       => true, 'default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('atualizado_em', 'datetime',         ['notnull'       => true, 'default' => 'CURRENT_TIMESTAMP']);

        $table->addForeignKeyConstraint('address', ['id_bairro'], ['id'], ['onDelete' => 'RESTRICT', 'onUpdate' => 'CASCADE'], 'fk_pole_bairro');

    }

    public function down(Schema $schema): void
    {
       # $schema->dropTable('pole');
    }
}