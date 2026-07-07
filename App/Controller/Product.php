<?php

declare(strict_types=1);

namespace App\Controller;

final class Product extends Base
{
    public function home($request, $response)
{
    $params = $request->getQueryParams();

    $nome       = trim((string) ($params['nome'] ?? ''));
    $idSupplier = $params['id_supplier'] ?? '';
    $ativo      = $params['ativo'] ?? '';

    // Query base reaproveitada nas três contagens, com os mesmos filtros do formulário
    $baseQuery = function () use ($nome, $idSupplier, $ativo) {
        $query = \App\Database\DB::select('COUNT(*)')->from('products');

        if ($nome !== '') {
            $query->setParameter('nome', '%' . $nome . '%');
            $query->andWhere('nome ILIKE :nome');
        }

        if ($idSupplier !== '' && is_numeric($idSupplier)) {
            $query->andWhere('id_supplier = ' . (int) $idSupplier);
        }

        if ($ativo === '0' || $ativo === '1') {
            $boolLiteral = $ativo === '1' ? 'true' : 'false';
            $query->andWhere("ativo = {$boolLiteral}");
        }

        return $query;
    };

    $totalItens    = (int) $baseQuery()->fetchOne();
    $totalAtivos   = (int) $baseQuery()->andWhere('ativo = true')->fetchOne();
    $totalInativos = $totalItens - $totalAtivos;

    return $this->getTwig()
        ->render($response, $this->setView('products'), [
            'titulo'        => '',
            'totalItens'    => $totalItens,
            'totalAtivos'   => $totalAtivos,
            'totalInativos' => $totalInativos,
        ])
        ->withHeader('Content-Type', 'text/html')
        ->withStatus(200);
}

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

    /**
     * Atualiza um produto existente. Segue o mesmo padrão do Supplier::update().
     */
    public function update($request, $response)
    {
        $form = $request->getParsedBody();
        $id   = $form['id'] ?? null;

        if (is_null($id) || $id === '') {
            return $this->json($response, ['status' => false, 'msg' => 'Por favor informe o ID do registro', 'id' => 0], 200);
        }

        $FieldsAndValues = [
            'nome'         => $form['nome']         ?? null,
            'codigo_barra' => $form['codigo_barra'] ?? null,
            'unidade'      => $form['unidade']      ?? null,
            'preco_compra' => $form['preco_compra'] ?? null,
            'descricao'    => $form['descricao']    ?? null,
            'ativo'        => (int)(($form['ativo'] ?? '') === 'true'),
        ];

        try {
            $IsUpdated = \App\Database\DB::connection()->update('products', $FieldsAndValues, ['id' => $id]);
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

    /**
     * Retorna os dados de um produto em JSON, para preencher o modal
     * de edição via fetch (sem recarregar a página). Mesmo padrão do
     * Supplier::details().
     */
    public function details($request, $response, $args)
    {
        $id = $args['id'] ?? null;

        if (is_null($id) || !is_numeric($id)) {
            return $this->json($response, ['status' => false, 'msg' => 'ID inválido.'], 200);
        }

        $product = \App\Database\DB::select('*')
            ->from('products')
            ->where('id = ' . (int) $id)
            ->fetchAssociative();

        if (!$product) {
            return $this->json($response, ['status' => false, 'msg' => 'Produto não encontrado.'], 200);
        }

        return $this->json($response, ['status' => true, 'data' => $product], 200);
    }

    /**
     * Retorna o estoque atual de um produto (para preencher o modal), ou,
     * se "nova_quantidade" vier preenchida, calcula a diferença e registra
     * um novo movimento em stock_movement (entrada ou saída conforme o sinal).
     * A materialized view mvw_estoque é recalculada automaticamente pelo
     * trigger trg_refresh_estoque_on_movement após o insert.
     */
    public function selecionarestoque($request, $response)
    {
        try {
            $form = $request->getParsedBody();
            $id   = $form['id'] ?? null;

            if (is_null($id) || !is_numeric($id)) {
                return $this->json($response, ['status' => false, 'msg' => 'Informe o produto.'], 422);
            }

            $id = (int) $id;

            $estoqueAtual = \App\Database\DB::select('estoque_atual')
                ->from('mvw_estoque')
                ->where('id_produto = ' . $id)
                ->fetchOne();

            $estoqueAtual = $estoqueAtual !== false ? (float) $estoqueAtual : 0.0;

            if (!isset($form['nova_quantidade']) || $form['nova_quantidade'] === '') {
                return $this->json($response, [
                    'status'        => true,
                    'estoque_atual' => $estoqueAtual,
                ]);
            }

            $novoEstoqueDesejado = (float) str_replace(',', '.', (string) $form['nova_quantidade']);
            $quantidadeAjuste    = $novoEstoqueDesejado - $estoqueAtual;

            if ($quantidadeAjuste == 0.0) {
                return $this->json($response, ['status' => true, 'msg' => 'O estoque já está neste valor.']);
            }

            \App\Database\DB::connection()->insert('stock_movement', [
                'id_produto'         => $id,
                'quantidade_entrada' => $quantidadeAjuste > 0 ? $quantidadeAjuste : 0,
                'quantidade_saida'   => $quantidadeAjuste < 0 ? abs($quantidadeAjuste) : 0,
                'observacao'         => 'AJUSTE MANUAL',
                'data_cadastro'      => date('Y-m-d H:i:s'),
                'data_atualizacao'   => date('Y-m-d H:i:s'),
            ]);

            return $this->json($response, [
                'status' => true,
                'msg'    => 'Estoque ajustado! Movimentação de ' . abs($quantidadeAjuste) . ' unidade(s).',
            ]);
        } catch (\Throwable $e) {
            return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $e->getMessage()], 500);
        }
    }

