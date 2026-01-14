# Cartella SSL

Questa cartella contiene la documentazione riguardo ai certificati SSL usati dall'immagine Docker.

Il repository non committa certificati o chiavi private reali. Se non fornisci certificati validi
(ad esempio in sviluppo locale), al primo avvio del container lo script di setup genererà
automaticamente dei certificati self-signed e li posizionerà in `/ssl` all'interno del container.

File gestiti (non committati):

- `/ssl/server.crt` — certificato TLS
- `/ssl/server.key` — chiave privata

Per usare certificati personalizzati del server web:

1. Posiziona i file in `ssl/` nel repository:
	- `ssl/server.crt` — certificato TLS del server
	- `ssl/server.key` — chiave privata del server
2. Ricostruisci o riavvia i container:
	- Rebuild: `docker compose up -d --build`
	- Solo restart: `docker compose restart php-apache` (se i file sono montati come volume)

Attenzione: i certificati self-signed generati sono solo per sviluppo e testing; non usare in produzione.

Nota: i certificati presenti nella cartella `certificate/` sono distinti e servono per l'autenticazione client
verso le API GovPay (mTLS applicazione → GovPay), non per l'HTTPS del server web (browser → applicazione).

## Troubleshooting: permessi (rootless Docker / utente deploy)

Se in un ambiente Apache fallisce con un errore tipo:

`SSLCertificateKeyFile: file '/ssl/server.key' does not exist or is empty`

ma sul server i file sembrano presenti, la causa più comune è un problema di permessi sul bind mount.

Esempio classico:
- i file `ssl/server.key` e `ssl/server.crt` sulla macchina host sono **owner root** con permessi stretti (es. `600`);
- il deploy viene eseguito da un utente non-root (es. `dev`);
- se il motore container è **rootless** (o in generale non ha accesso ai file), dentro il container i file risultano
	“mancanti/vuoti” e Apache non parte.

Verifiche utili sul server:
- `ls -l ssl/server.key ssl/server.crt`
- `wc -c ssl/server.key ssl/server.crt` (attenzione a file da 0 byte)
- `docker info | grep -i rootless` (per capire se Docker è rootless)

Fix tipico (adegua utente/gruppo al tuo caso):
- `sudo chown dev:dev ssl/server.key ssl/server.crt`
- `chmod 600 ssl/server.key`
- `chmod 644 ssl/server.crt`

Nota: lo script di bootstrap può generare certificati self-signed se mancano, ma se la directory `ssl/` è montata
in sola lettura o con owner/perms non scrivibili dall'utente che esegue il deploy, la generazione può fallire e Apache
si blocca in fase di avvio.