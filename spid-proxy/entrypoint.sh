#!/bin/bash
set -euo pipefail

# Workspace runtime su volume
TARGET_DIR="/var/www/spid-cie-php"
SOURCE_DIR="/opt/spid-cie-php"

# Modalità gestione metadata:
# - mutable (default): comportamento attuale, aggiorna file di setup/config in base alle env.
# - locked: NON modifica automaticamente file che impattano metadata (setup/authsources/openssl/proxy config).
SPID_PROXY_METADATA_MODE="${SPID_PROXY_METADATA_MODE:-mutable}"
if [ "${SPID_PROXY_METADATA_MODE}" != "mutable" ] && [ "${SPID_PROXY_METADATA_MODE}" != "locked" ]; then
  echo "[spid-proxy] WARNING: SPID_PROXY_METADATA_MODE non valido ('${SPID_PROXY_METADATA_MODE}'), uso 'mutable'" >&2
  SPID_PROXY_METADATA_MODE="mutable"
fi

# SimpleSAML baseurlpath (di default: myservice). Deve essere un path-segment semplice.
SPID_PROXY_SERVICE_NAME="${SPID_PROXY_SERVICE_NAME:-myservice}"
if ! echo "${SPID_PROXY_SERVICE_NAME}" | grep -Eq '^[A-Za-z0-9_-]+$'; then
  echo "[spid-proxy] WARNING: SPID_PROXY_SERVICE_NAME non valido ('${SPID_PROXY_SERVICE_NAME}'), uso 'myservice'" >&2
  SPID_PROXY_SERVICE_NAME="myservice"
fi

# Snapshot/Frozen metadata (file statici sotto www/metadata/)
SPID_PROXY_METADATA_SNAPSHOT_ON_START="${SPID_PROXY_METADATA_SNAPSHOT_ON_START:-0}"
SPID_PROXY_METADATA_PUBLISH_ON_START="${SPID_PROXY_METADATA_PUBLISH_ON_START:-0}"
SPID_PROXY_METADATA_PUBLISH_TARGET="${SPID_PROXY_METADATA_PUBLISH_TARGET:-current}"
SPID_PROXY_GENERATE_ONLY="${SPID_PROXY_GENERATE_ONLY:-0}"

if [ "${SPID_PROXY_METADATA_PUBLISH_TARGET}" != "current" ] && [ "${SPID_PROXY_METADATA_PUBLISH_TARGET}" != "next" ]; then
  echo "[spid-proxy] WARNING: SPID_PROXY_METADATA_PUBLISH_TARGET non valido ('${SPID_PROXY_METADATA_PUBLISH_TARGET}'), uso 'current'" >&2
  SPID_PROXY_METADATA_PUBLISH_TARGET="current"
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

# ---- UI overrides (NO vendor changes in git) ----
# Il progetto spid-cie-php non lega sempre la UI al flag addCIE; qui applichiamo un override minimo e idempotente.
# Strategia: inseriamo un blocco CSS condizionale che nasconde tab/pannello CIE se SPID_PROXY_ADD_CIE!=1.
PROXY_HOME_FILE="${TARGET_DIR}/www/proxy-home.php"
if [ -f "${PROXY_HOME_FILE}" ]; then
  if ! grep -q "GOVPAY_CIE_UI_TOGGLE" "${PROXY_HOME_FILE}"; then
    echo "[spid-proxy] Applico override UI CIE (toggle via SPID_PROXY_ADD_CIE) su proxy-home.php"
    php -r '
      $p = getenv("PROXY_HOME_FILE");
      if (!$p || !is_file($p)) { exit(0); }
      $c = file_get_contents($p);
      if ($c === false) { exit(0); }
      if (strpos($c, "GOVPAY_CIE_UI_TOGGLE") !== false) { exit(0); }
      $needle = "<?php \$spidsdk->insertSPIDButtonCSS(); ?>";
      if (strpos($c, $needle) === false) { exit(0); }

      $injection = "<!-- GOVPAY_CIE_UI_TOGGLE -->\n" .
        "<?php if ((getenv(\"SPID_PROXY_ADD_CIE\") ?: \"0\") !== \"1\") { ?>\n" .
        "<style>\n" .
        "  /* Hide CIE UI when disabled via env */\n" .
        "  a[href=\"#tab-cie\"], a[aria-controls=\"tab-cie\"], #tab-cie { display: none !important; }\n" .
        "</style>\n" .
        "<?php } ?>\n";

      $c = str_replace($needle, $injection . $needle, $c);
      file_put_contents($p, $c);
    ' PROXY_HOME_FILE="${PROXY_HOME_FILE}"
  fi
