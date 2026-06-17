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
}