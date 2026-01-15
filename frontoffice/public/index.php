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
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
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

if (!function_exists('frontoffice_build_avviso_preview')) {
    function frontoffice_build_avviso_preview(array $pendenza, string $idDominio): array
    {
        $numeroAvviso = trim((string)($pendenza['numeroAvviso'] ?? ''));
        $downloadUrl = ($numeroAvviso !== '' && $idDominio !== '')
            ? '/avvisi/' . rawurlencode($idDominio) . '/' . rawurlencode($numeroAvviso)
            : null;
        $state = strtoupper((string)($pendenza['stato'] ?? ''));
        $importo = $pendenza['importo'] ?? null;

        return [
            'numero_avviso' => $numeroAvviso,
            'importo' => is_numeric($importo) ? (float)$importo : null,
            'causale' => trim((string)($pendenza['causale'] ?? '')),
            'id_pendenza' => (string)($pendenza['idPendenza'] ?? ''),
            'id_a2a' => (string)($pendenza['idA2A'] ?? ''),
            'data_validita' => $pendenza['dataValidita'] ?? null,
            'data_scadenza' => $pendenza['dataScadenza'] ?? null,
            'soggetto_pagatore' => $pendenza['soggettoPagatore'] ?? null,
            'stato' => [
                'code' => $state,
                'label' => frontoffice_map_pendenza_state($state),
            ],
            'is_payable' => frontoffice_is_pendenza_payable($state),
            'download_url' => $downloadUrl,
            'voci' => $pendenza['voci'] ?? [],
            'id_dominio' => $idDominio,
            'checkout_url' => frontoffice_env_value(
                'FRONTOFFICE_PAGOPA_CHECKOUT_URL',
                'https://checkout.pagopa.it/inserisci-dati-avviso'
            ),
        ];
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
    '/spid/callback' => static function () use ($method): array {
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

        $user = [
            'first_name' => (string)($_POST['name'] ?? ''),
            'last_name' => (string)($_POST['familyName'] ?? ''),
            'email' => (string)($_POST['email'] ?? ''),
            'fiscal_number' => (string)($_POST['fiscalNumber'] ?? ''),
            'spid_code' => (string)($_POST['spidCode'] ?? ''),
            'provider_id' => (string)($_POST['providerId'] ?? ''),
            'provider_name' => (string)($_POST['providerName'] ?? ''),
            'response_id' => (string)($_POST['responseId'] ?? ''),
        ];

        // Se il proxy usa handler Sign/Encrypt, qui arriverebbe anche `data` (JWS/JWE).
        // Per ora manteniamo compatibilità con handler Plain (campi in chiaro).
        if (isset($_POST['data']) && is_string($_POST['data']) && $_POST['data'] !== '') {
            $user['token'] = (string)$_POST['data'];
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
                'download_url' => ($numeroAvviso !== '' && $idDominio !== '')
                    ? '/avvisi/' . rawurlencode($idDominio) . '/' . rawurlencode($numeroAvviso)
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
