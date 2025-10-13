<?php

declare(strict_types=1);

namespace App\Controllers;

use GuzzleHttp\Client;
use GovPay\Backoffice\Api\ReportisticaApi;
use GovPay\Backoffice\Configuration as BackofficeConfiguration;
use GovPay\Backoffice\Model\RaggruppamentoStatistica;
use GovPay\Backoffice\ObjectSerializer as BackofficeSerializer;
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

        $errors = [];
        $statsJson = null;
        $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
        if (class_exists(ReportisticaApi::class)) {
            if (!empty($backofficeUrl)) {
                try {
                    $config = new BackofficeConfiguration();
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

                    $httpClient = new Client($guzzleOptions);
                    $api = new ReportisticaApi($httpClient, $config);

                    $gruppi = [RaggruppamentoStatistica::DOMINIO];
                    $idDominioEnv = getenv('ID_DOMINIO');
                    if ($idDominioEnv !== false && $idDominioEnv !== '') {
                        $result = $api->findQuadratureRiscossioni($gruppi, 1, 10, null, null, trim($idDominioEnv));
                    } else {
                        $result = $api->findQuadratureRiscossioni($gruppi, 1, 10);
                    }

                    $data = BackofficeSerializer::sanitizeForSerialization($result);
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

        if (isset($_SESSION['user'])) {
            $this->twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
        }

        return $this->twig->render($response, 'home.html.twig', [
            'debug' => nl2br(htmlspecialchars($debug)),
            'stats_json' => $statsJson,
            'errors' => $errors,
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
