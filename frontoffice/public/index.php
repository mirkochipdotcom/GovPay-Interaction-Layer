<?php
declare(strict_types=1);

use App\Database\EntrateRepository;
use App\Logger;
use App\Services\ValidationService;
use GovPay\Pagamenti\Api\PendenzeApi as PagamentiPendenzeApi;
use GovPay\Pagamenti\Configuration as PagamentiConfiguration;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require dirname(__DIR__) . '/vendor/autoload.php';

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
        $idDominio = frontoffice_env_value('ID_DOMINIO', '');
        if ($idDominio !== '') {
            try {
                $repo = new EntrateRepository();
                $rows = $repo->listAbilitateByDominio($idDominio);
                foreach ($rows as $row) {
                    $id = (string)($row['id_entrata'] ?? '');
                    $label = trim((string)($row['descrizione'] ?? $row['descrizione_effettiva'] ?? $id));
                    if ($id === '') {
                        continue;
                    }
                    $options[] = ['id' => $id, 'label' => $label ?: $id];
                }
                if ($options !== []) {
                    Logger::getInstance()->info('Tipologie frontoffice caricate dal DB', ['idDominio' => $idDominio, 'count' => count($options)]);
                }
            } catch (\Throwable $e) {
                Logger::getInstance()->warning('Impossibile caricare le tipologie per il frontoffice', ['error' => $e->getMessage()]);
            }
        }

        if ($options === []) {
            Logger::getInstance()->warning('Tipologie frontoffice assenti dal DB: uso fallback statico', ['idDominio' => $idDominio]);
            $options = [
                ['id' => 'SERV_MENSA', 'label' => 'Mensa e servizi scolastici'],
                ['id' => 'SERV_NIDI', 'label' => "Nidi d'infanzia / rette asilo"],
                ['id' => 'SERV_OCCUPAZIONE_SUOLO', 'label' => 'Occupazione suolo pubblico'],
                ['id' => 'SERV_SANZIONI', 'label' => 'Sanzioni e contravvenzioni'],
                ['id' => 'SERV_DIRITTI_SEGRETERIA', 'label' => 'Diritti di segreteria e certificati'],
                ['id' => 'SERV_ALTRO', 'label' => 'Altro pagamento spontaneo'],
            ];
        }

        return $options;
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
        $ident = strtoupper(preg_replace('/\s+/', '', (string)($raw['identificativo'] ?? '')));
        $surname = trim((string)($raw['anagrafica'] ?? ''));
        $name = trim((string)($raw['nome'] ?? ''));
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
        $pagamentiUrl = frontoffice_env_value('GOVPAY_PAGAMENTI_URL', '');
        $idA2A = frontoffice_env_value('ID_A2A', '');
        if ($pagamentiUrl === '' || $idA2A === '' || $idPendenza === '') {
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
            $api = new PagamentiPendenzeApi($client, $config);
            $result = $api->getPendenza($idA2A, $idPendenza);
            return json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Logger::getInstance()->warning('Impossibile recuperare il dettaglio della pendenza da GovPay Pagamenti', ['error' => $e->getMessage()]);
            return null;
        }
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

        $payload = [
            'idTipoPendenza' => $idTipo,
            'idDominio' => $idDominio,
            'causale' => $causale,
            'importo' => $importo,
            'annoRiferimento' => $anno,
            'soggettoPagatore' => frontoffice_prepare_payer($payerRaw),
            'voci' => frontoffice_build_voci($idDominio, $idTipo, $causale, $importo),
            'dataValidita' => date('Y-m-d'),
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
            'data_scadenza' => $detail['dataScadenza'] ?? null,
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

$serviceOptions = frontoffice_load_service_options();

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
    '/pagamento-spontaneo' => static function () use ($method, $serviceOptions, $env): array {
        $baseContext = [
            'service_options' => $serviceOptions,
            'default_year' => (int) date('Y'),
            'pay_portal_url' => $env('FRONTOFFICE_PAGOPA_CHECKOUT_URL', 'https://checkout.pagopa.it/'),
        ];
        if ($method === 'POST') {
            $result = frontoffice_process_spontaneous_request($_POST, $serviceOptions);
            $baseContext = array_merge($baseContext, $result);
        }
        return [
            'template' => 'pagamenti/spontaneo.html.twig',
            'context' => $baseContext,
        ];
    },
    '/pagamento-avviso' => static function () use ($method): array {
        $isPost = $method === 'POST';
        return [
            'template' => 'pagamenti/avviso.html.twig',
            'context' => [
                'form_submitted' => $isPost,
                'form_data' => $isPost ? $_POST : [],
                'form_feedback' => $isPost
                    ? [
                        'type' => 'success',
                        'title' => 'Avviso trovato',
                        'message' => 'Stiamo verificando la posizione. Presto verrai reindirizzato al portale PagoPA per concludere il pagamento.',
                    ]
                    : null,
            ],
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
    'current_user' => null,
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
