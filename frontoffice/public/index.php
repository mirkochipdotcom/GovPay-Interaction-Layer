<?php
declare(strict_types=1);

use App\Database\EntrateRepository;
use App\Database\ExternalPaymentTypeRepository;
use App\Logger;
use App\Services\ValidationService;
use GovPay\Pagamenti\Api\PendenzeApi as PagamentiPendenzeApi;
use GovPay\Pagamenti\Configuration as PagamentiConfiguration;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require dirname(__DIR__) . '/vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('govpay_frontoffice');

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    if (!$isHttps && isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $isHttps = strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
    }

    $rawProfiles = (string)($_ENV['COMPOSE_PROFILES'] ?? getenv('COMPOSE_PROFILES') ?? '');
    $profiles = $rawProfiles !== ''
        ? array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $rawProfiles))))
        : [];
    $profiles = array_values(array_unique($profiles));

    $spidEnabledByProfile = in_array('spid-proxy', $profiles, true) || in_array('external', $profiles, true);
    if (in_array('none', $profiles, true) || $profiles === []) {
        $spidEnabledByProfile = false;
    }

    // Per callback SPID via POST cross-site (dal proxy al frontoffice) i browser non inviano cookie SameSite=Lax.
    // Quindi, quando SPID è abilitato, serve SameSite=None (che richiede anche Secure).
    $sameSite = ($spidEnabledByProfile && $isHttps) ? 'None' : 'Lax';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => $sameSite,
    ]);
    session_start();
}

if (!function_exists('frontoffice_env_value')) {
    function frontoffice_env_value(string $key, ?string $default = null): string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }
        if ($value === null || $value === '') {
            return $default ?? '';
        }
        return (string) $value;
    }
}

$env = static function (string $key, ?string $default = null): string {
    return frontoffice_env_value($key, $default);
};

if (!function_exists('frontoffice_load_service_options')) {
    function frontoffice_load_service_options(): array
    {
        $options = [];
        $internalOptions = [];
        $externalOptions = [];
        $idDominio = frontoffice_env_value('ID_DOMINIO', '');
        if ($idDominio !== '') {
            try {
                $repo = new EntrateRepository();
                $rows = $repo->listByDominio($idDominio);
                foreach ($rows as $row) {
                    if ((int)($row['abilitato_backoffice'] ?? 0) !== 1) {
                        continue;
                    }
                    $id = (string)($row['id_entrata'] ?? '');
                    if ($id === '') {
                        continue;
                    }
                    $label = trim((string)($row['descrizione_effettiva'] ?? $row['descrizione'] ?? $id));
                    if ($label === '') {
                        $label = $id;
                    }
                    $externalUrl = trim((string)($row['external_url'] ?? '')) ?: null;
                    $descrizioneEstesa = trim((string)($row['descrizione_estesa'] ?? ''));
                    $internalOptions[] = [
                        'id' => $id,
                        'label' => $label,
                        'type' => $externalUrl ? 'external' : 'internal',
                        'external_url' => $externalUrl,
                        'descrizione_estesa' => $descrizioneEstesa !== '' ? $descrizioneEstesa : null,
                    ];
                }
                if ($internalOptions !== []) {
                    $internalCount = count(array_filter($internalOptions, static fn ($opt) => ($opt['type'] ?? 'internal') === 'internal'));
                    $externalCount = count($internalOptions) - $internalCount;
                    Logger::getInstance()->info('Tipologie frontoffice caricate dal DB', [
                        'idDominio' => $idDominio,
                        'internal' => $internalCount,
                        'external' => $externalCount,
                    ]);
                }
            } catch (\Throwable $e) {
                Logger::getInstance()->warning('Impossibile caricare le tipologie per il frontoffice', ['error' => $e->getMessage()]);
            }
        }

        // Tipologie di pagamento esterne (catalogo locale) - sempre indipendenti dal dominio
        try {
            $repoExternal = new ExternalPaymentTypeRepository();
            $rows = $repoExternal->listAll();
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $id = (string)($row['id'] ?? '');
                if ($id === '') {
                    continue;
                }
                $label = trim((string)($row['descrizione'] ?? ''));
                if ($label === '') {
                    $label = 'Servizio esterno ' . $id;
                }
                $url = trim((string)($row['url'] ?? ''));
                if ($url === '') {
                    continue;
                }
                $descrizioneEstesa = trim((string)($row['descrizione_estesa'] ?? ''));
                $externalOptions[] = [
                    'id' => 'EXT:' . $id,
                    'label' => $label,
                    'type' => 'external',
                    'external_url' => $url,
                    'descrizione_estesa' => $descrizioneEstesa !== '' ? $descrizioneEstesa : null,
                ];
            }
        } catch (\Throwable $e) {
            Logger::getInstance()->warning('Impossibile caricare le tipologie esterne per il frontoffice', ['error' => $e->getMessage()]);
        }

        $options = array_merge($internalOptions, $externalOptions);

        if ($options === []) {
            Logger::getInstance()->warning('Tipologie frontoffice assenti dal DB: uso fallback statico', ['idDominio' => $idDominio]);
            $options = [
                ['id' => 'SERV_MENSA', 'label' => 'Mensa e servizi scolastici', 'type' => 'internal', 'external_url' => null, 'descrizione_estesa' => null],
                ['id' => 'SERV_NIDI', 'label' => "Nidi d'infanzia / rette asilo", 'type' => 'internal', 'external_url' => null, 'descrizione_estesa' => null],
                ['id' => 'SERV_OCCUPAZIONE_SUOLO', 'label' => 'Occupazione suolo pubblico', 'type' => 'internal', 'external_url' => null, 'descrizione_estesa' => null],
                ['id' => 'SERV_SANZIONI', 'label' => 'Sanzioni e contravvenzioni', 'type' => 'internal', 'external_url' => null, 'descrizione_estesa' => null],
                ['id' => 'SERV_DIRITTI_SEGRETERIA', 'label' => 'Diritti di segreteria e certificati', 'type' => 'internal', 'external_url' => null, 'descrizione_estesa' => null],
                ['id' => 'SERV_ALTRO', 'label' => 'Altro pagamento spontaneo', 'type' => 'internal', 'external_url' => null, 'descrizione_estesa' => null],
            ];
        }

        usort($options, static fn ($a, $b) => strcasecmp((string)($a['label'] ?? ''), (string)($b['label'] ?? '')));
        return $options;
    }
}

if (!function_exists('frontoffice_find_service_option')) {
    function frontoffice_find_service_option(array $options, string $id): ?array
    {
        foreach ($options as $option) {
            if (($option['id'] ?? null) === $id) {
                return $option;
            }
        }
        return null;
    }
}

if (!function_exists('frontoffice_basic_auth')) {
    function frontoffice_basic_auth(): ?array
    {
        $username = getenv('GOVPAY_USER');
        $password = getenv('GOVPAY_PASSWORD');
        if ($username !== false && $password !== false && $username !== '' && $password !== '') {
            return [(string)$username, (string)$password];
        }
        return null;
    }
}

if (!function_exists('frontoffice_govpay_client_options')) {
    function frontoffice_govpay_client_options(): array
    {
        $options = [];
        $authMethod = getenv('AUTHENTICATION_GOVPAY');
        if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
            $cert = frontoffice_env_value('GOVPAY_TLS_CERT', '');
            $key = frontoffice_env_value('GOVPAY_TLS_KEY', '');
            $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD');
            if ($cert === '' || $key === '') {
                throw new \RuntimeException('mTLS abilitato ma certificati GovPay non configurati');
            }
            $options['cert'] = $cert;
            $options['ssl_key'] = ($keyPass !== false && $keyPass !== null && $keyPass !== '')
                ? [$key, (string)$keyPass]
                : $key;
        }
        return $options;
    }
}

if (!function_exists('frontoffice_pagamenti_api_client')) {
    function frontoffice_pagamenti_api_client(): ?PagamentiPendenzeApi
    {
        $pagamentiUrl = frontoffice_env_value('GOVPAY_PAGAMENTI_URL', '');
        if ($pagamentiUrl === '') {
            Logger::getInstance()->warning('GOVPAY_PAGAMENTI_URL non impostata per il frontoffice');
            return null;
        }
        if (!class_exists(PagamentiPendenzeApi::class)) {
            Logger::getInstance()->warning('Client GovPay Pagamenti non disponibile nel frontoffice');
            return null;
        }

        try {
            $config = new PagamentiConfiguration();
            $config->setHost(rtrim($pagamentiUrl, '/'));
            if ($auth = frontoffice_basic_auth()) {
                $config->setUsername($auth[0]);
                $config->setPassword($auth[1]);
            }
            $client = new Client(frontoffice_govpay_client_options());
            return new PagamentiPendenzeApi($client, $config);
        } catch (\Throwable $e) {
            Logger::getInstance()->warning('Impossibile istanziare il client GovPay Pagamenti', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}

if (!function_exists('frontoffice_get_logged_user')) {
    function frontoffice_get_logged_user(): ?array
    {
        $user = $_SESSION['frontoffice_user'] ?? null;
        return (is_array($user) && $user !== []) ? $user : null;
    }
}

if (!function_exists('frontoffice_get_logged_user_fiscal_number')) {
    function frontoffice_get_logged_user_fiscal_number(): string
    {
        $user = frontoffice_get_logged_user();
        if ($user === null) {
            return '';
        }
        $raw = (string)($user['fiscal_number'] ?? '');
        return strtoupper(preg_replace('/\s+/', '', trim($raw)));
    }
}

if (!function_exists('frontoffice_compose_profiles')) {
    function frontoffice_compose_profiles(): array
    {
        $raw = frontoffice_env_value('COMPOSE_PROFILES', '');
        if ($raw === '') {
            return [];
        }
        $parts = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $raw))));
        return array_values(array_unique($parts));
    }
}

if (!function_exists('frontoffice_spid_mode')) {
    /**
     * @return 'none'|'external'|'internal'
     */
    function frontoffice_spid_mode(): string
    {
        $profiles = frontoffice_compose_profiles();
        if (in_array('none', $profiles, true)) {
            return 'none';
        }
        if (in_array('spid-proxy', $profiles, true)) {
            return 'internal';
        }
        if (in_array('external', $profiles, true)) {
            return 'external';
        }
        return 'none';
    }
}

if (!function_exists('frontoffice_spid_enabled')) {
    function frontoffice_spid_enabled(): bool
    {
        return frontoffice_spid_mode() !== 'none';
    }
}

if (!function_exists('frontoffice_spid_proxy_insecure_ssl')) {
    function frontoffice_spid_proxy_insecure_ssl(string $proxyBaseUrl): bool
    {
        $host = (string)(parse_url($proxyBaseUrl, PHP_URL_HOST) ?: '');
        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }
}

if (!function_exists('frontoffice_http_get')) {
    function frontoffice_http_get(string $url, bool $insecureSsl = false): ?array
    {
        // Preferisci cURL se disponibile (gestione timeouts/SSL più robusta).
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            if ($insecureSsl) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }

            $body = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            if (!is_string($body) || $body === '' || $status < 200 || $status >= 300) {
                if ($err !== '') {
                    Logger::getInstance()->warning('HTTP GET fallita', ['url' => $url, 'error' => $err, 'status' => $status]);
                }
                return null;
            }
            $data = json_decode($body, true);
            return is_array($data) ? $data : null;
        }

        $opts = [
            'http' => [
                'method' => 'GET',
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ];
        if (stripos($url, 'https://') === 0) {
            $opts['ssl'] = [
                'verify_peer' => !$insecureSsl,
                'verify_peer_name' => !$insecureSsl,
            ];
        }
        $ctx = stream_context_create($opts);
        $body = @file_get_contents($url, false, $ctx);
        if (!is_string($body) || $body === '') {
            return null;
        }
        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }
}

