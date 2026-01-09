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

// Manteniamo un riferimento al ClassLoader Composer dell'app.
// Serve perché l'autoloader di spid-cie-php viene registrato in modalità "prepend",
// e può prendere precedenza su Twig dell'app causando 500 nelle pagine non SPID.
$frontofficeAppAutoloader = require dirname(__DIR__) . '/vendor/autoload.php';

if (!function_exists('frontoffice_capture_output')) {
    function frontoffice_capture_output(callable $fn): string
    {
        ob_start();
        try {
            $fn();
        } finally {
            return (string) ob_get_clean();
        }
    }
}

if (!function_exists('frontoffice_session_start')) {
    function frontoffice_session_start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        // Non blocchiamo la request se headers già inviati: semplicemente niente sessione.
        if (headers_sent()) {
            return;
        }
        @session_start();
    }
}

if (!function_exists('frontoffice_session_get_current_user')) {
    function frontoffice_session_get_current_user(): ?array
    {
        frontoffice_session_start();
        $value = $_SESSION['frontoffice_current_user'] ?? null;
        return is_array($value) ? $value : null;
    }
}

if (!function_exists('frontoffice_session_set_current_user')) {
    function frontoffice_session_set_current_user(array $user): void
    {
        frontoffice_session_start();
        $_SESSION['frontoffice_current_user'] = $user;
    }
}

if (!function_exists('frontoffice_session_clear_current_user')) {
    function frontoffice_session_clear_current_user(): void
    {
        frontoffice_session_start();
        unset($_SESSION['frontoffice_current_user']);
    }
}

if (!function_exists('frontoffice_safe_return_to')) {
    function frontoffice_safe_return_to(?string $value, string $default = '/'): string
    {
        $value = (string)($value ?? '');
        $value = trim($value);
        if ($value === '') {
            return $default;
        }
        // Evita open redirect: accetta solo path relativi assoluti ("/...")
        if ($value[0] !== '/') {
            return $default;
        }
        // Evita "//example.com" e path traversal bizzarri
        if (str_starts_with($value, '//') || str_contains($value, '\\')) {
            return $default;
        }
        return $value;
    }
}

if (!function_exists('frontoffice_is_spid_cie_enabled')) {
    function frontoffice_is_spid_cie_enabled(): bool
    {
        $provider = trim((string) frontoffice_env_value('FRONTOFFICE_AUTH_PROVIDER', ''));
        if ($provider === '') {
            $provider = trim((string) frontoffice_env_value('FRONT_OFFICE_AUTH_PROVIDER', ''));
        }
        $provider = strtolower($provider);
        return $provider === 'spid_cie';
    }
}

if (!function_exists('frontoffice_spid_cie_root')) {
    function frontoffice_spid_cie_root(): string
    {
        $root = trim((string) frontoffice_env_value('SPID_CIE_ROOT', ''));
        if ($root !== '') {
            return $root;
        }
        // Default pensato per il container
        if (is_dir('/var/www/spid-cie-php')) {
            return '/var/www/spid-cie-php';
        }
        return '';
    }
}

if (!function_exists('frontoffice_spid_cie_sdk')) {
    function frontoffice_spid_cie_sdk(): ?object
    {
        if (!frontoffice_is_spid_cie_enabled()) {
            return null;
        }

        $root = frontoffice_spid_cie_root();
        if ($root === '') {
            return null;
        }
        $bootstrap = rtrim($root, '/\\') . '/spid-php.php';
        if (!is_file($bootstrap)) {
            Logger::getInstance()->warning('SPID/CIE abilitato ma spid-php.php non trovato', ['path' => $bootstrap]);
            return null;
        }

        try {
            require_once $bootstrap;

            // spid-cie-php (tramite SimpleSAML) registra il suo Composer autoloader in prepend.
            // Ripristiniamo la precedenza dell'autoloader dell'app per evitare conflitti (es. Twig).
            if (isset($GLOBALS['frontofficeAppAutoloader']) && $GLOBALS['frontofficeAppAutoloader'] instanceof \Composer\Autoload\ClassLoader) {
                $GLOBALS['frontofficeAppAutoloader']->register(true);
            }

            if (!class_exists('SPID_PHP')) {
                Logger::getInstance()->warning('SPID_PHP non disponibile dopo include spid-php.php');
                return null;
            }
            return new \SPID_PHP();
        } catch (\Throwable $e) {
            Logger::getInstance()->warning('Errore inizializzazione SDK SPID/CIE', ['error' => $e->getMessage()]);
            return null;
        }
    }
}

