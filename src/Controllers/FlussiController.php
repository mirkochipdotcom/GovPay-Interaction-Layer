<?php
/**
 * SPDX-License-Identifier: EUPL-1.2
 * License: European Union Public Licence v1.2 (EUPL-1.2)
 */
declare(strict_types=1);

namespace App\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

/**
 * Controller per la ricerca dei flussi di rendicontazione.
 * Utilizza l'endpoint Backoffice /flussiRendicontazione per ottenere l'elenco paginato.
 */
class FlussiController
{
    public function __construct(private readonly Twig $twig) {}

    public function search(Request $request, Response $response): Response
    {
        $this->exposeCurrentUser();

        $params = (array)($request->getQueryParams() ?? []);
        $errors = [];
        $highlightId = isset($params['highlight']) ? (string)$params['highlight'] : null;
        $filters = [
            'q' => isset($params['q']) ? (string)$params['q'] : null,
            'pagina' => max(1, (int)($params['pagina'] ?? 1)),
            'risultatiPerPagina' => min(200, max(1, (int)($params['risultatiPerPagina'] ?? 25))),
            'ordinamento' => (string)($params['ordinamento'] ?? '-data'),
            'idDominio' => (string)($params['idDominio'] ?? (getenv('ID_DOMINIO') ?: '')),
            'idA2A' => (string)($params['idA2A'] ?? (getenv('ID_A2A') ?: '')),
            'idFlusso' => (string)($params['idFlusso'] ?? ''),
            'statoFlusso' => (string)($params['statoFlusso'] ?? ''),
            'dataDa' => (string)($params['dataDa'] ?? ''),
            'dataA' => (string)($params['dataA'] ?? ''),
            'iuv' => (string)($params['iuv'] ?? ''),
            'incassato' => (string)($params['incassato'] ?? ''),
            'escludiObsoleti' => (string)($params['escludiObsoleti'] ?? ''),
        ];
        $orderValue = $filters['ordinamento'];
        if (!in_array($orderValue, ['+data', '-data'], true)) {
            $orderValue = '-data';
        }
        $filters['ordinamento'] = $orderValue;
        $orderField = 'data';
        $orderDirection = $orderValue[0] === '-' ? 'desc' : 'asc';

        $results = null;
        $numPagine = null;
        $numRisultati = null;
        $queryMade = false;
        $prevUrl = null;
        $nextUrl = null;

        if (($filters['q'] ?? null) !== null) {
            $queryMade = true;
            $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
            if ($backofficeUrl === '') {
                $errors[] = 'Variabile GOVPAY_BACKOFFICE_URL non impostata';
            } else {
                try {
                    $http = $this->makeHttpClient();
                    $pagina = $filters['pagina'];
                    $rpp = $filters['risultatiPerPagina'];
                    $orderParam = $filters['ordinamento'] === '-data' ? null : $filters['ordinamento'];
                    $query = [
                        'pagina' => $pagina,
                        'risultatiPerPagina' => $rpp,
                        'ordinamento' => $orderParam,
                        'idDominio' => $filters['idDominio'] ?: null,
                        'idA2A' => $filters['idA2A'] ?: null,
                        'idFlusso' => $filters['idFlusso'] ?: null,
                        'dataDa' => $filters['dataDa'] ?: null,
                        'dataA' => $filters['dataA'] ?: null,
                        'statoFlussoRendicontazione' => $filters['statoFlusso'] ?: null,
                        'iuv' => $filters['iuv'] ?: null,
                        'incassato' => $filters['incassato'] ?: null,
                        'escludiObsoleti' => $filters['escludiObsoleti'] ?: null,
                        'metadatiPaginazione' => 'true',
                        'maxRisultati' => 'true',
                    ];
                    $query = array_filter($query, static fn($v) => $v !== null && $v !== '');

                    $username = getenv('GOVPAY_USER');
                    $password = getenv('GOVPAY_PASSWORD');

                    $options = [
                        'headers' => ['Accept' => 'application/json'],
                        'query' => $query,
                    ];
                    if ($username && $password) {
                        $options['auth'] = [$username, $password];
                    }

                    $url = rtrim($backofficeUrl, '/') . '/flussiRendicontazione';
                    if (getenv('APP_DEBUG') && $filters['q']) {
                        error_log('[FlussiController] GET ' . $url . '?' . http_build_query($query));
                    }

                    $resp = $http->request('GET', $url, $options);
                    $json = (string)$resp->getBody();
                    $dataArr = json_decode($json, true);
                    if (!is_array($dataArr)) {
                        throw new \RuntimeException('Parsing JSON fallito');
                    }

                    $extractInt = static function(array $src, array $paths): ?int {
                        foreach ($paths as $path) {
                            $cursor = $src;
                            foreach (explode('.', $path) as $segment) {
                                if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                                    continue 2;
                                }
                                $cursor = $cursor[$segment];
                            }
                            if ($cursor !== null && $cursor !== '') {
                                return (int)$cursor;
                            }
                        }
                        return null;
                    };

                    $numPagine = $extractInt($dataArr, ['numPagine', 'num_pagine', 'metadatiPaginazione.numPagine', 'metadatiPaginazione.num_pagine']);
                    $numRisultati = $extractInt($dataArr, ['numRisultati', 'num_risultati', 'metadatiPaginazione.numRisultati', 'metadatiPaginazione.num_risultati']);
                    if ($numPagine === null && $numRisultati !== null && $rpp > 0) {
                        $numPagine = (int)ceil($numRisultati / $rpp);
                    }

                    $results = $dataArr;

                    if (isset($results['risultati']) && is_array($results['risultati'])) {
                        $directionMul = $orderDirection === 'desc' ? -1 : 1;
                        $extractDate = static function (array $item): ?int {
                            foreach (['data', 'dataFlusso', 'data_flusso', 'dataRegolamento', 'data_regolamento'] as $key) {
                                if (isset($item[$key]) && $item[$key] !== '') {
                                    $ts = strtotime((string)$item[$key]);
                                    if ($ts !== false) {
                                        return $ts;
                                    }
                                }
                            }
                            return null;
                        };
                        usort($results['risultati'], static function ($left, $right) use ($directionMul, $extractDate) {
                            $a = is_array($left) ? $extractDate($left) : null;
                            $b = is_array($right) ? $extractDate($right) : null;
                            if ($a === $b) {
                                return 0;
                            }
                            if ($a === null) {
                                return 1;
                            }
                            if ($b === null) {
                                return -1;
                            }
                            return ($a <=> $b) * $directionMul;
                        });
                    }

                    $basePath = $request->getUri()->getPath();
                    $qsBase = $params;
                    $qsBase['q'] = '1';
                    $qsBase['ordinamento'] = $filters['ordinamento'];
                    $qsBase['pagina'] = $filters['pagina'];
                    $qsBase['risultatiPerPagina'] = $filters['risultatiPerPagina'];
                    unset($qsBase['highlight']);
                    $buildUrl = static fn(array $payload) => $basePath . '?' . http_build_query($payload, '', '&', PHP_QUERY_RFC3986);

                    if ($filters['pagina'] > 1) {
                        $prev = $qsBase;
                        $prev['pagina'] = $filters['pagina'] - 1;
                        $prevUrl = $buildUrl($prev);
                    }
                    if ($numPagine !== null && $filters['pagina'] < $numPagine) {
                        $next = $qsBase;
                        $next['pagina'] = $filters['pagina'] + 1;
                        $nextUrl = $buildUrl($next);
                    }
                } catch (ClientException $ce) {
                    $errors[] = 'Errore chiamata Flussi: ' . $ce->getMessage();
                    $detailBody = $ce->getResponse() ? (string)$ce->getResponse()->getBody() : '';
                    if ($detailBody !== '') {
                        $errors[] = 'Dettaglio API: ' . $detailBody;
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Errore chiamata Flussi: ' . $e->getMessage();
                }
            }
        }

        $qsClean = $params;
        unset($qsClean['highlight']);
        $qsCurrent = http_build_query($qsClean, '', '&', PHP_QUERY_RFC3986);
        $returnUrl = '/pagamenti/ricerca-flussi' . ($qsCurrent ? ('?' . $qsCurrent) : '');

        return $this->twig->render($response, 'pagamenti/ricerca_flussi.html.twig', [
            'filters' => $filters,
            'errors' => $errors,
            'results' => $results,
            'num_pagine' => $numPagine,
            'num_risultati' => $numRisultati,
            'query_made' => $queryMade,
            'prev_url' => $prevUrl,
            'next_url' => $nextUrl,
            'return_url' => $returnUrl,
            'highlight_id' => $highlightId,
            'order_field' => $orderField,
            'order_direction' => $orderDirection,
        ]);
    }

