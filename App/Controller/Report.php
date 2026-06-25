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

        return $this->getTwig()
            ->render($response, $this->setView('report'), [
                'titulo' => '',
                'tipos'  => $tipos,
            ])
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
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

        # Validação da direção evita SQL injection no ORDER BY
        $orderType  = strtoupper($form['order'][0]['dir'] ?? 'DESC');
        $orderType  = in_array($orderType, ['ASC', 'DESC'], true) ? $orderType : 'DESC';
        $orderField = $columns[$posField];

        try {
            # Total geral DataTables: recordsTotal
            $totalRecords = (int) \App\Database\DB::select('COUNT(*)')
                ->from('reports')
                ->fetchOne();

            # Query principal com WHERE opcional
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

            # Total com filtro aplicado — clona o query e troca o SELECT por COUNT
            $filteredRecords = (int) (clone $query)
                ->select('COUNT(*)')
                ->fetchOne();

            # Resultados paginados e ordenados
            $reports = $query
                ->orderBy($orderField, $orderType)
                ->setFirstResult($start)
                ->setMaxResults($length)
                ->fetchAllAssociative();

            # Formatação para o DataTables
            # Formatação para o DataTables
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
}
