<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class TypeProblemSeeder extends AbstractSeed
{
    public function run(): void
    {
        $tipos = [
            ['id' => 1, 'descricao' => 'Lâmpada apagada',   'ativo' => true],
            ['id' => 2, 'descricao' => 'Luz piscando',       'ativo' => true],
            ['id' => 3, 'descricao' => 'Estrutura danificada','ativo' => true],
            ['id' => 4, 'descricao' => 'Outro',              'ativo' => true],
        ];

        $table = $this->table('type_problem');

        foreach ($tipos as $tipo) {
            $exists = $this->fetchRow(
                'SELECT id FROM type_problem WHERE id = ' . $tipo['id']
            );

            if (!$exists) {
                $table->insert([
                    'id'               => $tipo['id'],
                    'descricao'        => $tipo['descricao'],
                    'ativo'            => $tipo['ativo'],
                    'data_cadastro'    => date('Y-m-d H:i:s'),
                    'data_atualizacao' => date('Y-m-d H:i:s'),
                ])->saveData();
            }
        }
    }
}
