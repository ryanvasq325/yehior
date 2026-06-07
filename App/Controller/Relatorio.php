<?php

declare(strict_types=1);

namespace App\Controller;


final class Relatorio extends Base
{
    public function relatorio($request, $response)
    {
        return $this->getTwig()
            ->render($response, $this->setView('relatorio'), [
                'titulo' => '',
            ])
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }
}