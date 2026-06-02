<?php

declare(strict_types=1);

namespace App\Database\Seed;

use App\Database\AbstractSeed;

final class Seed20260602145638 extends AbstractSeed
{
    # Descrição curta e legível do que esta seed insere
    public function getDescription(): string
    {
        return 'tipo_problema';
    }

    # Executa os inserts padrão do sistema
    # Use $this->insertIfNotExists() para que rodar a seed mais de uma vez seja seguro
    public function run(): void
    {
        $tipos = [
            'Lâmpada apagada',
            'Luz piscando',
            'Estrutura danificada',
            'Outro',
        ];

        foreach ($tipos as $descricao) {
            $this->insertIfNotExists(
                'tipo_problema',
                [
                    'descricao' => $descricao,
                    'ativo'     => true,
                    'criado_em' => date('Y-m-d H:i:s'),
                ],
                ['descricao'] // coluna UNIQUE na tabela
            );
        }
    }
}