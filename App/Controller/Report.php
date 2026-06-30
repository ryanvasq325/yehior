<?php

declare(strict_types=1);

namespace App\Controller;

final class Report extends Base
{
    public function home($request, $response)
    {
        $tipos = \App\Database\DB::select('id', 'descricao')
            ->from('type_problem')
            ->where('ativo = true')
            ->orderBy('id', 'ASC')
            ->fetchAllAssociative();

        $params = $request->getQueryParams();

        return $this->getTwig()
            ->render($response, $this->setView('report'), [
                'titulo'  => '',
                'tipos'   => $tipos,
                'success' => isset($params['success']),
            ])
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }

    public function store($request, $response)
    {
        $form = $request->getParsedBody();

        $idTipoProblema = $form['id_tipo_problema'] ?? null;
        $cep            = trim((string) ($form['cep'] ?? ''));
        $endereco       = trim((string) ($form['address'] ?? ''));
        $numero         = trim((string) ($form['numero'] ?? ''));
        $bairro         = trim((string) ($form['district'] ?? ''));
        $poste          = trim((string) ($form['pole'] ?? ''));
        $latitude       = $form['latitude'] ?? '';
        $longitude      = $form['longitude'] ?? '';
        $descricao      = trim((string) ($form['descricao'] ?? ''));

        $erros = [];
        if (empty($idTipoProblema) || !is_numeric($idTipoProblema)) {
            $erros[] = 'Selecione o tipo do problema.';
        }
        if ($cep === '')      $erros[] = 'Informe o CEP.';
        if ($endereco === '') $erros[] = 'Informe o logradouro.';
        if ($numero === '')   $erros[] = 'Informe o número.';
        if ($bairro === '')   $erros[] = 'Informe o bairro.';
        if ($poste === '')    $erros[] = 'Informe o número do poste.';

        if (!empty($erros)) {
            return $this->renderComErro($response, implode(' ', $erros), [
                'old_tipo'      => $idTipoProblema,
                'old_cep'       => $cep,
                'old_address'   => $endereco,
                'old_numero'    => $numero,
                'old_district'  => $bairro,
                'old_pole'      => $poste,
                'old_latitude'  => $latitude,
                'old_longitude' => $longitude,
                'old_descricao' => $descricao,
            ], 422);
        }

        // ⚠️ Ajuste para o seu fluxo real de autenticação (JWT em cookie httpOnly).
        // Se o middleware já injeta o usuário autenticado na request, troque a linha abaixo
        // pelo nome correto do atributo (ex: $request->getAttribute('user_id')).
        $user       = $request->getAttribute('user');
        $idCustomer = $user->id ?? null;

        try {
            \App\Database\DB::connection()->insert('reports', [
                'id_customer'      => $idCustomer,
                'id_tipo_problema' => (int) $idTipoProblema,
                'cep'              => $cep,
                'endereco'         => $endereco,
                'numero'           => $numero,
                'bairro'           => $bairro,
                'poste'            => $poste,
                'latitude'         => $latitude !== '' ? $latitude : null,
                'longitude'        => $longitude !== '' ? $longitude : null,
                'descricao'        => $descricao !== '' ? $descricao : null,
                'resolvido'        => 0, // boolean no Postgres via Doctrine DBAL — cast (int) evita o bug que vocês já pegaram antes
            ]);
        } catch (\Exception $e) {
            return $this->renderComErro($response, 'Não foi possível salvar o reporte: ' . $e->getMessage(), [], 500);
        }

        return $response
            ->withHeader('Location', '/report/home?success=1')   // ✅ bate com o grupo /report + rota /home
            ->withStatus(302);
    }

    public function pendentes($request, $response)
    {
        $total = (int) \App\Database\DB::select('COUNT(*)')
            ->from('reports')
            ->where('resolvido = false')
            ->fetchOne();

        return $this->json($response, ['total' => $total]);
    }

    public function delete($request, $response)
    {
        $form = $request->getParsedBody();
        $id = $form['id'] ?? null;
        if (is_null($id) || $id === '') {
            return $this->json($response, ['status' => false, 'msg' => 'Informe o código do relatório', 'id' => 0], 403);
        }
        try {
            $IsDeleted = \App\Database\DB::connection()->delete('reports', ['id' => $id]);
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
            1 => 'id_customer',
            2 => 'id_tipo_problema',
            3 => 'id_produto',
            4 => 'cep',
            5 => 'descricao',
            6 => 'resolvido',
        ];

        $posField = (isset($form['order'][0]['column']) && isset($columns[(int) $form['order'][0]['column']]))
            ? (int) $form['order'][0]['column']
            : 0;

        $orderType  = strtoupper($form['order'][0]['dir'] ?? 'DESC');
        $orderType  = in_array($orderType, ['ASC', 'DESC'], true) ? $orderType : 'DESC';
        $orderField = $columns[$posField];

        try {
            $totalRecords = (int) \App\Database\DB::select('COUNT(*)')
                ->from('reports')
                ->fetchOne();

            $query = \App\Database\DB::select('*')->from('reports');

            if (!is_null($term) && $term !== '') {
                $query->setParameter('term', '%' . $term . '%');

                $query->where('CAST(id AS TEXT) ILIKE :term')
                    ->orWhere('id_customer ILIKE :term')
                    ->orWhere('id_tipo_problema ILIKE :term')
                    ->orWhere('id_produto ILIKE :term')
                    ->orWhere('cep ILIKE :term')
                    ->orWhere("TO_CHAR(criado_em, 'DD/MM/YYYY HH24:MI:SS') ILIKE :term")
                    ->orWhere("TO_CHAR(atualizado_em, 'DD/MM/YYYY HH24:MI:SS') ILIKE :term");
            }

            $filteredRecords = (int) (clone $query)
                ->select('COUNT(*)')
                ->fetchOne();

            $reports = $query
                ->orderBy($orderField, $orderType)
                ->setFirstResult($start)
                ->setMaxResults($length)
                ->fetchAllAssociative();

            $rows = [];
            foreach ($reports as $key => $value) {
                $rows[$key] = [
                    $value['id'],
                    $value['id_customer']     ?? '',
                    $value['id_tipo_problema'] ?? '',
                    $value['id_produto']         ?? '',
                    $value['cep']           ?? '',
                    $value['descricao']         ?? '',
                    ($value['resolvido'] == true) ? 'Resolvido' : 'Pendente',
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

    private function renderComErro($response, string $erro, array $old = [], int $status = 422)
    {
        $tipos = \App\Database\DB::select('id', 'descricao')
            ->from('type_problem')
            ->where('ativo = true')
            ->orderBy('id', 'ASC')
            ->fetchAllAssociative();

        return $this->getTwig()
            ->render($response, $this->setView('report'), array_merge([
                'titulo' => '',
                'tipos'  => $tipos,
                'error'  => $erro,
            ], $old))
            ->withHeader('Content-Type', 'text/html')
            ->withStatus($status);
    }
}
