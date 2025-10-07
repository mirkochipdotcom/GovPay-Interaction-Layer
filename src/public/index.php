<?php
// Canonical composer autoload path used in the image build
// Public dir is /var/www/html/public, vendor lives in /var/www/html/vendor
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use App\Controllers\PendenzeController;

$app = AppFactory::create();
$app->setBasePath('');

$twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

// Basic route
$app->get('/', function ($request, $response, $args) use ($twig) {
    // Prepare a small debug string (legacy diagnostics)
    $debug = "";
    $api_class = 'GovPay\Pendenze\Api\PendenzeApi';
    if (class_exists($api_class)) {
        $debug .= "Classe trovata: $api_class\n";
        try {
            $g = new GuzzleHttp\Client();
            $client = new $api_class($g, new GovPay\Pendenze\Configuration());
            $debug .= "Istanza API creata con successo.\n";
        } catch (\Throwable $e) {
            $debug .= "Errore: " . $e->getMessage() . "\n";
        }
    } else {
        $debug .= "Classe API non trovata.\n";
    }

    return $twig->render($response, 'home.html.twig', ['debug' => nl2br(htmlspecialchars($debug))]);
});

// Pendenze route
$app->any('/pendenze', function ($request, $response) use ($twig) {
    // Use controller to prepare data
    $controller = new PendenzeController();
    $req = $controller->index($request, $response, []);
    $debug = $req->getAttribute('debug', '');
    return $twig->render($response, 'pendenze.html.twig', ['debug' => nl2br(htmlspecialchars($debug))]);
});

$app->run();
