#!/bin/bash
set -euo pipefail

# Workspace runtime su volume
TARGET_DIR="/var/www/spid-cie-php"
SOURCE_DIR="/opt/spid-cie-php"

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

# ---- Bootstrap non-interattivo (spid-php-setup.json) ----
# spid-cie-php esegue un setup interattivo durante gli script composer.
# Se spid-php-setup.json esiste ed include tutte le chiavi richieste, il setup diventa non-interattivo.
if [ ! -f "${TARGET_DIR}/spid-php-setup.json" ]; then
  echo "[spid-proxy] spid-php-setup.json mancante: genero configurazione di default (non-interattiva)"
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
      "addExamples" => false,
      "addProxyExample" => true,
      "proxyConfig" => [
        "clients" => [
          $clientId => [
            "name" => "Default client",
            "logo" => "/assets/img/logo.png",
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

if [ -n "${SPID_PROXY_PUBLIC_BASE_URL}" ]; then
  echo "[spid-proxy] Configuro dominio pubblico: ${SPID_PROXY_PUBLIC_BASE_URL} (host=${SPID_PROXY_PUBLIC_HOST})"

  # Aggiorna Apache: ServerName + redirect HTTP->HTTPS
  if [ -f /etc/apache2/conf-available/servername.conf ]; then
    echo "ServerName ${SPID_PROXY_PUBLIC_HOST}" > /etc/apache2/conf-available/servername.conf || true
  fi
  if [ -f /etc/apache2/sites-available/000-default.conf ]; then
    sed -i -E "s/^[[:space:]]*ServerName[[:space:]]+.*/    ServerName ${SPID_PROXY_PUBLIC_HOST}/" /etc/apache2/sites-available/000-default.conf || true
    sed -i -E "s#^[[:space:]]*Redirect[[:space:]]+permanent[[:space:]]+/[[:space:]]+https://[^/]+/#    Redirect permanent / ${SPID_PROXY_PUBLIC_BASE_URL}/#" /etc/apache2/sites-available/000-default.conf || true
  fi

  # Override file di configurazione (persistenti su bind-mount)
  TARGET_DIR="${TARGET_DIR}" \
  SPID_PROXY_PUBLIC_BASE_URL="${SPID_PROXY_PUBLIC_BASE_URL}" \
  SPID_PROXY_PUBLIC_HOST="${SPID_PROXY_PUBLIC_HOST}" \
  SPID_PROXY_ENTITY_ID="${SPID_PROXY_ENTITY_ID}" \
  SPID_PROXY_CLIENT_ID="${SPID_PROXY_CLIENT_ID:-}" \
  SPID_PROXY_REDIRECT_URIS="${SPID_PROXY_REDIRECT_URIS:-}" \
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

    $applyProxy = function() use ($target, $host, $clientId, $redirectsRaw) {
      $path = $target . "/spid-php-proxy.json";
      if (!file_exists($path)) return;
      $cfg = json_decode(file_get_contents($path), true);
      if (!is_array($cfg)) $cfg = [];

      $cfg["spDomain"] = $host;

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
        $content = preg_replace("/'entityID'\s*=>\s*'[^']*'/", "'entityID' => '" . addslashes($entityId) . "'", $content);
      }
      $orgSource = $urlEnte !== "" ? $urlEnte : $base;
      if ($orgSource !== "") {
        $orgUrl = rtrim($orgSource, "/") . "/";
        $content = preg_replace("/'OrganizationURL'\s*=>\s*array\('\w+'\s*=>\s*'[^']*'\)/", "'OrganizationURL' => array('it'=> '" . addslashes($orgUrl) . "')", $content);
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
  (cd "${TARGET_DIR}" && COMPOSER_ALLOW_SUPERUSER=1 composer update --no-interaction --no-progress) || true
fi

exec "$@"
