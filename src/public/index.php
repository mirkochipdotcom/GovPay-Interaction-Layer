<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app->setBasePath('');

// Basic route
$app->get('/', function ($request, $response) {
    $response->getBody()->write('<h1>GovPay Interaction Layer - Slim</h1>');
    return $response;
});

// Pendenze route
$app->any('/pendenze', function ($request, $response) {
    // Simple forward to existing template: use the old test logic or a controller later
    // For now include the legacy php page
    ob_start();
    include __DIR__ . '/../test.php';
    $html = ob_get_clean();
    $response->getBody()->write($html);
    return $response;
});

$app->run();
