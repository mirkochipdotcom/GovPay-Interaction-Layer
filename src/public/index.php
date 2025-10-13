<?php
// Serve file statici direttamente se esistono nella cartella public (img, css, js, ecc.)
if (php_sapi_name() === 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

[$app, $twig] = require __DIR__ . '/../bootstrap/app.php';
$routes = require __DIR__ . '/../routes/web.php';
$routes($app, $twig);

$app->run();
