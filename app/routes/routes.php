<?php

declare(strict_types=1);

$app->get('/', App\Controller\Home::class . ':home');
$app->get('/home', App\Controller\Home::class . ':home');
$app->get('/login', App\Controller\Login::class . ':login');
