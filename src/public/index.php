<?php
// Serve file statici direttamente se esistono nella cartella public (img, css, js, ecc.)
if (php_sapi_name() === 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}
// Canonical composer autoload path used in the image build
// Public dir is /var/www/html/public, vendor lives in /var/www/html/vendor
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpInternalServerErrorException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Controllers\PendenzeController;

$app = AppFactory::create();
$app->setBasePath('');

// Carichiamo sia i template specifici dell'app (src/templates) sia quelli condivisi a livello root (templates/)
$twig = Twig::create([
    __DIR__ . '/../templates',          // /var/www/html/src/templates
    '/var/www/html/templates',          // /var/www/html/templates (root, path assoluto)
], ['cache' => false]);
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
    $debug = '';
    try {
        $controller = new PendenzeController();
        $req = $controller->index($request, $response, []);
        $debug = $req->getAttribute('debug', '');
    } catch (\Throwable $e) {
        $debug .= "Errore controller: " . $e->getMessage();
    }
    try {
        return $twig->render($response, 'pendenze.html.twig', ['debug' => nl2br(htmlspecialchars($debug))]);
    } catch (\Throwable $e) {
        // Fallback minimale in caso di template non trovato
        $response->getBody()->write('<h1>Pendenze</h1><pre>' . htmlspecialchars($debug . "\n" . $e->getMessage()) . '</pre>');
        return $response->withStatus(500);
    }
});

$appDebugRaw = getenv('APP_DEBUG');
$displayErrorDetails = $appDebugRaw !== false && in_array(strtolower($appDebugRaw), ['1','true','yes','on'], true);
if ($displayErrorDetails) {
    $app->get('/_test-error', function() {
        throw new \RuntimeException('Errore di test intenzionale');
    });
}

// Error handling personalizzato per 404
$displayErrorDetails = $appDebugRaw !== false && in_array(strtolower($appDebugRaw), ['1','true','yes','on'], true);
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, true, true);
$errorMiddleware->setErrorHandler(HttpNotFoundException::class, function (
    Request $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) use ($twig) : Response {
    $response = new \Slim\Psr7\Response();
    return $twig->render($response->withStatus(404), 'errors/404.html.twig', [
        'path' => $request->getUri()->getPath()
    ]);
});

// Handler generico 500
$errorMiddleware->setDefaultErrorHandler(function (
    Request $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) use ($twig) : Response {
    // Log esteso per diagnosi (sempre) - evita leak in output se non in debug
    error_log('[APP ERROR] ' . $exception::class . ': ' . $exception->getMessage() . " in " . $exception->getFile() . ':' . $exception->getLine());
    foreach ($exception->getTrace() as $i => $t) {
        if ($i > 15) { break; }
        $fn = ($t['class'] ?? '') . ($t['type'] ?? '') . ($t['function'] ?? '');
        error_log("  #$i $fn (" . ($t['file'] ?? '?') . ':' . ($t['line'] ?? '?') . ")");
    }
    $status = $exception instanceof HttpInternalServerErrorException ? 500 : 500;
    $response = new \Slim\Psr7\Response();
    return $twig->render($response->withStatus($status), 'errors/500.html.twig', [
        'exception' => $exception,
        'displayErrorDetails' => $displayErrorDetails,
    ]);
});

// Rotta diagnostica per verificare i template caricabili (solo in debug)
if ($displayErrorDetails) {
    $app->get('/_diag/templates', function($request, $response) use ($twig) {
        $candidates = [
            'base.html.twig',
            'pendenze.html.twig',
            'home.html.twig',
            'partials/header.html.twig',
            'partials/footer.html.twig',
            'errors/404.html.twig',
            'errors/500.html.twig'
        ];
        $loader = $twig->getLoader();
        $rows = [];
        foreach ($candidates as $tpl) {
            $ok = 'missing';
            try { if ($loader->exists($tpl)) { $ok = 'ok'; } } catch (\Throwable $e) { $ok = 'error:' . $e->getMessage(); }
            $rows[] = [$tpl, $ok];
        }
        $body = "<h1>Template Diagnostic</h1><table border='1' cellpadding='4'><tr><th>Template</th><th>Status</th></tr>";
        foreach ($rows as [$t,$s]) { $body .= "<tr><td>" . htmlspecialchars($t) . "</td><td>" . htmlspecialchars($s) . "</td></tr>"; }
        $body .= '</table>';
        $response->getBody()->write($body);
        return $response;
    });
}

$app->run();
