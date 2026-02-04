#!/bin/sh
# Script che viene eseguito prima di docker compose up per generare i metadata SP
set -e

# In container usa path assoluto, altrimenti calcola da script location
if [ -d "/metadata/sp" ]; then
    METADATA_SP_DIR="/metadata/sp"
else
    SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
    PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
    METADATA_SP_DIR="${PROJECT_ROOT}/iam-proxy/metadata-sp"
fi
FRONTOFFICE_PUBLIC_BASE_URL="${FRONTOFFICE_PUBLIC_BASE_URL:-https://127.0.0.1:8444}"
DEFAULT_SP_CERT_PATH="${METADATA_SP_DIR}/sp-signing.crt"
DEFAULT_SP_KEY_PATH="${METADATA_SP_DIR}/sp-signing.key"
FRONTOFFICE_SAML_SP_X509CERT="${FRONTOFFICE_SAML_SP_X509CERT:-$DEFAULT_SP_CERT_PATH}"
FRONTOFFICE_SAML_SP_PRIVATEKEY="${FRONTOFFICE_SAML_SP_PRIVATEKEY:-$DEFAULT_SP_KEY_PATH}"
METADATA_BASENAME="frontoffice_sp.xml"
FORCE_OVERWRITE=0
MODE_NEW=0

while [ "$#" -gt 0 ]; do
    case "$1" in
        --new)
            MODE_NEW=1
            ;;
        --force)
            FORCE_OVERWRITE=1
            ;;
        --output)
            shift
            if [ -n "$1" ]; then
                METADATA_BASENAME="$1"
            fi
            ;;
    esac
    shift
done

if [ "$MODE_NEW" -eq 1 ]; then
    METADATA_BASENAME="frontoffice_sp-new.xml"
fi

METADATA_FILE="${METADATA_SP_DIR}/${METADATA_BASENAME}"

if [ "$MODE_NEW" -eq 1 ] && [ -f "$METADATA_FILE" ]; then
    TS="$(date +%Y%m%d%H%M%S)"
    METADATA_FILE="${METADATA_SP_DIR}/frontoffice_sp-new-${TS}.xml"
fi

# Crea la directory se non esiste
mkdir -p "$METADATA_SP_DIR"

is_inline_pem() {
    echo "$1" | grep -q "BEGIN "
}

