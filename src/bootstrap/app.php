<?php
/**
 * SPDX-License-Identifier: EUPL-1.2
 * License: European Union Public Licence v1.2 (EUPL-1.2)
 */

declare(strict_types=1);

use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use App\Middleware\SessionMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\FlashMiddleware;
use App\Middleware\CurrentPathMiddleware;
use App\Auth\UserRepository;
use App\Logger;

return (function (): array {
    $app = AppFactory::create();
    $app->setBasePath('');

    $templateCandidates = [
        __DIR__ . '/../templates',
        __DIR__ . '/../../templates',
        '/var/www/html/src/templates',
        '/var/www/html/templates',
    ];
    $templatePaths = [];
    foreach ($templateCandidates as $candidate) {
        $normalized = realpath($candidate) ?: $candidate;
        if (is_dir($normalized) && !in_array($normalized, $templatePaths, true)) {
            $templatePaths[] = $normalized;
        }
    }
    if ($templatePaths === []) {
        throw new \RuntimeException('Nessuna directory templates trovata nelle path configurate.');
    }

    $twig = Twig::create($templatePaths, ['cache' => false]);

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

    $pendenzaStates = [
        'NON_ESEGUITA' => ['label' => 'Da pagare', 'color' => 'secondary'],
        'NON_ESEGUITO' => ['label' => 'Da pagare', 'color' => 'secondary'],
        'TENTATIVO_DI_PAGAMENTO' => ['label' => 'Tentativo di pagamento in corso', 'color' => 'warning'],
        'ESEGUITA' => ['label' => 'Pagato', 'color' => 'success'],
        'ESEGUITO' => ['label' => 'Pagato', 'color' => 'success'],
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

    // Ensure storage logs directory exists and register logger global
    @mkdir(__DIR__ . '/../../storage/logs', 0775, true);
    $twig->getEnvironment()->addGlobal('app_logger', Logger::getInstance());

    // Register global error/exception handlers to also write to our application log
    set_error_handler(function (int $severity, string $message, string $file, int $line) {
        try {
            $context = [
                'file' => $file,
                'line' => $line,
                'severity' => $severity,
                'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
                'user_id' => isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null,
            ];
            Logger::getInstance()->error('PHP error: ' . $message, $context);
        } catch (\Throwable $_) {
            // swallow
        }
        // Let PHP internal handler run as well
        return false;
    });

    set_exception_handler(function (\Throwable $e) {
        try {
            $context = [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
                'user_id' => isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null,
            ];
            Logger::getInstance()->error('Uncaught exception: ' . $e->getMessage(), $context);
        } catch (\Throwable $_) {
            // swallow
        }
        // Fallback to default exception handling
        http_response_code(500);
        // Optionally rethrow or exit
        // echo "An error occurred";
    });

    register_shutdown_function(function () {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            try {
                $context = [
                    'file' => $err['file'] ?? null,
                    'line' => $err['line'] ?? null,
                    'type' => $err['type'] ?? null,
                    'message' => $err['message'] ?? null,
                    'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
                    'user_id' => isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null,
                ];
                Logger::getInstance()->error('Fatal shutdown error: ' . ($err['message'] ?? ''), $context);
            } catch (\Throwable $_) {
                // swallow
            }
        }
    });

    $publicPaths = ['/login', '/logout', '/assets/*', '/debug/*', '/guida'];
    $app->add(new AuthMiddleware($publicPaths));
    $app->add(new FlashMiddleware($twig));
    $app->add(new SessionMiddleware());
    $app->add(new CurrentPathMiddleware($twig));

    // current_user is populated per-request by CurrentPathMiddleware to ensure
    // session is started and DB enrichment (if needed) can run safely.

    return [$app, $twig];
})();
