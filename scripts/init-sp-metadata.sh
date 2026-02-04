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
if [ -d "/var/www/html/spid-certs" ]; then
    SPID_CERTS_DIR="/var/www/html/spid-certs"
else
    SPID_CERTS_DIR="${PROJECT_ROOT}/iam-proxy/spid-certs"
fi
FRONTOFFICE_PUBLIC_BASE_URL="${FRONTOFFICE_PUBLIC_BASE_URL:-https://127.0.0.1:8444}"
DEFAULT_SP_CERT_PATH="${SPID_CERTS_DIR}/sp-signing.crt"
DEFAULT_SP_KEY_PATH="${SPID_CERTS_DIR}/sp-signing.key"
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
$ipaCode = getenv('APP_ENTITY_IPA_CODE');
if (!$ipaCode) {
    $ipaCode = getenv('SATOSA_CONTACT_PERSON_IPA_CODE');
}
if (!$ipaCode) {
    $ipaCode = 'c_x000';
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

if ($signMetadata) {
    $pkey = @openssl_pkey_get_private($spKey);
    if ($pkey === false) {
        error_log('Invalid SP private key: unable to load private key');
        exit(1);
    }
    if (@openssl_x509_parse($spCert) === false) {
        error_log('Invalid SP certificate: unable to parse X509');
        exit(1);
    }
}

$authnRequestsSigned = $signMetadata;

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
        'authnRequestsSigned' => $authnRequestsSigned,
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
    $spData = $settingsObj->getSPData();
    $securityData = $settingsObj->getSecurityData();
    $contacts = $settingsObj->getContacts();
    $organization = $settingsObj->getOrganization();
    $metadata = \OneLogin\Saml2\Metadata::builder(
        $spData,
        (bool)($securityData['authnRequestsSigned'] ?? false),
        (bool)($securityData['wantAssertionsSigned'] ?? false),
        null,
        null,
        $contacts,
        $organization
    );
    if (strpos($metadata, 'xmlns:spid=') === false) {
        $metadata = preg_replace('/<md:EntityDescriptor\b/', '<md:EntityDescriptor xmlns:spid="https://spid.gov.it/saml-extensions"', $metadata, 1);
    }
    $metadata = preg_replace('/(<md:ContactPerson[^>]*contactType="other"[^>]*>)/', '$1' . "\n        <md:Extensions>\n            <spid:IPACode>{$ipaCode}</spid:IPACode>\n            <spid:Public />\n        </md:Extensions>", $metadata, 1);
    $metadata = preg_replace('/<md:AssertionConsumerService([^>]*?)index=\"\d+\"([^>]*?)\/>/', '<md:AssertionConsumerService$1index="0"$2 isDefault="true" />', $metadata);
    $metadata = preg_replace('/<md:AttributeConsumingService index=\"\d+\"/', '<md:AttributeConsumingService index="0"', $metadata);
    if ($signMetadata) {
        $metadata = \OneLogin\Saml2\Metadata::addX509KeyDescriptors($metadata, $spCert, false);
        $metadata = \OneLogin\Saml2\Metadata::signMetadata(
            $metadata,
            $spKey,
            $spCert,
            $securityData['signMetadataAlgorithm'] ?? null,
            $securityData['digestAlgorithm'] ?? null
        );
    }
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
