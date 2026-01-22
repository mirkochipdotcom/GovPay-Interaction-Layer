#!/bin/bash
set -euo pipefail

# Workspace runtime su volume
TARGET_DIR="/var/www/spid-cie-php"
SOURCE_DIR="/opt/spid-cie-php"

# Nota: questo entrypoint è "write-once".
# Genera i file (setup/config/cert) solo se MANCANO sul volume; se esistono, non li rigenera.
# Per rigenerare: elimina i file dal volume (es. spid-php-setup.json) o usa un working-dir dedicato.

# SimpleSAML baseurlpath (di default: myservice). Deve essere un path-segment semplice.
SPID_PROXY_SERVICE_NAME="${SPID_PROXY_SERVICE_NAME:-myservice}"
if ! echo "${SPID_PROXY_SERVICE_NAME}" | grep -Eq '^[A-Za-z0-9_-]+$'; then
  echo "[spid-proxy] WARNING: SPID_PROXY_SERVICE_NAME non valido ('${SPID_PROXY_SERVICE_NAME}'), uso 'myservice'" >&2
  SPID_PROXY_SERVICE_NAME="myservice"
fi

if [ ! -f "${TARGET_DIR}/.spid_proxy_copied" ]; then
  echo "[spid-proxy] Inizializzo working copy in ${TARGET_DIR}..."
  mkdir -p "${TARGET_DIR}"
  # Se il volume è vuoto, copio il codice. Se contiene già roba, non sovrascrivo aggressivamente.
  if [ -z "$(ls -A "${TARGET_DIR}" 2>/dev/null || true)" ]; then
    cp -a "${SOURCE_DIR}/." "${TARGET_DIR}/"
  else
    # Copia solo se mancano file chiave
    if [ ! -f "${TARGET_DIR}/composer.json" ]; then
      cp -a "${SOURCE_DIR}/." "${TARGET_DIR}/"
    fi
  fi
  touch "${TARGET_DIR}/.spid_proxy_copied" || true
fi

# Directory web persistente dove Setup.php scrive proxy.php e pagine di esempio
mkdir -p "${TARGET_DIR}/www" || true

# ---- Modalità generator metadata: consenti reset controllato ----
# In alcuni ambienti (es. dietro reverse proxy) può servire rigenerare i file persistenti
# nella working dir del generator per applicare nuove env (es. SPID_PROXY_PUBLIC_BASE_URL).
# Per evitare side-effect in produzione, abilitiamo il reset solo se:
# - SPID_PROXY_IS_METADATA_GENERATOR=1 (settata solo nel servizio spid-proxy-metadata)
# - SPID_PROXY_FORCE_REGEN_SETUP=1 (da passare esplicitamente quando serve)
if [ "${SPID_PROXY_IS_METADATA_GENERATOR:-0}" = "1" ] && [ "${SPID_PROXY_FORCE_REGEN_SETUP:-0}" = "1" ]; then
  echo "[spid-proxy] Metadata generator: reset config persistita (SPID_PROXY_FORCE_REGEN_SETUP=1)"
  rm -f "${TARGET_DIR}/spid-php-setup.json" "${TARGET_DIR}/spid-php-proxy.json" || true
  rm -f "${TARGET_DIR}/spid-php-openssl.cnf" "${TARGET_DIR}/cie-php-openssl.cnf" || true

  # Se cambia l'entityID pubblico, i certificati devono essere rigenerati (uri nel DN).
  rm -f "${TARGET_DIR}/cert/spid-sp.crt" "${TARGET_DIR}/cert/spid-sp.pem" || true
  rm -f "${TARGET_DIR}/cert/cie-sp.crt" "${TARGET_DIR}/cert/cie-sp.pem" || true
  rm -f "${TARGET_DIR}/vendor/simplesamlphp/simplesamlphp/cert/spid-sp.crt" "${TARGET_DIR}/vendor/simplesamlphp/simplesamlphp/cert/spid-sp.pem" || true
  rm -f "${TARGET_DIR}/vendor/simplesamlphp/simplesamlphp/cert/cie-sp.crt" "${TARGET_DIR}/vendor/simplesamlphp/simplesamlphp/cert/cie-sp.pem" || true

  # Forza il rerun di Setup::setup (via recovery post-update-cmd) per rigenerare cert e file web.
  rm -f "${TARGET_DIR}/www/proxy.php" "${TARGET_DIR}/www/proxy-home.php" || true
fi

