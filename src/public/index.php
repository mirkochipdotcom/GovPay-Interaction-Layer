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
use App\Database\EntrateRepository;
use App\Database\ExternalPaymentTypeRepository;

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
// Alias stati pendenza (globali per tutto l'app)
$pendenzaStates = [
    'NON_ESEGUITA' => ['label' => 'Da pagare', 'color' => 'secondary'],
    'TENTATIVO_DI_PAGAMENTO' => ['label' => 'Tentativo di pagamento in corso', 'color' => 'warning'],
    'ESEGUITA' => ['label' => 'Pagato', 'color' => 'success'],
    'RENDICONTATA' => ['label' => 'Rendicontato', 'color' => 'primary'],
    'ANNULLATA' => ['label' => 'Annullato', 'color' => 'light'],
    'SCADUTA' => ['label' => 'Scaduto', 'color' => 'danger'],
    'ESEGUITA_PARZIALE' => ['label' => 'Eseguito parzialmente', 'color' => 'warning'],
    'ANOMALA' => ['label' => 'Anomalia', 'color' => 'danger'],
    'ERRORE' => ['label' => 'Errore', 'color' => 'danger'],
    'INCASSATA' => ['label' => 'Incassato', 'color' => 'primary'],
];
$twig->getEnvironment()->addGlobal('pendenza_states', $pendenzaStates);
$app->add(TwigMiddleware::create($app, $twig));
// Public paths: login, logout, assets, debug, guida
$publicPaths = ['/login', '/logout', '/assets/*', '/debug/*', '/guida'];
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