if (!function_exists('frontoffice_spid_cie_current_user')) {
    function frontoffice_spid_cie_current_user(): ?array
    {
        $sdk = frontoffice_spid_cie_sdk();
        if ($sdk === null || !method_exists($sdk, 'isAuthenticated')) {
            return null;
        }

        try {
            if (!$sdk->isAuthenticated()) {
                return null;
            }
            $attributes = method_exists($sdk, 'getAttributes') ? (array) $sdk->getAttributes() : [];
            $getFirst = static function (array $attrs, string $key): ?string {
                $value = $attrs[$key] ?? null;
                if (is_array($value) && isset($value[0])) {
                    $value = $value[0];
                }
                $value = is_scalar($value) ? trim((string) $value) : '';
                return $value !== '' ? $value : null;
            };

            $firstName = $getFirst($attributes, 'name') ?? $getFirst($attributes, 'nome');
            $lastName = $getFirst($attributes, 'familyName') ?? $getFirst($attributes, 'cognome');
            $email = $getFirst($attributes, 'email');

            $fiscal = $getFirst($attributes, 'fiscalNumber') ?? $getFirst($attributes, 'codiceFiscale');
            if ($fiscal !== null) {
                $fiscal = strtoupper(trim($fiscal));
                // Spesso arriva nel formato "TINIT-XXXXXXXXXXXXXXX"
                if (str_starts_with($fiscal, 'TINIT-')) {
                    $fiscal = substr($fiscal, 6);
                }
            }

            $idp = null;
            if (method_exists($sdk, 'getAttribute')) {
                // Non sempre esiste un attributo IdP standard; lasciamo null se assente.
                $idp = null;
            }

            return [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email ?? ($fiscal ? strtolower($fiscal) . '@spid.local' : null),
                'fiscal_number' => $fiscal,
                'idp' => $idp,
                'attributes' => $attributes,
            ];
        } catch (\Throwable $e) {
            Logger::getInstance()->warning('Errore lettura utente SPID/CIE', ['error' => $e->getMessage()]);
            return null;
        }
    }
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

if (!function_exists('frontoffice_public_base_url')) {
    function frontoffice_public_base_url(): string
    {
        $base = trim((string) frontoffice_env_value('SPID_CIE_PUBLIC_BASE_URL', ''));
        if ($base !== '') {
            return rtrim($base, '/');
        }

        $https = (string) ($_SERVER['HTTPS'] ?? '');
        $scheme = ($https !== '' && strtolower($https) !== 'off') ? 'https' : 'http';
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '') {
            $host = 'localhost';
        }
        return $scheme . '://' . $host;
    }
}

if (!function_exists('frontoffice_spid_metadata_certificate_b64')) {
    function frontoffice_spid_metadata_certificate_b64(): ?string
    {
        $root = frontoffice_spid_cie_root();
        if ($root === '') {
            return null;
        }

        $candidates = [
            rtrim($root, '/\\') . '/vendor/simplesamlphp/simplesamlphp/cert/spid.crt',
            rtrim($root, '/\\') . '/cert/spid.crt',
        ];

        $pem = null;
        foreach ($candidates as $path) {
            if (is_file($path)) {
                $pem = @file_get_contents($path);
                break;
            }
        }
        if (!is_string($pem) || trim($pem) === '') {
            return null;
        }

        $pem = preg_replace('/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s+/', '', $pem);
        $pem = is_string($pem) ? trim($pem) : '';
        return $pem !== '' ? $pem : null;
    }
}

if (!function_exists('frontoffice_spid_metadata_certificate_candidates')) {
    function frontoffice_spid_metadata_certificate_candidates(): array
    {
        $root = frontoffice_spid_cie_root();
        if ($root === '') {
            return [];
        }
        return [
            rtrim($root, '/\\') . '/vendor/simplesamlphp/simplesamlphp/cert/spid.crt',
            rtrim($root, '/\\') . '/cert/spid.crt',
        ];
    }
}

if (!function_exists('frontoffice_spid_metadata_private_key_pem')) {
    function frontoffice_spid_metadata_private_key_pem(): ?string
    {
        $root = frontoffice_spid_cie_root();
        if ($root === '') {
            return null;
        }

        $candidates = [
            rtrim($root, '/\\') . '/vendor/simplesamlphp/simplesamlphp/cert/spid.key',
            rtrim($root, '/\\') . '/cert/spid.key',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                $pem = @file_get_contents($path);
                if (is_string($pem) && trim($pem) !== '') {
                    return $pem;
                }
            }
        }

        return null;
    }
}