    public function detail(Request $request, Response $response, array $args): Response
    {
        $this->exposeCurrentUser();

        $errors = [];
        $idFlusso = isset($args['idFlusso']) ? (string)$args['idFlusso'] : '';
        if ($idFlusso === '') {
            $errors[] = "ID flusso mancante.";
        }

        $flow = null;
        if (!$errors) {
            $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
            if ($backofficeUrl === '') {
                $errors[] = 'Variabile GOVPAY_BACKOFFICE_URL non impostata';
            } else {
                try {
                    $http = $this->makeHttpClient();
                    $username = getenv('GOVPAY_USER');
                    $password = getenv('GOVPAY_PASSWORD');

                    $options = [
                        'headers' => ['Accept' => 'application/json'],
                    ];
                    if ($username && $password) {
                        $options['auth'] = [$username, $password];
                    }

                    $endpoint = sprintf('%s/flussiRendicontazione/%s', rtrim($backofficeUrl, '/'), rawurlencode($idFlusso));
                    if (getenv('APP_DEBUG')) {
                        error_log('[FlussiController] GET ' . $endpoint);
                    }

                    $resp = $http->request('GET', $endpoint, $options);
                    $status = $resp->getStatusCode();
                    if (!in_array($status, [200, 201], true)) {
                        throw new \RuntimeException('Risposta inattesa dal servizio: ' . $status);
                    }

                    $json = (string)$resp->getBody();
                    $dataArr = json_decode($json, true);
                    if (!is_array($dataArr)) {
                        throw new \RuntimeException('Parsing JSON fallito');
                    }

                    $flow = $dataArr;
                } catch (ClientException $ce) {
                    $errors[] = 'Errore recupero dettaglio flusso: ' . $ce->getMessage();
                    $detailBody = $ce->getResponse() ? (string)$ce->getResponse()->getBody() : '';
                    if ($detailBody !== '') {
                        $errors[] = 'Dettaglio API: ' . $detailBody;
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Errore recupero dettaglio flusso: ' . $e->getMessage();
                }
            }
        }

        $return = (string)($request->getQueryParams()['return'] ?? '/pagamenti/ricerca-flussi');
        if ($return === '' || !str_starts_with($return, '/')) {
            $return = '/pagamenti/ricerca-flussi';
        }

        return $this->twig->render($response, 'pagamenti/dettaglio_flusso.html.twig', [
            'errors' => $errors,
            'flusso' => $flow,
            'id_flusso' => $idFlusso,
            'return_url' => $return,
        ]);
    }

    private function exposeCurrentUser(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        if (isset($_SESSION['user'])) {
            $this->twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
        }
    }

    private function makeHttpClient(): Client
    {
        $guzzleOptions = [];
        $authMethod = getenv('AUTHENTICATION_GOVPAY');
        if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
            $cert = getenv('GOVPAY_TLS_CERT');
            $key = getenv('GOVPAY_TLS_KEY');
            $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
            if (!empty($cert) && !empty($key)) {
                $guzzleOptions['cert'] = $cert;
                $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
            }
        }
        return new Client($guzzleOptions);
    }
}