# ---- Bootstrap non-interattivo (spid-php-setup.json) ----
# spid-cie-php esegue un setup interattivo durante gli script composer.
# Se spid-php-setup.json esiste ed include tutte le chiavi richieste, il setup diventa non-interattivo.
if [ ! -f "${TARGET_DIR}/spid-php-setup.json" ]; then
  echo "[spid-proxy] spid-php-setup.json mancante: genero configurazione di default (non-interattiva)"

  # Guardrail: evitare metadata con IPACode di fallback "code".
  # Se sei PA e hai scelto IPACode, devi valorizzare SPID_PROXY_ORG_CODE con un codice IPA reale (es. c_f646).
  if [ "${SPID_PROXY_ORG_IS_PUBLIC_ADMIN:-1}" = "1" ] && [ "${SPID_PROXY_ORG_CODE_TYPE:-}" = "IPACode" ]; then
    if [ -z "${SPID_PROXY_ORG_CODE:-}" ] || [ "${SPID_PROXY_ORG_CODE:-}" = "code" ]; then
      echo "[spid-proxy] ERROR: SPID_PROXY_ORG_CODE non valorizzato (o vale 'code') ma SPID_PROXY_ORG_CODE_TYPE=IPACode." >&2
      echo "[spid-proxy]        Imposta SPID_PROXY_ORG_CODE=<codice_ipa_reale> in .env.metadata (es. c_f646) e rigenera eliminando ${TARGET_DIR}/spid-php-setup.json." >&2
      exit 1
    fi
  fi

  TARGET_DIR="${TARGET_DIR}" \
  SPID_PROXY_PUBLIC_BASE_URL="${SPID_PROXY_PUBLIC_BASE_URL:-}" \
  SPID_PROXY_PUBLIC_HOST="${SPID_PROXY_PUBLIC_HOST:-}" \
  SPID_PROXY_CLIENT_ID="${SPID_PROXY_CLIENT_ID:-govpay_client}" \
  SPID_PROXY_CLIENT_SECRET="${SPID_PROXY_CLIENT_SECRET:-}" \
  SPID_PROXY_REDIRECT_URIS="${SPID_PROXY_REDIRECT_URIS:-/proxy-sample.php}" \
  SPID_PROXY_SERVICE_NAME="${SPID_PROXY_SERVICE_NAME:-myservice}" \
  SPID_PROXY_PRODUCTION="${SPID_PROXY_PRODUCTION:-0}" \
  SPID_PROXY_ADD_SPID="${SPID_PROXY_ADD_SPID:-1}" \
  SPID_PROXY_ADD_CIE="${SPID_PROXY_ADD_CIE:-0}" \
  SPID_PROXY_ADD_DEMO_IDP="${SPID_PROXY_ADD_DEMO_IDP:-1}" \
  SPID_PROXY_ADD_DEMO_VALIDATOR_IDP="${SPID_PROXY_ADD_DEMO_VALIDATOR_IDP:-1}" \
  SPID_PROXY_ADD_AGID_VALIDATOR_IDP="${SPID_PROXY_ADD_AGID_VALIDATOR_IDP:-1}" \
  SPID_PROXY_LOCAL_TEST_IDP_METADATA_URL="${SPID_PROXY_LOCAL_TEST_IDP_METADATA_URL:-}" \
  SPID_PROXY_ATTRS="${SPID_PROXY_ATTRS:-spidCode,name,familyName,fiscalNumber,email}" \
  SPID_PROXY_SIMPLESAMLPHP_ADMIN_PASSWORD="${SPID_PROXY_SIMPLESAMLPHP_ADMIN_PASSWORD:-admin}" \
  SPID_PROXY_SIMPLESAMLPHP_SECRETSALT="${SPID_PROXY_SIMPLESAMLPHP_SECRETSALT:-}" \
  SPID_PROXY_ORG_NAME="${SPID_PROXY_ORG_NAME:-}" \
  SPID_PROXY_ORG_DISPLAY_NAME="${SPID_PROXY_ORG_DISPLAY_NAME:-}" \
  SPID_PROXY_ORG_URL="${SPID_PROXY_ORG_URL:-}" \
  SPID_PROXY_ORG_IS_PUBLIC_ADMIN="${SPID_PROXY_ORG_IS_PUBLIC_ADMIN:-1}" \
  SPID_PROXY_ORG_CODE_TYPE="${SPID_PROXY_ORG_CODE_TYPE:-}" \
  SPID_PROXY_ORG_CODE="${SPID_PROXY_ORG_CODE:-}" \
  SPID_PROXY_ORG_IDENTIFIER="${SPID_PROXY_ORG_IDENTIFIER:-}" \
  SPID_PROXY_ORG_FISCAL_CODE="${SPID_PROXY_ORG_FISCAL_CODE:-}" \
  SPID_PROXY_ORG_NACE2_CODE="${SPID_PROXY_ORG_NACE2_CODE:-84.11}" \
  SPID_PROXY_ORG_COUNTRY="${SPID_PROXY_ORG_COUNTRY:-IT}" \
  SPID_PROXY_ORG_LOCALITY="${SPID_PROXY_ORG_LOCALITY:-Locality}" \
  SPID_PROXY_ORG_MUNICIPALITY="${SPID_PROXY_ORG_MUNICIPALITY:-H501}" \
  SPID_PROXY_ORG_PROVINCE="${SPID_PROXY_ORG_PROVINCE:-RM}" \
  SPID_PROXY_ORG_EMAIL="${SPID_PROXY_ORG_EMAIL:-info@organization.org}" \
  SPID_PROXY_ORG_PHONE="${SPID_PROXY_ORG_PHONE:-+3912345678}" \
  SPID_PROXY_ADD_EXAMPLES="${SPID_PROXY_ADD_EXAMPLES:-0}" \
  SPID_PROXY_ADD_PROXY_EXAMPLE="${SPID_PROXY_ADD_PROXY_EXAMPLE:-1}" \
  SPID_PROXY_FPA_ID_PAESE="${SPID_PROXY_FPA_ID_PAESE:-IT}" \
  SPID_PROXY_FPA_ID_CODICE="${SPID_PROXY_FPA_ID_CODICE:-}" \
  SPID_PROXY_FPA_DENOMINAZIONE="${SPID_PROXY_FPA_DENOMINAZIONE:-}" \
  SPID_PROXY_FPA_INDIRIZZO="${SPID_PROXY_FPA_INDIRIZZO:-}" \
  SPID_PROXY_FPA_NUMERO_CIVICO="${SPID_PROXY_FPA_NUMERO_CIVICO:-}" \
  SPID_PROXY_FPA_CAP="${SPID_PROXY_FPA_CAP:-}" \
  SPID_PROXY_FPA_COMUNE="${SPID_PROXY_FPA_COMUNE:-}" \
  SPID_PROXY_FPA_PROVINCIA="${SPID_PROXY_FPA_PROVINCIA:-RM}" \
  SPID_PROXY_FPA_NAZIONE="${SPID_PROXY_FPA_NAZIONE:-IT}" \
  SPID_PROXY_FPA_ORG_NAME="${SPID_PROXY_FPA_ORG_NAME:-}" \
  SPID_PROXY_FPA_ORG_EMAIL="${SPID_PROXY_FPA_ORG_EMAIL:-info@organization.org}" \
  SPID_PROXY_FPA_ORG_PHONE="${SPID_PROXY_FPA_ORG_PHONE:-+3912345678}" \
  SPID_PROXY_SIGN_RESPONSE="${SPID_PROXY_SIGN_RESPONSE:-1}" \
  SPID_PROXY_ENCRYPT_RESPONSE="${SPID_PROXY_ENCRYPT_RESPONSE:-0}" \
  SPID_PROXY_LEVEL="${SPID_PROXY_LEVEL:-2}" \
  SPID_PROXY_ATCS_INDEX="${SPID_PROXY_ATCS_INDEX:-0}" \
  SPID_PROXY_TOKEN_EXP_TIME="${SPID_PROXY_TOKEN_EXP_TIME:-1200}" \
  SPID_PROXY_RESPONSE_ATTR_PREFIX="${SPID_PROXY_RESPONSE_ATTR_PREFIX:-}" \
  APP_ENTITY_NAME="${APP_ENTITY_NAME:-Service Provider Name}" \
  URL_ENTE="${URL_ENTE:-}" \
  ID_DOMINIO="${ID_DOMINIO:-code}" \
  php -r '
    $target = getenv("TARGET_DIR") ?: "/var/www/spid-cie-php";
    $base = rtrim(getenv("SPID_PROXY_PUBLIC_BASE_URL") ?: "", "/");
    $host = getenv("SPID_PROXY_PUBLIC_HOST") ?: "localhost";
    // entityID NON configurabile: è determinato da come serviamo i metadata (endpoint statici).
    // - SPID: ${base}/spid-metadata.xml
    // - CIE:  ${base}/cie-metadata.xml
    $spidEntityId = $base !== "" ? ($base . "/spid-metadata.xml") : "https://localhost/spid-metadata.xml";
    $cieEntityId = $base !== "" ? ($base . "/cie-metadata.xml") : "https://localhost/cie-metadata.xml";

    $clientId = getenv("SPID_PROXY_CLIENT_ID") ?: "govpay_client";
    $clientSecret = getenv("SPID_PROXY_CLIENT_SECRET") ?: "";
    $redirectsRaw = getenv("SPID_PROXY_REDIRECT_URIS") ?: "/proxy-sample.php";
    $redirects = array_values(array_filter(array_map("trim", preg_split("/[\s,]+/", $redirectsRaw))));
    if (!$redirects) $redirects = ["/proxy-sample.php"];

    $serviceName = getenv("SPID_PROXY_SERVICE_NAME") ?: "myservice";
    $production = (getenv("SPID_PROXY_PRODUCTION") ?: "0") === "1";

    $addSpid = (getenv("SPID_PROXY_ADD_SPID") ?: "1") === "1";
    $addCie = (getenv("SPID_PROXY_ADD_CIE") ?: "0") === "1";
    $addDemoIdp = (getenv("SPID_PROXY_ADD_DEMO_IDP") ?: "1") === "1";
    $addDemoValidatorIdp = (getenv("SPID_PROXY_ADD_DEMO_VALIDATOR_IDP") ?: "1") === "1";
    $addAgidValidatorIdp = (getenv("SPID_PROXY_ADD_AGID_VALIDATOR_IDP") ?: "1") === "1";
    $localTestIdpMetadata = getenv("SPID_PROXY_LOCAL_TEST_IDP_METADATA_URL") ?: "";

    $attrsRaw = getenv("SPID_PROXY_ATTRS") ?: "spidCode,name,familyName,fiscalNumber,email";
    $attrs = array_values(array_filter(array_map("trim", explode(",", $attrsRaw))));
    // Setup.php si aspetta gli attributi già quotati con apici singoli.
    // Evitiamo un apice letterale nel codice, perché il blocco PHP è in una stringa bash tra apici singoli.
    $attrs = array_map(fn($a) => chr(39) . $a . chr(39), $attrs);

    $adminPassword = getenv("SPID_PROXY_SIMPLESAMLPHP_ADMIN_PASSWORD") ?: "admin";
    $secretsaltEnv = getenv("SPID_PROXY_SIMPLESAMLPHP_SECRETSALT") ?: "";
    $secretsalt = $secretsaltEnv !== "" ? $secretsaltEnv : bin2hex(random_bytes(16));

    $appEntityName = getenv("APP_ENTITY_NAME") ?: "Service Provider Name";
    $orgName = getenv("SPID_PROXY_ORG_NAME") ?: $appEntityName;
    $orgDisplayName = getenv("SPID_PROXY_ORG_DISPLAY_NAME") ?: $orgName;

    // Branding UI del proxy (header/logo nella pagina /proxy-home.php)
    $frontofficeBaseUrl = rtrim(getenv("FRONTOFFICE_PUBLIC_BASE_URL") ?: "", "/");
    $defaultLogo = $frontofficeBaseUrl !== "" ? ($frontofficeBaseUrl . "/img/stemma_ente.png") : "/assets/img/logo.png";
    $clientName = getenv("SPID_PROXY_CLIENT_NAME") ?: $orgDisplayName;
    $clientDescription = getenv("SPID_PROXY_CLIENT_DESCRIPTION") ?: $orgName;
    $clientLogo = getenv("SPID_PROXY_CLIENT_LOGO") ?: $defaultLogo;
    $orgUrlSource = getenv("SPID_PROXY_ORG_URL") ?: (getenv("URL_ENTE") ?: ($base !== "" ? ($base . "/") : "https://www.organization.org"));
    $orgUrl = rtrim($orgUrlSource, "/") . "/";

    $idDominio = getenv("ID_DOMINIO") ?: "code";
    $isPublicAdministration = (getenv("SPID_PROXY_ORG_IS_PUBLIC_ADMIN") ?: "1") === "1";
    $orgCodeType = getenv("SPID_PROXY_ORG_CODE_TYPE") ?: ($isPublicAdministration ? "IPACode" : "VATNumber");
    $orgCode = getenv("SPID_PROXY_ORG_CODE") ?: $idDominio;
    $orgIdentifier = getenv("SPID_PROXY_ORG_IDENTIFIER") ?: ($isPublicAdministration ? ("PA:IT-" . $orgCode) : ("VATIT-" . $orgCode));
    $orgFiscalCode = getenv("SPID_PROXY_ORG_FISCAL_CODE") ?: $orgCode;
    $orgNace2 = getenv("SPID_PROXY_ORG_NACE2_CODE") ?: "84.11";

    $orgCountry = getenv("SPID_PROXY_ORG_COUNTRY") ?: "IT";
    $orgLocality = getenv("SPID_PROXY_ORG_LOCALITY") ?: "Locality";
    $orgMunicipality = getenv("SPID_PROXY_ORG_MUNICIPALITY") ?: "H501";
    $orgProvince = getenv("SPID_PROXY_ORG_PROVINCE") ?: "RM";

    $orgEmail = getenv("SPID_PROXY_ORG_EMAIL") ?: "info@organization.org";
    $orgPhone = getenv("SPID_PROXY_ORG_PHONE") ?: "+3912345678";

  $addExamples = (getenv("SPID_PROXY_ADD_EXAMPLES") ?: "0") === "1";
  $addProxyExample = (getenv("SPID_PROXY_ADD_PROXY_EXAMPLE") ?: "1") === "1";

  // Campi FPA (richiesti dal setup quando ORG_IS_PUBLIC_ADMIN=0)
  $fpaIdPaese = getenv("SPID_PROXY_FPA_ID_PAESE") ?: "IT";
  $fpaIdCodice = getenv("SPID_PROXY_FPA_ID_CODICE") ?: $orgCode;
  $fpaDenominazione = getenv("SPID_PROXY_FPA_DENOMINAZIONE") ?: $orgName;
  $fpaIndirizzo = getenv("SPID_PROXY_FPA_INDIRIZZO") ?: "";
  $fpaNumeroCivico = getenv("SPID_PROXY_FPA_NUMERO_CIVICO") ?: "";
  $fpaCAP = getenv("SPID_PROXY_FPA_CAP") ?: "";
  $fpaComune = getenv("SPID_PROXY_FPA_COMUNE") ?: "";
  $fpaProvincia = getenv("SPID_PROXY_FPA_PROVINCIA") ?: $orgProvince;
  $fpaNazione = getenv("SPID_PROXY_FPA_NAZIONE") ?: "IT";
  $fpaOrgName = getenv("SPID_PROXY_FPA_ORG_NAME") ?: $orgName;
  $fpaOrgEmail = getenv("SPID_PROXY_FPA_ORG_EMAIL") ?: $orgEmail;
  $fpaOrgPhone = getenv("SPID_PROXY_FPA_ORG_PHONE") ?: $orgPhone;

    $signResponse = (getenv("SPID_PROXY_SIGN_RESPONSE") ?: "1") === "1";
    $encryptResponse = (getenv("SPID_PROXY_ENCRYPT_RESPONSE") ?: "0") === "1";
    $level = (int)(getenv("SPID_PROXY_LEVEL") ?: "2");
    $atcsIndex = (int)(getenv("SPID_PROXY_ATCS_INDEX") ?: "0");
    $tokenExp = (int)(getenv("SPID_PROXY_TOKEN_EXP_TIME") ?: "1200");
    $attrPrefix = getenv("SPID_PROXY_RESPONSE_ATTR_PREFIX") ?: "";

    $cfg = [
      "production" => $production,
      "acsCustomLocation" => "",
      "sloCustomLocation" => "",
      "installDir" => $target,
      "wwwDir" => $target . "/www",
      "loggingHandler" => "errorlog",
      "loggingDir" => "log/",
      "logFile" => "simplesamlphp.log",
      "serviceName" => $serviceName,
      "storeType" => "phpsession",
      "storeSqlDsn" => "mysql:host=localhost;dbname=saml",
      "storeSqlUsername" => "admin",
      "storeSqlPassword" => "password",

      // entityID usato dal setup upstream come default (storicamente SPID). Noi lo impostiamo a SPID.
      "entityID" => $spidEntityId,
      "spDomain" => $host,
      "spName" => $orgName,
      "spDescription" => $orgName,
      "spOrganizationName" => $orgName,
      "spOrganizationDisplayName" => $orgDisplayName,
      "spOrganizationURL" => $orgUrl,
      "acsIndex" => 0,

      "spIsPublicAdministration" => $isPublicAdministration,
      "spOrganizationCodeType" => $orgCodeType,
      "spOrganizationCode" => $orgCode,
      "spOrganizationIdentifier" => $orgIdentifier,
      "spOrganizationFiscalCode" => $orgFiscalCode,
      "spOrganizationNace2Code" => $orgNace2,
      "spCountryName" => $orgCountry,
      "spLocalityName" => $orgLocality,
      "spMunicipality" => $orgMunicipality,
      "spProvince" => $orgProvince,

      // Campi FPA (usati solo se spIsPublicAdministration=false)
      "fpaIdPaese" => $fpaIdPaese,
      "fpaIdCodice" => $fpaIdCodice,
      "fpaDenominazione" => $fpaDenominazione,
      "fpaIndirizzo" => $fpaIndirizzo,
      "fpaNumeroCivico" => $fpaNumeroCivico,
      "fpaCAP" => $fpaCAP,
      "fpaComune" => $fpaComune,
      "fpaProvincia" => $fpaProvincia,
      "fpaNazione" => $fpaNazione,
      "fpaOrganizationName" => $fpaOrgName,
      "fpaOrganizationEmailAddress" => $fpaOrgEmail,
      "fpaOrganizationTelephoneNumber" => $fpaOrgPhone,

      // Questi due devono essere NON null per generare metadata
      "spOrganizationEmailAddress" => $orgEmail,
      "spOrganizationTelephoneNumber" => $orgPhone,

      // Setup SPID/CIE
      "addSPID" => $addSpid,
      "addCIE" => $addCie,
      "attr" => $attrs,
      "addDemoIDP" => $addDemoIdp,
      "addDemoValidatorIDP" => $addDemoValidatorIdp,
      "addLocalTestIDP" => $localTestIdpMetadata,
      "addValidatorIDP" => $addAgidValidatorIdp,

      // Esempi
      "addExamples" => $addExamples,
      "addProxyExample" => $addProxyExample,
      "proxyConfig" => [
        "clients" => [
          $clientId => [
            "name" => $clientName,
            "description" => $clientDescription,
            "logo" => $clientLogo,
            "client_id" => $clientId,
            "client_secret" => $clientSecret,
            "level" => $level,
            "atcs_index" => $atcsIndex,
            "handler" => "Plain",
            "tokenExpTime" => $tokenExp,
            "response_attributes_prefix" => $attrPrefix,
            "redirect_uri" => $redirects,
          ],
        ],
        "signProxyResponse" => $signResponse,
        "encryptProxyResponse" => $encryptResponse,
      ],

      // SimpleSAMLphp
      "adminPassword" => $adminPassword,
      "secretsalt" => $secretsalt,
    ];

    file_put_contents($target . "/spid-php-setup.json", json_encode($cfg));
  '

  if [ ! -f "${TARGET_DIR}/spid-php-setup.json" ]; then
    echo "[spid-proxy] ERROR: fallita generazione spid-php-setup.json" >&2
    exit 1
  fi
