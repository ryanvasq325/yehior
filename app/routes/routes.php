<?php

declare(strict_types=1);

$app->get('/', App\Controller\Home::class . ':home'); #->add(App\Middleware\Middleware::web());
$app->get('/home', App\Controller\Home::class . ':home'); #->add(App\Middleware\Middleware::web());
$app->get('/admin', App\Controller\admin::class . ':admin'); #->add(App\Middleware\Middleware::web());
$app->get('/login', App\Controller\Login::class . ':login'); #->add(App\Middleware\Middleware::web());
$app->get('/report', App\Controller\Report::class . ':home'); #->add(App\Middleware\Middleware::web());


$app->group('/authentication', function (Slim\Routing\RouteCollectorProxy $group) {
    $group->get('/logout', App\Controller\Login::class . ':logout');
    $group->post('/authenticate', App\Controller\Login::class . ':authenticate');
    $group->post('/preregister', App\Controller\Login::class . ':preRegister');
});