fi

# ---- Forzatura rigenerazione setup/metadata ----
# Per default, spid-cie-php salva la configurazione su volume e non rigenera ad ogni avvio.
# Imposta SPID_PROXY_FORCE_SETUP=1 per forzare una rigenerazione (utile quando cambi env e vuoi
# rigenerare metadata/config senza cancellare tutto il volume).
FORCE_SETUP_RUN=0
echo "[spid-proxy] Force flags: SPID_PROXY_FORCE_SETUP=${SPID_PROXY_FORCE_SETUP:-0} SPID_PROXY_FORCE_CERT=${SPID_PROXY_FORCE_CERT:-0}"

# Guardrail produzione: se metadata è locked, non permettere rigenerazioni accidentali.
if [ "${SPID_PROXY_METADATA_MODE}" = "locked" ] && { [ "${SPID_PROXY_FORCE_SETUP:-0}" = "1" ] || [ "${SPID_PROXY_FORCE_CERT:-0}" = "1" ]; }; then
  echo "[spid-proxy] WARNING: SPID_PROXY_METADATA_MODE=locked: ignoro SPID_PROXY_FORCE_SETUP/SPID_PROXY_FORCE_CERT per proteggere il metadata congelato" >&2
  SPID_PROXY_FORCE_SETUP=0
  SPID_PROXY_FORCE_CERT=0
fi