fi

# ---- Env-driven config (dominio/base URL) ----
SPID_PROXY_PUBLIC_BASE_URL="${SPID_PROXY_PUBLIC_BASE_URL:-}"
if [ -n "${SPID_PROXY_PUBLIC_BASE_URL}" ]; then
  # normalizza (no trailing slash)
  SPID_PROXY_PUBLIC_BASE_URL="${SPID_PROXY_PUBLIC_BASE_URL%/}"
fi

# host[:port] estratto da base URL (se presente)
SPID_PROXY_PUBLIC_HOSTPORT=""
if [ -n "${SPID_PROXY_PUBLIC_BASE_URL}" ]; then
  SPID_PROXY_PUBLIC_HOSTPORT="$(echo "${SPID_PROXY_PUBLIC_BASE_URL}" | sed -E 's#^https?://([^/]+).*#\1#')"
fi

# host senza porta (per cert / spDomain)
SPID_PROXY_PUBLIC_HOST="${SPID_PROXY_PUBLIC_HOSTPORT%%:*}"
if [ -z "${SPID_PROXY_PUBLIC_HOST}" ] && [ -n "${APACHE_SERVER_NAME:-}" ]; then
  SPID_PROXY_PUBLIC_HOST="${APACHE_SERVER_NAME}"
