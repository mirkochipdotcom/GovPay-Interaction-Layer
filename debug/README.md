# Strumento di debug (montato senza rebuild)

Questa cartella contiene pagine/utility di debug servite da `public/debug/` e montate come volume nel container. 
Obiettivo: poter aggiungere/modificare file di debug e vederli subito, senza ricostruire l'immagine Docker.

## Come funziona

- Nel `docker-compose.yml` montiamo la cartella di debug:
  ```yaml
  services:
    php-apache:
      # ...
      volumes:
        - ./debug:/var/www/html/public/debug
  ```
- Apache espone i file direttamente in `https://<host>:<port>/debug/`.
- Qualsiasi modifica in `debug/` è immediatamente visibile nel container (niente `docker compose build`).

## Come usarlo

1) Avvia l'app con Docker Compose
2) Crea/aggiungi file PHP/HTML in `debug/` (es. `index.php`, `test.php`)
3) Apri `https://127.0.0.1:8443/debug/` oppure un path specifico, es. `.../debug/test.php`

Esempi da terminale:
- Windows PowerShell
  ```powershell
  curl.exe -k -i https://127.0.0.1:8443/debug/
  ```
- Linux/macOS/WSL
  ```bash
  curl -k -i https://127.0.0.1:8443/debug/
  ```

## Variabili utili (se richieste dal codice di debug)

- `GOVPAY_PENDENZE_URL`
- `AUTHENTICATION_GOVPAY` (es. `sslheader` per mTLS)
- `GOVPAY_TLS_CERT`, `GOVPAY_TLS_KEY`, `GOVPAY_TLS_KEY_PASSWORD`

Configura queste variabili via `.env` o environment del container. Non includere segreti nei file di debug.

## Sicurezza

- La cartella `debug/` è per uso locale/sviluppo. Non esporla in produzione.
- Non committare file con segreti (certificati/chiavi/credenziali).
