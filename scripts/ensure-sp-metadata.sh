#!/bin/bash
# Script che viene eseguito prima di docker compose up per generare i metadata SP
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Source delle variabili d'ambiente
if [ -f "$PROJECT_ROOT/.env" ]; then
    export $(cat "$PROJECT_ROOT/.env" | grep -v '^#' | xargs)
fi

if [ -d "/metadata/sp" ]; then
    METADATA_SP_DIR="/metadata/sp"
else
    METADATA_SP_DIR="${PROJECT_ROOT}/iam-proxy/metadata-sp"
fi
FRONTOFFICE_PUBLIC_BASE_URL="${FRONTOFFICE_PUBLIC_BASE_URL:-https://127.0.0.1:8444}"
METADATA_FILE="${METADATA_SP_DIR}/frontoffice_sp.xml"

# Crea la directory se non esiste
mkdir -p "$METADATA_SP_DIR"

# Se il file esiste già, non rigenerare
if [ -f "$METADATA_FILE" ]; then
    echo "[INFO] Metadata SP già presente"
    exit 0
fi

echo "[INFO] Generando metadata SP per: $FRONTOFFICE_PUBLIC_BASE_URL"

# Geniera i metadata usando uno script inline
php - "$FRONTOFFICE_PUBLIC_BASE_URL" > "$METADATA_FILE" << 'PHPEOF'
<?php
use OneLogin\Saml2\Settings;

// Carica l'autoloader di Composer se disponibile
$autoloaderPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../frontoffice/vendor/autoload.php',
];

foreach ($autoloaderPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

// Se OneLogin non è disponibile, lo script non può generare i metadata
if (!class_exists('OneLogin\Saml2\Settings')) {
    // Fallback: crea un metadata minimo senza firma
    $frontofficeBaseUrl = isset($argv[1]) ? $argv[1] : 'https://127.0.0.1:8444';
    $spEntityId = rtrim($frontofficeBaseUrl, '/') . '/saml/sp';
    $acsUrl = rtrim($frontofficeBaseUrl, '/') . '/spid/callback';
    
    $metadata = <<<XML
<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
                     validUntil="2027-02-03T15:14:13Z"
                     cacheDuration="PT604800S"
                     entityID="$spEntityId">
    <md:SPSSODescriptor AuthnRequestsSigned="false" WantAssertionsSigned="true" protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
                                Location="$frontofficeBaseUrl/spid/logout" />
        <md:NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:transient</md:NameIDFormat>
        <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"
                                     Location="$acsUrl"
                                     index="1" />
    </md:SPSSODescriptor>
</md:EntityDescriptor>
XML;
    
    echo $metadata;
    exit(0);
}

$frontofficeBaseUrl = isset($argv[1]) ? $argv[1] : 'https://127.0.0.1:8444';
$spEntityId = rtrim($frontofficeBaseUrl, '/') . '/saml/sp';
$acsUrl = rtrim($frontofficeBaseUrl, '/') . '/spid/callback';

$settings = [
    'strict' => false,
    'debug' => false,
    'sp' => [
        'entityId' => $spEntityId,
        'assertionConsumerService' => [
            'url' => $acsUrl,
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        ],
        'singleLogoutService' => [
            'url' => rtrim($frontofficeBaseUrl, '/') . '/spid/logout',
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        ],
        'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
    ],
    'idp' => [
        'entityId' => 'http://placeholder',
        'singleSignOnService' => ['url' => 'http://placeholder'],
        'x509cert' => 'placeholder',
    ],
    'security' => [
        'authnRequestsSigned' => false,
        'wantAssertionsSigned' => true,
        'wantMessagesSigned' => true,
        'wantNameId' => false,
        'wantNameIdEncrypted' => false,
        'signatureAlgorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
    ],
];

try {
    $settingsObj = new Settings($settings);
    $metadata = $settingsObj->getSPMetadata();
    $errors = $settingsObj->validateMetadata($metadata);
    
    if (!empty($errors)) {
        fwrite(STDERR, "Metadata validation errors: " . implode(", ", $errors) . "\n");
        exit(1);
    }
    
    echo $metadata;
} catch (\Exception $e) {
    fwrite(STDERR, "ERROR generating metadata: " . $e->getMessage() . "\n");
    exit(1);
}
PHPEOF

if [ $? -ne 0 ]; then
    echo "[ERROR] Fallito il caricamento dei metadata SP"
    rm -f "$METADATA_FILE"
    exit 1
fi

# Crea anche la versione senza estensione .xml
cp "$METADATA_FILE" "${METADATA_FILE%.xml}"

echo "[OK] Metadata SP generati con successo"
echo "[INFO] Metadata file: $METADATA_FILE"
