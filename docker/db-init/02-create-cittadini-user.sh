#!/bin/bash
set -euo pipefail

if [ -z "${DB_USER_CITTADINI:-}" ] || [ -z "${DB_PASSWORD_CITTADINI:-}" ]; then
  echo "[db-init] Variabili DB_USER_CITTADINI/DB_PASSWORD_CITTADINI non impostate, salto."
  exit 0
fi

cat <<'EON'
[db-init] Creazione utente cittadini se necessario...
EON

mysql --protocol=socket -u root -p"${MYSQL_ROOT_PASSWORD}" <<SQL
CREATE USER IF NOT EXISTS '${DB_USER_CITTADINI}'@'%' IDENTIFIED BY '${DB_PASSWORD_CITTADINI}';
GRANT SELECT, INSERT, UPDATE ON \`${MYSQL_DATABASE}\`.* TO '${DB_USER_CITTADINI}'@'%';
FLUSH PRIVILEGES;
SQL
