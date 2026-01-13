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
# Se esistono già i file di config (spid-php-setup.json / spid-php-proxy.json) proviamo no-interaction.
if [ ! -d "${TARGET_DIR}/vendor" ]; then
  if [ -f "${TARGET_DIR}/spid-php-setup.json" ]; then
    echo "[spid-proxy] vendor/ mancante: provo composer install (no-interaction) usando config esistente"
    (cd "${TARGET_DIR}" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction) || true
  else
    echo "[spid-proxy] vendor/ mancante e nessuna spid-php-setup.json presente." >&2
    echo "[spid-proxy] Esegui setup interattivo una volta:" >&2
    echo "  docker compose exec spid-proxy bash" >&2
    echo "  cd /var/www/spid-cie-php" >&2
    echo "  composer install" >&2
  fi
fi

exec "$@"
