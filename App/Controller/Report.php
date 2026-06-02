<?php

declare(strict_types=1);

namespace App\Controller;

final class Report extends Base
{
    public function home($request, $response)
    {
        $tipos = \App\Database\DB::select('id', 'descricao')
            ->from('tipo_problema')
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
}