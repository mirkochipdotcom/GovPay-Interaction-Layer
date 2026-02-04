#!/bin/bash
# Script di inizializzazione che genera i metadata SP e li rende disponibili a SATOSA
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
if [ -d "/metadata/sp" ]; then
    METADATA_SP_DIR="/metadata/sp"
else
    METADATA_SP_DIR="${PROJECT_ROOT}/iam-proxy/metadata-sp"
fi
FRONTOFFICE_PUBLIC_BASE_URL="${FRONTOFFICE_PUBLIC_BASE_URL:-https://127.0.0.1:8444}"
DEFAULT_SP_CERT_PATH="${METADATA_SP_DIR}/sp-signing.crt"
DEFAULT_SP_KEY_PATH="${METADATA_SP_DIR}/sp-signing.key"
FRONTOFFICE_SAML_SP_X509CERT="${FRONTOFFICE_SAML_SP_X509CERT:-$DEFAULT_SP_CERT_PATH}"
FRONTOFFICE_SAML_SP_PRIVATEKEY="${FRONTOFFICE_SAML_SP_PRIVATEKEY:-$DEFAULT_SP_KEY_PATH}"

echo "[INIT] Inizializzazione metadata SP per SATOSA..."

# Crea la directory se non esiste
mkdir -p "$METADATA_SP_DIR"

# File metadata SP
METADATA_FILE="${METADATA_SP_DIR}/frontoffice_sp.xml"

is_inline_pem() {
    echo "$1" | grep -q "BEGIN "
}

ensure_sp_signing_keys() {
    local cert_path="$1"
    local key_path="$2"
    if is_inline_pem "$cert_path" || is_inline_pem "$key_path"; then
        return 0
    fi
    if [ -f "$cert_path" ] && [ -f "$key_path" ]; then
        return 0
    fi
    if ! command -v openssl >/dev/null 2>&1; then
        echo "[WARN] openssl non trovato: impossibile generare certificati SP di default"
        return 0
    fi
    echo "[INFO] Generazione certificati SP di default"
    local org_name="${APP_ENTITY_NAME:-GovPay}"
    mkdir -p "$(dirname "$cert_path")" "$(dirname "$key_path")"
    openssl req -newkey rsa:2048 -nodes \
        -keyout "$key_path" \
        -x509 -days 3650 \
        -out "$cert_path" \
        -subj "/C=IT/O=${org_name}/CN=SP Metadata Signing" >/dev/null 2>&1
}

ensure_sp_signing_keys "$FRONTOFFICE_SAML_SP_X509CERT" "$FRONTOFFICE_SAML_SP_PRIVATEKEY"

export FRONTOFFICE_SAML_SP_X509CERT
export FRONTOFFICE_SAML_SP_PRIVATEKEY

# Se il file esiste già, non rigenerare
if [ -f "$METADATA_FILE" ]; then
    echo "[INFO] Metadata SP già presente: $METADATA_FILE"
    exit 0
fi

# Genere i metadata usando il container frontoffice
echo "[INFO] Generando metadata SP per: $FRONTOFFICE_PUBLIC_BASE_URL"

# Funzione per generare metadata (inline PHP per evitare dipendenza da file)
php - "$FRONTOFFICE_PUBLIC_BASE_URL" << 'EOF'
<?php
require_once '/var/www/html/vendor/autoload.php';
use OneLogin\Saml2\Settings;

$frontofficeBaseUrl = isset($argv[1]) ? $argv[1] : 'https://127.0.0.1:8444';
$spEntityId = rtrim($frontofficeBaseUrl, '/') . '/saml/sp';
$acsUrl = rtrim($frontofficeBaseUrl, '/') . '/spid/callback';
$sloUrl = rtrim($frontofficeBaseUrl, '/') . '/logout';
$orgName = getenv('APP_ENTITY_NAME') ?: 'GovPay';
$orgSuffix = getenv('APP_ENTITY_SUFFIX') ?: '';
$orgDisplay = trim($orgName . ($orgSuffix !== '' ? ' - ' . $orgSuffix : '')) ?: $orgName;
$orgUrl = getenv('APP_ENTITY_URL') ?: rtrim($frontofficeBaseUrl, '/');
$supportEmail = getenv('APP_SUPPORT_EMAIL');
if (!$supportEmail) {
    $domain = preg_replace('/[^a-z0-9]+/', '', strtolower($orgName)) ?: 'ente';
    $supportEmail = 'support@' . $domain . '.it';
}

