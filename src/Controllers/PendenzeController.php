<?php
/**
 * SPDX-License-Identifier: EUPL-1.2
 * License: European Union Public Licence v1.2 (EUPL-1.2)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Database\EntrateRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GovPay\Backoffice\Api\PendenzeApi as BackofficePendenzeApi;
use GovPay\Backoffice\Configuration as BackofficeConfiguration;
use GovPay\Backoffice\Model\RaggruppamentoStatistica;
use GovPay\Backoffice\Model\StatoPendenza;
use GovPay\Backoffice\ObjectSerializer as BackofficeSerializer;
use GovPay\Pendenze\Api\PendenzeApi;
use GovPay\Pendenze\Configuration as PendenzeConfiguration;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class PendenzeController
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
        if (class_exists(BackofficePendenzeApi::class)) {
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
                    if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
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
                    $api = new BackofficePendenzeApi($httpClient, $config);

                    $gruppi = [RaggruppamentoStatistica::DOMINIO];
                    $idDominioEnv = getenv('ID_DOMINIO');
                    if ($idDominioEnv !== false && $idDominioEnv !== '') {
                        $stats = $api->findQuadratureRiscossioni($gruppi, 1, 10, null, null, trim((string)$idDominioEnv));
                    } else {
                        $stats = $api->findQuadratureRiscossioni($gruppi, 1, 10);
                    }

                    $data = BackofficeSerializer::sanitizeForSerialization($stats);
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

        $this->exposeCurrentUser();

        return $this->twig->render($response, 'pendenze.html.twig', [
            'debug' => nl2br(htmlspecialchars($debug, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
            'stats_json' => $statsJson,
            'errors' => $errors,
        ]);
    }

    public function search(Request $request, Response $response): Response
    {
        
        $params = (array)($request->getQueryParams() ?? []);
        $errors = [];

        // Recupero tipologie pendenze abilitate
        $idDominio = $filters['idDominio'] ?? (getenv('ID_DOMINIO') ?: '');
        $tipologie = [];
        if ($idDominio) {
            $repo = new EntrateRepository();
            $tipologie = $repo->listAbilitateByDominio($idDominio);
        }

        $allowedStates = class_exists(StatoPendenza::class)
            ? StatoPendenza::getAllowableEnumValues()
            : [];

        $ordinamento = $request->getQueryParams()['ordinamento'] ?? '-dataCaricamento';


        $filters = [
            'q' => isset($params['q']) ? (string)$params['q'] : null,
            'pagina' => max(1, (int)($params['pagina'] ?? 1)),
            'risultatiPerPagina' => min(200, max(1, (int)($params['risultatiPerPagina'] ?? 25))),
            'ordinamento' => $ordinamento,
            'idDominio' => (string)($params['idDominio'] ?? (getenv('ID_DOMINIO') ?: '')),
            'idA2A' => (string)($params['idA2A'] ?? (getenv('ID_A2A') ?: '')),
            'idPendenza' => (string)($params['idPendenza'] ?? ''),
            'idDebitore' => (string)($params['idDebitore'] ?? ''),
            'stato' => (string)($params['stato'] ?? ''),
            'idPagamento' => (string)($params['idPagamento'] ?? ''),
            'dataDa' => (string)($params['dataDa'] ?? ''),
            'dataA' => (string)($params['dataA'] ?? ''),
            'direzione' => (string)($params['direzione'] ?? ''),
            'divisione' => (string)($params['divisione'] ?? ''),
            'iuv' => (string)($params['iuv'] ?? ''),
            'tipologiaPendenza' => (string)($params['tipologiaPendenza'] ?? ''),
        ];

        // Ricavo idEntrata dal filtro tipologiaPendenza
        $idEntrata = $filters['tipologiaPendenza'] ?? null;

        $validFields = ['dataCaricamento', 'dataValidita', 'dataScadenza', 'stato'];

        $normalizeOrder = static function (string $value, array $allowedFields): string {
            $value = trim($value);
            $direction = null;
            if ($value !== '') {
                $first = $value[0];
                if ($first === '+' || $first === '-') {
                    $direction = $first;
                    $value = substr($value, 1);
                }
            }
            $field = ltrim($value, '+-');
            if ($field === '' || !in_array($field, $allowedFields, true)) {
                $field = 'dataCaricamento';
            }
            if ($direction === null) {
                $direction = '+';
            }
            return $direction . $field;
        };

        $filters['ordinamento'] = $normalizeOrder((string)($filters['ordinamento'] ?? ''), $validFields);

        if ($filters['stato'] !== '' && !in_array($filters['stato'], $allowedStates, true)) {
            $errors[] = 'Valore "stato" non valido';
            $filters['stato'] = '';
        }

        $results = null;
        $numPagine = null;
        $numRisultati = null;
        $queryMade = false;
        $prevUrl = null;
        $nextUrl = null;

        if (($filters['q'] ?? null) !== null) {
            $queryMade = true;
            $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
            if (!class_exists(BackofficePendenzeApi::class)) {
                $errors[] = 'Client Backoffice non disponibile (namespace GovPay\\Backoffice)';
            } elseif (empty($backofficeUrl)) {
                $errors[] = 'Variabile GOVPAY_BACKOFFICE_URL non impostata';
            } else {
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
                    if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
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
                    $api = new BackofficePendenzeApi($httpClient, $config);

                    $pagina = $filters['pagina'];
                    $rpp = $filters['risultatiPerPagina'];
                    $ordinamento = $filters['ordinamento'];
                    $idDominio = $filters['idDominio'] !== '' ? $filters['idDominio'] : null;
                    $idA2A = $filters['idA2A'] !== '' ? $filters['idA2A'] : null;
                    $idDebitore = $filters['idDebitore'] !== '' ? $filters['idDebitore'] : null;
                    $stato = $filters['stato'] !== '' ? $filters['stato'] : null;
                    $idPagamento = $filters['idPagamento'] !== '' ? $filters['idPagamento'] : null;
                    $idPendenza = $filters['idPendenza'] !== '' ? $filters['idPendenza'] : null;
                    $dataDa = $filters['dataDa'] !== '' ? $filters['dataDa'] : null;
                    $dataA = $filters['dataA'] !== '' ? $filters['dataA'] : null;
                    $direzione = $filters['direzione'] !== '' ? $filters['direzione'] : null;
                    $divisione = $filters['divisione'] !== '' ? $filters['divisione'] : null;
                    $iuv = $filters['iuv'] !== '' ? $filters['iuv'] : null;
                    $idEntrata = $filters['tipologiaPendenza'] !== '' ? $filters['tipologiaPendenza'] : null;

    // ...existing code...

                    $mostraSpontanei = 'false';
                    $metadatiPaginazione = 'true';
                    $maxRisultati = 'true';

                    if ($idA2A === null || $idA2A === '') {
                        $errors[] = 'Parametro idA2A obbligatorio per la ricerca pendenze';
                    } else {
                        $url = rtrim($backofficeUrl, '/') . '/pendenze';
                        $query = [
                            'pagina' => $pagina,
                            'risultatiPerPagina' => $rpp,
                            'ordinamento' => $ordinamento,
                            'campi' => null,
                            'idDominio' => $idDominio,
                            'idA2A' => $idA2A,
                            'idDebitore' => $idDebitore,
                            'stato' => $stato,
                            'idPagamento' => $idPagamento,
                            'idPendenza' => $idPendenza,
                            'dataDa' => $dataDa,
                            'dataA' => $dataA,
                            'direzione' => $direzione,
                            'divisione' => $divisione,
                            'iuv' => $iuv,
                            'mostraSpontaneiNonPagati' => $mostraSpontanei,
                            'metadatiPaginazione' => $metadatiPaginazione,
                            'maxRisultati' => $maxRisultati,
                            'idTipoPendenza' => $idEntrata,
                        ];
                    
                        $query = array_filter($query, static fn($v) => $v !== null && $v !== '');

                        if (getenv('APP_DEBUG') && $filters['q']) {
                            error_log('[PendenzeController] GET ' . $url . '?' . http_build_query($query));
                        }

                        $requestOptions = [
                            'headers' => ['Accept' => 'application/json'],
                            'query' => $query,
                        ];
                        if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                            $requestOptions['auth'] = [$username, $password];
                        }

                        $resp = $httpClient->request('GET', $url, $requestOptions);
                        $json = (string)$resp->getBody();
                        $dataArr = json_decode($json, true);
                        if (json_last_error() !== JSON_ERROR_NONE || !is_array($dataArr)) {
                            throw new \RuntimeException('Parsing JSON fallito: ' . json_last_error_msg());
                        }

                        $extractInt = static function (array $source, array $paths): ?int {
                            foreach ($paths as $path) {
                                $cursor = $source;
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

                        $numPagine = $extractInt($dataArr, [
                            'numPagine',
                            'num_pagine',
                            'metaDatiPaginazione.numPagine',
                            'metaDatiPaginazione.num_pagine',
                            'metadatiPaginazione.numPagine',
                            'metadatiPaginazione.num_pagine',
                            'paginazione.numeroPagine',
                            'paginazione.numPagine',
                        ]);
                        $numRisultati = $extractInt($dataArr, [
                            'numRisultati',
                            'num_risultati',
                            'metaDatiPaginazione.numRisultati',
                            'metaDatiPaginazione.num_risultati',
                            'metadatiPaginazione.numRisultati',
                            'metadatiPaginazione.num_risultati',
                            'paginazione.numeroRisultati',
                            'paginazione.numRisultati',
                        ]);
                        if ($numPagine === null && $numRisultati !== null && $rpp > 0) {
                            $numPagine = (int)ceil($numRisultati / $rpp);
                        }
                        $results = $dataArr;

                        $basePath = $request->getUri()->getPath();
                        $qsBase = $params;
                        $qsBase['q'] = '1';
                        unset($qsBase['ordRecentiPrima']);
                        $qsBase['ordinamento'] = $filters['ordinamento'];
                        unset($qsBase['highlight']);
                        $qsBase['pagina'] = $filters['pagina'];
                        $qsBase['risultatiPerPagina'] = $filters['risultatiPerPagina'];

                        $buildUrl = static fn(array $paramSet): string => $basePath . '?' . http_build_query($paramSet, '', '&', PHP_QUERY_RFC3986);
                        $extractQueryString = static function (string $link): string {
                            $parts = parse_url($link);
                            if ($parts !== false && isset($parts['query'])) {
                                return (string)$parts['query'];
                            }
                            $pos = strpos($link, '?');
                            return $pos === false ? '' : substr($link, $pos + 1);
                        };

                        if ($filters['pagina'] > 1) {
                            $prevParams = $qsBase;
                            $prevParams['pagina'] = $filters['pagina'] - 1;
                            $prevUrl = $buildUrl($prevParams);
                        }
                        if ($numPagine !== null && $filters['pagina'] < $numPagine) {
                            $nextParams = $qsBase;
                            $nextParams['pagina'] = $filters['pagina'] + 1;
                            $nextUrl = $buildUrl($nextParams);
                        }

                        $nextLinkRaw = $results['prossimiRisultati'] ?? $results['prossimi_risultati'] ?? null;
                        if ($nextUrl === null && is_string($nextLinkRaw) && $nextLinkRaw !== '') {
                            $queryString = $extractQueryString($nextLinkRaw);
                            if ($queryString !== '') {
                                $linkParams = $qsBase;
                                parse_str($queryString, $linkQuery);
                                if (isset($linkQuery['pagina'])) {
                                    $linkParams['pagina'] = max(1, (int)$linkQuery['pagina']);
                                } elseif (isset($linkQuery['page'])) {
                                    $linkParams['pagina'] = max(1, (int)$linkQuery['page']);
                                } elseif (isset($linkQuery['offset']) && isset($linkQuery['risultati_per_pagina'])) {
                                    $perPage = (int)$linkQuery['risultati_per_pagina'];
                                    $pageFromOffset = $perPage > 0 ? (int)floor(((int)$linkQuery['offset']) / $perPage) + 1 : null;
                                    if ($pageFromOffset !== null && $pageFromOffset > 0) {
                                        $linkParams['pagina'] = $pageFromOffset;
                                    }
                                }

                                if (isset($linkQuery['risultati_per_pagina'])) {
                                    $linkParams['risultatiPerPagina'] = max(1, (int)$linkQuery['risultati_per_pagina']);
                                } elseif (isset($linkQuery['risultatiPerPagina'])) {
                                    $linkParams['risultatiPerPagina'] = max(1, (int)$linkQuery['risultatiPerPagina']);
                                }

                                if (isset($linkQuery['ordinamento'])) {
                                    $linkParams['ordinamento'] = (string)$linkQuery['ordinamento'];
                                }

                                if (($linkParams['pagina'] ?? $filters['pagina']) !== $filters['pagina']) {
                                    $nextUrl = $buildUrl($linkParams);
                                }
                            }
                        }
                    }
                } catch (ClientException $ce) {
                    $errors[] = 'Errore chiamata Pendenze: ' . $ce->getMessage();
                    $detailBody = $ce->getResponse() ? (string)$ce->getResponse()->getBody() : '';
                    if ($detailBody !== '') {
                        $errors[] = 'Dettaglio API: ' . $detailBody;
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Errore chiamata Pendenze: ' . $e->getMessage();
                }
            }
        }

        $highlightId = $params['highlight'] ?? null;
        $qsCurrent = $request->getUri()->getQuery();
        $returnUrl = '/pendenze/ricerca' . ($qsCurrent ? ('?' . $qsCurrent) : '');

        $this->exposeCurrentUser();

        return $this->twig->render($response, 'pendenze/ricerca.html.twig', [
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
            'tipologie_pendenze' => $tipologie,
        ]);
    }

    public function showInsert(Request $request, Response $response): Response
    {
        $this->exposeCurrentUser();
        return $this->twig->render($response, 'pendenze/inserimento.html.twig');
    }

    public function showBulkInsert(Request $request, Response $response): Response
    {
        $this->exposeCurrentUser();
        return $this->twig->render($response, 'pendenze/inserimento_massivo.html.twig');
    }

    public function showDetail(Request $request, Response $response, array $args): Response
    {
        $this->exposeCurrentUser();

        $idPendenza = $args['idPendenza'] ?? '';
        $q = $request->getQueryParams();
        $ret = $q['return'] ?? '/pendenze/ricerca';
        if (strpos((string)$ret, '/pendenze/ricerca') !== 0) {
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
                $username = getenv('GOVPAY_USER');
                $password = getenv('GOVPAY_PASSWORD');
                $guzzleOptions = [
                    'headers' => ['Accept' => 'application/json'],
                ];
                $authMethod = getenv('AUTHENTICATION_GOVPAY');
                if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
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
                    $http = new Client($guzzleOptions);
                    $url = rtrim($backofficeUrl, '/') . '/pendenze/' . rawurlencode((string)$idA2A) . '/' . rawurlencode($idPendenza);
                    $resp = $http->request('GET', $url);
                    $json = (string)$resp->getBody();
                    $data = json_decode($json, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \RuntimeException('Parsing JSON fallito: ' . json_last_error_msg());
                    }
                    $pendenza = $data;
                }
            } catch (ClientException $ce) {
                $code = $ce->getResponse() ? $ce->getResponse()->getStatusCode() : 0;
                $error = $code === 404 ? 'Pendenza non trovata (404)' : 'Errore client nella chiamata pendenza: ' . $ce->getMessage();
            } catch (\Throwable $e) {
                $error = 'Errore chiamata pendenza: ' . $e->getMessage();
            }
        }

        return $this->twig->render($response, 'pendenze/dettaglio.html.twig', [
            'idPendenza' => $idPendenza,
            'return_url' => $ret,
            'pendenza' => $pendenza,
            'error' => $error,
            'id_dominio' => $pendenza['idDominio'] ?? (getenv('ID_DOMINIO') ?: ''),
        ]);
    }

    public function downloadAvviso(Request $request, Response $response, array $args): Response
    {
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
                'headers' => ['Accept' => 'application/pdf'],
            ];
            $authMethod = getenv('AUTHENTICATION_GOVPAY');
            if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
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

            $http = new Client($guzzleOptions);
            $url = rtrim($backofficeUrl, '/') . '/avvisi/' . rawurlencode($idDominio) . '/' . rawurlencode($numeroAvviso);
            $resp = $http->request('GET', $url);
            $contentType = $resp->getHeaderLine('Content-Type') ?: 'application/pdf';
            $pdf = (string)$resp->getBody();
            $filename = 'avviso-' . $idDominio . '-' . $numeroAvviso . '.pdf';

            $response = $response
                ->withHeader('Content-Type', $contentType)
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withHeader('Cache-Control', 'no-store');
            $response->getBody()->write($pdf);
            return $response;
        } catch (ClientException $ce) {
            $code = $ce->getResponse() ? $ce->getResponse()->getStatusCode() : 0;
            $msg = $code === 404 ? 'Avviso non trovato' : ('Errore client avviso: ' . $ce->getMessage());
            $response->getBody()->write($msg);
            return $response->withStatus($code ?: 500);
        } catch (\Throwable $e) {
            $response->getBody()->write('Errore scaricamento avviso: ' . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    public function downloadRicevuta(Request $request, Response $response, array $args): Response
    {
        $idDominio = $args['idDominio'] ?? '';
        $iuv = $args['iuv'] ?? '';
        $ccp = $args['ccp'] ?? '';
        if ($idDominio === '' || $iuv === '' || $ccp === '') {
            $response->getBody()->write('Parametri mancanti');
            return $response->withStatus(400);
        }

        $pendenzeUrl = getenv('GOVPAY_PENDENZE_URL') ?: '';
        if (empty($pendenzeUrl)) {
            $response->getBody()->write('GOVPAY_PENDENZE_URL non impostata');
            return $response->withStatus(500);
        }

        try {
            $username = getenv('GOVPAY_USER');
            $password = getenv('GOVPAY_PASSWORD');
            $guzzleOptions = [
                'headers' => ['Accept' => 'application/pdf'],
            ];
            $authMethod = getenv('AUTHENTICATION_GOVPAY');
            if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
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

            $http = new Client($guzzleOptions);
            $url = rtrim($pendenzeUrl, '/') . '/rpp/'
                . rawurlencode($idDominio) . '/' . rawurlencode($iuv) . '/' . rawurlencode($ccp) . '/rt';
            $resp = $http->request('GET', $url);
            $contentType = $resp->getHeaderLine('Content-Type') ?: 'application/pdf';
            $pdf = (string)$resp->getBody();
            $filename = 'rt-' . $iuv . '-' . $ccp . '.pdf';

            $response = $response
                ->withHeader('Content-Type', $contentType)
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withHeader('Cache-Control', 'no-store');
            $response->getBody()->write($pdf);
            return $response;
        } catch (ClientException $ce) {
            $code = $ce->getResponse() ? $ce->getResponse()->getStatusCode() : 0;
            $msg = $code === 404 ? 'Ricevuta non trovata' : ('Errore client ricevuta: ' . $ce->getMessage());
            $response->getBody()->write($msg);
            return $response->withStatus($code ?: 500);
        } catch (\Throwable $e) {
            $response->getBody()->write('Errore scaricamento ricevuta: ' . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    public function downloadDominioLogo(Request $request, Response $response, array $args): Response
    {
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
            'headers' => ['Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*;q=0.8,*/*;q=0.5'],
        ];
        $authMethod = getenv('AUTHENTICATION_GOVPAY');
        if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
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

        try {
            $http = new Client($guzzleOptions);
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
        } catch (ClientException $ce) {
            // Continua con fallback
        } catch (\Throwable $e) {
            // Prosegue con fallback
        }

        try {
            if (!class_exists('GovPay\\Backoffice\\Api\\EntiCreditoriApi')) {
                $response->getBody()->write('Client Backoffice EntiCreditori non disponibile');
                return $response->withStatus(500);
            }

            $config = new BackofficeConfiguration();
            $config->setHost(rtrim($backofficeUrl, '/'));
            if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                $config->setUsername($username);
                $config->setPassword($password);
            }

            $httpClient = new Client($guzzleOptions);
            $entiApi = new \GovPay\Backoffice\Api\EntiCreditoriApi($httpClient, $config);
            $domRes = $entiApi->getDominio($idDominio);

            $logo = null;
            if (is_object($domRes)) {
                if (method_exists($domRes, 'getLogo')) {
                    $logo = $domRes->getLogo();
                } elseif (property_exists($domRes, 'logo')) {
                    $logo = $domRes->logo;
                }
            }
            if ($logo === null) {
                $domData = json_decode(json_encode($domRes), true);
                if (is_array($domData)) {
                    $logo = $domData['logo'] ?? null;
                }
            }

            if (!$logo || !is_string($logo)) {
                $response->getBody()->write('Logo non disponibile');
                return $response->withStatus(404);
            }

            if (preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.*)$/', $logo, $matches)) {
                $contentType = $matches[1];
                $bytes = base64_decode($matches[2], true);
                if ($bytes === false) {
                    $response->getBody()->write('Logo non valido');
                    return $response->withStatus(415);
                }

                $response = $response
                    ->withHeader('Content-Type', $contentType)
                    ->withHeader('Content-Disposition', 'inline; filename="logo-' . $idDominio . '"')
                    ->withHeader('Cache-Control', 'no-store');
                $response->getBody()->write($bytes);
                return $response;
            }

            $bytes = base64_decode($logo, true);
            if ($bytes !== false && $bytes !== '') {
                $response = $response
                    ->withHeader('Content-Type', 'image/png')
                    ->withHeader('Content-Disposition', 'inline; filename="logo-' . $idDominio . '.png"')
                    ->withHeader('Cache-Control', 'no-store');
                $response->getBody()->write($bytes);
                return $response;
            }

            if (is_string($logo) && str_starts_with($logo, '/')) {
                try {
                    $http = new Client($guzzleOptions);
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
                } catch (\Throwable $e) {
                    // Continua verso 404
                }
            }

            $response->getBody()->write('Logo non disponibile');
            return $response->withStatus(404);
        } catch (\Throwable $e) {
            $response->getBody()->write('Errore recupero logo: ' . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    private function exposeCurrentUser(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
            $this->twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
        }
    }

    private function shouldFallbackToRaw(\Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());
        return str_contains($message, 'invalid length')
            || str_contains($message, 'must be smaller than or equal to')
            || str_contains($message, 'length');
    }
}

