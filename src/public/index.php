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
use App\Controllers\UsersController;
use App\Middleware\SessionMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\FlashMiddleware;
use App\Middleware\CurrentPathMiddleware;
use App\Auth\UserRepository;

$app = AppFactory::create();
$app->setBasePath('');

// Carichiamo sia i template specifici dell'app (src/templates) sia quelli condivisi a livello root (templates/)
$twig = Twig::create([
    __DIR__ . '/../templates',          // /var/www/html/src/templates
    '/var/www/html/templates',          // /var/www/html/templates (root, path assoluto)
], ['cache' => false]);
$entityName = getenv('APP_ENTITY_NAME') ?: 'Comune di Esempio';
$entitySuffix = getenv('APP_ENTITY_SUFFIX') ?: 'Servizi ai cittadini';
$entityGovernment = getenv('APP_ENTITY_GOVERNMENT') ?: '';
$customLogoFs = '/var/www/html/public/img/stemma_ente.png';
$hasCustomLogo = file_exists($customLogoFs);
$appLogo = $hasCustomLogo
    ? ['type' => 'img', 'src' => '/img/stemma_ente.png']
    : ['type' => 'sprite', 'src' => '/assets/bootstrap-italia/svg/sprites.svg#it-pa'];
$customFaviconIco = '/var/www/html/public/img/favicon.ico';
$customFaviconPng = '/var/www/html/public/img/favicon.png';
$appFavicon = file_exists($customFaviconIco)
    ? ['href' => '/img/favicon.ico', 'type' => 'image/x-icon']
    : (file_exists($customFaviconPng)
        ? ['href' => '/img/favicon.png', 'type' => 'image/png']
        : ['href' => '/img/favicon_default.png', 'type' => 'image/png']);
$twig->getEnvironment()->addGlobal('app_entity', [
    'name' => $entityName,
    'suffix' => $entitySuffix,
    'full' => $entityName . ' - ' . $entitySuffix,
    'government' => $entityGovernment,
]);
$twig->getEnvironment()->addGlobal('app_logo', $appLogo);
$twig->getEnvironment()->addGlobal('app_favicon', $appFavicon);
$app->add(TwigMiddleware::create($app, $twig));
// Public paths: login, logout, assets, debug
$publicPaths = ['/login', '/logout', '/assets/*', '/debug/*'];
// LIFO execution: add Auth, then Flash, then Session so execution is Session -> Flash -> Auth
$app->add(new AuthMiddleware($publicPaths));
$app->add(new FlashMiddleware($twig));
$app->add(new SessionMiddleware());
// Espone current_path a Twig su ogni richiesta
$app->add(new CurrentPathMiddleware($twig));

// Inject user into Twig globals if logged
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
    $twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
}

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

    // Backoffice stats: /quadrature/riscossioni -> stampa JSON grezzo
    $errors = [];
    $statsJson = null;
    $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
    if (class_exists('\\GovPay\\Backoffice\\Api\\ReportisticaApi')) {
        if (!empty($backofficeUrl)) {
            try {
                // Configurazione client Backoffice
                $config = new \GovPay\Backoffice\Configuration();
                $config->setHost(rtrim($backofficeUrl, '/'));

                $username = getenv('GOVPAY_USER');
                $password = getenv('GOVPAY_PASSWORD');
                if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                    $config->setUsername($username);
                    $config->setPassword($password);
                }

                // Opzioni Guzzle per mTLS se richiesto
                $guzzleOptions = [];
                $authMethod = getenv('AUTHENTICATION_GOVPAY');
                if ($authMethod !== false && strtolower($authMethod) === 'sslheader') {
                    $cert = getenv('GOVPAY_TLS_CERT');
                    $key = getenv('GOVPAY_TLS_KEY');
                    $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
                    if (!empty($cert) && !empty($key)) {
                        $guzzleOptions['cert'] = $cert;
                        $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
                    } else {
                        $errors[] = 'mTLS abilitato ma GOVPAY_TLS_CERT/GOVPAY_TLS_KEY non impostati';
                    }
                }

                $httpClient = new \GuzzleHttp\Client($guzzleOptions);
                $api = new \GovPay\Backoffice\Api\ReportisticaApi($httpClient, $config);

                // Almeno un gruppo è richiesto dall'API: usiamo DOMINIO di default
                $gruppi = [\GovPay\Backoffice\Model\RaggruppamentoStatistica::DOMINIO];
                // Se configurato in .env, filtra per ID_DOMINIO
                $idDominioEnv = getenv('ID_DOMINIO');
                if ($idDominioEnv !== false && $idDominioEnv !== '') {
                    $idDominio = trim($idDominioEnv);
                    // Parametri: gruppi, pagina, risultati_per_pagina, data_da, data_a, id_dominio
                    $result = $api->findQuadratureRiscossioni($gruppi, 1, 10, null, null, $idDominio);
                } else {
                    $result = $api->findQuadratureRiscossioni($gruppi, 1, 10);
                }

                // Serializza il modello in JSON leggibile
                $data = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($result);
                $statsJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            } catch (\Throwable $e) {
                $errors[] = 'Errore chiamata Backoffice: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'Variabile GOVPAY_BACKOFFICE_URL non impostata';
        }
    } else {
        $errors[] = 'Client Backoffice non disponibile (namespace GovPay\\Backoffice)';
    }

    // ensure user available in this request too
    if (isset($_SESSION['user'])) {
        $twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
    }
    return $twig->render($response, 'home.html.twig', [
        'debug' => nl2br(htmlspecialchars($debug)),
        'stats_json' => $statsJson,
        'errors' => $errors,
    ]);
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
        if (isset($_SESSION['user'])) {
            $twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
        }
        return $twig->render($response, 'pendenze.html.twig', ['debug' => nl2br(htmlspecialchars($debug))]);
    } catch (\Throwable $e) {
        // Fallback minimale in caso di template non trovato
        $response->getBody()->write('<h1>Pendenze</h1><pre>' . htmlspecialchars($debug . "\n" . $e->getMessage()) . '</pre>');
        return $response->withStatus(500);
    }
});