$spCert = getenv('FRONTOFFICE_SAML_SP_X509CERT') ?: '';
$spKey = getenv('FRONTOFFICE_SAML_SP_PRIVATEKEY') ?: '';

$loadPem = static function (string $value): string {
    $trimmed = trim($value);
    if ($trimmed === '') {
        return '';
    }
    if (str_contains($trimmed, 'BEGIN ')) {
        return $trimmed;
    }
    if (is_file($trimmed)) {
        $content = @file_get_contents($trimmed);
        return $content !== false ? trim($content) : '';
    }
    return $trimmed;
};

$spCert = $loadPem($spCert);
$spKey = $loadPem($spKey);
$signMetadata = ($spCert !== '' && $spKey !== '');

$settings = [
    'strict' => false,
    'debug' => false,
    'sp' => [
        'entityId' => $spEntityId,
        'assertionConsumerService' => [
            'url' => $acsUrl,
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'index' => 0,
            'isDefault' => true,
        ],
        'singleLogoutService' => [
            'url' => $sloUrl,
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        ],
        'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
        'x509cert' => $spCert,
        'privateKey' => $spKey,
        'attributeConsumingService' => [
            'serviceName' => $orgDisplay,
            'serviceDescription' => $orgDisplay,
            'requestedAttributes' => [
                ['name' => 'spidCode', 'friendlyName' => 'spidCode', 'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic', 'isRequired' => true],
                ['name' => 'name', 'friendlyName' => 'name', 'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic', 'isRequired' => true],
                ['name' => 'familyName', 'friendlyName' => 'familyName', 'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic', 'isRequired' => true],
                ['name' => 'fiscalNumber', 'friendlyName' => 'fiscalNumber', 'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic', 'isRequired' => true],
                ['name' => 'email', 'friendlyName' => 'email', 'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic', 'isRequired' => true],
            ],
        ],
    ],
    'idp' => [
        'entityId' => 'http://placeholder',
        'singleSignOnService' => ['url' => 'http://placeholder'],
        'x509cert' => 'placeholder',
    ],
    'organization' => [
        'en' => [
            'name' => $orgName,
            'displayname' => $orgDisplay,
            'url' => $orgUrl,
        ],
        'it' => [
            'name' => $orgName,
            'displayname' => $orgDisplay,
            'url' => $orgUrl,
        ],
    ],
    'contactPerson' => [
        'other' => [
            'givenName' => $orgName,
            'emailAddress' => $supportEmail,
        ],
    ],
    'security' => [
        'authnRequestsSigned' => false,
        'wantAssertionsSigned' => true,
        'wantMessagesSigned' => true,
        'wantNameId' => false,
        'wantNameIdEncrypted' => false,
        'signatureAlgorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
        'signMetadata' => $signMetadata,
        'signMetadataAlgorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
        'digestAlgorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256',
    ],
];

try {
    $settingsObj = new Settings($settings);
    $metadata = $settingsObj->getSPMetadata();
    $errors = $settingsObj->validateMetadata($metadata);
    
    if (!empty($errors)) {
        error_log("Metadata validation errors: " . implode(", ", $errors));
        exit(1);
    }
    
    echo $metadata;
} catch (\Exception $e) {
    error_log("ERROR generating metadata: " . $e->getMessage());
    exit(1);
}
EOF

# Salva i metadata generati
if [ $? -eq 0 ]; then
    echo "[OK] Metadata SP generati con successo"
else
    echo "[ERROR] Fallito il caricamento dei metadata SP"
    exit 1
fi
