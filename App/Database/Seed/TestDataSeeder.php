<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class TestDataSeeder extends AbstractSeed
{
    public function getDependencies(): array
    {
        return [TypeProblemSeeder::class];
    }

    public function run(): void
    {
        // Users (dados de teste — senha padrão "senha123" para todos)
        $senhaHash = password_hash('senha123', PASSWORD_DEFAULT);

        $users = [
            ['id' => 1, 'nome' => 'João',    'sobrenome' => 'Silva',    'cpf' => '11111111111', 'rg' => '111111111'],
            ['id' => 2, 'nome' => 'Maria',   'sobrenome' => 'Souza',    'cpf' => '22222222222', 'rg' => '222222222'],
            ['id' => 3, 'nome' => 'Carlos',  'sobrenome' => 'Pereira',  'cpf' => '33333333333', 'rg' => '333333333'],
            ['id' => 4, 'nome' => 'Ana',     'sobrenome' => 'Lima',     'cpf' => '44444444444', 'rg' => '444444444'],
            ['id' => 5, 'nome' => 'Pedro',   'sobrenome' => 'Costa',    'cpf' => '55555555555', 'rg' => '555555555'],
        ];

        foreach ($users as $u) {
            $exists = $this->fetchRow('SELECT id FROM users WHERE id = ' . $u['id']);
            if (!$exists) {
                $this->table('users')->insert([
                    'id'               => $u['id'],
                    'nome'             => $u['nome'],
                    'sobrenome'        => $u['sobrenome'],
                    'cpf'              => $u['cpf'],
                    'rg'               => $u['rg'],
                    'senha'            => $senhaHash,
                    'ativo'            => true,
                    'data_cadastro'    => date('Y-m-d H:i:s'),
                    'data_atualizacao' => date('Y-m-d H:i:s'),
                ])->saveData();
            }
        }

        // Addresses (vinculados a users via id_users)
        $addresses = [
            ['id' => 1, 'id_users' => 1, 'bairro' => 'Centro',         'cep' => '76960-000'],
            ['id' => 2, 'id_users' => 2, 'bairro' => 'Centro',         'cep' => '76960-000'],
            ['id' => 3, 'id_users' => 3, 'bairro' => 'Jardim América', 'cep' => '76961-000'],
            ['id' => 4, 'id_users' => 4, 'bairro' => 'Jardim América', 'cep' => '76961-000'],
            ['id' => 5, 'id_users' => 5, 'bairro' => 'Nova Cacoal',    'cep' => '76962-000'],
        ];

        foreach ($addresses as $a) {
            $exists = $this->fetchRow('SELECT id FROM address WHERE id = ' . $a['id']);
            if (!$exists) {
                $this->table('address')->insert([
                    'id'               => $a['id'],
                    'id_users'         => $a['id_users'],
                    'id_supplier'      => null,
                    'bairro'           => $a['bairro'],
                    'cep'              => $a['cep'],
                    'logradouro'       => 'Rua Teste',
                    'numero'           => '100',
                    'complemento'      => null,
                    'data_cadastro'    => date('Y-m-d H:i:s'),
                    'data_atualizacao' => date('Y-m-d H:i:s'),
                ])->saveData();
            }
        }

        // Reports (IDs 46-60)
        $reports = [
            [46,  2, 5],
            [47,  1, 3],
            [48,  3, 4],
            [49,  2, 1],
            [50,  3, 2],
            [51,  1, 5],
            [52,  2, 3],
            [53,  3, 1],
            [54,  1, 4],
            [55,  2, 2],
            [56,  3, 5],
            [57,  1, 2],
            [58,  2, 4],
            [59,  3, 3],
            [60,  1, 1],
        ];

        foreach ($reports as [$id, $id_tipo, $id_users]) {
            $exists = $this->fetchRow('SELECT id FROM reports WHERE id = ' . $id);
            if (!$exists) {
                $this->table('reports')->insert([
                    'id'               => $id,
                    'id_users'         => $id_users,
                    'id_tipo_problema' => $id_tipo,
                    'cep'              => '76960-000',
                    'descricao'        => 'Teste seed',
                    'resolvido'        => false,
                    'data_cadastro'    => date('Y-m-d H:i:s'),
                    'data_atualizacao' => date('Y-m-d H:i:s'),
                ])->saveData();
            }
        }
    }
}