    public function listingdata($request, $response)
    {
        $form = $request->getParsedBody();

        $term   = $form['search']['value'] ?? null;
        $start  = (int) ($form['start']  ?? 0);
        $length = (int) ($form['length'] ?? 10);

        $columns = [
            0 => 'p.id',
            1 => 'p.nome',
            2 => 'p.codigo_barra',
            3 => 'p.unidade',
            4 => 'p.preco_compra',
            5 => 'p.descricao',
            7 => 'm.estoque_atual',
        ];

        $posField = (isset($form['order'][0]['column']) && isset($columns[(int) $form['order'][0]['column']]))
            ? (int) $form['order'][0]['column']
            : 0;

        $orderType  = strtoupper($form['order'][0]['dir'] ?? 'DESC');
        $orderType  = in_array($orderType, ['ASC', 'DESC'], true) ? $orderType : 'DESC';
        $orderField = $columns[$posField];

        try {
            $totalRecords = (int) \App\Database\DB::select('COUNT(*)')
                ->from('products')
                ->fetchOne();

            $query = \App\Database\DB::select('p.*', 'm.estoque_atual')
                ->from('products', 'p')
                ->leftJoin('p', 'mvw_estoque', 'm', 'm.id_produto = p.id');

            if (!is_null($term) && $term !== '') {
                $query->setParameter('term', '%' . $term . '%');

                $query->where('CAST(p.id AS TEXT) ILIKE :term')
                    ->orWhere('p.nome ILIKE :term')
                    ->orWhere('p.codigo_barra ILIKE :term')
                    ->orWhere('p.descricao ILIKE :term');
            }

            $filteredRecords = (int) (clone $query)
                ->select('COUNT(*)')
                ->fetchOne();

            $products = $query
                ->orderBy($orderField, $orderType)
                ->setFirstResult($start)
                ->setMaxResults($length)
                ->fetchAllAssociative();

            $rows = [];
            foreach ($products as $key => $value) {
                $rows[$key] = [
                    $value['id'],
                    $value['nome']         ?? '',
                    $value['codigo_barra'] ?? '',
                    $value['unidade']      ?? '',
                    $value['preco_compra'] ?? '',
                    $value['descricao']    ?? '',
                    $value['estoque_atual'] ?? 0,
                    "<td>
            <button type='button' class='btn btn-sm btn-warning' onclick='EditProduct(" . $value['id'] . ");'>
                <i class='fa-solid fa-pen-to-square'></i> Editar
            </button>
            <button type='button' class='btn btn-sm btn-info' onclick='AjustarEstoque(" . $value['id'] . ");'>
                <i class='fa-solid fa-boxes-packing'></i> Estoque
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
            ], 500);
        }
    }
}