<?php

declare(strict_types=1);

namespace App\Controller;


final class Admin extends Base
{
    public function gestao($request, $response)
    {
        // Busca todos os reportes que possuem latitude e longitude salvas
        // para exibir como pins no mapa do painel admin.
        // endereco/bairro/poste agora vêm direto das colunas de reports
        // (não dependem mais de um cadastro de endereço do cidadão).
        $reportsMap = \App\Database\DB::select(
                'r.id',
                'r.latitude AS lat',
                'r.longitude AS lng',
                'r.cep',
                'r.endereco',
                'r.numero',
                'r.bairro',
                'r.poste',
                'r.resolvido',
                'tp.descricao AS tipo',
                'c.nome AS cidadao'
            )
            ->from('reports', 'r')
            ->leftJoin('r', 'type_problem', 'tp', 'r.id_tipo_problema = tp.id')
            ->leftJoin('r', 'customer',     'c',  'r.id_customer = c.id')
            ->where('r.latitude IS NOT NULL AND r.longitude IS NOT NULL')
            ->fetchAllAssociative();

        // ⚠️ Ajuste os nomes das tabelas abaixo (users/supplier/products) caso
        // sejam diferentes no seu schema real.

        $totalPendentes = (int) \App\Database\DB::select('COUNT(*)')
            ->from('reports')
            ->where('resolvido = false')
            ->fetchOne();

        $totalReports = (int) \App\Database\DB::select('COUNT(*)')
            ->from('reports')
            ->fetchOne();

        $totalUsers = (int) \App\Database\DB::select('COUNT(*)')
            ->from('users')
            ->fetchOne();

        $totalSuppliers = (int) \App\Database\DB::select('COUNT(*)')
            ->from('supplier')
            ->fetchOne();

        $totalProducts = (int) \App\Database\DB::select('COUNT(*)')
            ->from('products')
            ->fetchOne();

        // Últimos reportes cadastrados, para a tabela "Reportes recentes"
        $recentReports = \App\Database\DB::select(
                'r.id',
                'c.nome AS customer_nome',
                'tp.descricao AS problema',
                'r.cep',
                'r.resolvido',
                'r.data_cadastro AS criado_em'
            )
            ->from('reports', 'r')
            ->leftJoin('r', 'type_problem', 'tp', 'r.id_tipo_problema = tp.id')
            ->leftJoin('r', 'customer',     'c',  'r.id_customer = c.id')
            ->orderBy('r.data_cadastro', 'DESC')
            ->setMaxResults(6)
            ->fetchAllAssociative();

        // Contagem por tipo de problema, para o card "Por tipo de problema".
        // Vem direto da tabela type_problem — qualquer tipo novo cadastrado
        // aparece automaticamente aqui, sem precisar mexer no template.
        $tiposRaw = \App\Database\DB::select('tp.id', 'tp.descricao', 'COUNT(r.id) AS total')
            ->from('type_problem', 'tp')
            ->leftJoin('tp', 'reports', 'r', 'r.id_tipo_problema = tp.id')
            ->where('tp.ativo = true')
            ->groupBy('tp.id', 'tp.descricao')
            ->orderBy('tp.id', 'ASC')
            ->fetchAllAssociative();

        // Ícone de exibição por slug — só estética. Tipo novo/desconhecido
        // cai no ícone padrão (🔹) em vez de sumir da lista.
        $iconesPorSlug = [
            'lampada_apagada'      => '💡',
            'luz_piscando'         => '🔴',
            'fio_cortado'          => '✂️',
            'estrutura_danificada' => '🏚️',
            'trecho_sem_luz'       => '🌑',
            'outro'                => '❓',
        ];

        $tiposComContagem = array_map(function ($t) use ($iconesPorSlug) {
            $slug = $this->slugify($t['descricao']);

            return [
                'descricao' => $t['descricao'],
                'total'     => (int) $t['total'],
                'icon'      => $iconesPorSlug[$slug] ?? '🔹',
            ];
        }, $tiposRaw);

        return $this->getTwig()
            ->render($response, $this->setView('gestao'), [
                'titulo'            => '',
                'reportsMap'        => $reportsMap,
                'totalReports'      => $totalReports,
                'totalUsers'        => $totalUsers,
                'totalSuppliers'    => $totalSuppliers,
                'totalProducts'     => $totalProducts,
                'totalPending'      => $totalPendentes,
                'recentReports'     => $recentReports,
                'tiposComContagem'  => $tiposComContagem,
            ])
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }

