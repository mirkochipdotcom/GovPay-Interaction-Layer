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

    public function search(Request $request, Response $response)
    {
        $params = (array)($request->getQueryParams() ?? []);
        $errors = [];

        // Stato pendenza enum (per select)
        $allowedStates = [];
        if (class_exists('\\GovPay\\Backoffice\\Model\\StatoPendenza')) {
            $allowedStates = \GovPay\Backoffice\Model\StatoPendenza::getAllowableEnumValues();
        }

        // Filtri con default leggeri
        // Ordinamento: singolo toggle ordRecentiPrima (checked => recenti prima).
        // Se il form è stato inviato (q=1) e la checkbox non è presente => vecchie prima.
        $formSubmitted = array_key_exists('q', $params);
        if (array_key_exists('ordRecentiPrima', $params)) {
            $ordinamento = '-dataCaricamento';
        } elseif ($formSubmitted) {
            $ordinamento = '+dataCaricamento';
        } else {
            $ordinamento = (string)($params['ordinamento'] ?? '-dataCaricamento');
        }

        $filters = [
            'q' => isset($params['q']) ? (string)$params['q'] : null,
            'pagina' => max(1, (int)($params['pagina'] ?? 1)),
            'risultatiPerPagina' => min(200, max(1, (int)($params['risultatiPerPagina'] ?? 25))),
            // Ordinamento di default: più recenti prima
            'ordinamento' => $ordinamento,
            'idDominio' => (string)($params['idDominio'] ?? (getenv('ID_DOMINIO') ?: '')),
            'idA2A' => (string)($params['idA2A'] ?? ''),
            'idPendenza' => (string)($params['idPendenza'] ?? ''),
            'idDebitore' => (string)($params['idDebitore'] ?? ''),
            'stato' => (string)($params['stato'] ?? ''),
            'idPagamento' => (string)($params['idPagamento'] ?? ''),
            'dataDa' => (string)($params['dataDa'] ?? ''),
            'dataA' => (string)($params['dataA'] ?? ''),
            'direzione' => (string)($params['direzione'] ?? ''),
            'divisione' => (string)($params['divisione'] ?? ''),
            'iuv' => (string)($params['iuv'] ?? ''),
        ];

        // Normalizza stato
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

        // Esegui la ricerca solo se richiesto esplicitamente (q=1)
        if (($filters['q'] ?? null) !== null) {
            $queryMade = true;

            // Controlli di configurazione
            $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
            if (!class_exists('\\GovPay\\Backoffice\\Api\\PendenzeApi')) {
                $errors[] = 'Client Backoffice non disponibile (namespace GovPay\\Backoffice)';
            } elseif (empty($backofficeUrl)) {
                $errors[] = 'Variabile GOVPAY_BACKOFFICE_URL non impostata';
            } else {
                try {
                    $config = new \GovPay\Backoffice\Configuration();
                    $config->setHost(rtrim($backofficeUrl, '/'));

                    // Basic auth opzionale
                    $username = getenv('GOVPAY_USER');
                    $password = getenv('GOVPAY_PASSWORD');
                    if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                        $config->setUsername($username);
                        $config->setPassword($password);
                    }

                    // mTLS opzionale
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

                    $httpClient = new \GuzzleHttp\Client($guzzleOptions);
                    $api = new \GovPay\Backoffice\Api\PendenzeApi($httpClient, $config);

                    // Mappa parametri verso l'SDK
                    $pagina = $filters['pagina'];
                    $rpp = $filters['risultatiPerPagina'];
                    $ordinamento = $filters['ordinamento'];
                    $campi = null; // non usato per ora
                    $idDominio = $filters['idDominio'] !== '' ? $filters['idDominio'] : null;
                    $idA2A = $filters['idA2A'] !== '' ? $filters['idA2A'] : null;
                    $idDebitore = $filters['idDebitore'] !== '' ? $filters['idDebitore'] : null;
                    $stato = $filters['stato'] !== '' ? $filters['stato'] : null;
                    $idPagamento = $filters['idPagamento'] !== '' ? $filters['idPagamento'] : null;
                    $idPendenza = $filters['idPendenza'] !== '' ? $filters['idPendenza'] : null;
                    $dataDa = $filters['dataDa'] !== '' ? $filters['dataDa'] : null;
                    $dataA = $filters['dataA'] !== '' ? $filters['dataA'] : null;
                    $idTipoPendenza = null; // multi-value, per ora non esposto
                    $direzione = $filters['direzione'] !== '' ? $filters['direzione'] : null;
                    $divisione = $filters['divisione'] !== '' ? $filters['divisione'] : null;
                    $iuv = $filters['iuv'] !== '' ? $filters['iuv'] : null;
                    $mostraSpontanei = false;
                    $metadatiPaginazione = true;
                    $maxRisultati = true;

                    try {
                        // 1) Prova via SDK (può fallire per vincoli dei modelli generati)
                        $result = $api->findPendenze(
                            $pagina,
                            $rpp,
                            $ordinamento,
                            $campi,
                            $idDominio,
                            $idA2A,
                            $idDebitore,
                            $stato,
                            $idPagamento,
                            $idPendenza,
                            $dataDa,
                            $dataA,
                            $idTipoPendenza,
                            $direzione,
                            $divisione,
                            $iuv,
                            $mostraSpontanei,
                            $metadatiPaginazione,
                            $maxRisultati
                        );

                        $data = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($result);
                        $dataArr = is_array($data) ? $data : json_decode(json_encode($data), true);
                    } catch (\Throwable $sdkEx) {
                        // 2) Fallback raw HTTP se l'SDK fallisce per es. "invalid length"
                        $msg = $sdkEx->getMessage();
                        $shouldFallback = stripos($msg, 'invalid length') !== false
                            || stripos($msg, 'must be smaller than or equal to') !== false
                            || stripos($msg, 'length') !== false;
                        if (!$shouldFallback) {
                            throw $sdkEx; // errore diverso: propaga al catch esterno
                        }

                        // Backoffice findPendenze: path '/pendenze' e parametri in snake_case
                        $url = rtrim($backofficeUrl, '/') . '/pendenze';
                        $query = [
                            'pagina' => $pagina,
                            'risultati_per_pagina' => $rpp,
                            'ordinamento' => $ordinamento,
                            // 'campi' => $campi, // omesso se null
                            'id_dominio' => $idDominio,
                            'id_a2_a' => $idA2A,
                            'id_debitore' => $idDebitore,
                            'stato' => $stato,
                            'id_pagamento' => $idPagamento,
                            'id_pendenza' => $idPendenza,
                            'data_da' => $dataDa,
                            'data_a' => $dataA,
                            // 'id_tipo_pendenza' => $idTipoPendenza,
                            'direzione' => $direzione,
                            'divisione' => $divisione,
                            'iuv' => $iuv,
                            'mostra_spontanei_non_pagati' => $mostraSpontanei,
                            'metadati_paginazione' => $metadatiPaginazione,
                            'max_risultati' => $maxRisultati,
                        ];
                        $query = array_filter($query, static function($v) { return $v !== null && $v !== ''; });
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
                    }

                    // Estraggo info paginazione e imposto risultati
                    $numPagine = $dataArr['numPagine'] ?? null;
                    $numRisultati = $dataArr['numRisultati'] ?? null;
                    $results = $dataArr;

                    // Costruisco prev/next URL
                    $basePath = $request->getUri()->getPath();
                    $qs = $params;
                    $qs['q'] = '1';
                    if ($filters['pagina'] > 1) {
                        $qs['pagina'] = $filters['pagina'] - 1;
                        // Propaga esplicitamente l'ordinamento calcolato
                        $qs['ordinamento'] = $ordinamento;
                        $prevUrl = $basePath . '?' . http_build_query($qs);
                    }
                    if ($numPagine !== null && $filters['pagina'] < (int)$numPagine) {
                        $qs['pagina'] = $filters['pagina'] + 1;
                        $qs['ordinamento'] = $ordinamento;
                        $nextUrl = $basePath . '?' . http_build_query($qs);
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Errore chiamata Pendenze: ' . $e->getMessage();
                }
            }
        }

        return $request
            ->withAttribute('filters', $filters)
            ->withAttribute('errors', $errors)
            ->withAttribute('allowed_states', $allowedStates)
            ->withAttribute('results', $results)
            ->withAttribute('num_pagine', $numPagine)
            ->withAttribute('num_risultati', $numRisultati)
            ->withAttribute('query_made', $queryMade)
            ->withAttribute('prev_url', $prevUrl)
            ->withAttribute('next_url', $nextUrl);
    }
}