if (!function_exists('frontoffice_spid_decode_proxy_token')) {
    /**
     * Decodifica un token (JWS o JWS+JWE) usando l'endpoint verify del proxy.
     * Nota: in DEV con certificati self-signed (localhost) disabilitiamo la verifica SSL.
     */
    function frontoffice_spid_decode_proxy_token(string $proxyBase, string $token, bool $decrypt, string $secret, string $service = 'spid'): ?array
    {
        $proxyBase = rtrim($proxyBase, '/');
        if ($proxyBase === '' || $token === '') {
            return null;
        }
        $decryptFlag = $decrypt ? 'Y' : 'N';
        $url = $proxyBase . '/proxy.php?action=verify'
            . '&token=' . rawurlencode($token)
            . '&decrypt=' . rawurlencode($decryptFlag)
            . '&service=' . rawurlencode($service);
        if ($decrypt) {
            $url .= '&secret=' . rawurlencode($secret);
        }
        return frontoffice_http_get($url, frontoffice_spid_proxy_insecure_ssl($proxyBase));
    }
}

if (!function_exists('frontoffice_normalize_amount')) {
    function frontoffice_normalize_amount($value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }
        if ($value === null || $value === '') {
            return 0.0;
        }
        return is_numeric($value) ? round((float)$value, 2) : 0.0;
    }
}

if (!function_exists('frontoffice_generate_pendenza_id')) {
    function frontoffice_generate_pendenza_id(): string
    {
        try {
            $rand = bin2hex(random_bytes(8));
        } catch (\Throwable $e) {
            $rand = md5((string)microtime(true));
        }
        $candidate = 'GIL-' . substr($rand, 0, 16);
        return substr(preg_replace('/[^A-Za-z0-9\-_]/', '-', $candidate), 0, 35);
    }
}

if (!function_exists('frontoffice_build_voci')) {
    function frontoffice_build_voci(string $idDominio, string $idTipo, string $descrizione, float $importo): array
    {
        $iban = $codCont = $tipoBollo = $tipoCont = '';
        try {
            if ($idDominio !== '' && $idTipo !== '') {
                $repo = new EntrateRepository();
                $details = $repo->findDetails($idDominio, $idTipo);
                if ($details) {
                    $iban = (string)($details['iban_accredito'] ?? '');
                    $codCont = (string)($details['codice_contabilita'] ?? '');
                    $tipoBollo = (string)($details['tipo_bollo'] ?? '');
                    $tipoCont = (string)($details['tipo_contabilita'] ?? '');
                }
            }
        } catch (\Throwable $e) {
            Logger::getInstance()->warning('Impossibile recuperare i dettagli di contabilita', ['error' => $e->getMessage()]);
        }

        $voice = [
            'idVocePendenza' => '1',
            'descrizione' => $descrizione,
            'importo' => $importo,
        ];

        if ($tipoBollo !== '') {
            $voice['tipoBollo'] = $tipoBollo;
        } elseif ($iban !== '' && $tipoCont !== '' && $codCont !== '') {
            $voice['ibanAccredito'] = $iban;
            $voice['tipoContabilita'] = $tipoCont;
            $voice['codiceContabilita'] = $codCont;
        } else {
            $targetCode = $codCont !== '' ? $codCont : $idTipo;
            $voice['codEntrata'] = substr(preg_replace('/[^A-Za-z0-9\-_.]/', '', $targetCode) ?: $idTipo, 0, 35);
        }

        return [$voice];
    }
}

