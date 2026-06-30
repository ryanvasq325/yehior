<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CordsReports extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('reports');
        $table
            ->addColumn('latitude',  'decimal', ['precision' => 10, 'scale' => 7, 'null' => true, 'after' => 'cep'])
            ->addColumn('longitude', 'decimal', ['precision' => 10, 'scale' => 7, 'null' => true, 'after' => 'latitude'])
            ->update();
    }
}