fi
if [ -z "${SPID_PROXY_PUBLIC_HOST}" ]; then
  SPID_PROXY_PUBLIC_HOST="localhost"
fi

# ---- Certificati self-signed coerenti con entityID (solo generator, su richiesta) ----
# La validazione CIE verifica la coerenza tra entityID e certificato (campo URI nel DN).
# In modalità generator, quando forziamo la rigenerazione, generiamo cert SPID/CIE allineati
# a SPID_PROXY_PUBLIC_BASE_URL per evitare mismatch.
if [ "${SPID_PROXY_IS_METADATA_GENERATOR:-0}" = "1" ] && [ "${SPID_PROXY_FORCE_REGEN_SETUP:-0}" = "1" ] && [ -n "${SPID_PROXY_PUBLIC_BASE_URL}" ]; then
  echo "[spid-proxy] Metadata generator: genero certificati SPID/CIE coerenti con SPID_PROXY_PUBLIC_BASE_URL"

  mkdir -p "${TARGET_DIR}/vendor/simplesamlphp/simplesamlphp/cert" "${TARGET_DIR}/cert" || true

  SPID_ENTITY_ID="${SPID_PROXY_PUBLIC_BASE_URL%/}/spid-metadata.xml"
  CIE_ENTITY_ID="${SPID_PROXY_PUBLIC_BASE_URL%/}/cie-metadata.xml"

  TARGET_DIR="${TARGET_DIR}" \
  SPID_ENTITY_ID="${SPID_ENTITY_ID}" \
  CIE_ENTITY_ID="${CIE_ENTITY_ID}" \
  php -r '
    $target = getenv("TARGET_DIR") ?: "/var/www/spid-cie-php";
    $setupPath = $target . "/spid-php-setup.json";
    $cfg = is_file($setupPath) ? json_decode(file_get_contents($setupPath), true) : [];
    if (!is_array($cfg)) $cfg = [];

    $orgName = (string)($cfg["spOrganizationName"] ?? "Service Provider");
    $orgDisplay = (string)($cfg["spOrganizationDisplayName"] ?? $orgName);
    $orgId = (string)($cfg["spOrganizationIdentifier"] ?? "PA:IT-UNKNOWN");
    $country = (string)($cfg["spCountryName"] ?? "IT");
    $locality = (string)($cfg["spLocalityName"] ?? "Locality");
    if ($orgName === "") $orgName = "Service Provider";
    if ($orgDisplay === "") $orgDisplay = $orgName;
    if ($orgId === "") $orgId = "PA:IT-UNKNOWN";
    if ($country === "") $country = "IT";
    if ($locality === "") $locality = "Locality";

    $spidEntity = (string)(getenv("SPID_ENTITY_ID") ?: "");
    $cieEntity  = (string)(getenv("CIE_ENTITY_ID") ?: "");

    $spidCnf = "oid_section = spid_oids\n\n" .
      "[ req ]\n" .
      "default_bits = 3072\n" .
      "default_md = sha256\n" .
      "distinguished_name = dn\n" .
      "encrypt_key = no\n" .
      "prompt = no\n" .
      "req_extensions  = req_ext\n\n" .
      "[ spid_oids ]\n" .
      "agidcert=1.3.76.16.6\n" .
      "spid-publicsector-SP=1.3.76.16.4.2.1\n" .
      "uri=2.5.4.83\n\n" .
      "[ dn ]\n" .
      "organizationName={$orgName}\n" .
      "commonName={$orgDisplay}\n" .
      "uri={$spidEntity}\n" .
      "organizationIdentifier={$orgId}\n" .
      "countryName={$country}\n" .
      "localityName={$locality}\n\n" .
      "[ req_ext ]\n" .
      "basicConstraints=CA:FALSE\n" .
      "keyUsage=critical,digitalSignature,nonRepudiation\n" .
      "certificatePolicies=@agid_policies,@spid_policies\n\n" .
      "[ agid_policies ]\n" .
      "policyIdentifier=agidcert\n" .
      "userNotice=@agidcert_notice\n\n" .
      "[ agidcert_notice ]\n" .
      "explicitText=\"agIDcert\"\n\n" .
      "[ spid_policies ]\n" .
      "policyIdentifier = spid-publicsector-SP\n" .
      "userNotice=@spid_notice\n\n" .
      "[ spid_notice ]\n" .
      "explicitText=\"cert_SP_Pub\"\n";
    file_put_contents($target . "/spid-php-openssl.cnf", $spidCnf);

    $cieCnf = "oid_section = cie_oids\n\n" .
      "[ req ]\n" .
      "default_bits = 3072\n" .
      "default_md = sha256\n" .
      "distinguished_name = dn\n" .
      "encrypt_key = no\n" .
      "prompt = no\n" .
      "req_extensions  = req_ext\n\n" .
      "[ cie_oids ]\n" .
      "uri=2.5.4.83\n\n" .
      "[ dn ]\n" .
      "organizationName={$orgName}\n" .
      "commonName={$orgDisplay}\n" .
      "uri={$cieEntity}\n" .
      "organizationIdentifier={$orgId}\n" .
      "countryName={$country}\n" .
      "localityName={$locality}\n\n" .
      "[ req_ext ]\n";
    file_put_contents($target . "/cie-php-openssl.cnf", $cieCnf);
  ' || true

  if ! openssl req -new -x509 -config "${TARGET_DIR}/spid-php-openssl.cnf" -days 730 \
    -keyout "${TARGET_DIR}/vendor/simplesamlphp/simplesamlphp/cert/spid-sp.pem" \
    -out "${TARGET_DIR}/vendor/simplesamlphp/simplesamlphp/cert/spid-sp.crt" \
    -extensions req_ext >/dev/null 2>&1; then
    echo "[spid-proxy] ERROR: generazione certificato SPID fallita" >&2
    exit 1
  fi

  if ! openssl req -new -x509 -config "${TARGET_DIR}/cie-php-openssl.cnf" -days 730 \
    -keyout "${TARGET_DIR}/vendor/simplesamlphp/simplesamlphp/cert/cie-sp.pem" \
    -out "${TARGET_DIR}/vendor/simplesamlphp/simplesamlphp/cert/cie-sp.crt" \
    -extensions req_ext >/dev/null 2>&1; then
    echo "[spid-proxy] ERROR: generazione certificato CIE fallita" >&2
    exit 1
  fi

  cp -f "${TARGET_DIR}/vendor/simplesamlphp/simplesamlphp/cert/spid-sp.pem" "${TARGET_DIR}/cert/spid-sp.pem" || true
  cp -f "${TARGET_DIR}/vendor/simplesamlphp/simplesamlphp/cert/spid-sp.crt" "${TARGET_DIR}/cert/spid-sp.crt" || true
  cp -f "${TARGET_DIR}/vendor/simplesamlphp/simplesamlphp/cert/cie-sp.pem" "${TARGET_DIR}/cert/cie-sp.pem" || true
  cp -f "${TARGET_DIR}/vendor/simplesamlphp/simplesamlphp/cert/cie-sp.crt" "${TARGET_DIR}/cert/cie-sp.crt" || true