if (!function_exists('frontoffice_prepare_payer')) {
    function frontoffice_prepare_payer(array $raw): array
    {
        $type = strtoupper((string)($raw['tipo'] ?? 'F'));
        if (!in_array($type, ['F', 'G'], true)) {
            $type = 'F';
        }

        $upper = static function (string $value): string {
            $value = trim($value);
            if ($value === '') {
                return '';
            }
            return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
        };

        $ident = strtoupper(preg_replace('/\s+/', '', (string)($raw['identificativo'] ?? '')));
        $surname = $upper((string)($raw['anagrafica'] ?? ''));
        $name = $upper((string)($raw['nome'] ?? ''));
        $anagrafica = $type === 'G' ? $surname : trim(($name !== '' ? $name . ' ' : '') . $surname);
        if ($anagrafica === '') {
            $anagrafica = $surname;
        }

        $payload = [
            'tipo' => $type,
            'identificativo' => $ident,
            'anagrafica' => $anagrafica,
        ];

        $email = trim((string)($raw['email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $payload['email'] = $email;
        }
        $phone = trim((string)($raw['telefono'] ?? ''));
        if ($phone !== '') {
            $payload['cellulare'] = $phone;
        }

        return $payload;
    }
}

if (!function_exists('frontoffice_extract_numero_avviso')) {
    function frontoffice_extract_numero_avviso(?array $response, ?array $detail = null): ?string
    {
        $candidates = [];
        if ($response) {
            $candidates[] = $response['numeroAvviso'] ?? null;
            $candidates[] = $response['numero_avviso'] ?? null;
            $candidates[] = $response['pendenza']['numeroAvviso'] ?? null;
            $candidates[] = $response['pendenza']['numero_avviso'] ?? null;
            if (!empty($response['avvisi'][0]['numeroAvviso'])) {
                $candidates[] = $response['avvisi'][0]['numeroAvviso'];
            }
        }
        if ($detail) {
            $candidates[] = $detail['numeroAvviso'] ?? null;
        }

        foreach ($candidates as $candidate) {
            $value = trim((string)($candidate ?? ''));
            if ($value !== '') {
                return $value;
            }
        }
        return null;
    }
}

if (!function_exists('frontoffice_send_pendenza_to_backoffice')) {
    function frontoffice_send_pendenza_to_backoffice(array $payload): array
    {
        $backofficeUrl = frontoffice_env_value('GOVPAY_BACKOFFICE_URL', '');
        $idA2A = frontoffice_env_value('ID_A2A', '');
        if ($backofficeUrl === '' || $idA2A === '') {
            return ['success' => false, 'errors' => ['Configurazione GovPay incompleta (GOVPAY_BACKOFFICE_URL o ID_A2A mancanti).']];
        }

        $idPendenza = frontoffice_generate_pendenza_id();
        unset($payload['idPendenza']);

        try {
            $client = new Client(frontoffice_govpay_client_options());
            $requestOptions = [
                'headers' => [
                    'Accept' => 'application/json',
                    'Connection' => 'close',
                ],
                'json' => $payload,
            ];
            if ($auth = frontoffice_basic_auth()) {
                $requestOptions['auth'] = $auth;
            }

            $url = rtrim($backofficeUrl, '/') . '/pendenze/' . rawurlencode($idA2A) . '/' . rawurlencode($idPendenza);
            $resp = $client->request('PUT', $url, $requestOptions);
            $data = json_decode((string)$resp->getBody(), true);
            return ['success' => true, 'idPendenza' => $idPendenza, 'response' => $data];
        } catch (ClientException $e) {
            $body = '';
            if ($e->getResponse()) {
                $body = (string)$e->getResponse()->getBody();
            }
            $message = $body !== '' ? $body : $e->getMessage();
            Logger::getInstance()->error('Errore invio pendenza frontoffice', ['error' => $message]);
            return ['success' => false, 'errors' => [Logger::sanitizeErrorForDisplay($message)]];
        } catch (\Throwable $e) {
            Logger::getInstance()->error('Errore inatteso invio pendenza frontoffice', ['error' => $e->getMessage()]);
            return ['success' => false, 'errors' => [Logger::sanitizeErrorForDisplay($e->getMessage())]];
        }
    }
}

if (!function_exists('frontoffice_fetch_pagamenti_detail')) {
    function frontoffice_fetch_pagamenti_detail(string $idPendenza): ?array
    {
        $idA2A = frontoffice_env_value('ID_A2A', '');
        if ($idA2A === '' || $idPendenza === '') {
            return null;
        }
        $api = frontoffice_pagamenti_api_client();
        if ($api === null) {
            return null;
        }
        try {
            $result = $api->getPendenza($idA2A, $idPendenza);
            return json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Logger::getInstance()->warning('Impossibile recuperare il dettaglio della pendenza da GovPay Pagamenti', ['error' => $e->getMessage()]);
            return null;
        }
    }
}

if (!function_exists('frontoffice_pendenza_belongs_to_cf')) {
    function frontoffice_pendenza_belongs_to_cf(array $pendenza, string $codiceFiscale): bool
    {
        $expected = strtoupper(preg_replace('/\s+/', '', trim($codiceFiscale)));
        if ($expected === '') {
            return false;
        }

        $candidates = [];
        foreach (['idDebitore', 'codiceFiscaleDebitore', 'id_debitore'] as $key) {
            if (isset($pendenza[$key]) && is_string($pendenza[$key])) {
                $candidates[] = $pendenza[$key];
            }
        }

        if (isset($pendenza['soggettoPagatore']) && is_array($pendenza['soggettoPagatore'])) {
            foreach (['identificativo', 'identificativoUnivoco', 'codiceFiscale', 'fiscalNumber'] as $key) {
                if (isset($pendenza['soggettoPagatore'][$key]) && is_string($pendenza['soggettoPagatore'][$key])) {
                    $candidates[] = $pendenza['soggettoPagatore'][$key];
                }
            }
        }

        foreach ($candidates as $value) {
            $normalized = strtoupper(preg_replace('/\s+/', '', trim((string)$value)));
            if ($normalized !== '' && $normalized === $expected) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('frontoffice_normalize_avviso_code')) {
    function frontoffice_normalize_avviso_code(string $value): string
    {
        $normalized = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $value));
        return substr($normalized, 0, 35);
    }
}

if (!function_exists('frontoffice_map_pendenza_state')) {
    function frontoffice_map_pendenza_state(string $state): string
    {
        $code = strtoupper(trim($state));
        switch ($code) {
            case 'NON_ESEGUITA':
                return 'Da pagare';
            case 'ESEGUITA':
                return 'Pagata';
            case 'ESEGUITA_PARZIALE':
                return 'Pagata parzialmente';
            case 'ANNULLATA':
                return 'Annullata';
            case 'SCADUTA':
                return 'Scaduta';
            case 'ANOMALA':
                return 'In verifica';
            default:
                return 'Stato sconosciuto';
        }
    }
}

if (!function_exists('frontoffice_is_pendenza_payable')) {
    function frontoffice_is_pendenza_payable(string $state): bool
    {
        $code = strtoupper(trim($state));
        return in_array($code, ['NON_ESEGUITA', 'ESEGUITA_PARZIALE'], true);
    }
}

if (!function_exists('frontoffice_is_pendenza_paid')) {
    function frontoffice_is_pendenza_paid(string $state): bool
    {
        $code = strtoupper(trim($state));
        return $code === 'ESEGUITA';
    }
}

if (!function_exists('frontoffice_extract_ricevuta_identifiers_from_pendenza_detail')) {
    function frontoffice_extract_ricevuta_identifiers_from_pendenza_detail(array $detail): array
    {
        $iuv = trim((string)($detail['iuv'] ?? ''));
        $idRicevuta = trim((string)($detail['idRicevuta'] ?? ''));
        $ccp = '';
        foreach (['ccp', 'codiceContestoPagamento', 'codice_contesto_pagamento'] as $k) {
            if (isset($detail[$k]) && is_scalar($detail[$k])) {
                $ccp = trim((string)$detail[$k]);
                if ($ccp !== '') {
                    break;
                }
            }
        }

        $voci = $detail['voci'] ?? null;
        if (is_array($voci)) {
            foreach ($voci as $voce) {
                if (!is_array($voce)) {
                    continue;
                }
                $riscossione = $voce['riscossione'] ?? null;
                if (!is_array($riscossione)) {
                    continue;
                }
                if ($iuv === '') {
                    $iuv = trim((string)($riscossione['iuv'] ?? ''));
                }
                if ($idRicevuta === '') {
                    $idRicevuta = trim((string)($riscossione['idRicevuta'] ?? ''));
                }
                if ($ccp === '') {
                    foreach (['ccp', 'codiceContestoPagamento', 'codice_contesto_pagamento'] as $k) {
                        if (isset($riscossione[$k]) && is_scalar($riscossione[$k])) {
                            $ccp = trim((string)$riscossione[$k]);
                            if ($ccp !== '') {
                                break;
                            }
                        }
                    }
                }

                if ($iuv !== '' && $idRicevuta !== '' && $ccp !== '') {
                    break;
                }
            }
        }

        return [
            'iuv' => $iuv,
            'idRicevuta' => $idRicevuta,
            'ccp' => $ccp,
        ];
    }
}

if (!function_exists('frontoffice_govpay_pendenze_base_url')) {
    function frontoffice_govpay_pendenze_base_url(): string
    {
        $pendenzeUrl = frontoffice_env_value('GOVPAY_PENDENZE_URL', '');
        return rtrim($pendenzeUrl, '/');
    }
}

if (!function_exists('frontoffice_govpay_pagamenti_base_url')) {
    function frontoffice_govpay_pagamenti_base_url(): string
    {
        $pagamentiUrl = frontoffice_env_value('GOVPAY_PAGAMENTI_URL', '');
        return rtrim($pagamentiUrl, '/');
    }
}

if (!function_exists('frontoffice_fetch_ricevute_for_iuv')) {
    function frontoffice_fetch_ricevute_for_iuv(string $idDominio, string $iuv): ?array
    {
        $baseUrl = frontoffice_govpay_pagamenti_base_url();
        if ($baseUrl === '' || $idDominio === '' || $iuv === '') {
            return null;
        }

        try {
            $client = new Client(frontoffice_govpay_client_options());
            $options = [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ];
            if ($auth = frontoffice_basic_auth()) {
                $options['auth'] = [$auth[0], $auth[1]];
            }
            // Allineato al backoffice (diag): richiedi solo ricevute con esito positivo.
            $options['query'] = ['esito' => 'ESEGUITO'];

            $url = $baseUrl . '/ricevute/' . rawurlencode($idDominio) . '/' . rawurlencode($iuv);
            $response = $client->request('GET', $url, $options);
            $status = $response->getStatusCode();
            if ($status < 200 || $status >= 300) {
                return null;
            }

            $body = (string)$response->getBody();
            if ($body === '') {
                return null;
            }
            $data = json_decode($body, true);
            return is_array($data) ? $data : null;
        } catch (\Throwable $e) {
            Logger::getInstance()->warning('Errore durante la ricerca ricevute GovPay', [
                'idDominio' => $idDominio,
                'iuv' => $iuv,
                'error' => Logger::sanitizeErrorForDisplay($e->getMessage()),
            ]);
            return null;
        }
    }
}

if (!function_exists('frontoffice_stream_rt_pdf')) {
    function frontoffice_stream_rt_pdf(string $idDominio, string $iuv, string $ccp): void
    {
        $baseUrl = frontoffice_govpay_pendenze_base_url();
        if ($idDominio === '' || $iuv === '' || $ccp === '') {
            http_response_code(404);
            echo 'Ricevuta non disponibile.';
            return;
        }
        if ($baseUrl === '') {
            http_response_code(503);
            echo 'Configurazione mancante: GOVPAY_PENDENZE_URL non impostata.';
            return;
        }

        try {
            $client = new Client(frontoffice_govpay_client_options());
            $options = [
                'headers' => [
                    'Accept' => 'application/pdf',
                ],
            ];
            if ($auth = frontoffice_basic_auth()) {
                $options['auth'] = [$auth[0], $auth[1]];
            }

            $url = $baseUrl . '/rpp/' . rawurlencode($idDominio) . '/' . rawurlencode($iuv) . '/' . rawurlencode($ccp) . '/rt';
            $response = $client->request('GET', $url, $options);
            $status = $response->getStatusCode();
            if ($status < 200 || $status >= 300) {
                http_response_code(404);
                echo 'Ricevuta non disponibile.';
                return;
            }

            $contentType = strtolower(implode(' ', $response->getHeader('Content-Type')));
            if ($contentType !== '' && strpos($contentType, 'application/pdf') === false) {
                http_response_code(503);
                echo 'La ricevuta non è disponibile in formato PDF.';
                return;
            }

            $filename = 'rt-' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $iuv . '_' . $ccp) . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('X-Content-Type-Options: nosniff');
            echo (string)$response->getBody();
            return;
        } catch (ClientException $e) {
            http_response_code(($e->getResponse() ? $e->getResponse()->getStatusCode() : 404) ?: 404);
            echo 'Ricevuta non disponibile.';
            return;
        } catch (\Throwable $e) {
            Logger::getInstance()->warning('Errore durante lo scarico PDF RT (Pendenze /rpp)', [
                'idDominio' => $idDominio,
                'iuv' => $iuv,
                'ccp' => $ccp,
                'error' => Logger::sanitizeErrorForDisplay($e->getMessage()),
            ]);
            http_response_code(503);
            echo 'Al momento non riusciamo a scaricare la ricevuta. Riprova più tardi.';
        }
    }
}

if (!function_exists('frontoffice_find_paid_rpp_for_pendenza')) {
    /**
     * Ricava (iuv, ccp) interrogando Pendenze v2 (TransazioniApi::findRpp).
     * Serve come fallback quando il dettaglio Pagamenti non contiene i riferimenti della RT.
     */
    function frontoffice_find_paid_rpp_for_pendenza(string $idDominio, string $idPendenza, ?string $idA2A = null): ?array
    {
        $pendenzeHost = frontoffice_govpay_pendenze_base_url();
        if ($pendenzeHost === '' || $idDominio === '' || $idPendenza === '') {
            return null;
        }
        if (!class_exists('\GovPay\Pendenze\Api\TransazioniApi') || !class_exists('\GovPay\Pendenze\Configuration')) {
            return null;
        }

        try {
            $config = new \GovPay\Pendenze\Configuration();
            $config->setHost(rtrim($pendenzeHost, '/'));
            $guzzleOptions = frontoffice_govpay_client_options();
            if ($auth = frontoffice_basic_auth()) {
                $guzzleOptions['auth'] = [$auth[0], $auth[1]];
            }
            $httpClient = new Client($guzzleOptions);
            $api = new \GovPay\Pendenze\Api\TransazioniApi($httpClient, $config);

            $esito = class_exists('\GovPay\Pendenze\Model\EsitoRpp')
                ? \GovPay\Pendenze\Model\EsitoRpp::ESEGUITO
                : 'ESEGUITO';

            $result = $api->findRpp(
                1,
                25,
                null,
                null,
                $idDominio,
                null,
                null,
                ($idA2A !== null && $idA2A !== '') ? $idA2A : null,
                $idPendenza,
                null,
                $esito
            );

            $data = json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
            $rows = $data['risultati'] ?? $data['rpps'] ?? null;
            if (!is_array($rows) || $rows === []) {
                return null;
            }

            $first = $rows[0] ?? null;
            if (!is_array($first)) {
                return null;
            }

            $pickScalar = static function ($value): string {
                return is_scalar($value) ? trim((string)$value) : '';
            };
            $getPath = static function (array $root, array $path) use ($pickScalar): string {
                $current = $root;
                foreach ($path as $segment) {
                    if (!is_array($current) || !array_key_exists($segment, $current)) {
                        return '';
                    }
                    $current = $current[$segment];
                }
                return $pickScalar($current);
            };

            // GovPay Pendenze v2: i campi possono essere top-level oppure annidati in pendenza/rpt/rt.
            $iuv = $pickScalar($first['iuv'] ?? null);
            if ($iuv === '') {
                foreach ([
                    ['pendenza', 'iuvPagamento'],
                    ['pendenza', 'iuvAvviso'],
                    ['rpt', 'datiVersamento', 'identificativoUnivocoVersamento'],
                    ['rt', 'datiPagamento', 'identificativoUnivocoVersamento'],
                ] as $path) {
                    $iuv = $getPath($first, $path);
                    if ($iuv !== '') {
                        break;
                    }
                }
            }

            $ccp = $pickScalar($first['ccp'] ?? null);
            if ($ccp === '') {
                foreach ([
                    ['codiceContestoPagamento'],
                    ['pendenza', 'codiceContestoPagamento'],
                    ['rpt', 'datiVersamento', 'codiceContestoPagamento'],
                    // In alcune RT il campo arriva con iniziale maiuscola.
                    ['rt', 'datiPagamento', 'CodiceContestoPagamento'],
                    ['rt', 'datiPagamento', 'codiceContestoPagamento'],
                    // Fallback: spesso coincide col CCP.
                    ['rt', 'datiPagamento', 'datiSingoloPagamento', 0, 'identificativoUnivocoRiscossione'],
                ] as $path) {
                    $ccp = $getPath($first, $path);
                    if ($ccp !== '') {
                        break;
                    }
                }
            }

            if ($iuv === '' || $ccp === '') {
                return null;
            }

            return ['iuv' => $iuv, 'ccp' => $ccp];
        } catch (\Throwable $e) {
            Logger::getInstance()->warning('Impossibile recuperare RPP da Pendenze v2 per scarico RT', [
                'idDominio' => $idDominio,
                'idPendenza' => $idPendenza,
                'error' => Logger::sanitizeErrorForDisplay($e->getMessage()),
            ]);
            return null;
        }
    }
}

if (!function_exists('frontoffice_stream_ricevuta_pdf')) {
    function frontoffice_stream_ricevuta_pdf(string $idDominio, string $iuv, string $idRicevuta): void
    {
        $baseUrl = frontoffice_govpay_pagamenti_base_url();
        if ($baseUrl === '' || $idDominio === '' || $iuv === '' || $idRicevuta === '') {
            http_response_code(404);
            echo 'Ricevuta non disponibile.';
            return;
        }

        try {
            $client = new Client(frontoffice_govpay_client_options());
            $options = [
                'headers' => [
                    'Accept' => 'application/pdf',
                ],
            ];
            if ($auth = frontoffice_basic_auth()) {
                $options['auth'] = [$auth[0], $auth[1]];
            }

            $url = $baseUrl . '/ricevute/' . rawurlencode($idDominio) . '/' . rawurlencode($iuv) . '/' . rawurlencode($idRicevuta);
            $response = $client->request('GET', $url, $options);
            $status = $response->getStatusCode();
            if ($status < 200 || $status >= 300) {
                http_response_code(404);
                echo 'Ricevuta non disponibile.';
                return;
            }

            $contentType = strtolower(implode(' ', $response->getHeader('Content-Type')));
            if ($contentType !== '' && strpos($contentType, 'application/pdf') === false) {
                http_response_code(503);
                echo 'La ricevuta non è disponibile in formato PDF.';
                return;
            }

            $filename = 'ricevuta_' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $iuv . '_' . $idRicevuta) . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('X-Content-Type-Options: nosniff');
            echo (string)$response->getBody();
            return;
        } catch (\Throwable $e) {
            Logger::getInstance()->warning('Errore durante lo scarico PDF ricevuta GovPay', [
                'idDominio' => $idDominio,
                'iuv' => $iuv,
                'idRicevuta' => $idRicevuta,
                'error' => Logger::sanitizeErrorForDisplay($e->getMessage()),
            ]);
            http_response_code(503);
            echo 'Al momento non riusciamo a scaricare la ricevuta. Riprova più tardi.';
        }
    }
}