    /**
     * Converte uma descricao (ex: "Lâmpada apagada") em slug snake_case
     * (ex: "lampada_apagada"), removendo acentos e caracteres especiais.
     */
    private function slugify(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
        $texto = preg_replace('/[^a-z0-9]+/', '_', $texto) ?? $texto;

        return trim($texto, '_');
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
        // Totais gerais para os cards de resumo do topo
        $totalReports = (int) \App\Database\DB::select('COUNT(*)')
            ->from('reports')
            ->fetchOne();

        $totalResolvidos = (int) \App\Database\DB::select('COUNT(*)')
            ->from('reports')
            ->where('resolvido = true')
            ->fetchOne();

        $totalPendentes = $totalReports - $totalResolvidos;

        // Resumo por tipo de problema: total, resolvidos, pendentes e taxa de resolução.
        // LEFT JOIN a partir de type_problem garante que tipos sem nenhum report
        // apareçam com total = 0, em vez de simplesmente sumir da tabela.
        $tiposRaw = \App\Database\DB::select(
                'tp.descricao',
                'COUNT(r.id) AS total',
                'COUNT(CASE WHEN r.resolvido = true THEN 1 END) AS resolvidos'
            )
            ->from('type_problem', 'tp')
            ->leftJoin('tp', 'reports', 'r', 'r.id_tipo_problema = tp.id')
            ->groupBy('tp.descricao')
            ->orderBy('total', 'DESC')
            ->fetchAllAssociative();

        $tipos = array_map(function ($t) {
            $total      = (int) $t['total'];
            $resolvidos = (int) $t['resolvidos'];
            $pendentes  = $total - $resolvidos;
            $taxa       = $total > 0 ? round(($resolvidos / $total) * 100, 1) : 0;

            return [
                'descricao'  => $t['descricao'],
                'total'      => $total,
                'resolvidos' => $resolvidos,
                'pendentes'  => $pendentes,
                'taxa'       => $taxa,
            ];
        }, $tiposRaw);

        return $this->getTwig()
            ->render($response, $this->setView('relatorio'), [
                'titulo'          => '',
                'totalReports'    => $totalReports,
                'totalResolvidos' => $totalResolvidos,
                'totalPendentes'  => $totalPendentes,
                'tipos'           => $tipos,
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
            // bairro agora vem direto de reports.bairro (preenchido pelo cidadão
            // no formulário de report), não precisa mais de join com address
            $qb = \App\Database\DB::select('r.bairro', 'COUNT(r.id) AS total')
                ->from('reports', 'r')
                ->where('r.bairro IS NOT NULL')
                ->groupBy('r.bairro')
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
            // Gráfico de pizza: quantidade de reportes por tipo de problema
            $rows = \App\Database\DB::select('tp.descricao', 'COUNT(r.id) AS total')
                ->from('type_problem', 'tp')
                ->leftJoin('tp', 'reports', 'r', 'r.id_tipo_problema = tp.id')
                ->groupBy('tp.descricao')
                ->orderBy('total', 'DESC')
                ->fetchAllAssociative();

            $data = [
                'labels' => array_column($rows, 'descricao'),
                'values' => array_map(fn($r) => (int) $r['total'], $rows),
            ];

            return $this->json($response, $data, 200);
        } catch (\Throwable $e) {
            error_log('[bytipo] ' . $e->getMessage());
            return $this->json($response, ['status' => false, 'msg' => 'Erro ao buscar dados.'], 500);
        }
    }

    public function bymes($request, $response)
    {
        try {
            // Gráfico de barras empilhadas: reportes resolvidos x pendentes por mês,
            // agrupado a partir de data_cadastro
            $rows = \App\Database\DB::select(
                    "TO_CHAR(data_cadastro, 'YYYY-MM') AS mes",
                    'resolvido',
                    'COUNT(*) AS total'
                )
                ->from('reports')
                ->where('data_cadastro IS NOT NULL')
                ->groupBy('mes', 'resolvido')
                ->orderBy('mes', 'ASC')
                ->fetchAllAssociative();

            $meses            = [];
            $resolvidosPorMes = [];
            $pendentesPorMes  = [];

            foreach ($rows as $row) {
                $mes = $row['mes'];
                if (!isset($resolvidosPorMes[$mes])) {
                    $meses[]                 = $mes;
                    $resolvidosPorMes[$mes]   = 0;
                    $pendentesPorMes[$mes]    = 0;
                }
                if ($row['resolvido']) {
                    $resolvidosPorMes[$mes] = (int) $row['total'];
                } else {
                    $pendentesPorMes[$mes] = (int) $row['total'];
                }
            }

            sort($meses);

            $data = [
                'labels' => array_map([$this, 'formatarMesAno'], $meses),
                'series' => [
                    [
                        'name'   => 'Resolvidos',
                        'stack'  => 'total',
                        'values' => array_map(fn($m) => $resolvidosPorMes[$m], $meses),
                    ],
                    [
                        'name'   => 'Pendentes',
                        'stack'  => 'total',
                        'values' => array_map(fn($m) => $pendentesPorMes[$m], $meses),
                    ],
                ],
            ];

            return $this->json($response, $data, 200);
        } catch (\Throwable $e) {
            error_log('[bymes] ' . $e->getMessage());
            return $this->json($response, ['status' => false, 'msg' => 'Erro ao buscar dados.'], 500);
        }
    }

    /**
     * Converte "2026-07" em "Jul/2026" para exibir nos rótulos do gráfico.
     */
    private function formatarMesAno(string $mes): string
    {
        $nomes = [
            '01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr',
            '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
            '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez',
        ];

        [$ano, $numMes] = explode('-', $mes);

        return ($nomes[$numMes] ?? $numMes) . '/' . $ano;
    }
}