fi

# Fix difensivo: in alcune installazioni il file metadata remoto IdP può contenere un campo
# 'entityid' errato che punta a spid-metadata.xml (deve essere l'entityid dell'IdP o omesso).
TARGET_DIR="${TARGET_DIR}" \
php -r '
  $target = getenv("TARGET_DIR") ?: "/var/www/spid-cie-php";
  $path = $target . "/vendor/simplesamlphp/simplesamlphp/metadata/saml20-idp-remote.php";
  if (!file_exists($path)) return;
  $content = file_get_contents($path);
  $updated = preg_replace("/^\s*\x27entityid\x27\s*=>\s*\x27[^\x27]*spid-metadata\.xml\x27,\s*$/m", "", $content);
  if ($updated !== null && $updated !== $content) {
    file_put_contents($path, $updated);
  }
' || true

# ---- Proxy runtime config (spid-php-proxy.json) ----
# Necessario per /proxy-home.php e /proxy.php.
# In upstream viene creato dal setup interattivo; qui lo generiamo automaticamente se manca,
# evitando di scrivere metadata sul volume (che in runtime è montato read-only).
TARGET_DIR="${TARGET_DIR}" \
SPID_PROXY_PRODUCTION="${SPID_PROXY_PRODUCTION:-0}" \
SPID_PROXY_PUBLIC_HOST="${SPID_PROXY_PUBLIC_HOST:-localhost}" \
SPID_PROXY_CLIENT_ID="${SPID_PROXY_CLIENT_ID:-}" \
SPID_PROXY_CLIENT_SECRET="${SPID_PROXY_CLIENT_SECRET:-}" \
SPID_PROXY_REDIRECT_URIS="${SPID_PROXY_REDIRECT_URIS:-}" \
SPID_PROXY_SIGN_RESPONSE="${SPID_PROXY_SIGN_RESPONSE:-1}" \
SPID_PROXY_ENCRYPT_RESPONSE="${SPID_PROXY_ENCRYPT_RESPONSE:-0}" \
SPID_PROXY_LEVEL="${SPID_PROXY_LEVEL:-2}" \
SPID_PROXY_ATCS_INDEX="${SPID_PROXY_ATCS_INDEX:-0}" \
SPID_PROXY_TOKEN_EXP_TIME="${SPID_PROXY_TOKEN_EXP_TIME:-1200}" \
SPID_PROXY_RESPONSE_ATTR_PREFIX="${SPID_PROXY_RESPONSE_ATTR_PREFIX:-}" \
SPID_PROXY_CLIENT_NAME="${SPID_PROXY_CLIENT_NAME:-}" \
SPID_PROXY_CLIENT_DESCRIPTION="${SPID_PROXY_CLIENT_DESCRIPTION:-}" \
SPID_PROXY_CLIENT_LOGO="${SPID_PROXY_CLIENT_LOGO:-}" \
FRONTOFFICE_PUBLIC_BASE_URL="${FRONTOFFICE_PUBLIC_BASE_URL:-}" \
APP_ENTITY_NAME="${APP_ENTITY_NAME:-}" \
php -r '
  $target = getenv("TARGET_DIR") ?: "/var/www/spid-cie-php";
  $path = $target . "/spid-php-proxy.json";
  if (file_exists($path)) return;

  $production = (getenv("SPID_PROXY_PRODUCTION") ?: "0") === "1";
  $spDomain = trim(getenv("SPID_PROXY_PUBLIC_HOST") ?: "localhost");
  if ($spDomain === "") $spDomain = "localhost";

  $clientId = trim(getenv("SPID_PROXY_CLIENT_ID") ?: "govpay_client");
  if ($clientId === "") $clientId = "govpay_client";

  $clientSecret = trim(getenv("SPID_PROXY_CLIENT_SECRET") ?: "");

  $redirectsRaw = getenv("SPID_PROXY_REDIRECT_URIS") ?: "/proxy-sample.php";
  $redirects = array_values(array_filter(array_map("trim", preg_split("/[\\s,]+/", $redirectsRaw))));
  if ($redirects === []) $redirects = ["/proxy-sample.php"];

  $signResponse = trim((string)(getenv("SPID_PROXY_SIGN_RESPONSE") ?: "1")) === "1";
  $encryptResponse = trim((string)(getenv("SPID_PROXY_ENCRYPT_RESPONSE") ?: "0")) === "1";
  $level = (int)(getenv("SPID_PROXY_LEVEL") ?: "2");
  $atcsIndex = (int)(getenv("SPID_PROXY_ATCS_INDEX") ?: "0");
  $tokenExp = (int)(getenv("SPID_PROXY_TOKEN_EXP_TIME") ?: "1200");
  $attrPrefix = (string)(getenv("SPID_PROXY_RESPONSE_ATTR_PREFIX") ?: "");

  // Branding default (coerente con quanto usiamo per spid-php-setup.json)
  $frontofficeBaseUrl = rtrim(trim(getenv("FRONTOFFICE_PUBLIC_BASE_URL") ?: ""), "/");
  $appEntityName = trim(getenv("APP_ENTITY_NAME") ?: "");
  $defaultName = $appEntityName !== "" ? $appEntityName : "Service Provider";
  $name = trim(getenv("SPID_PROXY_CLIENT_NAME") ?: "");
  if ($name === "") $name = $defaultName;
  $desc = trim(getenv("SPID_PROXY_CLIENT_DESCRIPTION") ?: "");
  if ($desc === "") $desc = $defaultName;
  $logo = trim(getenv("SPID_PROXY_CLIENT_LOGO") ?: "");
  if ($logo === "" && $frontofficeBaseUrl !== "") $logo = $frontofficeBaseUrl . "/img/stemma_ente.png";
  if ($logo === "") $logo = "/assets/img/logo.png";

  $cfg = [
    "production" => $production,
    "spDomain" => $spDomain,
    "clients" => [
      $clientId => [
        "name" => $name,
        "description" => $desc,
        "logo" => $logo,
        "client_id" => $clientId,
        "client_secret" => $clientSecret,
        "level" => $level,
        "atcs_index" => $atcsIndex,
        "handler" => "Plain",
        "tokenExpTime" => $tokenExp,
        "response_attributes_prefix" => $attrPrefix,
        "redirect_uri" => $redirects,
      ],
    ],
    "signProxyResponse" => $signResponse,
    "encryptProxyResponse" => $encryptResponse,
    "tokenExpTime" => $tokenExp,
  ];

  file_put_contents($path, json_encode($cfg));
