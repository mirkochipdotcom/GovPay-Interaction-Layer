<?php
/**
 * SPDX-License-Identifier: EUPL-1.2
 * License: European Union Public Licence v1.2 (EUPL-1.2)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Database\EntrateRepository;
use App\Database\ExternalPaymentTypeRepository;
use App\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ConfigurazioneController
{
    public function __construct(private readonly Twig $twig)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        if (!$this->isSuperadmin()) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Accesso negato: permessi insufficienti'];
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        if (isset($_SESSION['user'])) {
            $this->twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
        }

        $errors = [];
        $cfgJson = null;
        $cfgArr = null;
        $appsJson = null;
        $appsArr = null;
        $appJson = null;
        $appArr = null;
        $profiloJson = null;
        $entrateJson = null;
        $entrateArr = null;
        $entrateSource = null;
        $pagamentiProfiloJson = null;
        $infoJson = null;
        $infoArr = null;
        $dominioJson = null;
        $dominioArr = null;
    $idDominio = null;
        $tab = $request->getQueryParams()['tab'] ?? 'principali';
        $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';

        if (class_exists('\\GovPay\\Backoffice\\Api\\ConfigurazioniApi')) {
            if (!empty($backofficeUrl)) {
                try {
                    $config = new \GovPay\Backoffice\Configuration();
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

                    $httpClient = new \GuzzleHttp\Client($guzzleOptions);

                    $api = new \GovPay\Backoffice\Api\ConfigurazioniApi($httpClient, $config);
                    $result = $api->getConfigurazioni();
                    $data = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($result);
                    $cfgJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    $cfgArr = $data;

                    try {
                        $appApi = new \GovPay\Backoffice\Api\ApplicazioniApi($httpClient, $config);
                        $apps = $appApi->findApplicazioni(1, 100, '+idA2A', null, null, null, null, true, true);
                        $appsData = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($apps);
                        $appsArr = is_array($appsData)
                            ? $appsData
                            : (json_decode(json_encode($appsData, JSON_UNESCAPED_SLASHES), true) ?: []);

                        $idA2A = getenv('ID_A2A') ?: '';

                        $appsJson = json_encode($appsArr, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                        if ($idA2A !== '') {
                            try {
                                $appDet = $appApi->getApplicazione($idA2A);
                                $appDetData = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($appDet);
                                $appJson = json_encode($appDetData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                $appArr = json_decode($appJson, true);
                                if (!is_array($appArr)) {
                                    $appArr = is_array($appDetData) ? $appDetData : [];
                                }
                            } catch (\Throwable $e) {
                                $errors[] = 'Errore lettura applicazione ' . $idA2A . ': ' . $e->getMessage();
                            }
                        }
                    } catch (\Throwable $e) {
                        $errors[] = 'Errore lettura applicazioni: ' . $e->getMessage();
                    }

                    try {
                        if (class_exists('GovPay\\Backoffice\\Api\\EntiCreditoriApi')) {
                            $entrApi = new \GovPay\Backoffice\Api\EntiCreditoriApi($httpClient, $config);
                            $idDominioEnv = getenv('ID_DOMINIO');
                            $entrateSource = '/entrate';
                            if ($idDominioEnv !== false && $idDominioEnv !== '') {
                                $idDominio = trim((string)$idDominioEnv);
                                $entrRes = $entrApi->findEntrateDominio($idDominio, 1, 200, '+idEntrata', null, null, null, true, true);
                                $entrateSource = '/domini/' . $idDominio . '/entrate';
                            } else {
                                $entrRes = $entrApi->findEntrate(1, 200, '+idEntrata', null, true, true);
                                $entrateSource = '/entrate';
                            }
                            $entrData = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($entrRes);
                            $entrateJson = json_encode($entrData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                            $entrateArr = json_decode($entrateJson, true);
                            if (!is_array($entrateArr)) {
                                $entrateArr = [];
                            }

                            if (!empty($idDominioEnv ?? '')) {
                                try {
                                    $repoEntr = new EntrateRepository();
                                    $rows = $entrateArr['risultati'] ?? [];
                                    if (isset($idDominio)) {
                                        foreach ($rows as $row) {
                                            $repoEntr->upsertFromBackoffice($idDominio, $row);
                                        }
                                        $entrateEff = $repoEntr->listByDominio($idDominio);
                                        $boMap = [];
                                        $ovrMap = [];
                                        $urlMap = [];
                                        $descrMap = [];
                                        $descrEffMap = [];
                                        foreach ($entrateEff as $r) {
                                            $idE = $r['id_entrata'];
                                            $boMap[$idE] = (int)$r['abilitato_backoffice'] === 1;
                                            $ovrMap[$idE] = isset($r['override_locale']) ? ((int)$r['override_locale'] === 1 ? 1 : 0) : null;
                                            $urlMap[$idE] = $r['external_url'] ?? null;
                                            $descrMap[$idE] = $r['descrizione_locale'] ?? null;
                                            $descrEffMap[$idE] = $r['descrizione_effettiva'] ?? ($r['descrizione'] ?? null);
                                        }
                                        $entrateArr['_bo_map'] = $boMap;
                                        $entrateArr['_override_map'] = $ovrMap;
                                        $entrateArr['_exturl_map'] = $urlMap;
                                        $entrateArr['_descr_map'] = $descrMap;
                                        $entrateArr['_descr_eff_map'] = $descrEffMap;
                                    }
                                } catch (\Throwable $e) {
                                    $errors[] = 'Sync DB entrate fallito: ' . $e->getMessage();
                                }
                            }
                        } else {
                            $errors[] = 'Client Backoffice EntiCreditori non disponibile';
                        }
                    } catch (\Throwable $e) {
                        $errors[] = 'Errore lettura entrate: ' . $e->getMessage();
                    }

                    try {
                        if (class_exists('GovPay\\Pendenze\\Api\\ProfiloApi')) {
                            $pendHost = getenv('GOVPAY_PENDENZE_URL') ?: '';
                            if (!empty($pendHost)) {
                                $pendCfg = new \GovPay\Pendenze\Configuration();
                                $pendCfg->setHost(rtrim($pendHost, '/'));
                                if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                                    $pendCfg->setUsername($username);
                                    $pendCfg->setPassword($password);
                                }
                                $pendClient = new \GuzzleHttp\Client($guzzleOptions);
                                $profApi = new \GovPay\Pendenze\Api\ProfiloApi($pendClient, $pendCfg);
                                $profRes = $profApi->getProfilo();
                                $profData = \GovPay\Pendenze\ObjectSerializer::sanitizeForSerialization($profRes);
                                $profiloJson = json_encode($profData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                            } else {
                                $errors[] = 'Variabile GOVPAY_PENDENZE_URL non impostata';
                            }
                        } else {
                            $errors[] = 'Client Pendenze non disponibile (namespace GovPay\\Pendenze)';
                        }
                    } catch (\Throwable $e) {
                        $errors[] = 'Errore lettura profilo Pendenze: ' . $e->getMessage();
                    }

                    try {
                        $pagHost = getenv('GOVPAY_PAGAMENTI_URL') ?: '';
                        if (!empty($pagHost)) {
                            $headers = ['Accept' => 'application/json'];
                            if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                                $headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
                            }
                            $http = new \GuzzleHttp\Client($guzzleOptions);
                            $resp = $http->request('GET', rtrim($pagHost, '/') . '/profilo', ['headers' => $headers]);
                            $pagamentiProfiloJson = (string)$resp->getBody();
                        } else {
                            $errors[] = 'Variabile GOVPAY_PAGAMENTI_URL non impostata';
                        }
                    } catch (\Throwable $e) {
                        $errors[] = 'Errore lettura profilo Pagamenti: ' . $e->getMessage();
                    }

                    try {
                        if (class_exists('GovPay\\Backoffice\\Api\\InfoApi')) {
                            $infoApi = new \GovPay\Backoffice\Api\InfoApi($httpClient, $config);
                            $infoRes = $infoApi->getInfo();
                            $infoData = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($infoRes);
                            $infoJson = json_encode($infoData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                            $infoArr = $infoData;
                        }
                    } catch (\Throwable $e) {
                        $errors[] = 'Errore lettura Info: ' . $e->getMessage();
                    }

                    try {
                        if (class_exists('GovPay\\Backoffice\\Api\\EntiCreditoriApi')) {
                            $idDom = getenv('ID_DOMINIO') ?: '';
                            if ($idDom !== '') {
                                $entiApi = new \GovPay\Backoffice\Api\EntiCreditoriApi($httpClient, $config);
                                $domRes = $entiApi->getDominio($idDom);
                                $domData = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($domRes);
                                $dominioJson = json_encode($domData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                $dominioArr = $domData;
                            } else {
                                $errors[] = 'Variabile ID_DOMINIO non impostata per lettura dominio';
                            }
                        }
                    } catch (\Throwable $e) {
                        $errors[] = 'Errore lettura dominio beneficiario: ' . $e->getMessage();
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Errore chiamata Backoffice: ' . $e->getMessage();
                }
            } else {
                $errors[] = 'Variabile GOVPAY_BACKOFFICE_URL non impostata';
            }
        } else {
            $errors[] = 'Client Backoffice non disponibile (namespace GovPay\\Backoffice)';
        }

        $externalTypes = [];
        try {
            $extRepo = new ExternalPaymentTypeRepository();
            $externalTypes = $extRepo->listAll();
        } catch (\Throwable $e) {
            $errors[] = 'Errore lettura tipologie esterne: ' . $e->getMessage();
        }

        // Read last N log lines from application log (safe guard: read only up to 20MB tail)
        $logsLines = [];
        $maxLines = 1000;
        $logPath = __DIR__ . '/../../storage/logs/app.log';
        if (is_file($logPath) && is_readable($logPath)) {
            $size = filesize($logPath);
            if ($size > 0) {
                try {
                    if ($size <= 20 * 1024 * 1024) { // 20 MB
                        $all = @file($logPath, FILE_IGNORE_NEW_LINES);
                        if ($all !== false) {
                            $slice = array_slice($all, -$maxLines);
                            $logsLines = array_reverse($slice);
                        }
                    } else {
                        $fp = @fopen($logPath, 'r');
                        if ($fp) {
                            $chunk = 20 * 1024 * 1024;
                            fseek($fp, -$chunk, SEEK_END);
                            $data = stream_get_contents($fp);
                            fclose($fp);
                            $all = explode("\n", $data);
                            $slice = array_slice($all, -$maxLines);
                            $logsLines = array_reverse($slice);
                        }
                    }
                } catch (\Throwable $_) {
                    // swallow errors reading logs; just leave logsLines empty
                    $logsLines = [];
                }
            }
        }

        return $this->twig->render($response, 'configurazione.html.twig', [
            'errors' => $errors,
            'cfg_json' => $cfgJson,
            'cfg' => $cfgArr,
            'apps_json' => $appsJson,
            'apps' => $appsArr,
            'app_json' => $appJson,
            'app' => $appArr,
            'idA2A' => getenv('ID_A2A') ?: null,
            'profilo_json' => $profiloJson,
            'entrate_json' => $entrateJson,
            'entrate' => $entrateArr,
            'entrate_source' => $entrateSource ?? '/entrate',
            'pagamenti_profilo_json' => $pagamentiProfiloJson,
            'info' => $infoArr,
            'info_json' => $infoJson,
            'dominio' => $dominioArr,
            'dominio_json' => $dominioJson,
            'tipologie_esterne' => $externalTypes,
            'backoffice_base' => rtrim($backofficeUrl, '/'),
            'tab' => $tab,
            'logs_lines' => $logsLines,
        ]);
    }

    public function createExternalPaymentType(Request $request, Response $response): Response
    {
        if (!$this->isSuperadmin()) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Accesso negato'];
            return $this->redirectToTab($response, 'tipologie_esterne');
        }

        $data = (array)($request->getParsedBody() ?? []);
        $descrizione = trim((string)($data['descrizione'] ?? ''));
        $url = trim((string)($data['url'] ?? ''));

        if ($descrizione === '' || $url === '') {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Compila descrizione e URL'];
            return $this->redirectToTab($response, 'tipologie_esterne');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'URL non valido'];
            return $this->redirectToTab($response, 'tipologie_esterne');
        }

        try {
            $repo = new ExternalPaymentTypeRepository();
            $repo->create($descrizione, $url);
            $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Tipologia esterna salvata'];
        } catch (\Throwable $e) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore salvataggio tipologia esterna: ' . $e->getMessage()];
        }

        return $this->redirectToTab($response, 'tipologie_esterne');
    }

    public function deleteExternalPaymentType(Request $request, Response $response, array $args): Response
    {
        if (!$this->isSuperadmin()) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Accesso negato'];
            return $this->redirectToTab($response, 'tipologie_esterne');
        }

        $id = isset($args['id']) ? (int)$args['id'] : 0;
        if ($id <= 0) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'ID tipologia non valido'];
            return $this->redirectToTab($response, 'tipologie_esterne');
        }

        try {
            $repo = new ExternalPaymentTypeRepository();
            $repo->delete($id);
            $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Tipologia esterna rimossa'];
        } catch (\Throwable $e) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore eliminazione tipologia esterna: ' . $e->getMessage()];
        }

        return $this->redirectToTab($response, 'tipologie_esterne');
    }

    public function updateDominio(Request $request, Response $response): Response
    {
        if (!$this->isSuperadmin()) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Accesso negato'];
            return $this->redirectToTab($response, 'dominio');
        }

        $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
        $idDom = getenv('ID_DOMINIO') ?: '';
        if ($backofficeUrl === '' || $idDom === '') {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Variabili GOVPAY_BACKOFFICE_URL o ID_DOMINIO non impostate'];
            return $this->redirectToTab($response, 'dominio');
        }

        // Setup HTTP client (basic or mTLS) like other Backoffice calls
        $username = getenv('GOVPAY_USER');
        $password = getenv('GOVPAY_PASSWORD');
        $guzzleOptions = ['headers' => ['Accept' => 'application/json']];
        $authMethod = getenv('AUTHENTICATION_GOVPAY');
        if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
            $cert = getenv('GOVPAY_TLS_CERT');
            $key = getenv('GOVPAY_TLS_KEY');
            $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
            if (!empty($cert) && !empty($key)) {
                $guzzleOptions['cert'] = $cert;
                $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
            } else {
                $_SESSION['flash'][] = ['type' => 'error', 'text' => 'mTLS abilitato ma certificati non impostati'];
                return $this->redirectToTab($response, 'dominio');
            }
        }

        if (!class_exists('GovPay\\Backoffice\\Api\\EntiCreditoriApi')) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Client Backoffice non disponibile'];
            return $this->redirectToTab($response, 'dominio');
        }

        $config = new \GovPay\Backoffice\Configuration();
        $config->setHost(rtrim($backofficeUrl, '/'));
        if ($username !== false && $password !== false && $username !== '' && $password !== '') {
            $config->setUsername($username);
            $config->setPassword($password);
        }

        $httpClient = new \GuzzleHttp\Client($guzzleOptions);
        $entiApi = new \GovPay\Backoffice\Api\EntiCreditoriApi($httpClient, $config);

        $data = (array)($request->getParsedBody() ?? []);

        try {
            // Fetch current domain model and read via getters
            $curr = $entiApi->getDominio($idDom);

            // Start from current values, then override only provided fields
            $payload = [
                'ragione_sociale' => (string)($curr->getRagioneSociale() ?? ''),
                'indirizzo' => (string)($curr->getIndirizzo() ?? ''),
                'civico' => (string)($curr->getCivico() ?? ''),
                'cap' => (string)($curr->getCap() ?? ''),
                'localita' => (string)($curr->getLocalita() ?? ''),
                'provincia' => (string)($curr->getProvincia() ?? ''),
                'nazione' => (string)($curr->getNazione() ?? ''),
                'email' => (string)($curr->getEmail() ?? ''),
                'pec' => (string)($curr->getPec() ?? ''),
                'tel' => (string)($curr->getTel() ?? ''),
                'fax' => (string)($curr->getFax() ?? ''),
                'web' => (string)($curr->getWeb() ?? ''),
                'gln' => (string)($curr->getGln() ?? ''),
                'cbill' => (string)($curr->getCbill() ?? ''),
                'iuv_prefix' => (string)($curr->getIuvPrefix() ?? ''),
                'stazione' => (string)($curr->getStazione() ?? ''),
                'aux_digit' => (string)($curr->getAuxDigit() ?? ''),
                'segregation_code' => (string)($curr->getSegregationCode() ?? ''),
                'logo' => (string)($curr->getLogo() ?? ''),
                'abilitato' => (bool)($curr->getAbilitato() ?? false),
                'intermediato' => (bool)($curr->getIntermediato() ?? false),
            ];

            // Overlay user-provided values (trim strings)
            $map = [
                'ragione_sociale','indirizzo','civico','cap','localita','provincia','nazione','email','pec','tel','fax','web','gln','cbill','iuv_prefix','stazione','aux_digit','segregation_code','logo'
            ];
            foreach ($map as $k) {
                if (array_key_exists($k, $data)) {
                    $payload[$k] = trim((string)$data[$k]);
                }
            }
            // Checkboxes
            if (array_key_exists('abilitato', $data)) {
                $payload['abilitato'] = ((string)$data['abilitato'] === '1');
            }
            if (array_key_exists('intermediato', $data)) {
                $payload['intermediato'] = ((string)$data['intermediato'] === '1');
            }

            // Required by model: ragione_sociale, gln, stazione, abilitato
            if ($payload['ragione_sociale'] === '' || $payload['gln'] === '' || $payload['stazione'] === '') {
                $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Compila i campi obbligatori: Ragione sociale, GLN e Stazione'];
                return $this->redirectToTab($response, 'dominio');
            }

            // Build request without forcing null on optional fields (omit empties)
            $req = [];
            $setIfNotEmpty = function(string $key) use (&$req, $payload) {
                if (isset($payload[$key]) && $payload[$key] !== '') {
                    $req[$key] = $payload[$key];
                }
            };
            $req['ragione_sociale'] = $payload['ragione_sociale'];
            $req['gln'] = $payload['gln'];
            $req['stazione'] = $payload['stazione'];
            $req['abilitato'] = (bool)$payload['abilitato'];
            foreach (['indirizzo','civico','cap','localita','provincia','nazione','email','pec','tel','fax','web','cbill','iuv_prefix','aux_digit','segregation_code','logo'] as $opt) {
                $setIfNotEmpty($opt);
            }
            // intermediato is boolean optional in model, include if present in form or differs from current
            if (array_key_exists('intermediato', $data)) {
                $req['intermediato'] = (bool)$payload['intermediato'];
            }

            $dominioPost = new \GovPay\Backoffice\Model\DominioPost($req);

            $entiApi->addDominio($idDom, $dominioPost);

            $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Dati dominio aggiornati con successo'];
            Logger::getInstance()->info('Dominio aggiornato', ['id_dominio' => $idDom, 'user_id' => $_SESSION['user']['id'] ?? null]);
        } catch (\GovPay\Backoffice\ApiException $e) {
            $code = $e->getCode();
            $body = method_exists($e, 'getResponseBody') ? $e->getResponseBody() : null;
            $msg = 'Errore Backoffice (' . $code . ') aggiornamento dominio';
            if ($body) {
                $msg .= ': ' . (is_string($body) ? $body : json_encode($body));
            } else {
                $msg .= ': ' . $e->getMessage();
            }
            $_SESSION['flash'][] = ['type' => 'error', 'text' => $msg];
            Logger::getInstance()->error('Errore Backoffice aggiornamento dominio', ['id_dominio' => $idDom, 'code' => $code, 'error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore aggiornamento dominio: ' . $e->getMessage()];
            Logger::getInstance()->error('Errore aggiornamento dominio', ['id_dominio' => $idDom, 'error' => $e->getMessage()]);
        }

        return $this->redirectToTab($response, 'dominio');
    }

    public function overrideTipologia(Request $request, Response $response, array $args): Response
    {
        if (!$this->isSuperadmin()) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Accesso negato'];
            return $this->redirectToTab($response, 'tipologie');
        }

        $idEntrata = $args['idEntrata'] ?? '';
        $idDominio = getenv('ID_DOMINIO') ?: '';
        if ($idEntrata === '' || $idDominio === '') {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Parametri mancanti'];
            return $this->redirectToTab($response, 'tipologie');
        }

        $data = (array)($request->getParsedBody() ?? []);
        $override = null;
        if (isset($data['action']) && $data['action'] === 'reset') {
            $override = null;
        } elseif (isset($data['enable'])) {
            $override = (string)$data['enable'] === '1';
        }

        try {
            $repo = new EntrateRepository();
            $repo->setOverride($idDominio, $idEntrata, $override);
            $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Impostazione salvata'];
            Logger::getInstance()->info('Tipologia override updated', ['id_dominio' => $idDominio, 'id_entrata' => $idEntrata, 'user_id' => $_SESSION['user']['id'] ?? null, 'override' => $override]);
        } catch (\Throwable $e) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore salvataggio: ' . $e->getMessage()];
            Logger::getInstance()->error('Error updating tipologia override', ['id_dominio' => $idDominio, 'id_entrata' => $idEntrata, 'user_id' => $_SESSION['user']['id'] ?? null, 'error' => $e->getMessage()]);
        }

        return $this->redirectToTab($response, 'tipologie');
    }

    public function updateTipologiaUrl(Request $request, Response $response, array $args): Response
    {
        if (!$this->isSuperadmin()) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Accesso negato'];
            return $this->redirectToTab($response, 'tipologie');
        }

        $idEntrata = $args['idEntrata'] ?? '';
        $idDominio = getenv('ID_DOMINIO') ?: '';
        if ($idEntrata === '' || $idDominio === '') {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Parametri mancanti'];
            return $this->redirectToTab($response, 'tipologie');
        }

        $data = (array)($request->getParsedBody() ?? []);
        $url = trim((string)($data['external_url'] ?? ''));
        if ($url === '') {
            $url = null;
        }

        try {
            $repo = new EntrateRepository();
            $repo->setExternalUrl($idDominio, $idEntrata, $url);
            $_SESSION['flash'][] = ['type' => 'success', 'text' => 'URL esterna salvata'];
            Logger::getInstance()->info('Tipologia external_url updated', ['id_dominio' => $idDominio, 'id_entrata' => $idEntrata, 'user_id' => $_SESSION['user']['id'] ?? null, 'url' => $url]);
        } catch (\Throwable $e) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore salvataggio URL: ' . $e->getMessage()];
            Logger::getInstance()->error('Error updating tipologia external_url', ['id_dominio' => $idDominio, 'id_entrata' => $idEntrata, 'user_id' => $_SESSION['user']['id'] ?? null, 'error' => $e->getMessage()]);
        }

        return $this->redirectToTab($response, 'tipologie');
    }

    public function updateTipologiaGovpay(Request $request, Response $response, array $args): Response
    {
        if (!$this->isSuperadmin()) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Accesso negato'];
            return $this->redirectToTab($response, 'tipologie');
        }

        $idEntrata = $args['idEntrata'] ?? '';
        $idDominio = getenv('ID_DOMINIO') ?: '';
        if ($idEntrata === '' || $idDominio === '') {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Parametri mancanti'];
            return $this->redirectToTab($response, 'tipologie');
        }

        $data = (array)($request->getParsedBody() ?? []);
        $enable = isset($data['enable']) ? ((string)$data['enable'] === '1') : null;
        if ($enable === null) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Parametro enable mancante'];
            return $this->redirectToTab($response, 'tipologie');
        }

        $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
        if (empty($backofficeUrl)) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'GOVPAY_BACKOFFICE_URL non impostata'];
            return $this->redirectToTab($response, 'tipologie');
        }

        try {
            $username = getenv('GOVPAY_USER');
            $password = getenv('GOVPAY_PASSWORD');
            $guzzleOptions = ['headers' => ['Accept' => 'application/json']];
            $authMethod = getenv('AUTHENTICATION_GOVPAY');
            if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
                $cert = getenv('GOVPAY_TLS_CERT');
                $key = getenv('GOVPAY_TLS_KEY');
                $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
                if (!empty($cert) && !empty($key)) {
                    $guzzleOptions['cert'] = $cert;
                    $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
                } else {
                    $_SESSION['flash'][] = ['type' => 'error', 'text' => 'mTLS abilitato ma certificati non impostati'];
                    return $this->redirectToTab($response, 'tipologie');
                }
            }

            if (!class_exists('GovPay\\Backoffice\\Api\\EntiCreditoriApi')) {
                $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Client Backoffice non disponibile'];
                return $this->redirectToTab($response, 'tipologie');
            }

            $config = new \GovPay\Backoffice\Configuration();
            $config->setHost(rtrim($backofficeUrl, '/'));
            if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                $config->setUsername($username);
                $config->setPassword($password);
            }

            $httpClient = new \GuzzleHttp\Client($guzzleOptions);
            $entiApi = new \GovPay\Backoffice\Api\EntiCreditoriApi($httpClient, $config);

            $curr = $entiApi->getEntrataDominio($idDominio, $idEntrata);
            $ibanAccredito = null;
            $codiceCont = null;
            if (is_object($curr)) {
                if (method_exists($curr, 'getIbanAccredito')) {
                    $ibanAccredito = $curr->getIbanAccredito();
                }
                if (method_exists($curr, 'getCodiceContabilita')) {
                    $codiceCont = $curr->getCodiceContabilita();
                }
                if ($ibanAccredito === null || $codiceCont === null) {
                    $currData = json_decode(json_encode($curr), true);
                    if (is_array($currData)) {
                        if ($ibanAccredito === null) {
                            $ibanAccredito = $currData['ibanAccredito'] ?? null;
                        }
                        if ($codiceCont === null) {
                            $codiceCont = $currData['codiceContabilita'] ?? null;
                        }
                    }
                }
            } else {
                $currData = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($curr);
                $currArr = json_decode(json_encode($currData), true);
                if (is_array($currArr)) {
                    $ibanAccredito = $currArr['ibanAccredito'] ?? null;
                    $codiceCont = $currArr['codiceContabilita'] ?? null;
                }
            }

            if (empty($ibanAccredito)) {
                $_SESSION['flash'][] = ['type' => 'error', 'text' => 'IBAN mancante sulla tipologia: impossibile aggiornare'];
                return $this->redirectToTab($response, 'tipologie');
            }

            $body = new \GovPay\Backoffice\Model\EntrataPost([
                'iban_accredito' => $ibanAccredito,
                'abilitato' => $enable,
            ]);

            if (!empty($codiceCont)) {
                $body->setCodiceContabilita($codiceCont);
            }

            $entiApi->addEntrataDominio($idDominio, $idEntrata, $body);

            $_SESSION['flash'][] = ['type' => 'success', 'text' => ($enable ? 'Abilitata' : 'Disabilitata') . ' su GovPay'];
            Logger::getInstance()->info('Tipologia govpay enabled toggled', ['id_dominio' => $idDominio, 'id_entrata' => $idEntrata, 'user_id' => $_SESSION['user']['id'] ?? null, 'enabled' => $enable]);
        } catch (\GuzzleHttp\Exception\ClientException $ce) {
            $code = $ce->getResponse() ? $ce->getResponse()->getStatusCode() : 0;
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore GovPay (' . $code . '): ' . $ce->getMessage()];
            Logger::getInstance()->error('Error toggling tipologia govpay', ['id_dominio' => $idDominio, 'id_entrata' => $idEntrata, 'user_id' => $_SESSION['user']['id'] ?? null, 'code' => $code, 'error' => $ce->getMessage()]);
        } catch (\Throwable $e) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore aggiornamento GovPay: ' . $e->getMessage()];
            Logger::getInstance()->error('Error toggling tipologia govpay', ['id_dominio' => $idDominio, 'id_entrata' => $idEntrata, 'user_id' => $_SESSION['user']['id'] ?? null, 'error' => $e->getMessage()]);
        }

        return $this->redirectToTab($response, 'tipologie');
    }

    public function resetTipologia(Request $request, Response $response, array $args): Response
    {
        if (!$this->isSuperadmin()) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Accesso negato'];
            return $this->redirectToTab($response, 'tipologie');
        }

        $idEntrata = $args['idEntrata'] ?? '';
        $idDominio = getenv('ID_DOMINIO') ?: '';
        if ($idEntrata === '' || $idDominio === '') {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Parametri mancanti'];
            return $this->redirectToTab($response, 'tipologie');
        }

        try {
            $repo = new EntrateRepository();
            $row = $repo->findOne($idDominio, $idEntrata);
            $repo->setExternalUrl($idDominio, $idEntrata, null);
            if ($row && ((int)$row['abilitato_backoffice'] === 1)) {
                $repo->setOverride($idDominio, $idEntrata, null);
            }
            $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Reset eseguito'];
            Logger::getInstance()->info('Tipologia reset', ['id_dominio' => $idDominio, 'id_entrata' => $idEntrata, 'user_id' => $_SESSION['user']['id'] ?? null]);
        } catch (\Throwable $e) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore reset: ' . $e->getMessage()];
            Logger::getInstance()->error('Error resetting tipologia', ['id_dominio' => $idDominio, 'id_entrata' => $idEntrata, 'user_id' => $_SESSION['user']['id'] ?? null, 'error' => $e->getMessage()]);
        }

        return $this->redirectToTab($response, 'tipologie');
    }

    public function updateTipologiaDescrizione(Request $request, Response $response, array $args): Response
    {
        if (!$this->isSuperadmin()) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Accesso negato'];
            return $this->redirectToTab($response, 'tipologie');
        }
        $idEntrata = $args['idEntrata'] ?? '';
        $idDominio = getenv('ID_DOMINIO') ?: '';
        if ($idEntrata === '' || $idDominio === '') {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Parametri mancanti'];
            return $this->redirectToTab($response, 'tipologie');
        }
        $data = (array)($request->getParsedBody() ?? []);
        $descr = trim((string)($data['descrizione'] ?? ''));
        if ($descr === '') {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Descrizione non valida'];
            return $this->redirectToTab($response, 'tipologie');
        }
        if (mb_strlen($descr) > 255) {
            $descr = mb_substr($descr, 0, 255);
        }
        try {
            $repo = new EntrateRepository();
            $repo->updateDescrizione($idDominio, $idEntrata, $descr);
            $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Descrizione aggiornata'];
            Logger::getInstance()->info('Tipologia descrizione aggiornata', ['id_dominio' => $idDominio, 'id_entrata' => $idEntrata, 'user_id' => $_SESSION['user']['id'] ?? null]);
        } catch (\Throwable $e) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore aggiornamento descrizione: ' . $e->getMessage()];
            Logger::getInstance()->error('Errore aggiornamento descrizione tipologia', ['id_dominio' => $idDominio, 'id_entrata' => $idEntrata, 'user_id' => $_SESSION['user']['id'] ?? null, 'error' => $e->getMessage()]);
        }
        return $this->redirectToTab($response, 'tipologie');
    }

    public function restoreTipologiaDescrizione(Request $request, Response $response, array $args): Response
    {
        if (!$this->isSuperadmin()) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Accesso negato'];
            return $this->redirectToTab($response, 'tipologie');
        }
        $idEntrata = $args['idEntrata'] ?? '';
        $idDominio = getenv('ID_DOMINIO') ?: '';
        if ($idEntrata === '' || $idDominio === '') {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Parametri mancanti'];
            return $this->redirectToTab($response, 'tipologie');
        }
        try {
            $repo = new EntrateRepository();
            $affected = $repo->clearDescrizioneLocale($idDominio, $idEntrata);
            if ($affected > 0) {
                $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Descrizione ripristinata da GovPay'];
                Logger::getInstance()->info('Tipologia descrizione ripristinata', ['id_dominio' => $idDominio, 'id_entrata' => $idEntrata, 'user_id' => $_SESSION['user']['id'] ?? null]);
            } else {
                $_SESSION['flash'][] = ['type' => 'info', 'text' => 'Nessuna descrizione locale trovata da cancellare'];
                Logger::getInstance()->warning('Restore descrizione nessuna riga aggiornata', ['id_dominio' => $idDominio, 'id_entrata' => $idEntrata, 'user_id' => $_SESSION['user']['id'] ?? null]);
            }
        } catch (\Throwable $e) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore ripristino descrizione: ' . $e->getMessage()];
            Logger::getInstance()->error('Errore ripristino descrizione tipologia', ['id_dominio' => $idDominio, 'id_entrata' => $idEntrata, 'user_id' => $_SESSION['user']['id'] ?? null, 'error' => $e->getMessage()]);
        }
        return $this->redirectToTab($response, 'tipologie');
    }

    private function redirectToTab(Response $response, string $tab): Response
    {
        return $response->withHeader('Location', '/configurazione?tab=' . $tab)->withStatus(302);
    }

    private function isSuperadmin(): bool
    {
        $u = $_SESSION['user'] ?? null;
        return $u && ($u['role'] ?? '') === 'superadmin';
    }
}