if (!function_exists('frontoffice_build_avviso_preview')) {
    function frontoffice_build_avviso_preview(array $pendenza, string $idDominio): array
    {
        $numeroAvviso = trim((string)($pendenza['numeroAvviso'] ?? ''));
        $downloadUrl = ($numeroAvviso !== '' && $idDominio !== '')
            ? '/avvisi/' . rawurlencode($idDominio) . '/' . rawurlencode($numeroAvviso)
            : null;
        $state = strtoupper((string)($pendenza['stato'] ?? ''));
        $importo = $pendenza['importo'] ?? null;

        $idPendenza = (string)($pendenza['idPendenza'] ?? '');
        $checkoutUrl = null;
        if (frontoffice_is_pendenza_payable($state) && $idPendenza !== '') {
            // Checkout dinamico (server-side) per evitare di esporre la subscription key al browser.
            $checkoutUrl = '/pendenze/' . rawurlencode($idPendenza) . '/checkout';
        } else {
            // Fallback legacy: link statico al portale checkout (solo inserimento dati avviso).
            $checkoutUrl = frontoffice_env_value(
                'FRONTOFFICE_PAGOPA_CHECKOUT_URL',
                'https://checkout.pagopa.it/inserisci-dati-avviso'
            );
        }

        return [
            'numero_avviso' => $numeroAvviso,
            'importo' => is_numeric($importo) ? (float)$importo : null,
            'causale' => trim((string)($pendenza['causale'] ?? '')),
            'id_pendenza' => $idPendenza,
            'id_a2a' => (string)($pendenza['idA2A'] ?? ''),
            'data_validita' => $pendenza['dataValidita'] ?? null,
            'data_scadenza' => $pendenza['dataScadenza'] ?? null,
            'soggetto_pagatore' => $pendenza['soggettoPagatore'] ?? null,
            'stato' => [
                'code' => $state,
                'label' => frontoffice_map_pendenza_state($state),
            ],
            'is_payable' => frontoffice_is_pendenza_payable($state),
            'is_paid' => frontoffice_is_pendenza_paid($state),
            'download_url' => $downloadUrl,
            'receipt_url' => (frontoffice_is_pendenza_paid($state) && (string)($pendenza['idPendenza'] ?? '') !== '')
                ? '/pendenze/' . rawurlencode((string)$pendenza['idPendenza']) . '/ricevuta'
                : null,
            'voci' => $pendenza['voci'] ?? [],
            'id_dominio' => $idDominio,
            'checkout_url' => $checkoutUrl,
        ];
    }
}

if (!function_exists('frontoffice_amount_to_cents')) {
    function frontoffice_amount_to_cents(float $amount): int
    {
        return (int)max(0, (int)round($amount * 100));
    }
}

if (!function_exists('frontoffice_pagopa_checkout_api_client')) {
    function frontoffice_pagopa_checkout_api_client(): ?\PagoPA\CheckoutEc\Api\DefaultApi
    {
        if (!class_exists(\PagoPA\CheckoutEc\Api\DefaultApi::class)) {
            return null;
        }

        $subscriptionKey = trim(frontoffice_env_value('PAGOPA_CHECKOUT_SUBSCRIPTION_KEY', ''));
        if ($subscriptionKey === '') {
            return null;
        }

        $config = \PagoPA\CheckoutEc\Configuration::getDefaultConfiguration();
        $host = trim(frontoffice_env_value('PAGOPA_CHECKOUT_EC_BASE_URL', ''));
        if ($host !== '') {
            $config->setHost(rtrim($host, '/'));
        }

        // La spec supporta sia header che query key; impostiamo entrambe allo stesso valore.
        $config->setApiKey('Ocp-Apim-Subscription-Key', $subscriptionKey);
        $config->setApiKey('subscription-key', $subscriptionKey);

        $httpClient = new \GuzzleHttp\Client([
            'timeout' => 15,
            'connect_timeout' => 10,
            'allow_redirects' => false,
        ]);

        return new \PagoPA\CheckoutEc\Api\DefaultApi($httpClient, $config);
    }
}

if (!function_exists('frontoffice_lookup_pagopa_avviso')) {
    function frontoffice_lookup_pagopa_avviso(string $numeroAvviso, string $codiceFiscale): array
    {
        $idDominio = frontoffice_env_value('ID_DOMINIO', '');
        if ($idDominio === '') {
            return [
                'success' => false,
                'errors' => ['Configurazione mancante: ID_DOMINIO non impostato.'],
            ];
        }

        $api = frontoffice_pagamenti_api_client();
        if ($api === null) {
            return [
                'success' => false,
                'errors' => ['Al momento non riusciamo a interrogare il sistema dei pagamenti. Riprova più tardi.'],
            ];
        }

        Logger::getInstance()->info('Ricerca avviso PagoPA avviata', [
            'idDominio' => $idDominio,
            'numeroAvviso' => $numeroAvviso,
            'identificativoPagatore' => $codiceFiscale,
        ]);

        $normalizedAvviso = frontoffice_normalize_avviso_code($numeroAvviso);

        try {
            $result = $api->findPendenze(
                1,
            10,
                null,
                $idDominio,
                null,
                null,
            $normalizedAvviso,
                null,
                null,
                $codiceFiscale,
                null,
                null,
                null,
                null,
                false,
                true,
                true
            );
            $data = json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Logger::getInstance()->warning('Errore durante la ricerca dell\'avviso PagoPA', [
                'codiceAvviso' => $numeroAvviso,
                'error' => Logger::sanitizeErrorForDisplay($e->getMessage()),
            ]);
            return [
                'success' => false,
                'errors' => ['Al momento non riusciamo a interrogare il sistema dei pagamenti. Riprova più tardi.'],
            ];
        }

        $risultati = $data['risultati'] ?? [];
        $loggerPayload = [
            'numRisultati' => $data['numRisultati'] ?? null,
            'risultatiPerPagina' => $data['risultatiPerPagina'] ?? null,
            'pagina' => $data['pagina'] ?? null,
            'risultatiCount' => is_array($risultati) ? count($risultati) : 0,
            'requestedAvviso' => $normalizedAvviso,
        ];
        Logger::getInstance()->info('Risposta GovPay Pagamenti per ricerca avviso', $loggerPayload);

        $pendenza = null;
        foreach ($risultati as $candidate) {
            $candidateAvviso = frontoffice_normalize_avviso_code((string)($candidate['numeroAvviso'] ?? ''));
            if ($candidateAvviso !== '' && $candidateAvviso === $normalizedAvviso) {
                $pendenza = $candidate;
                break;
            }
        }
        if (!$pendenza) {
            Logger::getInstance()->info('Nessun avviso corrispondente trovato dal frontoffice', $loggerPayload);
            return [
                'success' => false,
                'errors' => ['Nessun avviso trovato con i dati inseriti.'],
            ];
        }

        $payerId = strtoupper((string)($pendenza['soggettoPagatore']['identificativo'] ?? ''));
        if ($payerId !== '' && $payerId !== strtoupper($codiceFiscale)) {
            Logger::getInstance()->warning('Identificativo pagatore non coincide con l\'input', [
                'pagatore' => $payerId,
                'identificativoInput' => $codiceFiscale,
                'numeroAvviso' => $numeroAvviso,
            ]);
            return [
                'success' => false,
                'errors' => ['Il codice fiscale o la partita IVA indicata non coincide con il soggetto pagatore dell\'avviso.'],
            ];
        }

        $state = strtoupper((string)($pendenza['stato'] ?? ''));
        if (!frontoffice_is_pendenza_payable($state) && $state !== 'ESEGUITA') {
            Logger::getInstance()->info('Avviso trovato ma non pagabile/pagato', [
                'numeroAvviso' => $numeroAvviso,
                'stato' => $state,
            ]);
            return [
                'success' => false,
                'errors' => ['Nessun avviso trovato con i dati inseriti.'],
            ];
        }

        Logger::getInstance()->info('Avviso PagoPA recuperato dal frontoffice', [
            'numeroAvviso' => $numeroAvviso,
            'idPendenza' => $pendenza['idPendenza'] ?? null,
            'stato' => $state,
        ]);

        return [
            'success' => true,
            'preview' => frontoffice_build_avviso_preview($pendenza, $idDominio),
            'pendenza' => $pendenza,
        ];
    }
}

if (!function_exists('frontoffice_process_avviso_form')) {
    function frontoffice_process_avviso_form(array $data): array
    {
        $codiceAvviso = frontoffice_normalize_avviso_code((string)($data['codiceAvviso'] ?? ''));
        $rawIdentificativo = (string)($data['codiceFiscale'] ?? '');
        $identificativo = strtoupper(preg_replace('/\s+/', '', trim($rawIdentificativo)));
        $identificativoDigits = preg_replace('/\D+/', '', $identificativo);

        $lookupIdentificativo = $identificativo;
        if ($identificativoDigits !== '' && strlen($identificativoDigits) === 11) {
            $lookupIdentificativo = $identificativoDigits;
        }
        $formData = [
            'codiceAvviso' => $codiceAvviso,
            'codiceFiscale' => $lookupIdentificativo,
        ];

        $errors = [];
        if ($codiceAvviso === '' || strlen($codiceAvviso) < 13) {
            $errors[] = 'Inserisci un codice avviso valido (almeno 13 caratteri alfanumerici).';
        }

        if ($identificativo === '') {
            $errors[] = 'Il codice fiscale o la partita IVA del pagatore è obbligatorio.';
        } elseif (preg_match('/^[A-Z0-9]{16}$/', $identificativo) === 1) {
            $validation = ValidationService::validateCodiceFiscale($identificativo, '', '');
            if (!$validation['format_ok'] || !$validation['check_ok'] || !$validation['valid']) {
                $errors[] = $validation['message'] ?? 'Codice fiscale non valido.';
            }
        } elseif ($identificativoDigits !== '' && strlen($identificativoDigits) === 11) {
            $validation = ValidationService::validatePartitaIva($identificativoDigits);
            if (!$validation['valid']) {
                $errors[] = $validation['message'] ?? 'Partita IVA non valida.';
            }
        } else {
            $errors[] = 'Inserisci un codice fiscale (16 caratteri) o una partita IVA (11 cifre) valida.';
        }

        if ($errors) {
            return [
                'success' => false,
                'errors' => $errors,
                'form_data' => $formData,
            ];
        }

        $lookup = frontoffice_lookup_pagopa_avviso($codiceAvviso, $lookupIdentificativo);
        $lookup['form_data'] = $formData;
        return $lookup;
    }
}