if [ "${SPID_PROXY_FORCE_SETUP:-0}" = "1" ]; then
  echo "[spid-proxy] SPID_PROXY_FORCE_SETUP=1: forzo rigenerazione setup (spid-php-setup.json + config/metadata)"
  FORCE_SETUP_RUN=1
  rm -f "${TARGET_DIR}/spid-php-setup.json" "${TARGET_DIR}/spid-php-openssl.cnf" "${TARGET_DIR}/spid-php-proxy.json" || true
  # Questi file sono generati dal Setup e vivono su bind-mount: se restano, rischi di vedere valori vecchi
  # (es. spid.codeValue/IPACode) anche dopo aver cambiato .env.spid.
  rm -f "${TARGET_DIR}/vendor/simplesamlphp/simplesamlphp/config/authsources.php" || true
  echo "[spid-proxy] Cleanup done: $(ls -1 ${TARGET_DIR}/spid-php-setup.json ${TARGET_DIR}/spid-php-openssl.cnf ${TARGET_DIR}/spid-php-proxy.json 2>/dev/null | wc -l) files remain"
  # Opzionale: rigenera anche i certificati SPID (cert/). Attenzione: cambierà la chiave del SP.
  if [ "${SPID_PROXY_FORCE_CERT:-0}" = "1" ]; then
    echo "[spid-proxy] SPID_PROXY_FORCE_CERT=1: rimuovo certificati SPID in ${TARGET_DIR}/cert/"
    rm -rf "${TARGET_DIR}/cert"/* || true
  fi
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
      echo "[spid-proxy]        Imposta SPID_PROXY_ORG_CODE=<codice_ipa_reale> in .env.spid (es. c_f646) e rigenera con SPID_PROXY_FORCE_SETUP=1 + force-recreate." >&2
      exit 1
    fi
  fi

  TARGET_DIR="${TARGET_DIR}" \
  SPID_PROXY_PUBLIC_BASE_URL="${SPID_PROXY_PUBLIC_BASE_URL:-}" \
  SPID_PROXY_PUBLIC_HOST="${SPID_PROXY_PUBLIC_HOST:-}" \
  SPID_PROXY_ENTITY_ID="${SPID_PROXY_ENTITY_ID:-}" \
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
    $entityId = getenv("SPID_PROXY_ENTITY_ID") ?: "";
    if ($entityId === "" && $base !== "") {
      $entityId = $base . "/spid-metadata.xml";
    }

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

      "entityID" => $entityId !== "" ? $entityId : "https://localhost",
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
  ' || true
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

# EntityID (default: ${base}/spid-metadata.xml)
SPID_PROXY_ENTITY_ID="${SPID_PROXY_ENTITY_ID:-}"
if [ -z "${SPID_PROXY_ENTITY_ID}" ] && [ -n "${SPID_PROXY_PUBLIC_BASE_URL}" ]; then
  SPID_PROXY_ENTITY_ID="${SPID_PROXY_PUBLIC_BASE_URL}/spid-metadata.xml"
fi

# ---- Branding UI del proxy (nome/descrizione/logo) ----
# La pagina /proxy-home.php legge questi valori da spid-php-proxy.json (client.name/client.logo) e, in parte,
# da spid-php-setup.json (proxyConfig). Aggiorniamo entrambi in modo idempotente ad ogni avvio.
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

  $updateSetupJson = function() use ($target, $clientIdEnv, $deriveName, $deriveDesc, $deriveLogo) {
    $path = $target . "/spid-php-setup.json";
    if (!file_exists($path)) return;
    $cfg = json_decode(file_get_contents($path), true);
    if (!is_array($cfg)) return;

    if (!isset($cfg["proxyConfig"]) || !is_array($cfg["proxyConfig"])) return;
    if (!isset($cfg["proxyConfig"]["clients"]) || !is_array($cfg["proxyConfig"]["clients"]) || $cfg["proxyConfig"]["clients"] === []) return;

    $clientId = $clientIdEnv !== "" ? $clientIdEnv : (array_keys($cfg["proxyConfig"]["clients"])[0] ?? "");
    if ($clientId === "" || !isset($cfg["proxyConfig"]["clients"][$clientId]) || !is_array($cfg["proxyConfig"]["clients"][$clientId])) return;

    $existingName = (string)($cfg["proxyConfig"]["clients"][$clientId]["name"] ?? "");
    $existingDesc = (string)($cfg["proxyConfig"]["clients"][$clientId]["description"] ?? "");
    $existingLogo = (string)($cfg["proxyConfig"]["clients"][$clientId]["logo"] ?? "");

    $cfg["proxyConfig"]["clients"][$clientId]["name"] = $deriveName($existingName);
    $cfg["proxyConfig"]["clients"][$clientId]["description"] = $deriveDesc($existingDesc);
    $cfg["proxyConfig"]["clients"][$clientId]["logo"] = $deriveLogo($existingLogo);

    file_put_contents($path, json_encode($cfg));
  };

  $updateServiceName = function() use ($target, $serviceNameEnv) {
    if ($serviceNameEnv === "") return;
    $path = $target . "/spid-php-setup.json";
    if (!file_exists($path)) return;
    $cfg = json_decode(file_get_contents($path), true);
    if (!is_array($cfg)) return;
    // serviceName è usato per baseurlpath di SimpleSAML (es. /myservice).
    $cfg["serviceName"] = $serviceNameEnv;
    file_put_contents($path, json_encode($cfg));
  };

  $updateProxyJson();
  $updateSetupJson();
  $updateServiceName();
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
    $replacement = "\x27baseurlpath\x27 => \"{$serviceName}/\",";
    // Rimpiazza qualunque baseurlpath esistente (stringa) con quello desiderato.
    $updated = preg_replace("/^\s*\x27baseurlpath\x27\s*=>\s*\x27[^\x27]*\x27\s*,\s*$/m", "    {$replacement}", $content);
    if ($updated !== null && $updated !== $content) {
      file_put_contents($path, $updated);
    }
  ' || true

  if [ "${SPID_PROXY_METADATA_MODE}" = "locked" ]; then
    echo "[spid-proxy] Metadata LOCKED: salto aggiornamento automatico di setup/authsources/openssl/proxy config"
  else
    # Override file di configurazione (persistenti su bind-mount)
    TARGET_DIR="${TARGET_DIR}" \
    SPID_PROXY_PUBLIC_BASE_URL="${SPID_PROXY_PUBLIC_BASE_URL}" \
    SPID_PROXY_PUBLIC_HOST="${SPID_PROXY_PUBLIC_HOST}" \
    SPID_PROXY_ENTITY_ID="${SPID_PROXY_ENTITY_ID}" \
    SPID_PROXY_CLIENT_ID="${SPID_PROXY_CLIENT_ID:-}" \
    SPID_PROXY_CLIENT_SECRET="${SPID_PROXY_CLIENT_SECRET:-}" \
    SPID_PROXY_REDIRECT_URIS="${SPID_PROXY_REDIRECT_URIS:-}" \
    SPID_PROXY_SIGN_RESPONSE="${SPID_PROXY_SIGN_RESPONSE:-}" \
    SPID_PROXY_ENCRYPT_RESPONSE="${SPID_PROXY_ENCRYPT_RESPONSE:-}" \
    APP_ENTITY_NAME="${APP_ENTITY_NAME:-}" \
    URL_ENTE="${URL_ENTE:-}" \
    php -r '
    $target = getenv("TARGET_DIR") ?: "/var/www/spid-cie-php";
    $base = getenv("SPID_PROXY_PUBLIC_BASE_URL") ?: "";
    $host = getenv("SPID_PROXY_PUBLIC_HOST") ?: "localhost";
    $entityId = getenv("SPID_PROXY_ENTITY_ID") ?: "";
    $clientId = getenv("SPID_PROXY_CLIENT_ID") ?: "";
    $redirectsRaw = getenv("SPID_PROXY_REDIRECT_URIS") ?: "";
    $appEntityName = getenv("APP_ENTITY_NAME") ?: "";
    $urlEnte = getenv("URL_ENTE") ?: "";

    $applySetup = function() use ($target, $base, $host, $entityId, $appEntityName, $urlEnte) {
      $path = $target . "/spid-php-setup.json";
      if (!file_exists($path)) return;
      $cfg = json_decode(file_get_contents($path), true);
      if (!is_array($cfg)) $cfg = [];

      if ($base !== "") {
        $cfg["spOrganizationURL"] = $base . "/";
      }
      $cfg["spDomain"] = $host;
      if ($entityId !== "") {
        $cfg["entityID"] = $entityId;
      }
      if ($appEntityName !== "") {
        $cfg["spOrganizationName"] = $appEntityName;
        $cfg["spOrganizationDisplayName"] = $appEntityName;
      }
      if ($urlEnte !== "") {
        $cfg["spOrganizationURL"] = rtrim($urlEnte, "/") . "/";
      }

      file_put_contents($path, json_encode($cfg));
    };

    $clientSecret = getenv("SPID_PROXY_CLIENT_SECRET") ?: "";
    $signResponse = (getenv("SPID_PROXY_SIGN_RESPONSE") ?: "1") === "1";
    $encryptResponseRequested = (getenv("SPID_PROXY_ENCRYPT_RESPONSE") ?: "0") === "1";
    // Il flusso "encrypt" ha senso solo se almeno firmi la response.
    // Inoltre richiede un secret condiviso con il client che deve decifrare.
    $encryptResponse = $encryptResponseRequested && $signResponse && $clientSecret !== "";

    $applyProxy = function() use ($target, $host, $clientId, $redirectsRaw, $clientSecret, $signResponse, $encryptResponse) {
      $path = $target . "/spid-php-proxy.json";
      if (!file_exists($path)) return;
      $cfg = json_decode(file_get_contents($path), true);
      if (!is_array($cfg)) $cfg = [];

      $cfg["spDomain"] = $host;

      // Allinea modalità di response lato proxy.
      $cfg["signProxyResponse"] = (bool)$signResponse;
      $cfg["encryptProxyResponse"] = (bool)$encryptResponse;

      if (!isset($cfg["clients"]) || !is_array($cfg["clients"])) {
        $cfg["clients"] = [];
      }

      if ($clientId !== "") {
        $currentKeys = array_keys($cfg["clients"]);
        $oldKey = $currentKeys[0] ?? $clientId;
        if (!isset($cfg["clients"][$clientId])) {
          $cfg["clients"][$clientId] = $cfg["clients"][$oldKey] ?? ["client_id" => $clientId];
        }
        if ($oldKey !== $clientId && isset($cfg["clients"][$oldKey])) {
          unset($cfg["clients"][$oldKey]);
        }
        $cfg["clients"][$clientId]["client_id"] = $clientId;

        // Secret condiviso per decifrare JWE (se encryptProxyResponse=true)
        $cfg["clients"][$clientId]["client_secret"] = $clientSecret;

        // Se l handler è definito in modo errato o assente, proxy.php usa i flag globali.
        // Qui forziamo un valore coerente, così il comportamento è esplicito.
        if (!$signResponse) {
          $cfg["clients"][$clientId]["handler"] = "Plain";
        } elseif ($encryptResponse) {
          $cfg["clients"][$clientId]["handler"] = "EncryptSign";
        } else {
          $cfg["clients"][$clientId]["handler"] = "Sign";
        }

        if ($redirectsRaw !== "") {
          $parts = array_values(array_filter(array_map("trim", preg_split("/[\s,]+/", $redirectsRaw))));
          if (count($parts) > 0) {
            $cfg["clients"][$clientId]["redirect_uri"] = $parts;
          }
        }
      }

      file_put_contents($path, json_encode($cfg));
    };

    $applyAuthsources = function() use ($target, $entityId, $base, $urlEnte) {
      if ($entityId === "" && $base === "" && $urlEnte === "") return;
      $path = $target . "/vendor/simplesamlphp/simplesamlphp/config/authsources.php";
      if (!file_exists($path)) return;
      $content = file_get_contents($path);
      if ($entityId !== "") {
          $content = preg_replace("/\x27entityID\x27\s*=>\s*\x27[^\x27]*\x27/", "\x27entityID\x27 => \x27" . addslashes($entityId) . "\x27", $content);
      }
      $orgSource = $urlEnte !== "" ? $urlEnte : $base;
      if ($orgSource !== "") {
        $orgUrl = rtrim($orgSource, "/") . "/";
          $content = preg_replace(
            "/\x27OrganizationURL\x27\s*=>\s*array\(\x27\\w+\x27\s*=>\s*\x27[^\x27]*\x27\)/",
            "\x27OrganizationURL\x27 => array(\x27it\x27=> \x27" . addslashes($orgUrl) . "\x27)",
            $content
          );
      }
      file_put_contents($path, $content);
    };

    $applyOpenSSL = function() use ($target, $entityId) {
      if ($entityId === "") return;
      $path = $target . "/spid-php-openssl.cnf";
      if (!file_exists($path)) return;
      $content = file_get_contents($path);
      $content = preg_replace("/^uri=.*$/m", "uri=" . $entityId, $content);
      file_put_contents($path, $content);
    };

    $applySetup();
    $applyProxy();
    $applyAuthsources();
    $applyOpenSSL();
  ' || true
  fi
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
  CERT_HOST="${SPID_PROXY_PUBLIC_HOST}"
  CERT_HOST="${CERT_HOST%%:*}"
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
if [ ! -d "${TARGET_DIR}/vendor" ]; then
  echo "[spid-proxy] vendor/ mancante: eseguo composer update (no-interaction) per installare dipendenze e lanciare Setup::setup"
  # Composer 2.9+ può bloccare dipendenze con security advisories (es. Twig 2 richiesto da SimpleSAMLphp 1.19.x).
  # Per questo progetto, lasciamo proseguire l'install evitando il blocco "insecure".
  (COMPOSER_ALLOW_SUPERUSER=1 composer config --global audit.block-insecure false >/dev/null 2>&1) || true
  (cd "${TARGET_DIR}" && COMPOSER_ALLOW_SUPERUSER=1 composer update --no-interaction --no-progress) || true
fi

# Se richiesto, forza l'esecuzione dello script di setup anche se i file web esistono già.
if [ "$FORCE_SETUP_RUN" = "1" ] && [ -d "${TARGET_DIR}/vendor" ]; then
  echo "[spid-proxy] Forzo Setup::setup (composer post-update-cmd) e update-metadata"
  (COMPOSER_ALLOW_SUPERUSER=1 composer config --global audit.block-insecure false >/dev/null 2>&1) || true
  if [ -f "${TARGET_DIR}/spid-php-setup.json" ]; then
    echo "[spid-proxy] Setup.json (estratto): $(php -r '$p="/var/www/spid-cie-php/spid-php-setup.json"; $j=@json_decode(@file_get_contents($p),true); if(!is_array($j)) { echo "<invalid>"; exit(0);} echo "spOrganizationCodeType=".($j["spOrganizationCodeType"]??"<missing>")." spOrganizationCode=".($j["spOrganizationCode"]??"<missing>");')"
  else
    echo "[spid-proxy] ERRORE: spid-php-setup.json non presente prima del setup"
    exit 1
  fi

  (cd "${TARGET_DIR}" && COMPOSER_ALLOW_SUPERUSER=1 composer run-script post-update-cmd --no-interaction --no-ansi)
  (cd "${TARGET_DIR}" && COMPOSER_ALLOW_SUPERUSER=1 composer run-script update-metadata --no-interaction --no-ansi)

  if [ -f "${TARGET_DIR}/vendor/simplesamlphp/simplesamlphp/config/authsources.php" ]; then
    echo "[spid-proxy] Authsources.php (estratto): $(php -r '$p="/var/www/spid-cie-php/vendor/simplesamlphp/simplesamlphp/config/authsources.php"; $c=@file_get_contents($p); if($c===false){echo"<unreadable>"; exit(0);} $t=""; if(preg_match("/\\x27spid\\.codeType\\x27\\s*=>\\s*\\x27([^\\x27]*)\\x27/",$c,$m)) $t.="spid.codeType=".$m[1]." "; if(preg_match("/\\x27spid\\.codeValue\\x27\\s*=>\\s*\\x27([^\\x27]*)\\x27/",$c,$m)) $t.="spid.codeValue=".$m[1]; echo trim($t)?:"<not-found>";')"
  else
    echo "[spid-proxy] ERRORE: authsources.php non generato dopo post-update-cmd"
    exit 1
  fi
fi

# Recovery: se le dipendenze sono installate ma i file web non sono stati generati (es. primo avvio fallito),
# rilancia lo script di setup che scrive `www/proxy.php` e i metadata.
if [ -d "${TARGET_DIR}/vendor" ] && [ ! -f "${TARGET_DIR}/www/proxy.php" ]; then
  echo "[spid-proxy] vendor/ presente ma www/proxy.php mancante: rilancio composer post-update-cmd (Setup::setup)"
  (COMPOSER_ALLOW_SUPERUSER=1 composer config --global audit.block-insecure false >/dev/null 2>&1) || true
  (cd "${TARGET_DIR}" && COMPOSER_ALLOW_SUPERUSER=1 composer run-script post-update-cmd --no-interaction --no-ansi) || true
fi

# Esegue eventuale snapshot metadata DOPO che vendor/config sono presenti.
if [ "${SPID_PROXY_METADATA_SNAPSHOT_ON_START}" = "1" ]; then
  if [ -d "${TARGET_DIR}/vendor/simplesamlphp/simplesamlphp/www" ]; then
    META_DIR="${TARGET_DIR}/www/metadata"
    mkdir -p "${META_DIR}" || true
    TS="$(date -u +"%Y%m%dT%H%M%SZ" 2>/dev/null || date +"%Y%m%d%H%M%S")"
    OUT_FILE="${META_DIR}/spid-metadata-${TS}.xml"
    echo "[spid-proxy] Snapshot metadata: ${OUT_FILE}"

    apache2ctl start >/dev/null 2>&1 || true
    if command -v curl >/dev/null 2>&1; then
      curl -ksS --max-time 30 "https://localhost/myservice/module.php/saml/sp/metadata.php/spid" -o "${OUT_FILE}" || true
    else
      echo "[spid-proxy] WARNING: curl non disponibile, impossibile fare snapshot metadata" >&2
    fi
    apache2ctl stop >/dev/null 2>&1 || true

    if [ ! -s "${OUT_FILE}" ]; then
      echo "[spid-proxy] WARNING: snapshot metadata vuoto o fallito (${OUT_FILE})" >&2
      rm -f "${OUT_FILE}" || true
    else
      if [ "${SPID_PROXY_METADATA_PUBLISH_ON_START}" = "1" ]; then
        if [ "${SPID_PROXY_METADATA_PUBLISH_TARGET}" = "next" ]; then
          cp -f "${OUT_FILE}" "${META_DIR}/spid-metadata-next.xml" || true
          echo "[spid-proxy] Pubblicato metadata NEXT: ${META_DIR}/spid-metadata-next.xml"
        else
          cp -f "${OUT_FILE}" "${META_DIR}/spid-metadata-current.xml" || true
          echo "[spid-proxy] Pubblicato metadata CURRENT: ${META_DIR}/spid-metadata-current.xml"
        fi
      fi
    fi
  else
    echo "[spid-proxy] WARNING: vendor SimpleSAML assente, salto snapshot metadata" >&2
  fi
fi

if [ "${SPID_PROXY_GENERATE_ONLY}" = "1" ]; then
  echo "[spid-proxy] SPID_PROXY_GENERATE_ONLY=1: esco dopo generazione/snapshot (non avvio Apache in foreground)"
  exit 0
fi

exec "$@"