// Pendenze - sottosezioni placeholder
$app->get('/pendenze/ricerca', function($request, $response) use ($twig) {
    $controller = new PendenzeController();
    $req = $controller->search($request, $response);
    $filters = $req->getAttribute('filters', []);
    $errors = $req->getAttribute('errors', []);
    $allowedStates = $req->getAttribute('allowed_states', []);
    $results = $req->getAttribute('results');
    $numPagine = $req->getAttribute('num_pagine');
    $numRisultati = $req->getAttribute('num_risultati');
    $queryMade = $req->getAttribute('query_made');
    $prevUrl = $req->getAttribute('prev_url');
    $nextUrl = $req->getAttribute('next_url');
    if (isset($_SESSION['user'])) {
        $twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
    }
    return $twig->render($response, 'pendenze/ricerca.html.twig', [
        'filters' => $filters,
        'errors' => $errors,
        'allowed_states' => $allowedStates,
        'results' => $results,
        'num_pagine' => $numPagine,
        'num_risultati' => $numRisultati,
        'query_made' => $queryMade,
        'prev_url' => $prevUrl,
        'next_url' => $nextUrl,
    ]);
});

$app->get('/pendenze/inserimento', function($request, $response) use ($twig) {
    if (isset($_SESSION['user'])) {
        $twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
    }
    return $twig->render($response, 'pendenze/inserimento.html.twig');
});

$app->get('/pendenze/inserimento-massivo', function($request, $response) use ($twig) {
    if (isset($_SESSION['user'])) {
        $twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
    }
    return $twig->render($response, 'pendenze/inserimento_massivo.html.twig');
});

// Profile
$app->get('/profile', function($request, $response) use ($twig) {
    if (isset($_SESSION['user'])) {
        $twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
    }
    return $twig->render($response, 'profile.html.twig');
});

// Configurazione (solo superadmin): mostra il risultato di Backoffice /configurazioni
$app->get('/configurazione', function($request, $response) use ($twig) {
    // Controllo ruolo: solo superadmin
    $u = $_SESSION['user'] ?? null;
    if (!$u || ($u['role'] ?? '') !== 'superadmin') {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Accesso negato: permessi insufficienti'];
        return $response->withHeader('Location', '/')->withStatus(302);
    }
    if (isset($_SESSION['user'])) {
        $twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
    }

    $errors = [];
    $cfgJson = null;
    $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
    if (class_exists('\\GovPay\\Backoffice\\Api\\ConfigurazioniApi')) {
        if (!empty($backofficeUrl)) {
            try {
                $config = new \GovPay\Backoffice\Configuration();
                $config->setHost(rtrim($backofficeUrl, '/'));

                $username = getenv('GOVPAY_USER');
                $password = getenv('GOVPAY_PASSWORD');
                if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                    $config->setUsername($username);
                    $config->setPassword($password);
                }

                $guzzleOptions = [];
                $authMethod = getenv('AUTHENTICATION_GOVPAY');
                if ($authMethod !== false && strtolower($authMethod) === 'sslheader') {
                    $cert = getenv('GOVPAY_TLS_CERT');
                    $key = getenv('GOVPAY_TLS_KEY');
                    $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
                    if (!empty($cert) && !empty($key)) {
                        $guzzleOptions['cert'] = $cert;
                        $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
                    } else {
                        $errors[] = 'mTLS abilitato ma GOVPAY_TLS_CERT/GOVPAY_TLS_KEY non impostati';
                    }
                }

                $httpClient = new \GuzzleHttp\Client($guzzleOptions);
                $api = new \GovPay\Backoffice\Api\ConfigurazioniApi($httpClient, $config);
                $result = $api->getConfigurazioni();
                $data = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($result);
                $cfgJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            } catch (\Throwable $e) {
                $errors[] = 'Errore chiamata Backoffice: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'Variabile GOVPAY_BACKOFFICE_URL non impostata';
        }
    } else {
        $errors[] = 'Client Backoffice non disponibile (namespace GovPay\\Backoffice)';
    }

    return $twig->render($response, 'configurazione.html.twig', [
        'errors' => $errors,
        'cfg_json' => $cfgJson,
    ]);
});

