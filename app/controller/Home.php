<?php

declare(strict_types=1);

namespace app\controller;


final class Home extends Base
{
    public function home($request, $response)
    {
        return $this->getTwig()
            ->render($response, $this->setView('home'), [
                'titulo' => 'Bem-Vindo ao Yehi Or',
            ])
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }
}