<?php

declare(strict_types=1);

namespace App\Controller;


final class Admin extends Base
{
    public function gestao($request, $response)
    {
        // Busca todos os reportes que possuem latitude e longitude salvas
        // para exibir como pins no mapa do painel admin
        $reportsMap = \App\Database\DB::select(
                'r.id',
                'r.latitude AS lat',
                'r.longitude AS lng',
                'r.cep',
                'r.resolvido',
                'tp.descricao AS tipo',
                'c.nome AS cidadao',
                'a.logradouro AS endereco'
            )
            ->from('reports', 'r')
            ->leftJoin('r', 'type_problem', 'tp', 'r.id_tipo_problema = tp.id')
            ->leftJoin('r', 'customer',     'c',  'r.id_customer = c.id')
            ->leftJoin('r', 'address',      'a',  'r.id_customer = a.id_customer')
            ->where('r.latitude IS NOT NULL AND r.longitude IS NOT NULL')
            ->fetchAllAssociative();

        return $this->getTwig()
            ->render($response, $this->setView('gestao'), [
                'titulo'     => '',
                'reportsMap' => $reportsMap,
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
    public function listsupplier($request, $response)
    {
        return $this->getTwig()
            ->render($response, $this->setView('list-supplier'), [
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
}