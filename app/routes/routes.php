<?php

declare(strict_types=1);

$app->get('/', App\Controller\Home::class . ':home');
$app->get('/home', App\Controller\Home::class . ':home');
$app->get('/login', App\Controller\Login::class . ':login');
$app->get('/report', App\Controller\Report::class . ':home');


$app->group('/authentication', function (Slim\Routing\RouteCollectorProxy $group) {
    $group->get('/logout', App\Controller\Login::class . ':logout');
    $group->post('/authenticate', App\Controller\Login::class . ':authenticate');
    $group->post('/preregister', App\Controller\Login::class . ':preRegister');
});