' || true

# ---- Patch runtime: conserva contesto login su proxy-home.php ----
# In upstream, il bottone SPID può generare un link GET con solo ?idp=..., perdendo client_id/redirect_uri/state.
# Senza questi parametri proxy-home.php redirige a /metadata.xml. Qui rendiamo il comportamento robusto:
# al primo caricamento salviamo i parametri in sessione, e li ripristiniamo se manca il contesto.
TARGET_DIR="${TARGET_DIR}" \
php -r '
  $target = getenv("TARGET_DIR") ?: "/var/www/spid-cie-php";
  $path = $target . "/www/proxy-home.php";
  if (!file_exists($path)) return;
  $content = file_get_contents($path);
  if (!is_string($content) || $content === "") return;
  if (strpos($content, "GOVPAY_PATCH_CTX_SESSION") !== false) return;

  $needle = "\n    \$client_id = isset(\$_GET['client_id'])? \$_GET['client_id'] : null;\n";
  $pos = strpos($content, $needle);
  if ($pos === false) return;

  // Inserisci subito dopo il blocco che legge i parametri GET (client_id/level/redirect_uri/state/idp).
  $insertAfter = "    \$idp = isset(\$_GET['idp'])? \$_GET['idp'] : null;\n";
  $pos2 = strpos($content, $insertAfter, $pos);
  if ($pos2 === false) return;
  $pos2 += strlen($insertAfter);

  $patch = "\n" .
    "    // GOVPAY_PATCH_CTX_SESSION: keep context across IdP GET links\n" .
    "    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }\n" .
    "    if (\$client_id != null && \$client_id !== '' && \$redirect_uri != null && \$redirect_uri !== '') {\n" .
    "        \$_SESSION['govpay_proxy_ctx'] = array(\n" .
    "            'client_id' => \$client_id,\n" .
    "            'level' => \$level,\n" .
    "            'redirect_uri' => \$redirect_uri,\n" .
    "            'state' => \$state,\n" .
    "        );\n" .
    "    } elseif ((\$idp != null && \$idp !== '') && isset(\$_SESSION['govpay_proxy_ctx']) && is_array(\$_SESSION['govpay_proxy_ctx'])) {\n" .
    "        \$ctx = \$_SESSION['govpay_proxy_ctx'];\n" .
    "        if (\$client_id == null || \$client_id === '') \$client_id = \$ctx['client_id'] ?? \$client_id;\n" .
    "        if (\$redirect_uri == null || \$redirect_uri === '') \$redirect_uri = \$ctx['redirect_uri'] ?? \$redirect_uri;\n" .
    "        if (\$state == null || \$state === '') \$state = \$ctx['state'] ?? \$state;\n" .
    "        if (!isset(\$_GET['level']) && isset(\$ctx['level'])) \$level = \$ctx['level'];\n" .
    "    }\n";

  $updated = substr($content, 0, $pos2) . $patch . substr($content, $pos2);
  file_put_contents($path, $updated);
' || true