// Guida rapida
$app->get('/guida', function($request, $response) use ($twig) {
    if (isset($_SESSION['user'])) {
        $twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
    }
    return $twig->render($response, 'guida.html.twig');
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
    // ID pendenza da evidenziare (ritorno dal dettaglio)
    $highlightId = $request->getQueryParams()['highlight'] ?? null;
    if (isset($_SESSION['user'])) {
        $twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
    }
    // Costruisce l'URL di ritorno alla ricerca preservando la query corrente
    $qs = $request->getUri()->getQuery();
    $returnUrl = '/pendenze/ricerca' . ($qs ? ('?' . $qs) : '');
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
        'return_url' => $returnUrl,
        'highlight_id' => $highlightId,
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

// Dettaglio pendenza (API: /pendenze/{idA2A}/{idPendenza})
$app->get('/pendenze/dettaglio/{idPendenza}', function($request, $response, $args) use ($twig) {
    if (isset($_SESSION['user'])) {
        $twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
    }
    $idPendenza = $args['idPendenza'] ?? '';
    $q = $request->getQueryParams();
    $ret = $q['return'] ?? '/pendenze/ricerca';
    // Whitelisting: consenti solo ritorni verso /pendenze/ricerca
    if (strpos($ret, '/pendenze/ricerca') !== 0) {
        $ret = '/pendenze/ricerca';
    }

    $error = null;
    $pendenza = null;

    $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
    $idA2A = getenv('ID_A2A') ?: '';
    if ($idPendenza === '') {
        $error = 'ID pendenza non specificato';
    } elseif (empty($backofficeUrl)) {
        $error = 'Variabile GOVPAY_BACKOFFICE_URL non impostata';
    } elseif ($idA2A === '') {
        $error = 'Variabile ID_A2A non impostata nel file .env';
    } else {
        try {
            // Config client (basic/mTLS come altrove)
            $username = getenv('GOVPAY_USER');
            $password = getenv('GOVPAY_PASSWORD');
            $guzzleOptions = [
                'headers' => ['Accept' => 'application/json']
            ];
            $authMethod = getenv('AUTHENTICATION_GOVPAY');
            if ($authMethod !== false && strtolower($authMethod) === 'sslheader') {
                $cert = getenv('GOVPAY_TLS_CERT');
                $key = getenv('GOVPAY_TLS_KEY');
                $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
                if (!empty($cert) && !empty($key)) {
                    $guzzleOptions['cert'] = $cert;
                    $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
                } else {
                    $error = 'mTLS abilitato ma GOVPAY_TLS_CERT/GOVPAY_TLS_KEY non impostati';
                }
            }
            if (!$error && $username !== false && $password !== false && $username !== '' && $password !== '') {
                $guzzleOptions['auth'] = [$username, $password];
            }

            if (!$error) {
                $http = new \GuzzleHttp\Client($guzzleOptions);
                $url = rtrim($backofficeUrl, '/') . '/pendenze/' . rawurlencode($idA2A) . '/' . rawurlencode($idPendenza);
                $resp = $http->request('GET', $url);
                $json = (string)$resp->getBody();
                $data = json_decode($json, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('Parsing JSON fallito: ' . json_last_error_msg());
                }
                $pendenza = $data;
            }
        } catch (\GuzzleHttp\Exception\ClientException $ce) {
            $code = $ce->getResponse() ? $ce->getResponse()->getStatusCode() : 0;
            if ($code === 404) {
                $error = 'Pendenza non trovata (404)';
            } else {
                $error = 'Errore client nella chiamata pendenza: ' . $ce->getMessage();
            }
        } catch (\Throwable $e) {
            $error = 'Errore chiamata pendenza: ' . $e->getMessage();
        }
    }

    return $twig->render($response, 'pendenze/dettaglio.html.twig', [
        'idPendenza' => $idPendenza,
        'return_url' => $ret,
        'pendenza' => $pendenza,
        'error' => $error,
        // For download avviso
        'id_dominio' => ($pendenza['idDominio'] ?? (getenv('ID_DOMINIO') ?: '')),
    ]);
});

// Download Avviso (PDF): /avvisi/{idDominio}/{numeroAvviso}
$app->get('/avvisi/{idDominio}/{numeroAvviso}', function($request, $response, $args) {
    $idDominio = $args['idDominio'] ?? '';
    $numeroAvviso = $args['numeroAvviso'] ?? '';
    if ($idDominio === '' || $numeroAvviso === '') {
        $response->getBody()->write('Parametri mancanti');
        return $response->withStatus(400);
    }

    $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
    if (empty($backofficeUrl)) {
        $response->getBody()->write('GOVPAY_BACKOFFICE_URL non impostata');
        return $response->withStatus(500);
    }

    try {
        $username = getenv('GOVPAY_USER');
        $password = getenv('GOVPAY_PASSWORD');
        $guzzleOptions = [
            'headers' => [
                'Accept' => 'application/pdf'
            ]
        ];
        $authMethod = getenv('AUTHENTICATION_GOVPAY');
        if ($authMethod !== false && strtolower($authMethod) === 'sslheader') {
            $cert = getenv('GOVPAY_TLS_CERT');
            $key = getenv('GOVPAY_TLS_KEY');
            $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
            if (!empty($cert) && !empty($key)) {
                $guzzleOptions['cert'] = $cert;
                $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
            } else {
                $response->getBody()->write('mTLS abilitato ma GOVPAY_TLS_CERT/GOVPAY_TLS_KEY non impostati');
                return $response->withStatus(500);
            }
        }
        if ($username !== false && $password !== false && $username !== '' && $password !== '') {
            $guzzleOptions['auth'] = [$username, $password];
        }

        $http = new \GuzzleHttp\Client($guzzleOptions);
        $url = rtrim($backofficeUrl, '/') . '/avvisi/' . rawurlencode($idDominio) . '/' . rawurlencode($numeroAvviso);
        $resp = $http->request('GET', $url);
        $contentType = $resp->getHeaderLine('Content-Type');
        $pdf = (string)$resp->getBody();
        $filename = 'avviso-' . $idDominio . '-' . $numeroAvviso . '.pdf';
        $response = $response
            ->withHeader('Content-Type', $contentType ?: 'application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Cache-Control', 'no-store');
        $response->getBody()->write($pdf);
        return $response;
    } catch (\GuzzleHttp\Exception\ClientException $ce) {
        $code = $ce->getResponse() ? $ce->getResponse()->getStatusCode() : 0;
        $msg = $code === 404 ? 'Avviso non trovato' : ('Errore client avviso: ' . $ce->getMessage());
        $response->getBody()->write($msg);
        return $response->withStatus($code ?: 500);
    } catch (\Throwable $e) {
        $response->getBody()->write('Errore scaricamento avviso: ' . $e->getMessage());
        return $response->withStatus(500);
    }
});

// Download Ricevuta Telematica (RT) via API Pendenze: /pendenze/rpp/{idDominio}/{iuv}/{ccp}/rt
$app->get('/pendenze/rpp/{idDominio}/{iuv}/{ccp}/rt', function($request, $response, $args) {
    $idDominio = $args['idDominio'] ?? '';
    $iuv = $args['iuv'] ?? '';
    $ccp = $args['ccp'] ?? '';
    if ($idDominio === '' || $iuv === '' || $ccp === '') {
        $response->getBody()->write('Parametri mancanti');
        return $response->withStatus(400);
    }

    // Usa l'API Pendenze per recuperare la RT del tentativo RPP
    $pendenzeUrl = getenv('GOVPAY_PENDENZE_URL') ?: '';
    if (empty($pendenzeUrl)) {
        $response->getBody()->write('GOVPAY_PENDENZE_URL non impostata');
        return $response->withStatus(500);
    }

    try {
        $username = getenv('GOVPAY_USER');
        $password = getenv('GOVPAY_PASSWORD');
        $guzzleOptions = [
            'headers' => [
                'Accept' => 'application/pdf'
            ]
        ];
        $authMethod = getenv('AUTHENTICATION_GOVPAY');
        if ($authMethod !== false && strtolower($authMethod) === 'sslheader') {
            $cert = getenv('GOVPAY_TLS_CERT');
            $key = getenv('GOVPAY_TLS_KEY');
            $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
            if (!empty($cert) && !empty($key)) {
                $guzzleOptions['cert'] = $cert;
                $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
            } else {
                $response->getBody()->write('mTLS abilitato ma GOVPAY_TLS_CERT/GOVPAY_TLS_KEY non impostati');
                return $response->withStatus(500);
            }
        }
        if ($username !== false && $password !== false && $username !== '' && $password !== '') {
            $guzzleOptions['auth'] = [$username, $password];
        }

        $http = new \GuzzleHttp\Client($guzzleOptions);
        // Endpoint Pendenze: /rpp/{idDominio}/{iuv}/{ccp}/rt
        $url = rtrim($pendenzeUrl, '/') . '/rpp/'
            . rawurlencode($idDominio) . '/' . rawurlencode($iuv) . '/' . rawurlencode($ccp) . '/rt';

        // (debug headers rimossi)
        $resp = $http->request('GET', $url);
        $contentType = $resp->getHeaderLine('Content-Type');
        $pdf = (string)$resp->getBody();
        $filename = 'rt-' . $iuv . '-' . $ccp . '.pdf';
        $response = $response
            ->withHeader('Content-Type', $contentType ?: 'application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Cache-Control', 'no-store');
        $response->getBody()->write($pdf);
        return $response;
    } catch (\GuzzleHttp\Exception\ClientException $ce) {
        $code = $ce->getResponse() ? $ce->getResponse()->getStatusCode() : 0;
        $msg = $code === 404 ? 'Ricevuta non trovata' : ('Errore client ricevuta: ' . $ce->getMessage());
        $response->getBody()->write($msg);
        return $response->withStatus($code ?: 500);
    } catch (\Throwable $e) {
        $response->getBody()->write('Errore scaricamento ricevuta: ' . $e->getMessage());
        return $response->withStatus(500);
    }
});

// Dominio - Logo proxy: scarica il logo del dominio dal Backoffice (o decodifica base64)
$app->get('/domini/{idDominio}/logo', function($request, $response, $args) {
    $idDominio = $args['idDominio'] ?? '';
    if ($idDominio === '') {
        $response->getBody()->write('Parametri mancanti');
        return $response->withStatus(400);
    }

    $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
    if (empty($backofficeUrl)) {
        $response->getBody()->write('GOVPAY_BACKOFFICE_URL non impostata');
        return $response->withStatus(500);
    }

    $username = getenv('GOVPAY_USER');
    $password = getenv('GOVPAY_PASSWORD');
    $guzzleOptions = [
        'headers' => [
            'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*;q=0.8,*/*;q=0.5'
        ]
    ];
    $authMethod = getenv('AUTHENTICATION_GOVPAY');
    if ($authMethod !== false && strtolower($authMethod) === 'sslheader') {
        $cert = getenv('GOVPAY_TLS_CERT');
        $key = getenv('GOVPAY_TLS_KEY');
        $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
        if (!empty($cert) && !empty($key)) {
            $guzzleOptions['cert'] = $cert;
            $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
        } else {
            $response->getBody()->write('mTLS abilitato ma GOVPAY_TLS_CERT/GOVPAY_TLS_KEY non impostati');
            return $response->withStatus(500);
        }
    }
    if ($username !== false && $password !== false && $username !== '' && $password !== '') {
        $guzzleOptions['auth'] = [$username, $password];
    }

    // Primo tentativo: endpoint dedicato del backoffice /domini/{idDominio}/logo
    try {
        $http = new \GuzzleHttp\Client($guzzleOptions);
        $url = rtrim($backofficeUrl, '/') . '/domini/' . rawurlencode($idDominio) . '/logo';
        $resp = $http->request('GET', $url);
        $contentType = $resp->getHeaderLine('Content-Type') ?: 'image/png';
        $bytes = (string)$resp->getBody();
        $filename = 'logo-' . $idDominio;
        $response = $response
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->withHeader('Cache-Control', 'no-store');
        $response->getBody()->write($bytes);
        return $response;
    } catch (\GuzzleHttp\Exception\ClientException $ce) {
        // Se 404 o altro, prova fallback via getDominio e campo base64
    } catch (\Throwable $e) {
        // fallback sotto
    }

    // Fallback: recupera il dominio e prova a decodificare il campo logo base64 o data URL
    try {
        if (class_exists('GovPay\\Backoffice\\Api\\EntiCreditoriApi')) {
            $config = new \GovPay\Backoffice\Configuration();
            $config->setHost(rtrim($backofficeUrl, '/'));
            if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                $config->setUsername($username);
                $config->setPassword($password);
            }
            $httpClient = new \GuzzleHttp\Client($guzzleOptions);
            $entiApi = new \GovPay\Backoffice\Api\EntiCreditoriApi($httpClient, $config);
            $domRes = $entiApi->getDominio($idDominio);
            // Recupera il valore del logo in modo robusto (getter -> property -> array conversion)
            $logo = null;
            if (is_object($domRes)) {
                if (method_exists($domRes, 'getLogo')) {
                    $logo = $domRes->getLogo();
                } elseif (property_exists($domRes, 'logo')) {
                    $logo = $domRes->logo;
                }
            }
            if ($logo === null) {
                // fallback: serializza e decodifica ad array associativo
                $domData = json_decode(json_encode($domRes), true);
                if (is_array($domData)) {
                    $logo = $domData['logo'] ?? null;
                }
            }
            if (!$logo || !is_string($logo)) {
                $response->getBody()->write('Logo non disponibile');
                return $response->withStatus(404);
            }

            // data URL pattern: data:image/png;base64,XXXXX
            if (preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.*)$/', $logo, $m)) {
                $ct = $m[1];
                $b64 = $m[2];
                $bytes = base64_decode($b64, true);
                if ($bytes === false) {
                    $response->getBody()->write('Logo non valido');
                    return $response->withStatus(415);
                }
                $response = $response
                    ->withHeader('Content-Type', $ct)
                    ->withHeader('Content-Disposition', 'inline; filename="logo-' . $idDominio . '"')
                    ->withHeader('Cache-Control', 'no-store');
                $response->getBody()->write($bytes);
                return $response;
            }

            // base64 grezzo (senza data URL) -> assumiamo PNG
            $bytes = base64_decode($logo, true);
            if ($bytes !== false && $bytes !== '') {
                $response = $response
                    ->withHeader('Content-Type', 'image/png')
                    ->withHeader('Content-Disposition', 'inline; filename="logo-' . $idDominio . '.png"')
                    ->withHeader('Cache-Control', 'no-store');
                $response->getBody()->write($bytes);
                return $response;
            }

            // Se nel logo c'è un path tipo "/domini/{id}/logo", riprova via HTTP come ultima spiaggia
            if (is_string($logo) && str_starts_with($logo, '/')) {
                try {
                    $http = new \GuzzleHttp\Client($guzzleOptions);
                    $url = rtrim($backofficeUrl, '/') . $logo;
                    $resp = $http->request('GET', $url);
                    $contentType = $resp->getHeaderLine('Content-Type') ?: 'image/png';
                    $bytes = (string)$resp->getBody();
                    $response = $response
                        ->withHeader('Content-Type', $contentType)
                        ->withHeader('Content-Disposition', 'inline; filename="logo-' . $idDominio . '"')
                        ->withHeader('Cache-Control', 'no-store');
                    $response->getBody()->write($bytes);
                    return $response;
                } catch (\Throwable $e2) {
                    // prosegui a 404
                }
            }

            $response->getBody()->write('Logo non disponibile');
            return $response->withStatus(404);
        }
    } catch (\Throwable $e) {
        $response->getBody()->write('Errore recupero logo: ' . $e->getMessage());
        return $response->withStatus(500);
    }

    $response->getBody()->write('Logo non disponibile');
    return $response->withStatus(404);
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
    $cfgArr = null;
    $appsJson = null;
    $appsArr = null;
    $appJson = null;
    $appArr = null;
    $profiloJson = null;
    $entrateJson = null;
    $entrateArr = null;
    $entrateSource = null;
    $pagamentiProfiloJson = null;
    $infoJson = null;
    $infoArr = null;
    $dominioJson = null;
    $dominioArr = null;
    $tab = $request->getQueryParams()['tab'] ?? 'principali';
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
                // Configurazioni
                $api = new \GovPay\Backoffice\Api\ConfigurazioniApi($httpClient, $config);
                $result = $api->getConfigurazioni();
                $data = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($result);
                $cfgJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                $cfgArr = $data;

                // Applicazioni (lista) e dettaglio applicazione per idA2A (per vista Tipologie)
                try {
                    $appApi = new \GovPay\Backoffice\Api\ApplicazioniApi($httpClient, $config);
                    $apps = $appApi->findApplicazioni(1, 100, '+idA2A', null, null, null, null, true, true);
                    $appsData = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($apps);
                    $appsArr = is_array($appsData)
                        ? $appsData
                        : (json_decode(json_encode($appsData, JSON_UNESCAPED_SLASHES), true) ?: []);

                    $idA2A = getenv('ID_A2A') ?: '';

                    $appsJson = json_encode($appsArr, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                    // Dettaglio applicazione da ID_A2A
                    if ($idA2A !== '') {
                        try {
                            $appDet = $appApi->getApplicazione($idA2A);
                            $appDetData = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($appDet);
                            $appJson = json_encode($appDetData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                            $appArr = json_decode($appJson, true);
                            if (!is_array($appArr)) {
                                $appArr = is_array($appDetData) ? $appDetData : [];
                            }
                        } catch (\Throwable $e) {
                            $errors[] = 'Errore lettura applicazione ' . $idA2A . ': ' . $e->getMessage();
                        }
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Errore lettura applicazioni: ' . $e->getMessage();
                }

                // Backoffice - Entrate (tipologie di entrata) + Sync DB locale
                try {
                    if (class_exists('GovPay\\Backoffice\\Api\\EntiCreditoriApi')) {
                        $entrApi = new \GovPay\Backoffice\Api\EntiCreditoriApi($httpClient, $config);
                        $idDominioEnv = getenv('ID_DOMINIO');
                        $entrateSource = '/entrate';
                        // Prova prima l'elenco per dominio, se configurato; altrimenti fallback all'elenco globale
                        if ($idDominioEnv !== false && $idDominioEnv !== '') {
                            $idDominio = trim($idDominioEnv);
                            // findEntrateDominio($id_dominio, $pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $descrizione, $metadati_paginazione, $max_risultati)
                            $entrRes = $entrApi->findEntrateDominio($idDominio, 1, 200, '+idEntrata', null, null, null, true, true);
                            $entrateSource = '/domini/' . $idDominio . '/entrate';
                        } else {
                            // findEntrate($pagina, $risultati_per_pagina, $ordinamento, $campi, $metadati_paginazione, $max_risultati)
                            $entrRes = $entrApi->findEntrate(1, 200, '+idEntrata', null, true, true);
                            $entrateSource = '/entrate';
                        }
                        $entrData = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($entrRes);
                        // Serializza e riconverte per ottenere un array associativo stabile
                        $entrateJson = json_encode($entrData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                        $entrateArr = json_decode($entrateJson, true);
                        if (!is_array($entrateArr)) { $entrateArr = []; }

                        // Sync su DB locale se ho ID_DOMINIO
                        if (!empty($idDominioEnv)) {
                            try {
                                $repoEntr = new EntrateRepository();
                                $rows = $entrateArr['risultati'] ?? [];
                                foreach ($rows as $row) {
                                    $repoEntr->upsertFromBackoffice($idDominio, $row);
                                }
                                // Leggi back dal DB per UI (mappe varie)
                                $entrateEff = $repoEntr->listByDominio($idDominio);
                                $boMap = [];
                                $ovrMap = [];
                                $urlMap = [];
                                foreach ($entrateEff as $r) {
                                    $idE = $r['id_entrata'];
                                    $boMap[$idE] = (int)$r['abilitato_backoffice'] === 1;
                                    $ovrMap[$idE] = isset($r['override_locale']) ? ((int)$r['override_locale'] === 1 ? 1 : 0) : null;
                                    $urlMap[$idE] = $r['external_url'] ?? null;
                                }
                                $entrateArr['_bo_map'] = $boMap;
                                $entrateArr['_override_map'] = $ovrMap;
                                $entrateArr['_exturl_map'] = $urlMap;
                            } catch (\Throwable $e) {
                                $errors[] = 'Sync DB entrate fallito: ' . $e->getMessage();
                            }
                        }
                    } else {
                        $errors[] = 'Client Backoffice EntiCreditori non disponibile';
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Errore lettura entrate: ' . $e->getMessage();
                }

                // Pendenze - Profilo (raw JSON per tab Tipologie)
                try {
                    if (class_exists('GovPay\\Pendenze\\Api\\ProfiloApi')) {
                        $pendHost = getenv('GOVPAY_PENDENZE_URL') ?: '';
                        if (!empty($pendHost)) {
                            $pendCfg = new \GovPay\Pendenze\Configuration();
                            $pendCfg->setHost(rtrim($pendHost, '/'));
                            if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                                $pendCfg->setUsername($username);
                                $pendCfg->setPassword($password);
                            }
                            $pendClient = new \GuzzleHttp\Client($guzzleOptions);
                            $profApi = new \GovPay\Pendenze\Api\ProfiloApi($pendClient, $pendCfg);
                            $profRes = $profApi->getProfilo();
                            $profData = \GovPay\Pendenze\ObjectSerializer::sanitizeForSerialization($profRes);
                            $profiloJson = json_encode($profData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                        } else {
                            $errors[] = 'Variabile GOVPAY_PENDENZE_URL non impostata';
                        }
                    } else {
                        $errors[] = 'Client Pendenze non disponibile (namespace GovPay\\Pendenze)';
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Errore lettura profilo Pendenze: ' . $e->getMessage();
                }

                // Pagamenti - Profilo (raw JSON per tab Tipologie)
                try {
                    $pagHost = getenv('GOVPAY_PAGAMENTI_URL') ?: '';
                    if (!empty($pagHost)) {
                        $headers = ['Accept' => 'application/json'];
                        if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                            $headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
                        }
                        $http = new \GuzzleHttp\Client($guzzleOptions);
                        $resp = $http->request('GET', rtrim($pagHost, '/') . '/profilo', [ 'headers' => $headers ]);
                        $pagamentiProfiloJson = (string)$resp->getBody();
                    } else {
                        $errors[] = 'Variabile GOVPAY_PAGAMENTI_URL non impostata';
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Errore lettura profilo Pagamenti: ' . $e->getMessage();
                }

                // Backoffice - Info (/info)
                try {
                    if (class_exists('GovPay\\Backoffice\\Api\\InfoApi')) {
                        $infoApi = new \GovPay\Backoffice\Api\InfoApi($httpClient, $config);
                        $infoRes = $infoApi->getInfo();
                        $infoData = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($infoRes);
                        $infoJson = json_encode($infoData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                        $infoArr = $infoData;
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Errore lettura Info: ' . $e->getMessage();
                }

                // Backoffice - Dominio (/domini/{idDominio})
                try {
                    if (class_exists('GovPay\\Backoffice\\Api\\EntiCreditoriApi')) {
                        $idDom = getenv('ID_DOMINIO') ?: '';
                        if ($idDom !== '') {
                            $entiApi = new \GovPay\Backoffice\Api\EntiCreditoriApi($httpClient, $config);
                            $domRes = $entiApi->getDominio($idDom);
                            $domData = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($domRes);
                            $dominioJson = json_encode($domData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                            $dominioArr = $domData;
                        } else {
                            $errors[] = 'Variabile ID_DOMINIO non impostata per lettura dominio';
                        }
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Errore lettura dominio beneficiario: ' . $e->getMessage();
                }
            } catch (\Throwable $e) {
                $errors[] = 'Errore chiamata Backoffice: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'Variabile GOVPAY_BACKOFFICE_URL non impostata';
        }
    } else {
        $errors[] = 'Client Backoffice non disponibile (namespace GovPay\\Backoffice)';
    }

    $externalTypes = [];
    try {
        $extRepo = new ExternalPaymentTypeRepository();
        $externalTypes = $extRepo->listAll();
    } catch (\Throwable $e) {
        $errors[] = 'Errore lettura tipologie esterne: ' . $e->getMessage();
    }

    return $twig->render($response, 'configurazione.html.twig', [
        'errors' => $errors,
        'cfg_json' => $cfgJson,
        'cfg' => $cfgArr,
        'apps_json' => $appsJson,
        'apps' => $appsArr,
        'app_json' => $appJson ?? null,
        'app' => $appArr ?? null,
        'idA2A' => getenv('ID_A2A') ?: null,
        'profilo_json' => $profiloJson,
        'entrate_json' => $entrateJson,
        'entrate' => $entrateArr,
        'entrate_source' => $entrateSource ?? '/entrate',
        'pagamenti_profilo_json' => $pagamentiProfiloJson,
        'info' => $infoArr,
        'info_json' => $infoJson,
        'dominio' => $dominioArr,
        'dominio_json' => $dominioJson,
        'tipologie_esterne' => $externalTypes,
        'backoffice_base' => rtrim($backofficeUrl, '/'),
        'tab' => $tab,
    ]);
});

// Tipologie di pagamento esterne - crea
$app->post('/configurazione/tipologie-esterne', function($request, $response) {
    $u = $_SESSION['user'] ?? null;
    if (!$u || ($u['role'] ?? '') !== 'superadmin') {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Accesso negato'];
        return $response->withHeader('Location', '/configurazione?tab=tipologie_esterne')->withStatus(302);
    }

    $data = (array)($request->getParsedBody() ?? []);
    $descrizione = trim((string)($data['descrizione'] ?? ''));
    $url = trim((string)($data['url'] ?? ''));

    if ($descrizione === '' || $url === '') {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Compila descrizione e URL'];
        return $response->withHeader('Location', '/configurazione?tab=tipologie_esterne')->withStatus(302);
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'URL non valido'];
        return $response->withHeader('Location', '/configurazione?tab=tipologie_esterne')->withStatus(302);
    }

    try {
        $repo = new ExternalPaymentTypeRepository();
        $repo->create($descrizione, $url);
        $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Tipologia esterna salvata'];
    } catch (\Throwable $e) {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore salvataggio tipologia esterna: ' . $e->getMessage()];
    }

    return $response->withHeader('Location', '/configurazione?tab=tipologie_esterne')->withStatus(302);
});

// Tipologie di pagamento esterne - elimina
$app->post('/configurazione/tipologie-esterne/{id}/delete', function($request, $response, $args) {
    $u = $_SESSION['user'] ?? null;
    if (!$u || ($u['role'] ?? '') !== 'superadmin') {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Accesso negato'];
        return $response->withHeader('Location', '/configurazione?tab=tipologie_esterne')->withStatus(302);
    }

    $id = isset($args['id']) ? (int)$args['id'] : 0;
    if ($id <= 0) {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'ID tipologia non valido'];
        return $response->withHeader('Location', '/configurazione?tab=tipologie_esterne')->withStatus(302);
    }

    try {
        $repo = new ExternalPaymentTypeRepository();
        $repo->delete($id);
        $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Tipologia esterna rimossa'];
    } catch (\Throwable $e) {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore eliminazione tipologia esterna: ' . $e->getMessage()];
    }

    return $response->withHeader('Location', '/configurazione?tab=tipologie_esterne')->withStatus(302);
});

// Endpoint per override locale tipologie (solo superadmin)
$app->post('/configurazione/tipologie/{idEntrata}/override', function($request, $response, $args) use ($twig) {
    $u = $_SESSION['user'] ?? null;
    if (!$u || ($u['role'] ?? '') !== 'superadmin') {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Accesso negato'];
        return $response->withHeader('Location', '/configurazione?tab=tipologie')->withStatus(302);
    }
    $idEntrata = $args['idEntrata'] ?? '';
    $idDominio = getenv('ID_DOMINIO') ?: '';
    if ($idEntrata === '' || $idDominio === '') {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Parametri mancanti'];
        return $response->withHeader('Location', '/configurazione?tab=tipologie')->withStatus(302);
    }
    $data = (array)($request->getParsedBody() ?? []);
    // Valori attesi: enable=1/0 oppure action=reset
    $override = null;
    if (isset($data['action']) && $data['action'] === 'reset') {
        $override = null; // rimuovi override
    } elseif (isset($data['enable'])) {
        $override = (string)$data['enable'] === '1';
    }
    try {
        $repo = new EntrateRepository();
        $repo->setOverride($idDominio, $idEntrata, $override);
        $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Impostazione salvata'];
    } catch (\Throwable $e) {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore salvataggio: ' . $e->getMessage()];
    }
    return $response->withHeader('Location', '/configurazione?tab=tipologie')->withStatus(302);
});

// Endpoint per salvare l'URL esterna di una tipologia (solo superadmin)
$app->post('/configurazione/tipologie/{idEntrata}/url', function($request, $response, $args) use ($twig) {
    $u = $_SESSION['user'] ?? null;
    if (!$u || ($u['role'] ?? '') !== 'superadmin') {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Accesso negato'];
        return $response->withHeader('Location', '/configurazione?tab=tipologie')->withStatus(302);
    }
    $idEntrata = $args['idEntrata'] ?? '';
    $idDominio = getenv('ID_DOMINIO') ?: '';
    if ($idEntrata === '' || $idDominio === '') {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Parametri mancanti'];
        return $response->withHeader('Location', '/configurazione?tab=tipologie')->withStatus(302);
    }
    $data = (array)($request->getParsedBody() ?? []);
    $url = trim((string)($data['external_url'] ?? ''));
    if ($url === '') { $url = null; }
    try {
        $repo = new EntrateRepository();
        $repo->setExternalUrl($idDominio, $idEntrata, $url);
        $_SESSION['flash'][] = ['type' => 'success', 'text' => 'URL esterna salvata'];
    } catch (\Throwable $e) {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore salvataggio URL: ' . $e->getMessage()];
    }
    return $response->withHeader('Location', '/configurazione?tab=tipologie')->withStatus(302);
});

// Endpoint per attivare/disattivare la tipologia direttamente su GovPay (solo superadmin)
$app->post('/configurazione/tipologie/{idEntrata}/govpay', function($request, $response, $args) use ($twig) {
    $u = $_SESSION['user'] ?? null;
    if (!$u || ($u['role'] ?? '') !== 'superadmin') {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Accesso negato'];
        return $response->withHeader('Location', '/configurazione?tab=tipologie')->withStatus(302);
    }
    $idEntrata = $args['idEntrata'] ?? '';
    $idDominio = getenv('ID_DOMINIO') ?: '';
    if ($idEntrata === '' || $idDominio === '') {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Parametri mancanti'];
        return $response->withHeader('Location', '/configurazione?tab=tipologie')->withStatus(302);
    }
    $data = (array)($request->getParsedBody() ?? []);
    $enable = isset($data['enable']) ? ((string)$data['enable'] === '1') : null;
    if ($enable === null) {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Parametro enable mancante'];
        return $response->withHeader('Location', '/configurazione?tab=tipologie')->withStatus(302);
    }

    $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
    if (empty($backofficeUrl)) {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'GOVPAY_BACKOFFICE_URL non impostata'];
        return $response->withHeader('Location', '/configurazione?tab=tipologie')->withStatus(302);
    }

    try {
        // Configurazione autenticazione
        $username = getenv('GOVPAY_USER');
        $password = getenv('GOVPAY_PASSWORD');
        $guzzleOptions = ['headers' => ['Accept' => 'application/json']];
        $authMethod = getenv('AUTHENTICATION_GOVPAY');
        if ($authMethod !== false && strtolower($authMethod) === 'sslheader') {
            $cert = getenv('GOVPAY_TLS_CERT');
            $key = getenv('GOVPAY_TLS_KEY');
            $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
            if (!empty($cert) && !empty($key)) {
                $guzzleOptions['cert'] = $cert;
                $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
            } else {
                $_SESSION['flash'][] = ['type' => 'error', 'text' => 'mTLS abilitato ma certificati non impostati'];
                return $response->withHeader('Location', '/configurazione?tab=tipologie')->withStatus(302);
            }
        }
        if ($username !== false && $password !== false && $username !== '' && $password !== '') {
            // I client generatori usano basicAuth
        }

        // Usa client Backoffice generato
        if (!class_exists('GovPay\\Backoffice\\Api\\EntiCreditoriApi')) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Client Backoffice non disponibile'];
            return $response->withHeader('Location', '/configurazione?tab=tipologie')->withStatus(302);
        }
        $config = new \GovPay\Backoffice\Configuration();
        $config->setHost(rtrim($backofficeUrl, '/'));
        if ($username !== false && $password !== false && $username !== '' && $password !== '') {
            $config->setUsername($username);
            $config->setPassword($password);
        }
        $httpClient = new \GuzzleHttp\Client($guzzleOptions);
        $entiApi = new \GovPay\Backoffice\Api\EntiCreditoriApi($httpClient, $config);

        // Per aggiornare abilitato su una entrata dominio, si usa addEntrataDominio(put) con EntrataPost
        // È necessario fornire almeno l'IBAN di accredito richiesto dal modello
        // Recuperiamo i dati correnti della entrata dominio per leggere l'IBAN
        $curr = $entiApi->getEntrataDominio($idDominio, $idEntrata);
        // Estrai valori in modo robusto (getter -> property -> array associativo)
        $ibanAccredito = null;
        $codiceCont = null;
        if (is_object($curr)) {
            if (method_exists($curr, 'getIbanAccredito')) { $ibanAccredito = $curr->getIbanAccredito(); }
            if (method_exists($curr, 'getCodiceContabilita')) { $codiceCont = $curr->getCodiceContabilita(); }
            if ($ibanAccredito === null || $codiceCont === null) {
                // Fallback: serializza e decodifica come array associativo
                $currData = json_decode(json_encode($curr), true);
                if (is_array($currData)) {
                    if ($ibanAccredito === null) { $ibanAccredito = $currData['ibanAccredito'] ?? null; }
                    if ($codiceCont === null) { $codiceCont = $currData['codiceContabilita'] ?? null; }
                }
            }
        } else {
            // Se non è oggetto, prova via sanitize + json
            $currData = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($curr);
            $currArr = json_decode(json_encode($currData), true);
            if (is_array($currArr)) {
                $ibanAccredito = $currArr['ibanAccredito'] ?? null;
                $codiceCont = $currArr['codiceContabilita'] ?? null;
            }
        }
        if (empty($ibanAccredito)) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'IBAN mancante sulla tipologia: impossibile aggiornare'];
            return $response->withHeader('Location', '/configurazione?tab=tipologie')->withStatus(302);
        }
        $body = new \GovPay\Backoffice\Model\EntrataPost([
            'iban_accredito' => $ibanAccredito,
            // manteniamo abilitato secondo richiesta
            'abilitato' => $enable,
        ]);
        // Se disponibile, proviamo a propagare il codice contabilità esistente
        if (!empty($codiceCont)) {
            $body->setCodiceContabilita($codiceCont);
        }
        $entiApi->addEntrataDominio($idDominio, $idEntrata, $body);

        $_SESSION['flash'][] = ['type' => 'success', 'text' => ($enable ? 'Abilitata' : 'Disabilitata') . ' su GovPay'];
    } catch (\GuzzleHttp\Exception\ClientException $ce) {
        $code = $ce->getResponse() ? $ce->getResponse()->getStatusCode() : 0;
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore GovPay (' . $code . '): ' . $ce->getMessage()];
    } catch (\Throwable $e) {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore aggiornamento GovPay: ' . $e->getMessage()];
    }
    return $response->withHeader('Location', '/configurazione?tab=tipologie')->withStatus(302);
});

// Endpoint reset: cancella URL esterno e, se GovPay è attivo, riallinea lo stato locale a GovPay (override=null)
$app->post('/configurazione/tipologie/{idEntrata}/reset', function($request, $response, $args) use ($twig) {
    $u = $_SESSION['user'] ?? null;
    if (!$u || ($u['role'] ?? '') !== 'superadmin') {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Accesso negato'];
        return $response->withHeader('Location', '/configurazione?tab=tipologie')->withStatus(302);
    }
    $idEntrata = $args['idEntrata'] ?? '';
    $idDominio = getenv('ID_DOMINIO') ?: '';
    if ($idEntrata === '' || $idDominio === '') {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Parametri mancanti'];
        return $response->withHeader('Location', '/configurazione?tab=tipologie')->withStatus(302);
    }
    try {
        $repo = new EntrateRepository();
        $row = $repo->findOne($idDominio, $idEntrata);
        // Cancella sempre l'URL esterno
        $repo->setExternalUrl($idDominio, $idEntrata, null);
        // Se GovPay è attivo, allinea lo stato effettivo a GovPay rimuovendo override
        if ($row && ((int)$row['abilitato_backoffice'] === 1)) {
            $repo->setOverride($idDominio, $idEntrata, null);
        }
        $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Reset eseguito'];
    } catch (\Throwable $e) {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore reset: ' . $e->getMessage()];
    }
    return $response->withHeader('Location', '/configurazione?tab=tipologie')->withStatus(302);
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
// Espone un flag globale a Twig per consentire controlli condizionali lato template
$twig->getEnvironment()->addGlobal('app_debug', $displayErrorDetails);
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

    // Rotta di debug: elenca le ricevute disponibili per {idDominio}/{iuv}
    $app->get('/_diag/ricevute/{idDominio}/{iuv}', function($request, $response, $args) {
        $idDominio = $args['idDominio'] ?? '';
        $iuv = $args['iuv'] ?? '';
        if ($idDominio === '' || $iuv === '') {
            $response->getBody()->write('Parametri mancanti');
            return $response->withStatus(400);
        }

        $pagamentiUrl = getenv('GOVPAY_PAGAMENTI_URL') ?: '';
        if (empty($pagamentiUrl)) {
            $response->getBody()->write('GOVPAY_PAGAMENTI_URL non impostata');
            return $response->withStatus(500);
        }

        try {
            $username = getenv('GOVPAY_USER');
            $password = getenv('GOVPAY_PASSWORD');
            $guzzleOptions = [
                'headers' => ['Accept' => 'application/json']
            ];
            $authMethod = getenv('AUTHENTICATION_GOVPAY');
            if ($authMethod !== false && strtolower($authMethod) === 'sslheader') {
                $cert = getenv('GOVPAY_TLS_CERT');
                $key = getenv('GOVPAY_TLS_KEY');
                $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
                if (!empty($cert) && !empty($key)) {
                    $guzzleOptions['cert'] = $cert;
                    $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
                } else {
                    $response->getBody()->write('mTLS abilitato ma GOVPAY_TLS_CERT/GOVPAY_TLS_KEY non impostati');
                    return $response->withStatus(500);
                }
            }
            if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                $guzzleOptions['auth'] = [$username, $password];
            }

            $http = new \GuzzleHttp\Client($guzzleOptions);
            $url = rtrim($pagamentiUrl, '/') . '/ricevute/' . rawurlencode($idDominio) . '/' . rawurlencode($iuv);
            $resp = $http->request('GET', $url, ['query' => ['esito' => 'ESEGUITO']]);
            $json = (string)$resp->getBody();
            $response = $response->withHeader('Content-Type', 'application/json');
            $response->getBody()->write($json);
            return $response;
        } catch (\GuzzleHttp\Exception\ClientException $ce) {
            $code = $ce->getResponse() ? $ce->getResponse()->getStatusCode() : 0;
            $response->getBody()->write('Errore client diag ricevute: ' . $ce->getMessage());
            return $response->withStatus($code ?: 500);
        } catch (\Throwable $e) {
            $response->getBody()->write('Errore diag ricevute: ' . $e->getMessage());
            return $response->withStatus(500);
        }
    });
}

$app->run();