normalize_cert_path() {
    local value="$1"
    if is_inline_pem "$value"; then
        echo "$value"
        return
    fi
    case "$value" in
        /var/www/html/metadata-sp/*)
            echo "${METADATA_SP_DIR}/${value#/var/www/html/metadata-sp/}"
            ;;
        /metadata/sp/*)
            echo "${METADATA_SP_DIR}/${value#/metadata/sp/}"
            ;;
        *)
            echo "$value"
            ;;
    esac
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

FRONTOFFICE_SAML_SP_X509CERT="$(normalize_cert_path "$FRONTOFFICE_SAML_SP_X509CERT")"
FRONTOFFICE_SAML_SP_PRIVATEKEY="$(normalize_cert_path "$FRONTOFFICE_SAML_SP_PRIVATEKEY")"

ensure_sp_signing_keys "$FRONTOFFICE_SAML_SP_X509CERT" "$FRONTOFFICE_SAML_SP_PRIVATEKEY"

export FRONTOFFICE_SAML_SP_X509CERT
export FRONTOFFICE_SAML_SP_PRIVATEKEY

# Se il file esiste già, non rigenerare (modalità idempotente)
if [ -f "$METADATA_FILE" ] && [ "$FORCE_OVERWRITE" -ne 1 ]; then
    echo "[INFO] Metadata SP già presente"
    exit 0
fi

echo "[INFO] Generando metadata SP per: $FRONTOFFICE_PUBLIC_BASE_URL"

# Genera i metadata usando uno script inline (scrivi su file temp poi esegui)
cat > /tmp/generate-metadata.php << 'PHPEOF'
<?php
use OneLogin\Saml2\Settings;

// Carica l'autoloader di Composer se disponibile
$autoloaderPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../frontoffice/vendor/autoload.php',
    '/var/www/html/vendor/autoload.php',
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
    
    $metadata = <<<XML
<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
                     validUntil="2027-02-03T15:14:13Z"
                     cacheDuration="PT604800S"
                     entityID="$spEntityId">
    <md:SPSSODescriptor AuthnRequestsSigned="true" WantAssertionsSigned="true" protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
                                Location="$sloUrl" />
        <md:NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:transient</md:NameIDFormat>
        <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"
                         Location="$acsUrl"
                         index="0"
                         isDefault="true" />
        <md:AttributeConsumingService index="0">
            <md:ServiceName xml:lang="it">$orgDisplay</md:ServiceName>
            <md:RequestedAttribute Name="spidCode" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:basic" isRequired="true"/>
            <md:RequestedAttribute Name="name" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:basic" isRequired="true"/>
            <md:RequestedAttribute Name="familyName" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:basic" isRequired="true"/>
            <md:RequestedAttribute Name="fiscalNumber" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:basic" isRequired="true"/>
            <md:RequestedAttribute Name="email" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:basic" isRequired="true"/>
        </md:AttributeConsumingService>
    </md:SPSSODescriptor>
    <md:Organization>
        <md:OrganizationName xml:lang="it">$orgName</md:OrganizationName>
        <md:OrganizationName xml:lang="en">$orgName</md:OrganizationName>
        <md:OrganizationDisplayName xml:lang="it">$orgDisplay</md:OrganizationDisplayName>
        <md:OrganizationDisplayName xml:lang="en">$orgDisplay</md:OrganizationDisplayName>
        <md:OrganizationURL xml:lang="it">$orgUrl</md:OrganizationURL>
        <md:OrganizationURL xml:lang="en">$orgUrl</md:OrganizationURL>
    </md:Organization>
    <md:ContactPerson contactType="other">
        <md:GivenName>$orgName</md:GivenName>
        <md:EmailAddress>$supportEmail</md:EmailAddress>
    </md:ContactPerson>
</md:EntityDescriptor>
XML;
    
    echo $metadata;
    exit(0);
}

$frontofficeBaseUrl = isset($argv[1]) ? $argv[1] : 'https://127.0.0.1:8444';
$spEntityId = rtrim($frontofficeBaseUrl, '/') . '/saml/sp';
$acsUrl = rtrim($frontofficeBaseUrl, '/') . '/spid/callback';

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
            'url' => rtrim($frontofficeBaseUrl, '/') . '/logout',
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
    $metadata = $settingsObj->getSPMetadata();
    if (strpos($metadata, 'xmlns:spid=') === false) {
        $metadata = preg_replace('/<md:EntityDescriptor\b/', '<md:EntityDescriptor xmlns:spid="https://spid.gov.it/saml-extensions"', $metadata, 1);
    }
    $metadata = preg_replace('/(<md:ContactPerson[^>]*contactType="other"[^>]*>)/', '$1' . "\n        <md:Extensions>\n            <spid:IPACode>{$ipaCode}</spid:IPACode>\n            <spid:Public />\n        </md:Extensions>", $metadata, 1);
    $metadata = preg_replace('/<md:AssertionConsumerService([^>]*?)index="\d+"([^>]*?)\/>/', '<md:AssertionConsumerService$1index="0"$2 isDefault="true" />', $metadata);
    $metadata = preg_replace('/<md:AttributeConsumingService index="\d+"/', '<md:AttributeConsumingService index="0"', $metadata);
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

php /tmp/generate-metadata.php "$FRONTOFFICE_PUBLIC_BASE_URL" > "$METADATA_FILE"

if [ $? -ne 0 ]; then
    echo "[ERROR] Fallito il caricamento dei metadata SP"
    rm -f "$METADATA_FILE"
    exit 1
fi

# Crea anche la versione senza estensione .xml
cp "$METADATA_FILE" "${METADATA_FILE%.xml}"

echo "[OK] Metadata SP generati con successo"
echo "[INFO] Metadata file: $METADATA_FILE"