# ---- Branding UI del proxy (nome/descrizione/logo) ----
# La pagina /proxy-home.php legge questi valori da spid-php-proxy.json.
# Nota: per evitare side-effect sul metadata, NON modifichiamo spid-php-setup.json su volumi già inizializzati.
TARGET_DIR="${TARGET_DIR}" \
SPID_PROXY_CLIENT_ID="${SPID_PROXY_CLIENT_ID:-}" \
SPID_PROXY_CLIENT_NAME="${SPID_PROXY_CLIENT_NAME:-}" \
SPID_PROXY_CLIENT_DESCRIPTION="${SPID_PROXY_CLIENT_DESCRIPTION:-}" \
SPID_PROXY_CLIENT_LOGO="${SPID_PROXY_CLIENT_LOGO:-}" \
SPID_PROXY_SERVICE_NAME="${SPID_PROXY_SERVICE_NAME:-}" \
FRONTOFFICE_PUBLIC_BASE_URL="${FRONTOFFICE_PUBLIC_BASE_URL:-}" \
APP_ENTITY_NAME="${APP_ENTITY_NAME:-}" \
php -r '
  $target = getenv("TARGET_DIR") ?: "/var/www/spid-cie-php";
  $clientIdEnv = trim(getenv("SPID_PROXY_CLIENT_ID") ?: "");
  $clientNameEnv = trim(getenv("SPID_PROXY_CLIENT_NAME") ?: "");
  $clientDescEnv = trim(getenv("SPID_PROXY_CLIENT_DESCRIPTION") ?: "");
  $clientLogoEnv = trim(getenv("SPID_PROXY_CLIENT_LOGO") ?: "");
  $serviceNameEnv = trim(getenv("SPID_PROXY_SERVICE_NAME") ?: "");
  $frontofficeBase = rtrim(trim(getenv("FRONTOFFICE_PUBLIC_BASE_URL") ?: ""), "/");
  $appEntityName = trim(getenv("APP_ENTITY_NAME") ?: "");

  $deriveName = function(string $existing) use ($clientNameEnv, $appEntityName): string {
    if ($clientNameEnv !== "") return $clientNameEnv;
    if ($appEntityName !== "") return $appEntityName;
    return $existing;
  };
  $deriveDesc = function(string $existing) use ($clientDescEnv, $appEntityName): string {
    if ($clientDescEnv !== "") return $clientDescEnv;
    if ($appEntityName !== "") return $appEntityName;
    return $existing;
  };
  $deriveLogo = function(string $existing) use ($clientLogoEnv, $frontofficeBase): string {
    if ($clientLogoEnv !== "") return $clientLogoEnv;
    if ($frontofficeBase !== "") return $frontofficeBase . "/img/stemma_ente.png";
    return $existing;
  };

  $updateProxyJson = function() use ($target, $clientIdEnv, $deriveName, $deriveDesc, $deriveLogo) {
    $path = $target . "/spid-php-proxy.json";
    if (!file_exists($path)) return;
    $cfg = json_decode(file_get_contents($path), true);
    if (!is_array($cfg)) return;
    if (!isset($cfg["clients"]) || !is_array($cfg["clients"]) || $cfg["clients"] === []) return;

    $clientId = $clientIdEnv !== "" ? $clientIdEnv : (array_keys($cfg["clients"])[0] ?? "");
    if ($clientId === "" || !isset($cfg["clients"][$clientId]) || !is_array($cfg["clients"][$clientId])) return;

    $existingName = (string)($cfg["clients"][$clientId]["name"] ?? "");
    $existingDesc = (string)($cfg["clients"][$clientId]["description"] ?? "");
    $existingLogo = (string)($cfg["clients"][$clientId]["logo"] ?? "");

    $cfg["clients"][$clientId]["name"] = $deriveName($existingName);
    $cfg["clients"][$clientId]["description"] = $deriveDesc($existingDesc);
    $cfg["clients"][$clientId]["logo"] = $deriveLogo($existingLogo);

    file_put_contents($path, json_encode($cfg));
  };

  $updateProxyJson();
' || true

if [ -n "${SPID_PROXY_PUBLIC_BASE_URL}" ]; then
  echo "[spid-proxy] Configuro dominio pubblico: ${SPID_PROXY_PUBLIC_BASE_URL} (host=${SPID_PROXY_PUBLIC_HOST})"

  # Aggiorna Apache: ServerName + redirect HTTP->HTTPS
  if [ -f /etc/apache2/conf-available/servername.conf ]; then
    echo "ServerName ${SPID_PROXY_PUBLIC_HOST}" > /etc/apache2/conf-available/servername.conf || true
  fi
  if [ -f /etc/apache2/sites-available/000-default.conf ]; then
    sed -i -E "s/^[[:space:]]*ServerName[[:space:]]+.*/    ServerName ${SPID_PROXY_PUBLIC_HOST}/" /etc/apache2/sites-available/000-default.conf || true
    sed -i -E "s#^[[:space:]]*Redirect[[:space:]]+permanent[[:space:]]+/[[:space:]]+https://[^/]+/#    Redirect permanent / ${SPID_PROXY_PUBLIC_BASE_URL}/#" /etc/apache2/sites-available/000-default.conf || true

    # Allinea Alias + rewrite al serviceName scelto (evita che cambiare SPID_PROXY_SERVICE_NAME rompa il proxy).
    sed -i -E "s#^[[:space:]]*Alias[[:space:]]+/[^[:space:]]+[[:space:]]+/var/www/spid-cie-php/vendor/simplesamlphp/simplesamlphp/www#    Alias /${SPID_PROXY_SERVICE_NAME} /var/www/spid-cie-php/vendor/simplesamlphp/simplesamlphp/www#" /etc/apache2/sites-available/000-default.conf || true
    sed -i -E "s#^([[:space:]]*RewriteRule[[:space:]]+\^/\(metadata\\\.xml\|spid-metadata\\\.xml\)\$)[[:space:]]+/[^/]+/module\\.php/saml/sp/metadata\\.php/spid(.*)#\1 /${SPID_PROXY_SERVICE_NAME}/module.php/saml/sp/metadata.php/spid\2#" /etc/apache2/sites-available/000-default.conf || true
    sed -i -E "s#^([[:space:]]*RewriteRule[[:space:]]+\^/cie-metadata\\\.xml\$)[[:space:]]+/[^/]+/module\\.php/saml/sp/metadata\\.php/cie(.*)#\1 /${SPID_PROXY_SERVICE_NAME}/module.php/saml/sp/metadata.php/cie\2#" /etc/apache2/sites-available/000-default.conf || true
  fi

  # Allinea baseurlpath di SimpleSAML (config.php) al serviceName scelto.
  # NOTA: questo non cambia il metadata servito (entityID/ACS/SLO), ma evita redirect/URL errati nei moduli.
  TARGET_DIR="${TARGET_DIR}" \
  SPID_PROXY_SERVICE_NAME="${SPID_PROXY_SERVICE_NAME}" \
  php -r '
    $target = getenv("TARGET_DIR") ?: "/var/www/spid-cie-php";
    $serviceName = trim(getenv("SPID_PROXY_SERVICE_NAME") ?: "myservice");
    $path = $target . "/vendor/simplesamlphp/simplesamlphp/config/config.php";
    if (!file_exists($path)) return;
    $content = file_get_contents($path);
    if (!is_string($content) || $content === "") return;

    $desired = $serviceName . "/";

    // Sostituzione robusta: intercetta qualunque riga con baseurlpath (indentazione/virgolette variabili).
    $updated = preg_replace(
      "/(^\s*[\x27\"]baseurlpath[\x27\"]\s*=>\s*)[\x27\"][^\x27\"]*[\x27\"](\s*,\s*$)/m",
      "\\1" . chr(39) . addslashes($desired) . chr(39) . "\\2",
      $content
    );

    // Se baseurlpath non esiste, inseriscilo subito dopo l'apertura dell'array di config.
    if ($updated !== null && $updated === $content && strpos($content, "baseurlpath") === false) {
      $insert = "    " . chr(39) . "baseurlpath" . chr(39) . " => " . chr(39) . $desired . chr(39) . ",\n";
      $updated = preg_replace("/(\$config\s*=\s*array\s*\(\s*\n)/", "\\1" . $insert, $content, 1);
      if ($updated === null) {
        $updated = $content;
      }
    }

    if ($updated !== null && $updated !== $content) {
      file_put_contents($path, $updated);
    }
  ' || true

  # Nota: non riscriviamo file persistenti (setup/proxy/authsources/openssl) su volumi già inizializzati.
  # L'output dei metadata è gestito dalla presenza/assenza dei file sul volume e dalla pipeline generate->promote.
fi

# Cert self-signed se mancante (dev)
needs_cert=false
if [ ! -f /ssl/server.crt ] || [ ! -f /ssl/server.key ]; then
  needs_cert=true
else
  # Apache si lamenta se il server cert è marcato come CA. Se succede, rigenero.
  if openssl x509 -in /ssl/server.crt -noout -text 2>/dev/null | grep -q "CA:TRUE"; then
    needs_cert=true
  fi