if (!function_exists('frontoffice_process_spontaneous_request')) {
    function frontoffice_process_spontaneous_request(array $data, array $serviceOptions): array
    {
        $context = ['form_data' => $data];
        $errors = [];
        $serviceMap = [];
        foreach ($serviceOptions as $option) {
            $serviceMap[$option['id']] = $option;
        }

        $idTipo = trim((string)($data['idTipoPendenza'] ?? ''));
        if ($idTipo === '' || !isset($serviceMap[$idTipo])) {
            $errors[] = 'Seleziona il servizio da pagare.';
        } else {
            $selectedOption = $serviceMap[$idTipo];
            if (($selectedOption['type'] ?? 'internal') !== 'internal') {
                $errors[] = 'La tipologia selezionata non può essere compilata su questo portale.';
            }
        }

        $causale = trim((string)($data['causale'] ?? ''));
        if ($causale === '') {
            $errors[] = 'La causale è obbligatoria.';
        } elseif (!ValidationService::validateCausaleLength($causale)) {
            $errors[] = 'La causale può contenere al massimo 140 caratteri.';
        }

        $importo = frontoffice_normalize_amount($data['importo'] ?? null);
        if ($importo <= 0) {
            $errors[] = 'Inserisci un importo valido (maggiore di zero).';
        }

        $defaultYear = (int)date('Y');
        $annoRaw = $data['annoRiferimento'] ?? $defaultYear;
        $anno = is_scalar($annoRaw) && is_numeric((string)$annoRaw) ? (int)$annoRaw : 0;
        if ($anno < $defaultYear - 5 || $anno > $defaultYear + 1) {
            $errors[] = 'Anno di riferimento non valido.';
        }

        if (empty($data['privacy'])) {
            $errors[] = 'Devi accettare l\'informativa privacy per proseguire.';
        }

        $payerRaw = is_array($data['soggettoPagatore'] ?? null) ? $data['soggettoPagatore'] : [];
        $payerType = strtoupper((string)($payerRaw['tipo'] ?? 'F'));
        if (!in_array($payerType, ['F', 'G'], true)) {
            $payerType = 'F';
        }
        $ident = trim((string)($payerRaw['identificativo'] ?? ''));
        if ($ident === '') {
            $errors[] = $payerType === 'G' ? 'La partita IVA è obbligatoria.' : 'Il codice fiscale è obbligatorio.';
        } else {
            if ($payerType === 'F') {
                $validation = ValidationService::validateCodiceFiscale($ident, $payerRaw['nome'] ?? '', $payerRaw['anagrafica'] ?? '');
                if (!$validation['format_ok'] || !$validation['check_ok'] || !$validation['valid']) {
                    $errors[] = $validation['message'] ?? 'Codice fiscale non valido.';
                }
            } else {
                $validation = ValidationService::validatePartitaIva($ident);
                if (!$validation['valid']) {
                    $errors[] = $validation['message'] ?? 'Partita IVA non valida.';
                }
            }
        }

        $surname = trim((string)($payerRaw['anagrafica'] ?? ''));
        $name = trim((string)($payerRaw['nome'] ?? ''));
        if ($surname === '') {
            $errors[] = $payerType === 'G' ? 'La ragione sociale è obbligatoria.' : 'Il cognome è obbligatorio.';
        }
        if ($payerType === 'F' && $name === '') {
            $errors[] = 'Il nome è obbligatorio per le persone fisiche.';
        }
        $email = trim((string)($payerRaw['email'] ?? ''));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Inserisci un indirizzo email valido.';
        }

        $idDominio = frontoffice_env_value('ID_DOMINIO', '');
        if ($idDominio === '') {
            $errors[] = 'Configurazione mancante: ID_DOMINIO non impostato.';
        }

        if ($errors) {
            $context['form_errors'] = $errors;
            $context['form_feedback'] = [
                'type' => 'danger',
                'title' => 'Controlla i dati inseriti',
                'message' => 'Alcuni campi non sono corretti. Correggili e riprova.',
            ];
            return $context;
        }

        // Scadenza automatica: oggi + 15 giorni (trasparente per l'utente)
        $dataScadenza = (new \DateTimeImmutable('today'))->modify('+15 days')->format('Y-m-d');

        $payload = [
            'idTipoPendenza' => $idTipo,
            'idDominio' => $idDominio,
            'causale' => $causale,
            'importo' => $importo,
            'annoRiferimento' => $anno,
            'soggettoPagatore' => frontoffice_prepare_payer($payerRaw),
            'voci' => frontoffice_build_voci($idDominio, $idTipo, $causale, $importo),
            'dataValidita' => date('Y-m-d'),
            'dataScadenza' => $dataScadenza,
        ];

        $note = trim((string)($data['noteRichiedente'] ?? ''));
        if ($note !== '') {
            $payload['datiAllegati'] = ['noteRichiedente' => mb_substr($note, 0, 400)];
        }

        $sendResult = frontoffice_send_pendenza_to_backoffice($payload);
        if (!$sendResult['success']) {
            $context['form_errors'] = $sendResult['errors'] ?? ['Invio pendenza non riuscito.'];
            $context['form_feedback'] = [
                'type' => 'danger',
                'title' => 'Invio non riuscito',
                'message' => implode(' ', $context['form_errors']),
            ];
            return $context;
        }

        $idPendenza = $sendResult['idPendenza'] ?? '';
        $detail = frontoffice_fetch_pagamenti_detail($idPendenza);
        $numeroAvviso = frontoffice_extract_numero_avviso($sendResult['response'] ?? null, $detail);
        $downloadUrl = ($numeroAvviso && $idDominio !== '')
            ? '/avvisi/' . rawurlencode($idDominio) . '/' . rawurlencode($numeroAvviso)
            : null;

        $context['pendenza_result'] = [
            'idPendenza' => $idPendenza,
            'numeroAvviso' => $numeroAvviso,
            'importo' => $importo,
            'causale' => $causale,
            'download_url' => $downloadUrl,
            'data_scadenza' => $detail['dataScadenza'] ?? $dataScadenza,
            'soggetto_pagatore' => $payload['soggettoPagatore'],
        ];

        $context['form_feedback'] = [
            'type' => 'success',
            'title' => 'Avviso generato',
            'message' => 'Abbiamo creato il tuo avviso PagoPA. Puoi scaricarlo subito oppure proseguire con il pagamento online.',
        ];
        $context['form_data'] = [];

        return $context;
    }
}

if (!function_exists('frontoffice_stream_avviso_pdf')) {
    function frontoffice_stream_avviso_pdf(string $idDominio, string $numeroAvviso): void
    {
        $expectedDominio = frontoffice_env_value('ID_DOMINIO', '');
        if ($expectedDominio !== '' && $idDominio !== $expectedDominio) {
            http_response_code(404);
            echo 'Avviso non trovato';
            return;
        }

        $backofficeUrl = frontoffice_env_value('GOVPAY_BACKOFFICE_URL', '');
        if ($backofficeUrl === '') {
            http_response_code(500);
            echo 'GOVPAY_BACKOFFICE_URL non impostata';
            return;
        }

        try {
            $options = frontoffice_govpay_client_options();
            $options['headers']['Accept'] = 'application/pdf';
            $options['headers']['Connection'] = 'close';
            if ($auth = frontoffice_basic_auth()) {
                $options['auth'] = $auth;
            }
            $client = new Client($options);
            $url = rtrim($backofficeUrl, '/') . '/avvisi/' . rawurlencode($idDominio) . '/' . rawurlencode($numeroAvviso);
            $resp = $client->request('GET', $url);
            header('Content-Type: ' . ($resp->getHeaderLine('Content-Type') ?: 'application/pdf'));
            header('Content-Disposition: attachment; filename="avviso-' . $idDominio . '-' . $numeroAvviso . '.pdf"');
            header('Cache-Control: no-store');
            echo (string)$resp->getBody();
        } catch (ClientException $e) {
            $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 502;
            http_response_code($status);
            echo 'Errore scaricamento avviso: ' . Logger::sanitizeErrorForDisplay($e->getMessage());
        } catch (\Throwable $e) {
            http_response_code(502);
            echo 'Errore scaricamento avviso: ' . Logger::sanitizeErrorForDisplay($e->getMessage());
        }
    }
}

$entityName = trim($env('APP_ENTITY_NAME', 'Comune di Montesilvano'));
$entitySuffix = trim($env('APP_ENTITY_SUFFIX', 'Provincia di Pescara'));
$entityGovernment = trim($env('APP_ENTITY_GOVERNMENT', 'Regione Abruzzo'));
$entityFull = trim($entityName . ($entitySuffix !== '' ? ' - ' . $entitySuffix : '')) ?: $entityGovernment;

$documentRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$imgCandidates = [
    $documentRoot . '/img',
    __DIR__ . '/img',
    dirname(__DIR__) . '/img',
    dirname(__DIR__, 2) . '/public/img',
    dirname(__DIR__, 2) . '/img',
];
$imgDir = null;
foreach ($imgCandidates as $candidate) {
    if ($candidate && is_dir($candidate)) {
        $imgDir = $candidate;
        break;
    }
}
if ($imgDir === null) {
    $imgDir = $documentRoot . '/img';
}

$customLogoPath = $imgDir . '/stemma_ente.png';
$appLogo = file_exists($customLogoPath)
    ? ['type' => 'img', 'src' => '/img/stemma_ente.png']
    : ['type' => 'sprite', 'src' => '/assets/bootstrap-italia/svg/sprites.svg#it-pa'];

$faviconCandidates = [
    ['href' => '/img/favicon.ico', 'path' => $imgDir . '/favicon.ico', 'type' => 'image/x-icon'],
    ['href' => '/img/favicon.png', 'path' => $imgDir . '/favicon.png', 'type' => 'image/png'],
];
$appFavicon = ['href' => '/img/favicon_default.png', 'type' => 'image/png'];
foreach ($faviconCandidates as $candidate) {
    if (file_exists($candidate['path'])) {
        $appFavicon = ['href' => $candidate['href'], 'type' => $candidate['type']];
        break;
    }
}

$supportEmail = 'pagamenti@' . preg_replace('/[^a-z0-9]+/', '', strtolower($entityName ?: 'ente')) . '.it';

$serviceCatalog = frontoffice_load_service_options();
$serviceInternalOptions = array_values(array_filter($serviceCatalog, static fn ($opt) => ($opt['type'] ?? 'internal') === 'internal'));
$serviceExternalOptions = array_values(array_filter($serviceCatalog, static fn ($opt) => ($opt['type'] ?? 'internal') === 'external'));

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$requestPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';
$normalizedPath = rtrim($requestPath, '/');
if ($normalizedPath === '') {
    $normalizedPath = '/';
}

$frontofficeBaseUrl = rtrim($env('FRONTOFFICE_PUBLIC_BASE_URL', ''), '/');
if ($frontofficeBaseUrl === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if ($host !== '') {
        $frontofficeBaseUrl = $scheme . '://' . $host;
    }
}
$spidCallbackPath = $env('FRONTOFFICE_SPID_CALLBACK_PATH', '/spid/callback');
if ($spidCallbackPath === '' || $spidCallbackPath[0] !== '/') {
    $spidCallbackPath = '/' . ltrim($spidCallbackPath, '/');
}
$spidCallbackUrl = $frontofficeBaseUrl !== '' ? ($frontofficeBaseUrl . $spidCallbackPath) : '';

