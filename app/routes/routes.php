<?php

declare(strict_types=1);

$app->get('/', app\controller\Home::class . ':home');
$app->get('/home', app\controller\Home::class . ':home');