// Users management (admin/superadmin)
$app->get('/users', function($request, $response) use ($twig) {
    $controller = new UsersController();
    $req = $controller->index($request, $response, []);
    $users = $req->getAttribute('users', []);
    if (isset($_SESSION['user'])) {
        $twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
    }
    return $twig->render($response, 'users/index.html.twig', ['users' => $users]);
});

$app->get('/users/new', function($request, $response) use ($twig) {
    if (isset($_SESSION['user'])) {
        $twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
    }
    return $twig->render($response, 'users/new.html.twig');
});

$app->post('/users/new', function($request, $response) use ($twig) {
    $controller = new UsersController();
    $resOrReq = $controller->create($request, $response, []);
    if ($resOrReq instanceof \Psr\Http\Message\ResponseInterface) {
        return $resOrReq; // redirect già pronto (flash impostato nel controller)
    }
    $error = $resOrReq->getAttribute('error');
    if ($error) {
        if (isset($_SESSION['user'])) {
            $twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
        }
        return $twig->render($response, 'users/new.html.twig', ['error' => $error]);
    }
    // fallback: torna alla lista
    return $response->withHeader('Location', '/users')->withStatus(302);
});

$app->get('/users/{id}/edit', function($request, $response, $args) use ($twig) {
    $controller = new UsersController();
    $req = $controller->edit($request, $response, $args);
    $editUser = $req->getAttribute('edit_user');
    if (isset($_SESSION['user'])) {
        $twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
    }
    return $twig->render($response, 'users/edit.html.twig', ['edit_user' => $editUser]);
});

$app->post('/users/{id}/edit', function($request, $response, $args) use ($twig) {
    $controller = new UsersController();
    $resOrReq = $controller->update($request, $response, $args);
    if ($resOrReq instanceof \Psr\Http\Message\ResponseInterface) {
        return $resOrReq; // redirect già pronto (flash impostato nel controller)
    }
    $error = $resOrReq->getAttribute('error');
    if ($error) {
        $editUser = $resOrReq->getAttribute('edit_user');
        if (isset($_SESSION['user'])) {
            $twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
        }
        return $twig->render($response, 'users/edit.html.twig', ['error' => $error, 'edit_user' => $editUser]);
    }
    // fallback
    return $response->withHeader('Location', '/users')->withStatus(302);
});

$app->post('/users/{id}/delete', function($request, $response, $args) {
    $controller = new UsersController();
    return $controller->delete($request, $response, $args);
});
// Login routes
$app->get('/login', function($request, $response) use ($twig) {
    if (isset($_SESSION['user'])) {
        return $response->withHeader('Location', '/')->withStatus(302);
    }
    return $twig->render($response, 'login.html.twig', [
        'error' => null,
        'last_email' => ''
    ]);
});

$app->post('/login', function($request, $response) use ($twig) {
    $data = (array)($request->getParsedBody() ?? []);
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $repo = new UserRepository();
    $user = $email !== '' ? $repo->findByEmail($email) : null;
    if ($user && $repo->verifyPassword($password, $user['password_hash'])) {
        // Set session user (minimal info)
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Accesso effettuato'];
        return $response->withHeader('Location', '/')->withStatus(302);
    }
    return $twig->render($response, 'login.html.twig', [
        'error' => 'Credenziali non valide',
        'last_email' => $email,
    ]);
});

$app->get('/logout', function($request, $response) {
    // Mantieni la sessione per mostrare il flash dopo il redirect
    $_SESSION['flash'][] = ['type' => 'info', 'text' => 'Sei stato disconnesso'];
    unset($_SESSION['user']);
    // Rigenera l'ID di sessione per sicurezza
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    return $response->withHeader('Location', '/login')->withStatus(302);
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