$routes = [
    '/' => static fn (): array => [
        'template' => 'home.html.twig',
        'context' => [],
    ],
    '/login' => static function () use ($env, $spidCallbackUrl): array {
        if (!frontoffice_spid_enabled()) {
            http_response_code(404);
            return [
                'template' => 'errors/404.html.twig',
                'context' => [
                    'requested_path' => '/login',
                ],
            ];
        }

        $proxyBase = rtrim($env('SPID_PROXY_PUBLIC_BASE_URL', ''), '/');
        $clientId = $env('SPID_PROXY_CLIENT_ID', '');

        $signResponse = trim((string)$env('SPID_PROXY_SIGN_RESPONSE', '1')) === '1';
        $encryptResponse = trim((string)$env('SPID_PROXY_ENCRYPT_RESPONSE', '0')) === '1';
        $clientSecret = trim((string)$env('SPID_PROXY_CLIENT_SECRET', ''));

        if ($encryptResponse && !$signResponse) {
            http_response_code(503);
            return [
                'template' => 'errors/503.html.twig',
                'context' => [
                    'message' => 'Login SPID non configurato: SPID_PROXY_ENCRYPT_RESPONSE=1 richiede anche SPID_PROXY_SIGN_RESPONSE=1.',
                ],
            ];
        }
        if ($encryptResponse && $clientSecret === '') {
            http_response_code(503);
            return [
                'template' => 'errors/503.html.twig',
                'context' => [
                    'message' => 'Login SPID non configurato: SPID_PROXY_ENCRYPT_RESPONSE=1 richiede SPID_PROXY_CLIENT_SECRET (la stessa chiave va configurata anche lato proxy).',
                ],
            ];
        }

        $redirectUri = $env('FRONTOFFICE_SPID_REDIRECT_URI', '');
        $allowedRedirectsRaw = $env('SPID_PROXY_REDIRECT_URIS', '');
        $allowedRedirects = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $allowedRedirectsRaw))));

        if ($redirectUri === '') {
            // Default: usa SEMPRE la callback del frontoffice.
            // Se SPID_PROXY_REDIRECT_URIS è valorizzato, deve includere questo URL (match esatto), altrimenti
            // il proxy rifiuterà il redirect o finirai su un endpoint "demo" tipo /proxy-sample.php.
            $redirectUri = $spidCallbackUrl;
            if ($redirectUri !== '' && $allowedRedirects !== [] && !in_array($redirectUri, $allowedRedirects, true)) {
                http_response_code(503);
                return [
                    'template' => 'errors/503.html.twig',
                    'context' => [
                        'message' => 'Login SPID non configurato: SPID_PROXY_REDIRECT_URIS deve includere la callback del frontoffice (' . $redirectUri . ').',
                    ],
                ];
            }
        }

        // Se l'utente ha impostato un redirect esplicito, verifica comunque che sia autorizzato dal proxy.
        if ($redirectUri !== '' && $allowedRedirects !== [] && !in_array($redirectUri, $allowedRedirects, true)) {
            http_response_code(503);
            return [
                'template' => 'errors/503.html.twig',
                'context' => [
                    'message' => 'Login SPID non configurato: FRONTOFFICE_SPID_REDIRECT_URI non è presente in SPID_PROXY_REDIRECT_URIS (' . $redirectUri . ').',
                ],
            ];
        }

        if ($proxyBase === '' || $clientId === '' || $redirectUri === '') {
            http_response_code(503);
            return [
                'template' => 'errors/503.html.twig',
                'context' => [
                    'message' => 'Login SPID non configurato: verifica SPID_PROXY_PUBLIC_BASE_URL, SPID_PROXY_CLIENT_ID e configura un redirect URI valido (FRONTOFFICE_SPID_REDIRECT_URI oppure SPID_PROXY_REDIRECT_URIS).',
                ],
            ];
        }

        try {
            $state = bin2hex(random_bytes(16));
        } catch (\Throwable $e) {
            $state = md5((string)microtime(true));
        }

        $returnTo = (string)($_GET['return_to'] ?? '/');
        if ($returnTo === '' || $returnTo[0] !== '/') {
            $returnTo = '/';
        }
        $_SESSION['spid_state'] = $state;
        $_SESSION['spid_return_to'] = $returnTo;

        $target = $proxyBase . '/proxy-home.php'
            . '?client_id=' . rawurlencode($clientId)
            . '&redirect_uri=' . rawurlencode($redirectUri)
            . '&state=' . rawurlencode($state);

        header('Location: ' . $target, true, 302);
        exit;
    },
    '/spid/callback' => static function () use ($method, $env): array {
        if (!frontoffice_spid_enabled()) {
            http_response_code(404);
            return [
                'template' => 'errors/404.html.twig',
                'context' => [
                    'requested_path' => '/spid/callback',
                ],
            ];
        }

        if ($method !== 'POST') {
            http_response_code(405);
            return [
                'template' => 'errors/404.html.twig',
                'context' => [
                    'requested_path' => '/spid/callback',
                ],
            ];
        }

        $state = (string)($_POST['state'] ?? '');
        $expectedState = (string)($_SESSION['spid_state'] ?? '');
        if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
            http_response_code(400);
            return [
                'template' => 'errors/503.html.twig',
                'context' => [
                    'message' => 'Callback SPID non valida (state mismatch). Riprovare il login.',
                ],
            ];
        }

        $providerId = (string)($_POST['providerId'] ?? '');
        $providerName = (string)($_POST['providerName'] ?? '');
        $responseId = (string)($_POST['responseId'] ?? '');

        $token = isset($_POST['data']) && is_string($_POST['data']) ? trim($_POST['data']) : '';
        $attrs = null;
        if ($token !== '') {
            $proxyBase = rtrim((string)$env('SPID_PROXY_PUBLIC_BASE_URL', ''), '/');
            $encryptResponse = trim((string)$env('SPID_PROXY_ENCRYPT_RESPONSE', '0')) === '1';
            $clientSecret = trim((string)$env('SPID_PROXY_CLIENT_SECRET', ''));
            if ($encryptResponse && $clientSecret === '') {
                http_response_code(503);
                return [
                    'template' => 'errors/503.html.twig',
                    'context' => [
                        'message' => 'Callback SPID non valida: response cifrata ma SPID_PROXY_CLIENT_SECRET non è configurato.',
                    ],
                ];
            }

            $service = (stripos($providerId, 'CIE') === 0) ? 'cie' : 'spid';
            $decoded = frontoffice_spid_decode_proxy_token($proxyBase, $token, $encryptResponse, $clientSecret, $service);
            if (!is_array($decoded) || !isset($decoded['data']) || !is_array($decoded['data'])) {
                http_response_code(503);
                return [
                    'template' => 'errors/503.html.twig',
                    'context' => [
                        'message' => 'Callback SPID non valida: impossibile decodificare la response del proxy. Verifica SPID_PROXY_SIGN_RESPONSE/SPID_PROXY_ENCRYPT_RESPONSE e la chiave SPID_PROXY_CLIENT_SECRET.',
                    ],
                ];
            }
            $attrs = $decoded['data'];
        }

        $user = [
            'first_name' => (string)(($attrs['name'] ?? null) ?? ($_POST['name'] ?? '')),
            'last_name' => (string)(($attrs['familyName'] ?? null) ?? ($_POST['familyName'] ?? '')),
            'email' => (string)(($attrs['email'] ?? null) ?? ($_POST['email'] ?? '')),
            'fiscal_number' => (string)(($attrs['fiscalNumber'] ?? null) ?? ($_POST['fiscalNumber'] ?? '')),
            'spid_code' => (string)(($attrs['spidCode'] ?? null) ?? ($_POST['spidCode'] ?? '')),
            'provider_id' => $providerId,
            'provider_name' => $providerName,
            'response_id' => $responseId,
        ];

        if ($token !== '') {
            $user['token'] = $token;
        }

        $_SESSION['frontoffice_user'] = $user;
        unset($_SESSION['spid_state']);
        $returnTo = (string)($_SESSION['spid_return_to'] ?? '/');
        unset($_SESSION['spid_return_to']);
        if ($returnTo === '' || $returnTo[0] !== '/') {
            $returnTo = '/';
        }

        header('Location: ' . $returnTo, true, 302);
        exit;
    },
    '/profile' => static function (): array {
        if (!frontoffice_spid_enabled()) {
            http_response_code(404);
            return [
                'template' => 'errors/404.html.twig',
                'context' => [
                    'requested_path' => '/profile',
                ],
            ];
        }

        $user = $_SESSION['frontoffice_user'] ?? null;
        if (!is_array($user) || $user === []) {
            header('Location: /login?return_to=%2Fprofile', true, 302);
            exit;
        }
        return [
            'template' => 'profile.html.twig',
            'context' => [
                'profile' => $user,
            ],
        ];
    },
    '/logout' => static function () use ($env, $frontofficeBaseUrl): array {
        if (!frontoffice_spid_enabled()) {
            http_response_code(404);
            return [
                'template' => 'errors/404.html.twig',
                'context' => [
                    'requested_path' => '/logout',
                ],
            ];
        }

        unset($_SESSION['frontoffice_user']);

        // Prova logout remoto via SPID proxy (IdP logout). Se non configurato, fallback a logout locale.
        $proxyBase = rtrim($env('SPID_PROXY_PUBLIC_BASE_URL', ''), '/');
        $clientId = trim($env('SPID_PROXY_CLIENT_ID', ''));

        $returnTo = trim((string)($_GET['return_to'] ?? '/'));
        if ($returnTo === '' || $returnTo[0] !== '/') {
            $returnTo = '/';
        }

        $frontofficeBase = rtrim((string)$frontofficeBaseUrl, '/');
        $redirectUri = $frontofficeBase !== '' ? ($frontofficeBase . $returnTo) : '';

        // Chiudi la sessione PHP locale (così il logout è immediato anche se il proxy impiega qualche redirect).
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
        }

        if ($proxyBase !== '' && $clientId !== '' && $redirectUri !== '') {
            try {
                $state = bin2hex(random_bytes(16));
            } catch (\Throwable $e) {
                $state = md5((string)microtime(true));
            }
            $target = $proxyBase . '/proxy.php'
                . '?action=logout'
                . '&client_id=' . rawurlencode($clientId)
                . '&redirect_uri=' . rawurlencode($redirectUri)
                . '&state=' . rawurlencode($state);

            header('Location: ' . $target, true, 302);
            exit;
        }

        header('Location: ' . $returnTo, true, 302);
        exit;
    },
    '/pagamento-spontaneo' => static function () use ($method, $serviceCatalog, $serviceInternalOptions, $serviceExternalOptions, $env): array {
        $defaultYear = (int) date('Y');
        $payPortalUrl = $env('FRONTOFFICE_PAGOPA_CHECKOUT_URL', 'https://checkout.pagopa.it/');
        $selectedId = $method === 'POST'
            ? trim((string)($_POST['idTipoPendenza'] ?? ''))
            : trim((string)($_GET['tipologia'] ?? ''));

        $selectedService = $selectedId !== ''
            ? frontoffice_find_service_option($serviceCatalog, $selectedId)
            : null;
        $selectionError = null;

        if ($method !== 'POST' && $selectedService && ($selectedService['type'] ?? 'internal') !== 'internal') {
            $target = trim((string)($selectedService['external_url'] ?? ''));
            if ($target !== '') {
                header('Location: ' . $target, true, 302);
                exit;
            }
            Logger::getInstance()->warning('Tipologia esterna priva di URL', ['id' => $selectedService['id']]);
            $selectionError = 'La tipologia selezionata non è disponibile al momento.';
            $selectedService = null;
        }

        $showForm = $method === 'POST' || ($selectedService && ($selectedService['type'] ?? 'internal') === 'internal');

        if (!$showForm) {
            return [
                'template' => 'pagamenti/spontaneo-list.html.twig',
                'context' => [
                    // Lista unica ordinata alfabeticamente (frontoffice_load_service_options() ritorna gia' A->Z)
                    // Manteniamo la caratterizzazione grafica tramite service.type (internal/external)
                    'internal_services' => $serviceCatalog,
                    'external_services' => [],
                    'selection_error' => $selectionError ?? ($selectedId !== '' ? 'La tipologia selezionata non è disponibile.' : null),
                ],
            ];
        }

        $baseContext = [
            'service_options' => $serviceInternalOptions,
            'default_year' => $defaultYear,
            'pay_portal_url' => $payPortalUrl,
            'selected_service' => $selectedService,
        ];

        if ($method !== 'POST' && $selectedService) {
            $baseContext['form_data'] = array_merge(['idTipoPendenza' => $selectedService['id']], $baseContext['form_data'] ?? []);
        }

        if ($method === 'POST') {
            $result = frontoffice_process_spontaneous_request($_POST, $serviceInternalOptions);
            $baseContext = array_merge($baseContext, $result);
            $resolvedId = (string)($baseContext['form_data']['idTipoPendenza'] ?? $selectedId);
            if ($resolvedId !== '') {
                $baseContext['selected_service'] = frontoffice_find_service_option($serviceCatalog, $resolvedId);
            }
            if (!empty($baseContext['selected_service'])) {
                $baseContext['form_data']['idTipoPendenza'] = $baseContext['selected_service']['id'];
            }
        } else {
            $baseContext['form_data'] = $baseContext['form_data'] ?? ['idTipoPendenza' => $selectedService['id'] ?? ''];
        }

        return [
            'template' => 'pagamenti/spontaneo.html.twig',
            'context' => $baseContext,
        ];
    },
    '/pagamento-avviso' => static function () use ($method): array {
        if ($method === 'POST') {
            $result = frontoffice_process_avviso_form($_POST);
            if (!empty($result['success'])) {
                return [
                    'template' => 'pagamenti/avviso-preview.html.twig',
                    'context' => [
                        'avviso_preview' => $result['preview'] ?? [],
                        'pendenza' => $result['pendenza'] ?? [],
                        'back_url' => '/pagamento-avviso',
                        'search_params' => $result['form_data'] ?? [],
                    ],
                ];
            }

            $errors = $result['errors'] ?? ['Non è stato possibile verificare l\'avviso.'];
            $message = implode(' ', array_unique($errors));

            return [
                'template' => 'pagamenti/avviso.html.twig',
                'context' => [
                    'form_submitted' => true,
                    'form_data' => $result['form_data'] ?? $_POST,
                    'form_errors' => $errors,
                    'form_feedback' => [
                        'type' => 'danger',
                        'title' => 'Verifica non riuscita',
                        'message' => $message,
                    ],
                ],
            ];
        }

        return [
            'template' => 'pagamenti/avviso.html.twig',
            'context' => [
                'form_submitted' => false,
                'form_data' => [],
                'form_errors' => [],
                'form_feedback' => null,
            ],
        ];
    },
    '/pendenze' => static function () use ($method, $env): array {
        if (!frontoffice_spid_enabled()) {
            http_response_code(404);
            return [
                'template' => 'errors/404.html.twig',
                'context' => [
                    'requested_path' => '/pendenze',
                ],
            ];
        }

        if ($method !== 'GET') {
            http_response_code(405);
            return [
                'template' => 'errors/404.html.twig',
                'context' => [
                    'requested_path' => '/pendenze',
                ],
            ];
        }

        $user = frontoffice_get_logged_user();
        if ($user === null) {
            header('Location: /login?return_to=%2Fpendenze', true, 302);
            exit;
        }

        $codiceFiscale = frontoffice_get_logged_user_fiscal_number();
        if ($codiceFiscale === '') {
            http_response_code(503);
            return [
                'template' => 'errors/503.html.twig',
                'context' => [
                    'message' => 'Profilo SPID incompleto: codice fiscale non presente. Riprovare il login.',
                ],
            ];
        }

        $page = (int)($_GET['page'] ?? 1);
        if ($page < 1) {
            $page = 1;
        }
        $perPage = (int)($_GET['per_page'] ?? 25);
        if ($perPage < 1) {
            $perPage = 25;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $idDominio = frontoffice_env_value('ID_DOMINIO', '');
        $idA2A = frontoffice_env_value('ID_A2A', '');
        $statoRaw = strtoupper(trim((string)($_GET['stato'] ?? '')));
        $allowedStates = [
            \GovPay\Pagamenti\Model\StatoPendenza::ESEGUITA,
            \GovPay\Pagamenti\Model\StatoPendenza::NON_ESEGUITA,
            \GovPay\Pagamenti\Model\StatoPendenza::ESEGUITA_PARZIALE,
            \GovPay\Pagamenti\Model\StatoPendenza::ANNULLATA,
            \GovPay\Pagamenti\Model\StatoPendenza::SCADUTA,
            \GovPay\Pagamenti\Model\StatoPendenza::ANOMALA,
        ];
        $stato = in_array($statoRaw, $allowedStates, true) ? $statoRaw : null;

        $api = frontoffice_pagamenti_api_client();
        if ($api === null) {
            http_response_code(503);
            return [
                'template' => 'errors/503.html.twig',
                'context' => [
                    'message' => 'Al momento non riusciamo a interrogare il sistema dei pagamenti. Riprova più tardi.',
                ],
            ];
        }

        try {
            $result = $api->findPendenze(
                $page,
                $perPage,
                null,
                $idDominio !== '' ? $idDominio : null,
                null,
                null,
                null,
                $idA2A !== '' ? $idA2A : null,
                null,
                $codiceFiscale,
                $stato,
                null,
                null,
                null,
                false,
                true,
                true
            );
            $data = json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Logger::getInstance()->warning('Errore durante la ricerca pendenze per utente frontoffice', [
                'codiceFiscale' => $codiceFiscale,
                'error' => Logger::sanitizeErrorForDisplay($e->getMessage()),
            ]);
            http_response_code(503);
            return [
                'template' => 'errors/503.html.twig',
                'context' => [
                    'message' => 'Al momento non riusciamo a interrogare il sistema dei pagamenti. Riprova più tardi.',
                ],
            ];
        }

        $risultati = $data['risultati'] ?? [];
        if (!is_array($risultati)) {
            $risultati = [];
        }

        $rows = [];
        foreach ($risultati as $pendenza) {
            if (!is_array($pendenza)) {
                continue;
            }
            $state = strtoupper((string)($pendenza['stato'] ?? ''));
            $numeroAvviso = trim((string)($pendenza['numeroAvviso'] ?? ''));
            $rows[] = [
                'id_pendenza' => (string)($pendenza['idPendenza'] ?? ''),
                'id_a2a' => (string)($pendenza['idA2A'] ?? ''),
                'numero_avviso' => $numeroAvviso,
                'causale' => trim((string)($pendenza['causale'] ?? '')),
                'importo' => isset($pendenza['importo']) && is_numeric($pendenza['importo']) ? (float)$pendenza['importo'] : null,
                'data_scadenza' => $pendenza['dataScadenza'] ?? null,
                'data_validita' => $pendenza['dataValidita'] ?? null,
                'stato' => [
                    'code' => $state,
                    'label' => frontoffice_map_pendenza_state($state),
                ],
                'is_payable' => frontoffice_is_pendenza_payable($state),
                'is_paid' => frontoffice_is_pendenza_paid($state),
                'download_url' => ($numeroAvviso !== '' && $idDominio !== '')
                    ? '/avvisi/' . rawurlencode($idDominio) . '/' . rawurlencode($numeroAvviso)
                    : null,
                'receipt_url' => (frontoffice_is_pendenza_paid($state) && (string)($pendenza['idPendenza'] ?? '') !== '')
                    ? '/pendenze/' . rawurlencode((string)$pendenza['idPendenza']) . '/ricevuta'
                    : null,
                'detail_url' => (string)($pendenza['idPendenza'] ?? '') !== ''
                    ? '/pendenze/' . rawurlencode((string)$pendenza['idPendenza'])
                    : null,
            ];
        }

        return [
            'template' => 'pendenze/index.html.twig',
            'context' => [
                'profile' => $user,
                'codice_fiscale' => $codiceFiscale,
                'pendenze' => $rows,
                'filters' => [
                    'stato' => $statoRaw,
                ],
                'pagination' => [
                    'pagina' => (int)($data['pagina'] ?? $page),
                    'risultati_per_pagina' => (int)($data['risultatiPerPagina'] ?? $perPage),
                    'num_risultati' => (int)($data['numRisultati'] ?? 0),
                    'total_pages' => (int) max(1, (int) ceil(((int)($data['numRisultati'] ?? 0)) / max(1, (int)($data['risultatiPerPagina'] ?? $perPage)))),
                ],
            ],
        ];
    },
];