fi

if [ "${needs_cert}" = true ]; then
  echo "[spid-proxy] Certificati SSL mancanti/non idonei: genero self-signed in /ssl"
  mkdir -p /ssl
  rm -f /ssl/server.crt /ssl/server.key || true
  CERT_HOST="${SPID_PROXY_PUBLIC_HOST:-localhost}"
  CERT_HOST="${CERT_HOST%%:*}"
  if [ -z "${CERT_HOST}" ]; then
    CERT_HOST="localhost"
  fi
  SAN="DNS:${CERT_HOST}"
  if [ "${CERT_HOST}" != "localhost" ]; then
    SAN="${SAN},DNS:localhost"
  fi
  openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /ssl/server.key -out /ssl/server.crt \
    -subj "/CN=${CERT_HOST}" \
    -addext "subjectAltName=${SAN}" \
    -addext "basicConstraints=CA:FALSE" \
    -addext "keyUsage=digitalSignature,keyEncipherment" \
    -addext "extendedKeyUsage=serverAuth" >/dev/null 2>&1 || true
  chmod 600 /ssl/server.key || true
  chmod 644 /ssl/server.crt || true
fi

# Nota: composer install di spid-cie-php può richiedere input (setup interattivo).
# Se esistono già i file di config (spid-php-setup.json) possiamo eseguire il setup in modo non-interattivo.
# IMPORTANT: trattiamo vendor come "incompleto" se manca vendor/autoload.php (è necessario per qualunque endpoint PHP).
if [ ! -f "${TARGET_DIR}/vendor/autoload.php" ]; then
  # Composer 2.9+ può bloccare dipendenze con security advisories (es. Twig 2 richiesto da SimpleSAMLphp 1.19.x).
  # Per questo progetto, lasciamo proseguire l'install evitando il blocco "insecure".
  (COMPOSER_ALLOW_SUPERUSER=1 composer config --global audit.block-insecure false >/dev/null 2>&1) || true

  if [ -d "${TARGET_DIR}/vendor" ]; then
    echo "[spid-proxy] vendor/ presente ma autoload.php mancante: rigenero autoloader con composer dump-autoload"
    (cd "${TARGET_DIR}" && COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --no-interaction --no-ansi) || true
  else
    echo "[spid-proxy] vendor/ mancante: eseguo composer install (no-interaction) per installare dipendenze e generare autoloader"
    (cd "${TARGET_DIR}" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --no-progress) || true
  fi

  if [ ! -f "${TARGET_DIR}/vendor/autoload.php" ]; then
    echo "[spid-proxy] ERROR: vendor/autoload.php ancora mancante dopo composer. Impossibile proseguire." >&2
    # In modalità generator è meglio fallire (altrimenti produrremmo metadata vuoti).
    exit 1
  fi
fi

# Recovery: se le dipendenze sono installate ma i file web non sono stati generati (es. primo avvio fallito),
# rilancia lo script di setup che scrive `www/proxy.php` / `www/proxy-home.php` e i metadata.
if [ -d "${TARGET_DIR}/vendor" ] && { [ ! -f "${TARGET_DIR}/www/proxy.php" ] || [ ! -f "${TARGET_DIR}/www/proxy-home.php" ]; }; then
  echo "[spid-proxy] vendor/ presente ma file web mancanti: rilancio composer post-update-cmd (Setup::setup)"
  (COMPOSER_ALLOW_SUPERUSER=1 composer config --global audit.block-insecure false >/dev/null 2>&1) || true
  (cd "${TARGET_DIR}" && COMPOSER_ALLOW_SUPERUSER=1 composer run-script post-update-cmd --no-interaction --no-ansi) || true
fi

# In modalità generator, quando richiediamo un reset esplicito, forziamo comunque
# il rerun di Setup::setup per rigenerare certificati/config a prescindere dallo stato dei file web.
if [ "${SPID_PROXY_IS_METADATA_GENERATOR:-0}" = "1" ] && [ "${SPID_PROXY_FORCE_REGEN_SETUP:-0}" = "1" ]; then
  echo "[spid-proxy] Metadata generator: forzo composer post-update-cmd (Setup::setup)"
  (COMPOSER_ALLOW_SUPERUSER=1 composer config --global audit.block-insecure false >/dev/null 2>&1) || true
  (cd "${TARGET_DIR}" && COMPOSER_ALLOW_SUPERUSER=1 composer run-script post-update-cmd --no-interaction --no-ansi) || true
fi

# Allinea entityID SPID/CIE in authsources.php (hardcoded sul base URL pubblico).
# È un dettaglio di “serving” (endpoint /spid-metadata.xml e /cie-metadata.xml), quindi NON è configurabile via env.
if [ -n "${SPID_PROXY_PUBLIC_BASE_URL}" ]; then
  AUTH_SOURCES_PATH="${TARGET_DIR}/vendor/simplesamlphp/simplesamlphp/config/authsources.php"
  if [ -f "${AUTH_SOURCES_PATH}" ]; then
    # Recovery extra-robusto: rimuove eventuali token letterali "\x27" rimasti da patch precedenti.
    # Se presenti, rendono il file PHP non parsabile e i metadata non vengono serviti.
    sed -i "s/\\\\x27/'/g" "${AUTH_SOURCES_PATH}" || true

    # Allinea gli entityID SPID/CIE al base URL pubblico (necessario per metadata e certificati coerenti).
    SPID_ENTITY_ID="${SPID_PROXY_PUBLIC_BASE_URL%/}/spid-metadata.xml" \
    CIE_ENTITY_ID="${SPID_PROXY_PUBLIC_BASE_URL%/}/cie-metadata.xml" \
    AUTH_SOURCES_PATH="${AUTH_SOURCES_PATH}" \
    php -r '
      $path = getenv("AUTH_SOURCES_PATH");
      $spid = getenv("SPID_ENTITY_ID");
      $cie  = getenv("CIE_ENTITY_ID");
      if (!$path || !is_file($path) || !$spid || !$cie) { exit(0); }
      $lines = file($path);
      if ($lines === false) { exit(0); }
      $sq = chr(39);
      $section = null;
      $changed = false;
      foreach ($lines as $i => $line) {
        if (strpos($line, $sq . "spid" . $sq) !== false && strpos($line, "=>") !== false) { $section = "spid"; }
        if (strpos($line, $sq . "cie" . $sq)  !== false && strpos($line, "=>") !== false) { $section = "cie"; }

        if ($section !== null && preg_match("/^\\s*\\),\\s*$/", $line)) { $section = null; }

        if ($section !== null && strpos($line, $sq . "entityID" . $sq) !== false) {
          $pattern = "/^\\s*" . preg_quote($sq, "/") . "entityID" . preg_quote($sq, "/") . "\\s*=>\\s*" . preg_quote($sq, "/") . "[^" . preg_quote($sq, "/") . "]*" . preg_quote($sq, "/") . ",\\s*$/";
          if (!preg_match($pattern, $line)) { continue; }
          $indent = preg_replace("/(^\\s*).*/", "$1", $line);
          $new = $indent . $sq . "entityID" . $sq . " => " . $sq . ($section === "spid" ? $spid : $cie) . $sq . ",\n";
          if ($new !== $line) {
            $lines[$i] = $new;
            $changed = true;
          }
        }
      }
      if ($changed) {
        file_put_contents($path, implode("", $lines));
      }
    ' || true
  fi
fi

exec "$@"
