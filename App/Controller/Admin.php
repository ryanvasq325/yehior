<?php

declare(strict_types=1);

namespace App\Controller;


final class Admin extends Base
{
    public function gestao($request, $response)
    {
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
                'u.nome AS cidadao'
            )
            ->from('reports', 'r')
            ->leftJoin('r', 'type_problem', 'tp', 'r.id_tipo_problema = tp.id')
            ->leftJoin('r', 'users',        'u',  'r.id_users = u.id')
            ->where('r.latitude IS NOT NULL AND r.longitude IS NOT NULL')
            ->fetchAllAssociative();

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
                'u.nome AS customer_nome',
                'tp.descricao AS problema',
                'r.cep',
                'r.resolvido',
                'r.data_cadastro AS criado_em'
            )
            ->from('reports', 'r')
            ->leftJoin('r', 'type_problem', 'tp', 'r.id_tipo_problema = tp.id')
            ->leftJoin('r', 'users',        'u',  'r.id_users = u.id')
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
        $params = $request->getQueryParams();

        $resolvidoFiltro = $params['resolvido'] ?? '';
        $tipoFiltro       = $params['id_tipo_problema'] ?? '';
        $cepFiltro        = trim((string) ($params['cep'] ?? ''));

        $query = \App\Database\DB::select(
                'r.id',
                'r.cep',
                'r.endereco AS address',
                'r.latitude',
                'r.longitude',
                'r.descricao',
                'r.resolvido',
                'r.data_cadastro AS criado_em',
                'tp.descricao AS tipo_descricao',
                'u.nome AS customer_nome'
            )
            ->from('reports', 'r')
            ->leftJoin('r', 'type_problem', 'tp', 'r.id_tipo_problema = tp.id')
            ->leftJoin('r', 'users',        'u',  'r.id_users = u.id');

        // Filtro de status: só aceita '0' ou '1' explicitamente, então o literal
        // é seguro de embutir direto (sem passar por bind de parâmetro), evitando
        // o mesmo problema de tipagem booleana do Postgres visto em outras telas.
        if ($resolvidoFiltro === '0' || $resolvidoFiltro === '1') {
            $boolLiteral = $resolvidoFiltro === '1' ? 'true' : 'false';
            $query->andWhere("r.resolvido = {$boolLiteral}");
        }

        if ($tipoFiltro !== '' && is_numeric($tipoFiltro)) {
            $tipoInt = (int) $tipoFiltro;
            $query->andWhere("r.id_tipo_problema = {$tipoInt}");
        }

        if ($cepFiltro !== '') {
            $query->setParameter('cep', '%' . $cepFiltro . '%');
            $query->andWhere('r.cep ILIKE :cep');
        }

        $reports = $query
            ->orderBy('r.data_cadastro', 'DESC')
            ->fetchAllAssociative();

        // Todos os tipos (não só os ativos) para o filtro conseguir mostrar
        // corretamente reportes antigos de tipos que já foram desativados
        $tiposProblema = \App\Database\DB::select('id', 'descricao')
            ->from('type_problem')
            ->orderBy('descricao', 'ASC')
            ->fetchAllAssociative();

        return $this->getTwig()
            ->render($response, $this->setView('list-report'), [
                'titulo'        => '',
                'reports'       => $reports,
                'tiposProblema' => $tiposProblema,
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
        $totalReports = (int) \App\Database\DB::select('COUNT(*)')
            ->from('reports')
            ->fetchOne();

        $totalResolvidos = (int) \App\Database\DB::select('COUNT(*)')
            ->from('reports')
            ->where('resolvido = true')
            ->fetchOne();

        $totalPendentes = $totalReports - $totalResolvidos;

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
            return $this->json($response, ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }
    public function getabcranking($request, $response)
    {
        try {
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