$routeDefinition = null;

if ($method === 'GET' && preg_match('#^/avvisi/([^/]+)/([^/]+)$#', $normalizedPath, $match)) {
    frontoffice_stream_avviso_pdf(rawurldecode($match[1]), rawurldecode($match[2]));
    return;
}

if ($method === 'GET' && preg_match('#^/pendenze/([^/]+)/checkout$#', $normalizedPath, $match)) {
    if (!frontoffice_spid_enabled()) {
        http_response_code(404);
        echo 'Not found';
        return;
    }

    $user = frontoffice_get_logged_user();
    if ($user === null) {
        header('Location: /login?return_to=' . rawurlencode($requestPath), true, 302);
        exit;
    }

    $idDominio = frontoffice_env_value('ID_DOMINIO', '');
    if ($idDominio === '') {
        http_response_code(503);
        echo 'Configurazione mancante: ID_DOMINIO non impostato.';
        return;
    }

    $idPendenza = trim(rawurldecode($match[1]));
    if ($idPendenza === '') {
        http_response_code(404);
        echo 'Not found';
        return;
    }

    if ($frontofficeBaseUrl === '') {
        http_response_code(503);
        echo 'Configurazione mancante: FRONTOFFICE_PUBLIC_BASE_URL non impostato.';
        return;
    }

    $detail = frontoffice_fetch_pagamenti_detail($idPendenza);
    if (!$detail) {
        http_response_code(404);
        echo 'Not found';
        return;
    }
    if (!frontoffice_pendenza_belongs_to_cf($detail, frontoffice_get_logged_user_fiscal_number())) {
        http_response_code(404);
        echo 'Not found';
        return;
    }

    $state = strtoupper((string)($detail['stato'] ?? ''));
    if (!frontoffice_is_pendenza_payable($state)) {
        http_response_code(404);
        echo 'Pagamento non disponibile per questa pendenza.';
        return;
    }

    $numeroAvviso = trim((string)($detail['numeroAvviso'] ?? ''));
    $numeroAvviso = preg_replace('/\D+/', '', $numeroAvviso);
    if (!is_string($numeroAvviso) || $numeroAvviso === '') {
        http_response_code(503);
        echo 'Numero avviso non disponibile.';
        return;
    }

    $importo = $detail['importo'] ?? null;
    $amountEur = is_numeric($importo) ? (float)$importo : 0.0;
    $amountCents = frontoffice_amount_to_cents($amountEur);
    if ($amountCents <= 0) {
        http_response_code(503);
        echo 'Importo non valido.';
        return;
    }

    $hasCheckoutClient = class_exists(\PagoPA\CheckoutEc\Api\DefaultApi::class);
    $subscriptionKeyConfigured = trim(frontoffice_env_value('PAGOPA_CHECKOUT_SUBSCRIPTION_KEY', '')) !== '';

    if (!$hasCheckoutClient || !$subscriptionKeyConfigured) {
        http_response_code(503);
        if (!$hasCheckoutClient) {
            echo 'Checkout pagoPA non configurato (client non installato).';
            Logger::getInstance()->warning('Checkout pagoPA non configurato: client non installato');
        } else {
            echo 'Checkout pagoPA non configurato (subscription key mancante).';
            Logger::getInstance()->warning('Checkout pagoPA non configurato: subscription key mancante');
        }
        return;
    }

    $api = frontoffice_pagopa_checkout_api_client();
    if ($api === null) {
        http_response_code(503);
        echo 'Checkout pagoPA non configurato.';
        Logger::getInstance()->warning('Checkout pagoPA non configurato: helper client null');
        return;
    }

    $companyName = trim(frontoffice_env_value('PAGOPA_CHECKOUT_COMPANY_NAME', frontoffice_env_value('APP_ENTITY_NAME', 'Ente')));
    if ($companyName === '') {
        $companyName = 'Ente';
    }
    $description = trim((string)($detail['causale'] ?? ''));

    $okUrl = trim(frontoffice_env_value('PAGOPA_CHECKOUT_RETURN_OK_URL', ''));
    $cancelUrl = trim(frontoffice_env_value('PAGOPA_CHECKOUT_RETURN_CANCEL_URL', ''));
    $errorUrl = trim(frontoffice_env_value('PAGOPA_CHECKOUT_RETURN_ERROR_URL', ''));
    if ($okUrl === '') {
        $okUrl = $frontofficeBaseUrl . '/pendenze/' . rawurlencode($idPendenza) . '?checkout=ok';
    }
    if ($cancelUrl === '') {
        $cancelUrl = $frontofficeBaseUrl . '/pendenze/' . rawurlencode($idPendenza) . '?checkout=cancel';
    }
    if ($errorUrl === '') {
        $errorUrl = $frontofficeBaseUrl . '/pendenze/' . rawurlencode($idPendenza) . '?checkout=error';
    }

    try {
        $notice = new \PagoPA\CheckoutEc\Model\PaymentNotice();
        $notice->setNoticeNumber($numeroAvviso);
        $notice->setFiscalCode($idDominio);
        $notice->setAmount($amountCents);
        $notice->setCompanyName($companyName);
        if ($description !== '') {
            $notice->setDescription($description);
        }

        $returnUrls = new \PagoPA\CheckoutEc\Model\CartRequestReturnUrls();
        $returnUrls->setReturnOkUrl($okUrl);
        $returnUrls->setReturnCancelUrl($cancelUrl);
        $returnUrls->setReturnErrorUrl($errorUrl);

        $cart = new \PagoPA\CheckoutEc\Model\CartRequest();
        $cart->setPaymentNotices([$notice]);
        $cart->setReturnUrls($returnUrls);

        [, $statusCode, $headers] = $api->postCartsWithHttpInfo($cart);

        $location = '';
        if (is_array($headers)) {
            foreach ($headers as $name => $values) {
                if (strtolower((string)$name) !== 'location') {
                    continue;
                }
                if (is_array($values) && isset($values[0]) && is_string($values[0])) {
                    $location = trim($values[0]);
                }
                break;
            }
        }

        if ($location === '' || $statusCode < 300 || $statusCode >= 400) {
            Logger::getInstance()->warning('Checkout pagoPA: risposta inattesa da POST /carts', [
                'idPendenza' => $idPendenza,
                'status' => $statusCode,
                'has_location' => $location !== '',
            ]);
            http_response_code(503);
            echo 'Al momento non riusciamo ad avviare il pagamento. Riprova più tardi.';
            return;
        }

        header('Location: ' . $location, true, 302);
        exit;
    } catch (\Throwable $e) {
        Logger::getInstance()->warning('Errore durante la creazione del carrello pagoPA Checkout', [
            'idPendenza' => $idPendenza,
            'error' => Logger::sanitizeErrorForDisplay($e->getMessage()),
        ]);
        http_response_code(503);
        echo 'Al momento non riusciamo ad avviare il pagamento. Riprova più tardi.';
        return;
    }
}

