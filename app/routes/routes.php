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
    $group->get('/gestao', App\Controller\Admin::class . ':gestao');
    $group->get('/listreport', App\Controller\Admin::class . ':listreport');
    $group->get('/users', App\Controller\Admin::class . ':users');
    $group->get('/listusers', App\Controller\Admin::class . ':listusers');
    $group->get('/products', App\Controller\Admin::class . ':products');
    $group->get('/relatorio', App\Controller\Admin::class . ':relatorio');
    $group->get('/listsupplier', App\Controller\Admin::class . ':listsupplier');

    $group->group('/status', function (Slim\Routing\RouteCollectorProxy $group) {
        $group->get('/getsalesdata', App\Controller\Admin::class . ':getsalesdata');
        $group->get('/getabcranking', App\Controller\Admin::class . ':getabcranking');
    });

});
$app->group('/produto', function (Slim\Routing\RouteCollectorProxy $group) {
    $group->post('/insert', App\Controller\Product::class . ':insert');
    $group->post('/update', App\Controller\Product::class . ':update');
});
$app->group('/fornecedor', function (Slim\Routing\RouteCollectorProxy $group) {
    $group->post('/insert', App\Controller\Supplier::class . ':insert');
    $group->post('/update', App\Controller\Supplier::class . ':update');
});

