<?php

declare(strict_types=1);

namespace App\Controller;


final class Admin extends Base
{
    public function admin($request, $response)
    {
        return $this->getTwig()
            ->render($response, $this->setView('admin'), [
                'titulo' => '',
            ])
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }
}