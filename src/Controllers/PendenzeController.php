<?php
namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class PendenzeController
{
    public function index(Request $request, Response $response, $args)
    {
        // Minimal controller: prepare debug string and render via Twig later in route
        $debug = '';
        $api_class = 'GovPay\\Pendenze\\Api\\PendenzeApi';
        if (class_exists($api_class)) {
            $debug .= "Classe trovata: $api_class\n";
        } else {
            $debug .= "Classe API non trovata.\n";
        }
        // Store debug in request attribute for the route middleware to use
        return $request->withAttribute('debug', $debug);
    }
}

