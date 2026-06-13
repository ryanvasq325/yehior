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
        // Customers
        $customers = [
            ['id' => 1, 'nome_fantasia' => 'João',    'sobrenome_razao' => 'Silva',    'cpf_cnpj' => '11111111111'],
            ['id' => 2, 'nome_fantasia' => 'Maria',   'sobrenome_razao' => 'Souza',    'cpf_cnpj' => '22222222222'],
            ['id' => 3, 'nome_fantasia' => 'Carlos',  'sobrenome_razao' => 'Pereira',  'cpf_cnpj' => '33333333333'],
            ['id' => 4, 'nome_fantasia' => 'Ana',     'sobrenome_razao' => 'Lima',     'cpf_cnpj' => '44444444444'],
            ['id' => 5, 'nome_fantasia' => 'Pedro',   'sobrenome_razao' => 'Costa',    'cpf_cnpj' => '55555555555'],
        ];

        foreach ($customers as $c) {
            $exists = $this->fetchRow('SELECT id FROM customer WHERE id = ' . $c['id']);
            if (!$exists) {
                $this->table('customer')->insert([
                    'id'               => $c['id'],
                    'nome_fantasia'    => $c['nome_fantasia'],
                    'sobrenome_razao'  => $c['sobrenome_razao'],
                    'cpf_cnpj'         => $c['cpf_cnpj'],
                    'ativo'            => true,
                    'data_cadastro'    => date('Y-m-d H:i:s'),
                    'data_atualizacao' => date('Y-m-d H:i:s'),
                ])->saveData();
            }
        }

        // Addresses
        $addresses = [
            ['id' => 1, 'id_customer' => 1, 'bairro' => 'Centro',         'cep' => '76960-000'],
            ['id' => 2, 'id_customer' => 2, 'bairro' => 'Centro',         'cep' => '76960-000'],
            ['id' => 3, 'id_customer' => 3, 'bairro' => 'Jardim América', 'cep' => '76961-000'],
            ['id' => 4, 'id_customer' => 4, 'bairro' => 'Jardim América', 'cep' => '76961-000'],
            ['id' => 5, 'id_customer' => 5, 'bairro' => 'Nova Cacoal',    'cep' => '76962-000'],
        ];

        foreach ($addresses as $a) {
            $exists = $this->fetchRow('SELECT id FROM address WHERE id = ' . $a['id']);
            if (!$exists) {
                $this->table('address')->insert([
                    'id'               => $a['id'],
                    'id_customer'      => $a['id_customer'],
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

        // Reports
        $reports = [
            [1,  1, 1],
            [2,  1, 1],
            [3,  2, 1],
            [4,  3, 2],
            [5,  1, 2],
            [6,  2, 2],
            [7,  1, 3],
            [8,  4, 3],
            [9,  1, 4],
            [10, 3, 4],
            [11, 2, 5],
            [12, 1, 5],
            [13, 1, 1],
            [14, 2, 2],
            [15, 3, 3],
        ];

        foreach ($reports as [$id, $id_tipo, $id_customer]) {
            $exists = $this->fetchRow('SELECT id FROM reports WHERE id = ' . $id);
            if (!$exists) {
                $this->table('reports')->insert([
                    'id'               => $id,
                    'id_customer'      => $id_customer,
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