if (!function_exists('frontoffice_spid_metadata_private_key_candidates')) {
    function frontoffice_spid_metadata_private_key_candidates(): array
    {
        $root = frontoffice_spid_cie_root();
        if ($root === '') {
            return [];
        }
        return [
            rtrim($root, '/\\') . '/vendor/simplesamlphp/simplesamlphp/cert/spid.key',
            rtrim($root, '/\\') . '/cert/spid.key',
        ];
    }
}

if (!function_exists('frontoffice_sign_spid_metadata_xml')) {
    function frontoffice_sign_spid_metadata_xml(string $xml, string $certB64, string $privateKeyPem, string $referenceId, ?string $passphrase = null): ?string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $previousInternalErrors = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previousInternalErrors);
        if (!$loaded) {
            return null;
        }

        $root = $dom->documentElement;
        if (!$root instanceof \DOMElement) {
            return null;
        }

        if ($referenceId === '') {
            $referenceId = $root->getAttribute('ID');
            if ($referenceId === '') {
                return null;
            }
        } else {
            $root->setAttribute('ID', $referenceId);
        }
        $root->setIdAttribute('ID', true);

        $digestSource = $root->C14N(true, false);
        if ($digestSource === false) {
            return null;
        }
        $digestValue = base64_encode(hash('sha256', $digestSource, true));

        $signature = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:Signature');
        if ($root->firstChild instanceof \DOMNode) {
            $root->insertBefore($signature, $root->firstChild);
        } else {
            $root->appendChild($signature);
        }

        $signedInfo = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:SignedInfo');
        $signature->appendChild($signedInfo);

        $canonMethod = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:CanonicalizationMethod');
        $canonMethod->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');
        $signedInfo->appendChild($canonMethod);

        $signatureMethod = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:SignatureMethod');
        $signatureMethod->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256');
        $signedInfo->appendChild($signatureMethod);

        $reference = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:Reference');
        $reference->setAttribute('URI', '#' . $referenceId);
        $signedInfo->appendChild($reference);

        $transforms = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:Transforms');
        $reference->appendChild($transforms);

        $transformEnveloped = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:Transform');
        $transformEnveloped->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');
        $transforms->appendChild($transformEnveloped);

        $transformC14n = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:Transform');
        $transformC14n->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');
        $transforms->appendChild($transformC14n);

        $digestMethod = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmlenc#sha256');
        $reference->appendChild($digestMethod);

        $digestValueNode = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:DigestValue', $digestValue);
        $reference->appendChild($digestValueNode);

        $canonicalSignedInfo = $signedInfo->C14N(true, false);
        if ($canonicalSignedInfo === false) {
            return null;
        }

        $privateKey = @openssl_pkey_get_private($privateKeyPem, $passphrase ?? '');
        if ($privateKey === false) {
            return null;
        }

        $binarySignature = '';
        $signatureOk = openssl_sign($canonicalSignedInfo, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKey);
        if (!$signatureOk) {
            return null;
        }

        $signatureValue = base64_encode($binarySignature);
        $signatureValueNode = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:SignatureValue', $signatureValue);
        $signature->appendChild($signatureValueNode);

        $keyInfo = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:KeyInfo');
        $x509Data = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:X509Data');
        $x509Certificate = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:X509Certificate', trim($certB64));
        $x509Data->appendChild($x509Certificate);
        $keyInfo->appendChild($x509Data);
        $signature->appendChild($keyInfo);

        return $dom->saveXML();
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

