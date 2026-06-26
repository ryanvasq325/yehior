<?php

declare(strict_types=1);

namespace App\Controller;

final class Product extends Base
{
    public function insert($request, $response)
    {
        $form = $request->getParsedBody();
        $FieldsAndValues = [
            'nome'     => $form['nome'],
            'codigo_barra' => $form['codigo_barra']          ?? null,
            'unidade'         => $form['unidade']    ?? null,
            'preco_compra'           => $form['preco_compra'] ?? null,
            'descricao'         => $form['descricao'] ?? null,
            'excluido'         => (int)(($form['excluido']         ?? '') === 'false'),
            'ativo'         => (int)(($form['ativo']         ?? '') === 'true'),
        ];


        try {
            $IsInserted = \App\Database\DB::connection()->insert('products', $FieldsAndValues);
            if (!$IsInserted) {
                return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $IsInserted, 'id' => 0], 500);
            }
            $id = \App\Database\DB::connection()->lastInsertId();

            return $this->json($response, ['status' => true, 'msg' => 'Salvo com sucesso!', 'id' => $id], 201);
        } catch (\Exception $e) {
            return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $e->getMessage(), 'id' => 0], 500);
        }
    }
    public function delete($request, $response)
    {
        $form = $request->getParsedBody();
        $id = $form['id'] ?? null;
        if (is_null($id) || $id === '') {
            return $this->json($response, ['status' => false, 'msg' => 'Informe o código do cliente', 'id' => 0], 403);
        }
        try {
            $IsDeleted = \App\Database\DB::connection()->delete('products', ['id' => $id]);
            if (!$IsDeleted) {
                return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $IsDeleted, 'id' => $id], 403);
            }
            return $this->json($response, ['status' => true, 'msg' => 'Removido com sucesso!', 'id' => $id]);
        } catch (\Exception $e) {
            return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $e->getMessage(), 'id' => 0], 500);
        }
    }
    public function listingdata($request, $response)
    {
        $form = $request->getParsedBody();

        $term   = $form['search']['value'] ?? null;
        $start  = (int) ($form['start']  ?? 0);
        $length = (int) ($form['length'] ?? 10);


        $columns = [
            0 => 'id',
            1 => 'nome',
            2 => 'codigo_barra',
            3 => 'unidade',
            4 => 'preco_compra',
            5 => 'descricao',
            6 => 'ativo',
        ];

        $posField = (isset($form['order'][0]['column']) && isset($columns[(int) $form['order'][0]['column']]))
            ? (int) $form['order'][0]['column']
            : 0;

        # Validação da direção evita SQL injection no ORDER BY
        $orderType  = strtoupper($form['order'][0]['dir'] ?? 'DESC');
        $orderType  = in_array($orderType, ['ASC', 'DESC'], true) ? $orderType : 'DESC';
        $orderField = $columns[$posField];

        try {
            # Total geral DataTables: recordsTotal
            $totalRecords = (int) \App\Database\DB::select('COUNT(*)')
                ->from('products')
                ->fetchOne();

            # Query principal com WHERE opcional
            $query = \App\Database\DB::select('*')->from('products');

            if (!is_null($term) && $term !== '') {
                $query->setParameter('term', '%' . $term . '%');

                $query->where('CAST(id AS TEXT) ILIKE :term')
                    ->orWhere('fantasia ILIKE :term')
                    ->orWhere('razao_social ILIKE :term')
                    ->orWhere('cnpj ILIKE :term')
                    ->orWhere('ie ILIKE :term')
                    ->orWhere("TO_CHAR(criado_em, 'DD/MM/YYYY HH24:MI:SS') ILIKE :term")
                    ->orWhere("TO_CHAR(atualizado_em, 'DD/MM/YYYY HH24:MI:SS') ILIKE :term");
            }

            # Total com filtro aplicado — clona o query e troca o SELECT por COUNT
            $filteredRecords = (int) (clone $query)
                ->select('COUNT(*)')
                ->fetchOne();

            # Resultados paginados e ordenados
            $products = $query
                ->orderBy($orderField, $orderType)
                ->setFirstResult($start)
                ->setMaxResults($length)
                ->fetchAllAssociative();

            # Formatação para o DataTables
            # Formatação para o DataTables
            $rows = [];
            foreach ($products as $key => $value) {
                $rows[$key] = [
                    $value['id'],
                    $value['nome']     ?? '',
                    $value['codigo_barra'] ?? '',
                    $value['unidade']         ?? '',
                    $value['preco_compra']           ?? '',
                    $value['descricao']         ?? '',
                    ($value['ativo'] == true) ? 'Ativo' : 'Inativo',
                    "<td>
            <a class='btn btn-sm btn-warning' href='/produto/detalhes/" . $value['id'] . "'>
                <i class='fa-solid fa-pen-to-square'></i> Editar
            </a>
            <button type='button' class='btn btn-sm btn-danger' onclick='ShowModal(" . $value['id'] . ");'>
                <i class='fa-solid fa-trash'></i> Excluir
            </button>
        </td>",
                ];
            }
            return $this->json($response, [
                'recordsTotal'    => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data'            => $rows,
            ], 200);
        } catch (\Exception $e) {
            return $this->json($response, [
                'status' => false,
                'msg'    => 'Restrição: ' . $e->getMessage(),
                'id'     => 0,
            ], 500);
        }
    }
}
