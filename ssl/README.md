# Cartella SSL

Questa cartella contiene la documentazione riguardo ai certificati SSL usati dall'immagine Docker.

Il repository non committa certificati o chiavi private reali. Se l'utente non fornisce certificati validi
(ad esempio in un ambiente di sviluppo locale), al primo avvio del container lo script di setup genererà
automaticamente dei certificati self-signed e li posizionerà in `/ssl` all'interno del container.

File gestiti (non committati):

- `/ssl/server.crt` — certificato TLS
- `/ssl/server.key` — chiave privata

Se vuoi fornire certificati personalizzati, monta la cartella `ssl/` nel container o copia i file nella directory
prima di buildare l'immagine.

Attenzione: i certificati self-signed generati sono solo per sviluppo e testing; non usare in produzione.