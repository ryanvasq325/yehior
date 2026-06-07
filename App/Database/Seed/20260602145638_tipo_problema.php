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
        // Adicionamos IDs fixos para que o "insertIfNotExists" saiba o que comparar
        $tipos = [
            1 => 'Lâmpada apagada',
            2 => 'Luz piscando',
            3 => 'Estrutura danificada',
            4 => 'Outro',
        ];

        foreach ($tipos as $id => $descricao) {
            $this->insertIfNotExists(
                'type_problem',
                [
                    'id'        => $id, // Passa o ID aqui
                    'descricao' => $descricao,
                    'ativo'     => true,
                    'criado_em' => date('Y-m-d H:i:s'),
                ],
                ['id'] // Altera de 'descricao' para 'id'
            );
        }
    }
}