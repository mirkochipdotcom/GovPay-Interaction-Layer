<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database\EntrateRepository;
use App\Database\ExternalPaymentTypeRepository;
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
                                        foreach ($entrateEff as $r) {
                                            $idE = $r['id_entrata'];
                                            $boMap[$idE] = (int)$r['abilitato_backoffice'] === 1;
                                            $ovrMap[$idE] = isset($r['override_locale']) ? ((int)$r['override_locale'] === 1 ? 1 : 0) : null;
                                            $urlMap[$idE] = $r['external_url'] ?? null;
                                        }
                                        $entrateArr['_bo_map'] = $boMap;
                                        $entrateArr['_override_map'] = $ovrMap;
                                        $entrateArr['_exturl_map'] = $urlMap;
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
        } catch (\Throwable $e) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore salvataggio: ' . $e->getMessage()];
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
        } catch (\Throwable $e) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore salvataggio URL: ' . $e->getMessage()];
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
        } catch (\GuzzleHttp\Exception\ClientException $ce) {
            $code = $ce->getResponse() ? $ce->getResponse()->getStatusCode() : 0;
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore GovPay (' . $code . '): ' . $ce->getMessage()];
        } catch (\Throwable $e) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore aggiornamento GovPay: ' . $e->getMessage()];
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
        } catch (\Throwable $e) {
            $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore reset: ' . $e->getMessage()];
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
