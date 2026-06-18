<?php

declare(strict_types=1);

namespace App\Controller;

final class Supplier extends Base
{
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
                return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $IsInserted, 'id' => 0], 500);
            }
            $id = \App\Database\DB::connection()->lastInsertId();
            return $this->json($response, ['status' => true, 'msg' => 'Fornecedor salvo com sucesso!', 'id' => $id], 201);
        } catch (\Exception $e) {
            return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $e->getMessage(), 'id' => 0], 500);
        }
    }
}