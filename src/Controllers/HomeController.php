<?php
/**
 * SPDX-License-Identifier: EUPL-1.2
 * License: European Union Public Licence v1.2 (EUPL-1.2)
 */

declare(strict_types=1);

namespace App\Controllers;

use GuzzleHttp\Client;
use GovPay\Pendenze\Api\PendenzeApi;
use GovPay\Pendenze\Configuration as PendenzeConfiguration;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class HomeController
{
    public function __construct(private readonly Twig $twig)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        $debug = '';
        $apiClass = PendenzeApi::class;
        if (class_exists($apiClass)) {
            $debug .= "Classe trovata: {$apiClass}\n";
            try {
                $client = new Client();
                new PendenzeApi($client, new PendenzeConfiguration());
                $debug .= "Istanza API creata con successo.\n";
            } catch (\Throwable $e) {
                $debug .= 'Errore: ' . $e->getMessage() . "\n";
            }
        } else {
            $debug .= "Classe API non trovata.\n";
        }

        if (isset($_SESSION['user'])) {
            $this->twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
        }

        return $this->twig->render($response, 'home.html.twig', [
            'debug' => nl2br(htmlspecialchars($debug)),
        ]);
    }

    public function guida(Request $request, Response $response): Response
    {
        if (isset($_SESSION['user'])) {
            $this->twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
        }

        return $this->twig->render($response, 'guida.html.twig');
    }

}
