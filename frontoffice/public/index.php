<?php
declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require dirname(__DIR__) . '/vendor/autoload.php';

$env = static function (string $key, ?string $default = null): string {
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default ?? '';
    }
    return (string) $value;
};

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

$loader = new FilesystemLoader(__DIR__ . '/../templates');
$twig = new Environment($loader, [
    'cache' => false,
    'autoescape' => 'html',
]);

echo $twig->render('home.html.twig', [
    'app_entity' => [
        'name' => $entityName,
        'suffix' => $entitySuffix,
        'government' => $entityGovernment,
        'full' => $entityFull,
    ],
    'app_logo' => $appLogo,
    'app_favicon' => $appFavicon,
    'current_user' => null,
    'current_path' => '/',
    'support_email' => $supportEmail,
    'support_phone' => $env('FRONTOFFICE_SUPPORT_PHONE', '800.000.000'),
    'support_hours' => $env('FRONTOFFICE_SUPPORT_HOURS', 'Lun-Ven 8:30-17:30'),
    'support_location' => $env('FRONTOFFICE_SUPPORT_LOCATION', 'Palazzo Municipale, piano terra<br>Martedì e Giovedì 9:00-12:30 / 15:00-17:00'),
]);
