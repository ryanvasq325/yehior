<?php

declare(strict_types=1);

namespace App\Controller;


final class Admin extends Base
{
    public function gestao($request, $response)
    {
        return $this->getTwig()
            ->render($response, $this->setView('gestao'), [
                'titulo' => '',
            ])
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }

    public function users($request, $response)
    {
        return $this->getTwig()
            ->render($response, $this->setView('users'), [
                'titulo' => '',
            ])
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }

    public function listusers($request, $response)
    {
        return $this->getTwig()
            ->render($response, $this->setView('list-users'), [
                'titulo' => '',
            ])
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }

    public function listreport($request, $response)
    {
        return $this->getTwig()
            ->render($response, $this->setView('list-report'), [
                'titulo' => '',
            ])
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }
    public function products($request, $response)
    {
        return $this->getTwig()
            ->render($response, $this->setView('products'), [
                'titulo' => '',
            ])
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }
    public function relatorio($request, $response)
    {
        return $this->getTwig()
            ->render($response, $this->setView('relatorio'), [
                'titulo' => '',
            ])
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }
    public function getsalesdata($request, $response)
    {
        try {
            $qb = \App\Database\DB::select('tp.descricao', 'COUNT(r.id) AS total')
                ->from('reports', 'r')
                ->join('r', 'type_problem', 'tp', 'r.id_tipo_problema = tp.id')
                ->groupBy('tp.descricao')
                ->orderBy('total', 'DESC');

            $rows = $qb->fetchAllAssociative();

            $data = [
                'labels' => array_column($rows, 'descricao'),
                'series' => [[
                    'name'   => 'Chamados',
                    'values' => array_map(fn($r) => (int) $r['total'], $rows),
                    'stack'  => null,
                ]],
            ];

            return $this->json($response, $data, 200);
        } catch (\Throwable $e) {
            error_log('[getsalesdata] ' . $e->getMessage());
            // temporário — remove depois de resolver
            return $this->json($response, ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }
    public function getabcranking($request, $response)
    {
        try {
            $qb = \App\Database\DB::select('a.bairro', 'COUNT(r.id) AS total')
                ->from('reports', 'r')
                ->join('r', 'address', 'a', 'r.id_customer = a.id_customer')
                ->groupBy('a.bairro')
                ->orderBy('total', 'DESC');

            $rows = $qb->fetchAllAssociative();

            $data = [
                'labels' => array_map(fn($r) => $r['bairro'] ?? 'Sem bairro', $rows),
                'values' => array_map(fn($r) => (int) $r['total'], $rows),
            ];

            return $this->json($response, $data, 200);
        } catch (\Throwable $e) {
            error_log('[getabcranking] ' . $e->getMessage());
            return $this->json($response, ['status' => false, 'msg' => 'Erro ao buscar dados.'], 500);
        }
    }
    public function bytipo($request, $response)
    {
        try {
            $qb = \App\Database\DB::select('tp.descricao', 'COUNT(r.id) AS total')
                ->from('reports', 'r')
                ->join('r', 'type_problem', 'tp', 'r.id_tipo_problema = tp.id')
                ->groupBy('tp.descricao')
                ->orderBy('total', 'DESC');

            $rows = $qb->fetchAllAssociative();

            $data = [
                'labels' => array_column($rows, 'descricao'),
                'values' => array_map(fn($r) => (int) $r['total'], $rows),
            ];

            return $this->json($response, $data, 200);
        } catch (\Throwable $e) {
            error_log('[Relatorio::bytipo] ' . $e->getMessage());
            return $this->json($response, ['status' => false, 'msg' => 'Erro ao buscar dados.'], 500);
        }
    }

    public function bymes($request, $response)
    {
        try {
            $qb = \App\Database\DB::select(
                    "to_char(r.data_cadastro, 'YYYY-MM') AS mes",
                    'COUNT(r.id) FILTER (WHERE r.resolvido = true) AS resolvidos',
                    'COUNT(r.id) FILTER (WHERE r.resolvido = false OR r.resolvido IS NULL) AS pendentes'
                )
                ->from('reports', 'r')
                ->groupBy('mes')
                ->orderBy('mes', 'ASC');

            $rows = $qb->fetchAllAssociative();

            $data = [
                'labels' => array_column($rows, 'mes'),
                'series' => [
                    [
                        'name'   => 'Resolvidos',
                        'values' => array_map(fn($r) => (int) $r['resolvidos'], $rows),
                        'stack'  => 'reportes',
                    ],
                    [
                        'name'   => 'Pendentes',
                        'values' => array_map(fn($r) => (int) $r['pendentes'], $rows),
                        'stack'  => 'reportes',
                    ],
                ],
            ];

            return $this->json($response, $data, 200);
        } catch (\Throwable $e) {
            error_log('[Relatorio::bymes] ' . $e->getMessage());
            return $this->json($response, ['status' => false, 'msg' => 'Erro ao buscar dados.'], 500);
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
            2 => 'sobrenome',
            3 => 'cpf',
            4 => 'rg',
            6 => 'ativo',
            5 => 'administrador',
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
                ->from('users')
                ->fetchOne();

            # Query principal com WHERE opcional
            $query = \App\Database\DB::select('*')->from('users');

            if (!is_null($term) && $term !== '') {
                $query->setParameter('term', '%' . $term . '%');

                $query->where('CAST(id AS TEXT) ILIKE :term')
                    ->orWhere('nome ILIKE :term')
                    ->orWhere('sobrenome ILIKE :term')
                    ->orWhere('cpf ILIKE :term')
                    ->orWhere('rg ILIKE :term')
                    ->orWhere("TO_CHAR(criado_em, 'DD/MM/YYYY HH24:MI:SS') ILIKE :term")
                    ->orWhere("TO_CHAR(atualizado_em, 'DD/MM/YYYY HH24:MI:SS') ILIKE :term");
            }

            # Total com filtro aplicado — clona o query e troca o SELECT por COUNT
            $filteredRecords = (int) (clone $query)
                ->select('COUNT(*)')
                ->fetchOne();

            # Resultados paginados e ordenados
            $users = $query
                ->orderBy($orderField, $orderType)
                ->setFirstResult($start)
                ->setMaxResults($length)
                ->fetchAllAssociative();

            # Formatação para o DataTables
            # Formatação para o DataTables
            $rows = [];
            foreach ($users as $key => $value) {
                $rows[$key] = [
                    $value['id'],
                    $value['nome']     ?? '',
                    $value['sobrenome']     ?? '',
                    $value['cpf']     ?? '',
                    $value['rg']     ?? '',
                    ($value['ativo'] == true) ? 'Ativo' : 'Inativo',
                    ($value['administrador'] == true) ? 'Sim' : 'Não',
                    "<td>
            <a class='btn btn-sm btn-warning' href='/usuario/detalhes/" . $value['id'] . "'>
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
    public function delete($request, $response)
    {
        $form = $request->getParsedBody();
        $id = $form['id'] ?? null;
        if (is_null($id) || $id === '') {
            return $this->json($response, ['status' => false, 'msg' => 'Informe o código do usuario', 'id' => 0], 403);
        }
        try {
            $IsDeleted = \App\Database\DB::connection()->delete('users', ['id' => $id]);
            if (!$IsDeleted) {
                return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $IsDeleted, 'id' => $id], 403);
            }
            return $this->json($response, ['status' => true, 'msg' => 'Removido com sucesso!', 'id' => $id]);
        } catch (\Exception $e) {
            return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $e->getMessage(), 'id' => 0], 500);
        }
    }
}
