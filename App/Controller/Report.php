<?php

declare(strict_types=1);

namespace App\Controller;

final class Report extends Base
{
    public function home($request, $response)
    {
        return $this->getTwig()
            ->render($response, $this->setView('report'), [
                'titulo' => '',
                'tipos'  => [],
            ])
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }
}
