<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware implements MiddlewareInterface
{
    /** @var string[] */
    private array $publicPaths;

    public function __construct(array $publicPaths = [])
    {
        $this->publicPaths = $publicPaths;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $path = $request->getUri()->getPath();
        foreach ($this->publicPaths as $pub) {
            if ($this->matchPath($path, $pub)) {
                return $handler->handle($request);
            }
        }

        if (isset($_SESSION['user'])) {
            return $handler->handle($request);
        }

        // Redirect a /login
        $resp = new SlimResponse(302);
        return $resp->withHeader('Location', '/login');
    }

    private function matchPath(string $path, string $pattern): bool
    {
        // simple wildcard suffix support like /assets/* or /debug/*
        if (str_ends_with($pattern, '*')) {
            $prefix = rtrim(substr($pattern, 0, -1), '/');
            return str_starts_with($path, $prefix);
        }
        return rtrim($path, '/') === rtrim($pattern, '/');
    }
}