if ($method === 'GET' && preg_match('#^/pendenze/([^/]+)/ricevuta$#', $normalizedPath, $match)) {
    if (!frontoffice_spid_enabled()) {
        http_response_code(404);
        echo 'Not found';
        return;
    }

    $user = frontoffice_get_logged_user();
    if ($user === null) {
        header('Location: /login?return_to=' . rawurlencode($requestPath), true, 302);
        exit;
    }

    $idDominio = frontoffice_env_value('ID_DOMINIO', '');
    if ($idDominio === '') {
        http_response_code(503);
        echo 'Configurazione mancante: ID_DOMINIO non impostato.';
        return;
    }

    $idPendenza = trim(rawurldecode($match[1]));
    if ($idPendenza === '') {
        http_response_code(404);
        echo 'Not found';
        return;
    }

    $detail = frontoffice_fetch_pagamenti_detail($idPendenza);
    if (!$detail) {
        http_response_code(404);
        echo 'Not found';
        return;
    }
    if (!frontoffice_pendenza_belongs_to_cf($detail, frontoffice_get_logged_user_fiscal_number())) {
        http_response_code(404);
        echo 'Not found';
        return;
    }

    $state = strtoupper((string)($detail['stato'] ?? ''));
    if (!frontoffice_is_pendenza_paid($state)) {
        http_response_code(404);
        echo 'Ricevuta non disponibile.';
        return;
    }

    $ids = frontoffice_extract_ricevuta_identifiers_from_pendenza_detail($detail);
    $iuv = trim((string)($ids['iuv'] ?? ''));
    $idRicevuta = trim((string)($ids['idRicevuta'] ?? ''));
    $ccp = trim((string)($ids['ccp'] ?? ''));

    // Fallback forte: se mancano IUV/CCP nel dettaglio, ricaviamoli da Pendenze v2 (RPP eseguite).
    $idA2AFromDetail = trim((string)($detail['idA2A'] ?? $detail['id_a2a'] ?? ''));
    if (($iuv === '' || $ccp === '')) {
        $rpp = frontoffice_find_paid_rpp_for_pendenza($idDominio, $idPendenza, ($idA2AFromDetail !== '' ? $idA2AFromDetail : null));
        if (is_array($rpp)) {
            if ($iuv === '') {
                $iuv = trim((string)($rpp['iuv'] ?? ''));
            }
            if ($ccp === '') {
                $ccp = trim((string)($rpp['ccp'] ?? ''));
            }
        }
    }

    if ($iuv === '') {
        http_response_code(404);
        echo 'Ricevuta non disponibile.';
        return;
    }

    // Allineato al backoffice: la RT si scarica da Pendenze v2 /rpp/{idDominio}/{iuv}/{ccp}/rt.
    if ($ccp !== '') {
        frontoffice_stream_rt_pdf($idDominio, $iuv, $ccp);
        return;
    }

    // Fallback: cerchiamo il CCP interrogando Pagamenti /ricevute/{idDominio}/{iuv}.
    $ricevute = frontoffice_fetch_ricevute_for_iuv($idDominio, $iuv);
    $risultati = is_array($ricevute['risultati'] ?? null) ? $ricevute['risultati'] : [];
    if ($risultati === []) {
        http_response_code(404);
        echo 'Ricevuta non disponibile.';
        return;
    }

    $first = $risultati[0] ?? null;
    $iuvFromApi = is_array($first) ? trim((string)($first['iuv'] ?? $iuv)) : $iuv;
    $ccpFromApi = '';
    if (is_array($first)) {
        foreach (['ccp', 'codiceContestoPagamento', 'codice_contesto_pagamento'] as $k) {
            if (isset($first[$k]) && is_scalar($first[$k])) {
                $ccpFromApi = trim((string)$first[$k]);
                if ($ccpFromApi !== '') {
                    break;
                }
            }
        }
    }
    if ($ccpFromApi === '' || $iuvFromApi === '') {
        // Ultimo fallback: se abbiamo idRicevuta (anche se non abbiamo CCP), proviamo lo scarico PDF legacy.
        if ($idRicevuta !== '') {
            frontoffice_stream_ricevuta_pdf($idDominio, $iuv, $idRicevuta);
            return;
        }
        http_response_code(404);
        echo 'Ricevuta non disponibile.';
        return;
    }

    frontoffice_stream_rt_pdf($idDominio, $iuvFromApi, $ccpFromApi);
    return;
}

if ($method === 'GET' && preg_match('#^/pendenze/([^/]+)$#', $normalizedPath, $match)) {
    if (!frontoffice_spid_enabled()) {
        http_response_code(404);
        $routeDefinition = [
            'template' => 'errors/404.html.twig',
            'context' => [
                'requested_path' => $requestPath,
            ],
        ];
    } else {
        $user = frontoffice_get_logged_user();
        if ($user === null) {
            header('Location: /login?return_to=%2Fpendenze', true, 302);
            exit;
        }

        $idPendenza = rawurldecode($match[1]);
        $idPendenza = trim($idPendenza);
        if ($idPendenza === '') {
            http_response_code(404);
            $routeDefinition = [
                'template' => 'errors/404.html.twig',
                'context' => [
                    'requested_path' => $requestPath,
                ],
            ];
        } else {
            $detail = frontoffice_fetch_pagamenti_detail($idPendenza);
            if (!$detail) {
                http_response_code(404);
                $routeDefinition = [
                    'template' => 'errors/404.html.twig',
                    'context' => [
                        'requested_path' => $requestPath,
                    ],
                ];
            } elseif (!frontoffice_pendenza_belongs_to_cf($detail, frontoffice_get_logged_user_fiscal_number())) {
                http_response_code(404);
                $routeDefinition = [
                    'template' => 'errors/404.html.twig',
                    'context' => [
                        'requested_path' => $requestPath,
                    ],
                ];
            } else {
                $idDominio = frontoffice_env_value('ID_DOMINIO', '');
                $routeDefinition = [
                    'template' => 'pendenze/detail.html.twig',
                    'context' => [
                        'profile' => $user,
                        'codice_fiscale' => frontoffice_get_logged_user_fiscal_number(),
                        'pendenza' => $detail,
                        'pendenza_preview' => frontoffice_build_avviso_preview($detail, $idDominio),
                        'back_url' => '/pendenze',
                    ],
                ];
            }
        }
    }
}

if ($routeDefinition === null) {
    $routeDefinition = $routes[$normalizedPath] ?? null;
}
if ($routeDefinition === null) {
    http_response_code(404);
    $route = [
        'template' => 'errors/404.html.twig',
        'context' => [
            'requested_path' => $requestPath,
        ],
    ];
} else {
    $route = is_callable($routeDefinition) ? $routeDefinition() : $routeDefinition;
}

$templateBase = dirname(__DIR__);
$templateCandidates = [
    $templateBase . '/frontoffice/templates',
    $templateBase . '/templates',
    dirname($templateBase) . '/templates',
    __DIR__ . '/../templates',
];
$templatePaths = [];
foreach ($templateCandidates as $candidate) {
    if ($candidate && is_dir($candidate) && !in_array($candidate, $templatePaths, true)) {
        $templatePaths[] = $candidate;
    }
}
if ($templatePaths === []) {
    $templatePaths[] = __DIR__ . '/../templates';
}

$loader = new FilesystemLoader($templatePaths);
$twig = new Environment($loader, [
    'cache' => false,
    'autoescape' => 'html',
]);

$baseContext = [
    'app_entity' => [
        'name' => $entityName,
        'suffix' => $entitySuffix,
        'government' => $entityGovernment,
        'full' => $entityFull,
    ],
    'app_logo' => $appLogo,
    'app_favicon' => $appFavicon,
    'current_user' => (isset($_SESSION['frontoffice_user']) && is_array($_SESSION['frontoffice_user'])) ? $_SESSION['frontoffice_user'] : null,
    'spid_enabled' => frontoffice_spid_enabled(),
    'spid_mode' => frontoffice_spid_mode(),
    'support_email' => $supportEmail,
    'support_phone' => $env('FRONTOFFICE_SUPPORT_PHONE', '800.000.000'),
    'support_hours' => $env('FRONTOFFICE_SUPPORT_HOURS', 'Lun-Ven 8:30-17:30'),
    'support_location' => $env('FRONTOFFICE_SUPPORT_LOCATION', 'Palazzo Municipale, piano terra<br>Martedì e Giovedì 9:00-12:30 / 15:00-17:00'),
];

$context = array_merge(
    $baseContext,
    ['current_path' => $normalizedPath],
    $route['context'] ?? []
);

echo $twig->render($route['template'], $context);
