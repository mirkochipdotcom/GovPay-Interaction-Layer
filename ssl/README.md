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