$routes = [
    '/' => static fn (): array => [
        'template' => 'home.html.twig',
        'context' => [],
    ],
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
    '/spid-metadata.xml' => static function () use ($entityName): array {
        // Metadata "full" per SPID Validator / DEMO:
        // include ContactPerson (evita crash entityType undefined) + AttributeConsumingService.
        // Non usiamo Twig qui: risposta raw XML.

        if (!frontoffice_is_spid_cie_enabled()) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo "Not found";
            exit;
        }

        $baseUrl = frontoffice_public_base_url();
        $entityId = $baseUrl . '/spid-cie/module.php/saml/sp/metadata.php/spid';
        $acs = $baseUrl . '/spid-cie/module.php/saml/sp/saml2-acs.php/spid';
        $slo = $baseUrl . '/spid-cie/module.php/saml/sp/saml2-logout.php/spid';

        $certB64 = frontoffice_spid_metadata_certificate_b64();
        if ($certB64 === null) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            $root = frontoffice_spid_cie_root();
            $candidates = frontoffice_spid_metadata_certificate_candidates();
            echo "SPID metadata certificate not available\n";
            echo "SPID_CIE_ROOT: " . ($root !== '' ? $root : '(empty)') . "\n";
            if ($candidates !== []) {
                echo "Checked paths:\n";
                foreach ($candidates as $path) {
                    echo "- " . $path . (is_file($path) ? " (exists)" : " (missing)") . "\n";
                }
            }
            echo "\nFix: ensure SPID/CIE runtime setup ran (spid-cie-php installed + SimpleSAMLphp cert generated).\n";
            exit;
        }

        $privateKeyPem = frontoffice_spid_metadata_private_key_pem();
        if ($privateKeyPem === null) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            $root = frontoffice_spid_cie_root();
            $candidates = frontoffice_spid_metadata_private_key_candidates();
            echo "SPID metadata private key not available\n";
            echo "SPID_CIE_ROOT: " . ($root !== '' ? $root : '(empty)') . "\n";
            if ($candidates !== []) {
                echo "Checked paths:\n";
                foreach ($candidates as $path) {
                    echo "- " . $path . (is_file($path) ? " (exists)" : " (missing)") . "\n";
                }
            }
            echo "\nFix: ensure SimpleSAMLphp private key (spid.key) exists and is readable.\n";
            exit;
        }

        $privateKeyPassword = trim((string) frontoffice_env_value('SPID_METADATA_PRIVATE_KEY_PASSWORD', ''));
        if ($privateKeyPassword === '') {
            $privateKeyPassword = null;
        }

        $orgName = trim((string) $entityName);
        if ($orgName === '') {
            $orgName = 'Service Provider';
        }

        $supportEmail = trim((string) frontoffice_env_value('SPID_METADATA_SUPPORT_EMAIL', ''));
        if ($supportEmail === '') {
            $supportEmail = 'support@' . preg_replace('/[^a-z0-9.\-]+/i', '', parse_url($baseUrl, PHP_URL_HOST) ?: 'example.local');
        }

        $supportPhone = trim((string) frontoffice_env_value('SPID_METADATA_SUPPORT_PHONE', ''));

        $ipaCode = trim((string) frontoffice_env_value('SPID_METADATA_IPA_CODE', ''));
        if ($ipaCode === '') {
            // Richiesto dai test SPID (spid:IPACode MUST be present)
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo "Missing required env SPID_METADATA_IPA_CODE";
            exit;
        }
        $vatNumber = trim((string) frontoffice_env_value('SPID_METADATA_VAT_NUMBER', ''));
        $fiscalCode = trim((string) frontoffice_env_value('SPID_METADATA_FISCAL_CODE', ''));

        $entityDescriptorId = 'ID-' . substr(hash('sha256', $entityId . $certB64), 0, 32);

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<md:EntityDescriptor";
        $xml .= " xmlns:md=\"urn:oasis:names:tc:SAML:2.0:metadata\"";
        $xml .= " xmlns:ds=\"http://www.w3.org/2000/09/xmldsig#\"";
        $xml .= " xmlns:spid=\"https://spid.gov.it/saml-extensions\"";
        $xml .= " entityID=\"" . htmlspecialchars($entityId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\"";
        $xml .= " ID=\"" . htmlspecialchars($entityDescriptorId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\">\n";

        $xml .= "  <md:SPSSODescriptor protocolSupportEnumeration=\"urn:oasis:names:tc:SAML:2.0:protocol\" AuthnRequestsSigned=\"true\" WantAssertionsSigned=\"true\">\n";
        $xml .= "    <md:KeyDescriptor use=\"signing\">\n";
        $xml .= "      <ds:KeyInfo>\n";
        $xml .= "        <ds:X509Data><ds:X509Certificate>" . $certB64 . "</ds:X509Certificate></ds:X509Data>\n";
        $xml .= "      </ds:KeyInfo>\n";
        $xml .= "    </md:KeyDescriptor>\n";

        // Ordine elementi: SingleLogoutService appartiene a SSODescriptorType,
        // quindi deve apparire prima degli elementi aggiunti da SPSSODescriptorType
        // (AssertionConsumerService / AttributeConsumingService).
        $xml .= "    <md:SingleLogoutService Binding=\"urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect\" Location=\"" . htmlspecialchars($slo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\"/>\n";
        $xml .= "    <md:NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:transient</md:NameIDFormat>\n";
        $xml .= "    <md:AssertionConsumerService index=\"0\" isDefault=\"true\" Binding=\"urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST\" Location=\"" . htmlspecialchars($acs, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\"/>\n";

        $xml .= "    <md:AttributeConsumingService index=\"0\" isDefault=\"true\">\n";
        $xml .= "      <md:ServiceName xml:lang=\"it\">" . htmlspecialchars($orgName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</md:ServiceName>\n";
        $xml .= "      <md:RequestedAttribute FriendlyName=\"fiscalNumber\" Name=\"fiscalNumber\" isRequired=\"true\"/>\n";
        $xml .= "      <md:RequestedAttribute FriendlyName=\"name\" Name=\"name\" isRequired=\"false\"/>\n";
        $xml .= "      <md:RequestedAttribute FriendlyName=\"familyName\" Name=\"familyName\" isRequired=\"false\"/>\n";
        $xml .= "      <md:RequestedAttribute FriendlyName=\"email\" Name=\"email\" isRequired=\"false\"/>\n";
        $xml .= "    </md:AttributeConsumingService>\n";

        $xml .= "  </md:SPSSODescriptor>\n";

        // ContactPerson: richiesto dal validator per evitare crash (entityType undefined).
        $xml .= "  <md:ContactPerson contactType=\"other\" spid:entityType=\"spid:aggregator\">\n";
        $xml .= "    <md:Extensions>\n";
        $xml .= "      <spid:IPACode>" . htmlspecialchars($ipaCode, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</spid:IPACode>\n";
        if ($vatNumber !== '') {
            $xml .= "      <spid:VATNumber>" . htmlspecialchars($vatNumber, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</spid:VATNumber>\n";
        }
        if ($fiscalCode !== '') {
            $xml .= "      <spid:FiscalCode>" . htmlspecialchars($fiscalCode, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</spid:FiscalCode>\n";
        }
        $xml .= "    </md:Extensions>\n";
        $xml .= "    <md:Company>" . htmlspecialchars($orgName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</md:Company>\n";
        $xml .= "    <md:EmailAddress>" . htmlspecialchars($supportEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</md:EmailAddress>\n";
        if ($supportPhone !== '') {
            $xml .= "    <md:TelephoneNumber>" . htmlspecialchars($supportPhone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</md:TelephoneNumber>\n";
        }
        $xml .= "  </md:ContactPerson>\n";

        // Organization (richiesta dal validator)
        $xml .= "  <md:Organization>\n";
        $xml .= "    <md:OrganizationName xml:lang=\"it\">" . htmlspecialchars($orgName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</md:OrganizationName>\n";
        $xml .= "    <md:OrganizationDisplayName xml:lang=\"it\">" . htmlspecialchars($orgName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</md:OrganizationDisplayName>\n";
        $xml .= "    <md:OrganizationURL xml:lang=\"it\">" . htmlspecialchars($baseUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</md:OrganizationURL>\n";
        $xml .= "  </md:Organization>\n";

        $xml .= "</md:EntityDescriptor>\n";

        $signedXml = frontoffice_sign_spid_metadata_xml($xml, $certB64, $privateKeyPem, $entityDescriptorId, $privateKeyPassword);
        if ($signedXml === null) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo "Unable to sign SPID metadata (XML-DSig generation failed).";
            exit;
        }

        header('Content-Type: text/xml; charset=UTF-8');
        echo $signedXml;
        exit;
    },
    '/login' => static function () use ($entityName, $appFavicon): array {
        // NOTA: spid-cie-php/SimpleSAML porta la sua dipendenza twig/twig.
        // Se renderizziamo questa pagina con Twig dell'app, si crea un conflitto (twig_cycle redeclare).
        // Qui quindi gestiamo login e UI in modo "raw" e facciamo exit prima del bootstrap Twig.

        $finalReturnTo = frontoffice_safe_return_to($_GET['returnTo'] ?? null, '/');
        $callbackReturnTo = '/login?returnTo=' . rawurlencode($finalReturnTo);

        // Se abbiamo già una sessione applicativa, non riavviamo SPID.
        if (frontoffice_session_get_current_user() !== null) {
            header('Location: ' . $finalReturnTo, true, 302);
            exit;
        }

        $sdk = frontoffice_spid_cie_sdk();

        $spidEnabled = false;
        $cieEnabled = false;
        if ($sdk !== null) {
            $spidEnabled = method_exists($sdk, 'isSPIDEnabled') ? (bool) $sdk->isSPIDEnabled() : false;
            $cieEnabled = method_exists($sdk, 'isCIEEnabled') ? (bool) $sdk->isCIEEnabled() : false;
        }

        // Se SimpleSAML ha già una sessione valida, mappiamo attributi -> sessione app.
        if ($sdk !== null && method_exists($sdk, 'isAuthenticated') && $sdk->isAuthenticated()) {
            try {
                $attributes = method_exists($sdk, 'getAttributes') ? (array) $sdk->getAttributes() : [];
                $getFirst = static function (array $attrs, string $key): ?string {
                    $value = $attrs[$key] ?? null;
                    if (is_array($value) && isset($value[0])) {
                        $value = $value[0];
                    }
                    $value = is_scalar($value) ? trim((string) $value) : '';
                    return $value !== '' ? $value : null;
                };

                $firstName = $getFirst($attributes, 'name') ?? $getFirst($attributes, 'nome');
                $lastName = $getFirst($attributes, 'familyName') ?? $getFirst($attributes, 'cognome');
                $email = $getFirst($attributes, 'email');
                $fiscal = $getFirst($attributes, 'fiscalNumber') ?? $getFirst($attributes, 'codiceFiscale');
                if ($fiscal !== null) {
                    $fiscal = strtoupper(trim($fiscal));
                    if (str_starts_with($fiscal, 'TINIT-')) {
                        $fiscal = substr($fiscal, 6);
                    }
                }

                frontoffice_session_set_current_user([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email ?? ($fiscal ? strtolower($fiscal) . '@spid.local' : null),
                    'fiscal_number' => $fiscal,
                    'attributes' => $attributes,
                ]);
            } catch (\Throwable $e) {
                Logger::getInstance()->warning('Errore mapping utente SPID/CIE in sessione', ['error' => $e->getMessage()]);
            }

            header('Location: ' . $finalReturnTo, true, 302);
            exit;
        }

        $authError = null;
        if (!frontoffice_is_spid_cie_enabled()) {
            $authError = null;
        } elseif ($sdk === null) {
            $authError = 'Accesso SPID/CIE non disponibile: configurazione mancante sul server.';
        } elseif (!$spidEnabled && !$cieEnabled) {
            $authError = 'Accesso SPID/CIE non ancora configurato su questo ambiente.';
        }

        // Se arriva un IdP, avvia la AuthnRequest.
        $idp = isset($_GET['idp']) ? trim((string) $_GET['idp']) : '';
        if ($sdk !== null && $idp !== '' && method_exists($sdk, 'login')) {
            try {
                $level = (int) frontoffice_env_value('SPID_LEVEL', '2');
                if ($level < 1 || $level > 3) {
                    $level = 2;
                }
                if (method_exists($sdk, 'isIdPAvailable') && !$sdk->isIdPAvailable($idp)) {
                    $authError = 'Identity Provider non riconosciuto.';
                } else {
                    $sdk->login($idp, $level, $callbackReturnTo);
                    exit;
                }
            } catch (\Throwable $e) {
                Logger::getInstance()->warning('Errore login SPID/CIE', ['error' => $e->getMessage()]);
                $authError = 'Non è stato possibile avviare l\'autenticazione. Riprova più tardi.';
            }
        }

        $spidCss = '';
        $spidHtml = '';
        $spidJs = '';
        $cieHtml = '';

        if ($sdk !== null) {
            if (method_exists($sdk, 'insertSPIDButtonCSS')) {
                $spidCss = frontoffice_capture_output(static fn () => $sdk->insertSPIDButtonCSS());
            }
            if ($spidEnabled && method_exists($sdk, 'insertSPIDButton')) {
                $spidHtml = frontoffice_capture_output(static fn () => $sdk->insertSPIDButton('L'));
            }
            if (method_exists($sdk, 'insertSPIDButtonJS')) {
                $spidJs = frontoffice_capture_output(static fn () => $sdk->insertSPIDButtonJS());
            }
            if ($cieEnabled && method_exists($sdk, 'insertCIEButton')) {
                $cieHtml = frontoffice_capture_output(static fn () => $sdk->insertCIEButton('default'));
            }
        }

        header('Content-Type: text/html; charset=UTF-8');
        echo "<!doctype html>\n";
        echo "<html lang=\"it\">\n";
        echo "<head>\n";
        echo "  <meta charset=\"utf-8\">\n";
        echo "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
        echo "  <title>Accesso – " . htmlspecialchars($entityName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</title>\n";
        echo "  <link rel=\"icon\" href=\"" . htmlspecialchars($appFavicon['href'] ?? '/img/favicon_default.png', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\" type=\"" . htmlspecialchars($appFavicon['type'] ?? 'image/png', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\">\n";
        echo "  <link rel=\"stylesheet\" href=\"/assets/bootstrap-italia/css/bootstrap-italia.min.css\">\n";
        echo "  <link rel=\"stylesheet\" href=\"/assets/fontawesome/css/all.min.css\">\n";
        echo "  <link rel=\"stylesheet\" href=\"/assets/css/app.css\">\n";
        if ($spidCss !== '') {
            echo $spidCss . "\n";
        }
        echo "</head>\n";
        echo "<body class=\"it-body\">\n";
        echo "  <main id=\"main\" class=\"pb-5\">\n";
        echo "    <div class=\"container-xxl pt-4\">\n";
        echo "      <div class=\"row justify-content-center\">\n";
        echo "        <div class=\"col-12 col-md-10 col-lg-8\">\n";
        echo "          <div class=\"card shadow-sm\">\n";
        echo "            <div class=\"card-body p-4\">\n";
        echo "              <h1 class=\"h4 mb-3\">Accedi</h1>\n";
        if ($authError) {
            echo "              <div class=\"alert alert-danger\" role=\"alert\">" . htmlspecialchars($authError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</div>\n";
        }
        if ($spidEnabled || $cieEnabled) {
            echo "              <p class=\"text-muted mb-4\">Puoi accedere utilizzando SPID o CIE.</p>\n";
            if ($spidEnabled) {
                echo "              <div class=\"mb-4\">" . $spidHtml . "</div>\n";
            }
            if ($cieEnabled) {
                echo "              <div class=\"mb-2\">" . $cieHtml . "</div>\n";
            }
            echo "              <p class=\"text-muted small mt-4 mb-0\">Selezionando un provider verrai reindirizzato al sistema di autenticazione.</p>\n";
        } else {
            echo "              <div class=\"alert alert-warning\" role=\"alert\">Accesso SPID/CIE non configurato su questo ambiente.</div>\n";
        }
        echo "            </div>\n";
        echo "          </div>\n";
        echo "        </div>\n";
        echo "      </div>\n";
        echo "    </div>\n";
        echo "  </main>\n";
        echo "  <script src=\"/assets/bootstrap-italia/js/bootstrap-italia.bundle.min.js\"></script>\n";
        echo "  <script src=\"/assets/js/app.js\"></script>\n";
        if ($spidJs !== '') {
            echo $spidJs . "\n";
        }
        echo "</body>\n";
        echo "</html>\n";
        exit;
    },
    '/logout' => static function (): array {
        frontoffice_session_clear_current_user();
        $sdk = frontoffice_spid_cie_sdk();
        $returnTo = frontoffice_safe_return_to($_GET['returnTo'] ?? null, '/');
        if ($sdk !== null && method_exists($sdk, 'logout')) {
            try {
                $sdk->logout($returnTo);
                exit;
            } catch (\Throwable $e) {
                Logger::getInstance()->warning('Errore logout SPID/CIE', ['error' => $e->getMessage()]);
            }
        }
        header('Location: ' . $returnTo, true, 302);
        exit;
    },
    '/profile' => static function (): array {
        if (frontoffice_session_get_current_user() === null) {
            header('Location: /login?returnTo=' . rawurlencode('/profile'), true, 302);
            exit;
        }
        return [
            'template' => 'profile.html.twig',
            'context' => [],
        ];
    },
];

$routeDefinition = null;

if ($method === 'GET' && preg_match('#^/avvisi/([^/]+)/([^/]+)$#', $normalizedPath, $match)) {
    frontoffice_stream_avviso_pdf(rawurldecode($match[1]), rawurldecode($match[2]));
    return;
}

$routeDefinition = $routes[$normalizedPath] ?? null;
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
    'current_user' => frontoffice_session_get_current_user(),
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
