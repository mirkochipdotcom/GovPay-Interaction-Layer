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

class FlashMiddleware implements MiddlewareInterface
{
    private Twig $twig;

    public function __construct(Twig $twig)
    {
        $this->twig = $twig;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $messages = $_SESSION['flash'] ?? [];
        if (!is_array($messages)) { $messages = []; }
        // Inject flash messages for this request
        $this->twig->getEnvironment()->addGlobal('flash', $messages);
        // Clear flash after injecting
        unset($_SESSION['flash']);
        return $handler->handle($request);
    }
}
