<?php

declare(strict_types=1);

$app->get('/', App\Controller\Home::class . ':home'); #->add(App\Middleware\Middleware::web());
$app->get('/home', App\Controller\Home::class . ':home'); #->add(App\Middleware\Middleware::web());
$app->get('/login', App\Controller\Login::class . ':login'); #->add(App\Middleware\Middleware::web());
$app->get('/report', App\Controller\Report::class . ':home'); #->add(App\Middleware\Middleware::web());


$app->group('/authentication', function (Slim\Routing\RouteCollectorProxy $group) {
    $group->get('/logout', App\Controller\Login::class . ':logout');
    $group->post('/authenticate', App\Controller\Login::class . ':authenticate');
    $group->post('/preregister', App\Controller\Login::class . ':preRegister');
});
$app->group('/admin', function (Slim\Routing\RouteCollectorProxy $group) {
    $group->get('/listreport', App\Controller\Admin::class . ':listreport');
    $group->get('/stock', App\Controller\Admin::class . ':stock');
    $group->get('/relatorio', App\Controller\Admin::class . ':relatorio');
    $group->get('/gestao', App\Controller\Admin::class . ':gestao');
});
