<?php
/**
 * SPDX-License-Identifier: EUPL-1.2
 * License: European Union Public Licence v1.2 (EUPL-1.2)
 */

declare(strict_types=1);

use App\Auth\UserRepository;
use App\Controllers\ConfigurazioneController;
use App\Controllers\HomeController;
use App\Controllers\PendenzeController;
use App\Controllers\UsersController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;

return function (App $app, Twig $twig): void {
    // Basic route
    $app->get('/', function (Request $request, Response $response) use ($twig): Response {
        $controller = new HomeController($twig);
        return $controller->index($request, $response);
    });

    // Guida rapida
    $app->get('/guida', function(Request $request, Response $response) use ($twig): Response {
        $controller = new HomeController($twig);
        return $controller->guida($request, $response);
    });

    // Pendenze
    $app->any('/pendenze', function(Request $request, Response $response) use ($twig): Response {
        $controller = new PendenzeController($twig);
        return $controller->index($request, $response);
    });

    $app->get('/pendenze/ricerca', function(Request $request, Response $response) use ($twig): Response {
        $controller = new PendenzeController($twig);
        return $controller->search($request, $response);
    });

    $app->get('/pendenze/inserimento', function(Request $request, Response $response) use ($twig): Response {
        $controller = new PendenzeController($twig);
        return $controller->showInsert($request, $response);
    });

    // Anteprima/preview prima della creazione pendenza
    $app->post('/pendenze/preview', function(Request $request, Response $response) use ($twig): Response {
        $controller = new PendenzeController($twig);
        return $controller->preview($request, $response);
    });

    // Rateizzazione: mostra form per generare le rate della pendenza
    $app->post('/pendenze/rateizza', function(Request $request, Response $response) use ($twig): Response {
        $controller = new PendenzeController($twig);
        return $controller->showRateizzazione($request, $response);
    });

    $app->post('/pendenze/create-rateizzazione', function(Request $request, Response $response) use ($twig): Response {
        $controller = new PendenzeController($twig);
        return $controller->createRateizzazione($request, $response);
    });

    $app->get('/pendenze/inserimento-massivo', function(Request $request, Response $response) use ($twig): Response {
        $controller = new PendenzeController($twig);
        return $controller->showBulkInsert($request, $response);
    });

    $app->get('/pendenze/dettaglio/{idPendenza}', function(Request $request, Response $response, array $args) use ($twig): Response {
        $controller = new PendenzeController($twig);
        return $controller->showDetail($request, $response, $args);
    });

    // Preview/print multi-rate document stored in session after createRateizzazione
    $app->get('/pendenze/multirata/preview', function(Request $request, Response $response) use ($twig): Response {
        // only in session
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
        $doc = $_SESSION['multi_rate_document'] ?? null;
        if (!$doc) return $response->withStatus(404);
        return $twig->render($response, 'pendenze/multirata.html.twig', ['multi' => $doc]);
    });

    $app->get('/avvisi/{idDominio}/{numeroAvviso}', function(Request $request, Response $response, array $args) use ($twig): Response {
        $controller = new PendenzeController($twig);
        return $controller->downloadAvviso($request, $response, $args);
    });

    // Scarica il PDF che contiene uno o più avvisi raggruppati per documento
    // Support both GET (querystring) and POST (JSON) clients for requesting
    // the document that aggregates one or more avvisi. The frontend posts
    // JSON { numeriAvviso: [ ... ] } so we expose a POST route in addition
    // to the existing GET to keep compatibility with direct links.
    $app->get('/documenti/{idDominio}/{numeroDocumento}/avvisi', function(Request $request, Response $response, array $args) use ($twig): Response {
        $controller = new PendenzeController($twig);
        return $controller->downloadAvvisiDocumento($request, $response, $args);
    });
    $app->post('/documenti/{idDominio}/{numeroDocumento}/avvisi', function(Request $request, Response $response, array $args) use ($twig): Response {
        $controller = new PendenzeController($twig);
        return $controller->downloadAvvisiDocumento($request, $response, $args);
    });

    $app->get('/pendenze/rpp/{idDominio}/{iuv}/{ccp}/rt', function(Request $request, Response $response, array $args) use ($twig): Response {
        $controller = new PendenzeController($twig);
        return $controller->downloadRicevuta($request, $response, $args);
    });

    // Dominio - Logo proxy: scarica il logo del dominio dal Backoffice (o decodifica base64)
    $app->get('/domini/{idDominio}/logo', function(Request $request, Response $response, array $args) use ($twig): Response {
        $controller = new PendenzeController($twig);
        return $controller->downloadDominioLogo($request, $response, $args);
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
        $controller = new ConfigurazioneController($twig);
        return $controller->index($request, $response);
    });

    // Tipologie di pagamento esterne - crea
    $app->post('/configurazione/tipologie-esterne', function($request, $response) use ($twig) {
        $controller = new ConfigurazioneController($twig);
        return $controller->createExternalPaymentType($request, $response);
    });

    // Tipologie di pagamento esterne - elimina
    $app->post('/configurazione/tipologie-esterne/{id}/delete', function($request, $response, $args) use ($twig) {
        $controller = new ConfigurazioneController($twig);
        return $controller->deleteExternalPaymentType($request, $response, $args);
    });

    // Endpoint per override locale tipologie (solo superadmin)
    $app->post('/configurazione/tipologie/{idEntrata}/override', function($request, $response, $args) use ($twig) {
        $controller = new ConfigurazioneController($twig);
        return $controller->overrideTipologia($request, $response, $args);
    });

    // Endpoint per salvare l'URL esterna di una tipologia (solo superadmin)
    $app->post('/configurazione/tipologie/{idEntrata}/url', function($request, $response, $args) use ($twig) {
        $controller = new ConfigurazioneController($twig);
        return $controller->updateTipologiaUrl($request, $response, $args);
    });

    // Endpoint per attivare/disattivare la tipologia direttamente su GovPay (solo superadmin)
    $app->post('/configurazione/tipologie/{idEntrata}/govpay', function($request, $response, $args) use ($twig) {
        $controller = new ConfigurazioneController($twig);
        return $controller->updateTipologiaGovpay($request, $response, $args);
    });

    // Endpoint reset: cancella URL esterna e, se GovPay è attivo, riallinea lo stato locale a GovPay (override=null)

    $app->post('/configurazione/tipologie/{idEntrata}/reset', function($request, $response, $args) use ($twig) {
        $controller = new ConfigurazioneController($twig);
        return $controller->resetTipologia($request, $response, $args);
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
        if ($resOrReq instanceof Response) {
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
        if ($resOrReq instanceof Response) {
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
            // Set session user (include name fields for templates)
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'first_name' => $user['first_name'] ?? '',
                'last_name' => $user['last_name'] ?? '',
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

        // Simple session diagnostic (only in debug)
        $app->get('/_diag/session', function($request, $response) use ($twig) {
            $sess = session_status() === PHP_SESSION_ACTIVE ? ($_SESSION ?? []) : null;
            $payload = [
                'session_active' => session_status() === PHP_SESSION_ACTIVE,
                'session' => $sess,
                'current_user' => $sess['user'] ?? null,
            ];
            $response->getBody()->write('<pre>' . htmlspecialchars(print_r($payload, true)) . '</pre>');
            return $response;
        });

        // Debug helper: login as seeded superadmin (only in debug)
        $app->get('/_diag/login-as-admin', function($request, $response) use ($twig) {
            try {
                $repo = new UserRepository();
                $user = $repo->findByEmail('admin@example.com');
                if (!$user) {
                    $response->getBody()->write('Admin user not found');
                    return $response->withStatus(404);
                }
                // Ensure session active
                if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'first_name' => $user['first_name'] ?? '',
                    'last_name' => $user['last_name'] ?? '',
                ];
                $response->getBody()->write('<p>Logged in as admin. <a href="/">Go to home</a></p>');
                return $response;
            } catch (\Throwable $e) {
                $response->getBody()->write('Error: ' . htmlspecialchars($e->getMessage()));
                return $response->withStatus(500);
            }
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

    // Pagamenti - viste base per Ricerca Incassi e Ricerca Flussi (utenti autenticati)
    $app->get('/pagamenti/ricerca-incassi', function($request, $response) use ($twig) {
        if (isset($_SESSION['user'])) {
            $twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
        }
        return $twig->render($response, 'pagamenti/ricerca_incassi.html.twig');
    });

    $app->get('/pagamenti/ricerca-flussi', function($request, $response) use ($twig) {
        if (isset($_SESSION['user'])) {
            $twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
        }
        return $twig->render($response, 'pagamenti/ricerca_flussi.html.twig');
    });
};
