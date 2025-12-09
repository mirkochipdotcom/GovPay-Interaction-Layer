<?php
/**
 * SPDX-License-Identifier: EUPL-1.2
 * License: European Union Public Licence v1.2 (EUPL-1.2)
 */
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use App\Auth\UserRepository;

class CurrentPathMiddleware implements MiddlewareInterface
{
    private Twig $twig;

    public function __construct(Twig $twig)
    {
        $this->twig = $twig;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $path = $request->getUri()->getPath();
        // Espone il percorso corrente come variabile globale Twig
        // Enrich current_user per request (session is started by SessionMiddleware)
        // Assicura che la sessione sia disponibile anche se l'ordine dei middleware cambia
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
            $sessionUser = $_SESSION['user'];
            try {
                if (empty($sessionUser['first_name']) || empty($sessionUser['last_name'])) {
                    $repo = new UserRepository();
                    $dbUser = $repo->findById((int)($sessionUser['id'] ?? 0));
                    if ($dbUser) {
                        $sessionUser['first_name'] = $dbUser['first_name'] ?? '';
                        $sessionUser['last_name'] = $dbUser['last_name'] ?? '';
                        $_SESSION['user'] = $sessionUser;
                    }
                }
            } catch (\Throwable $e) {
                // ignore and fallback to session data
            }
            $this->twig->getEnvironment()->addGlobal('current_user', $sessionUser);
        }
        $this->twig->getEnvironment()->addGlobal('current_path', $path);
        return $handler->handle($request);
    }
}
