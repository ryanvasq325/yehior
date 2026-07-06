<?php

declare(strict_types=1);

namespace App\Controller;

final class Supplier extends Base
{
    public function home($request, $response)
    {
        $totalSuppliers = (int) \App\Database\DB::select('COUNT(*)')
            ->from('supplier')
            ->fetchOne();

        return $this->getTwig()
            ->render($response, $this->setView('list-supplier'), [
                'titulo'         => '',
                'totalSuppliers' => $totalSuppliers,
            ])
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }

    public function insert($request, $response)
    {
        $form = $request->getParsedBody();
        $FieldsAndValues = [
            'nome_fantasia'       => $form['nomeExibicao']       ?? null,
            'sobrenome_razao'     => $form['nomeLegal']          ?? null,
            'cpf_cnpj'            => $form['numeroDocumento']    ?? null,
            'inscricao_estadual'  => $form['inscricaoEstadual']  ?? null,
            'nascimento_fundacao' => $form['dataRegistro']       ?? null,
            'ativo'               => (int)(($form['ativo'] ?? '') === 'true'),
        ];

        try {
            $IsInserted = \App\Database\DB::connection()->insert('supplier', $FieldsAndValues);
            if (!$IsInserted) {
                return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $IsInserted, 'id' => 0], 200);
            }
            $id = \App\Database\DB::connection()->lastInsertId();
            return $this->json($response, ['status' => true, 'msg' => 'Fornecedor salvo com sucesso!', 'id' => $id], 201);
        } catch (\Exception $e) {
            return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $e->getMessage(), 'id' => 0], 200);
        }
    }

    public function update($request, $response)
    {
        $form = $request->getParsedBody();
        $id = $form['id'] ?? null;
        if (is_null($id) || $id === '') {
            return $this->json($response, ['status' => false, 'msg' => 'Por favor informe o ID do registro', 'id' => 0], 200);
        }

        $FieldsAndValues = [
            'nome_fantasia'       => $form['nomeExibicao']       ?? null,
            'sobrenome_razao'     => $form['nomeLegal']          ?? null,
            'cpf_cnpj'            => $form['numeroDocumento']    ?? null,
            'inscricao_estadual'  => $form['inscricaoEstadual']  ?? null,
            'nascimento_fundacao' => $form['dataRegistro']       ?? null,
            'ativo'               => (int)(($form['ativo'] ?? '') === 'true'),
        ];
        try {
            $IsUpdated = \App\Database\DB::connection()->update('supplier', $FieldsAndValues, ['id' => $id]);
            if (!$IsUpdated) {
                return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $IsUpdated, 'id' => 0], 200);
            }
            return $this->json($response, ['status' => true, 'msg' => 'Alterado com sucesso!', 'id' => $id], 200);
        } catch (\Exception $e) {
            return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $e->getMessage(), 'id' => 0], 200);
        }
    }

    public function delete($request, $response)
    {
        $form = $request->getParsedBody();
        $id = $form['id'] ?? null;
        if (is_null($id) || $id === '') {
            return $this->json($response, ['status' => false, 'msg' => 'Informe o código do Fornecedor', 'id' => 0], 200);
        }
        try {
            $IsDeleted = \App\Database\DB::connection()->delete('supplier', ['id' => $id]);
            if (!$IsDeleted) {
                return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $IsDeleted, 'id' => $id], 200);
            }
            return $this->json($response, ['status' => true, 'msg' => 'Removido com sucesso!', 'id' => $id]);
        } catch (\Exception $e) {
            return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $e->getMessage(), 'id' => 0], 200);
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
            1 => 'nome_fantasia',
            2 => 'sobrenome_razao',
            3 => 'cpf_cnpj',
            4 => 'inscricao_estadual',
            5 => 'ativo',
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
                ->from('supplier')
                ->fetchOne();

            # Query principal com WHERE opcional
            $query = \App\Database\DB::select('*')->from('supplier');

            if (!is_null($term) && $term !== '') {
                $query->setParameter('term', '%' . $term . '%');

                $query->where('CAST(id AS TEXT) ILIKE :term')
                    ->orWhere('nome_fantasia ILIKE :term')
                    ->orWhere('sobrenome_razao ILIKE :term')
                    ->orWhere('cpf_cnpj ILIKE :term')
                    ->orWhere('inscricao_estadual ILIKE :term')
                    ->orWhere("TO_CHAR(criado_em, 'DD/MM/YYYY HH24:MI:SS') ILIKE :term")
                    ->orWhere("TO_CHAR(atualizado_em, 'DD/MM/YYYY HH24:MI:SS') ILIKE :term");
            }

            # Total com filtro aplicado — clona o query e troca o SELECT por COUNT
            $filteredRecords = (int) (clone $query)
                ->select('COUNT(*)')
                ->fetchOne();

            # Resultados paginados e ordenados
            $supplier = $query
                ->orderBy($orderField, $orderType)
                ->setFirstResult($start)
                ->setMaxResults($length)
                ->fetchAllAssociative();

            # Formatação para o DataTables
            $rows = [];
            foreach ($supplier as $key => $value) {
                $rows[$key] = [
                    $value['id'],
                    $value['nome_fantasia']     ?? '',
                    $value['sobrenome_razao'] ?? '',
                    $value['cpf_cnpj']         ?? '',
                    $value['inscricao_estadual']           ?? '',
                    ($value['ativo'] == true) ? 'Ativo' : 'Inativo',
                    "<td>
            <button type='button' class='btn btn-sm btn-warning' onclick='EditSupplier(" . $value['id'] . ");'>
                <i class='fa-solid fa-pen-to-square'></i> Editar
            </button>
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
            ], 200);
        }
    }

    /**
     * Retorna os dados de um fornecedor em JSON, para preencher o modal
     * de edição via fetch (sem recarregar a página).
     */
    public function details($request, $response, $args)
    {
        $id = $args['id'] ?? null;

        if (is_null($id) || !is_numeric($id)) {
            return $this->json($response, ['status' => false, 'msg' => 'ID inválido.'], 200);
        }

        $supplier = \App\Database\DB::select('*')
            ->from('supplier')
            ->where('id = ' . (int) $id)
            ->fetchAssociative();

        if (!$supplier) {
            return $this->json($response, ['status' => false, 'msg' => 'Fornecedor não encontrado.'], 200);
        }

        return $this->json($response, ['status' => true, 'data' => $supplier], 200);
    }
}