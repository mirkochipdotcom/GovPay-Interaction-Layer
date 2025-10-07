# Debug tester: `public/debug/index.php`

Questo README descrive il tester di debug presente in `public/debug/index.php`.
La cartella `public/debug/` è intenzionalmente ignorata da Git (contiene strumenti di debug locali e può contenere dati sensibili), mentre questo file README è tenuto sotto controllo versione per fornire istruzioni.

## Scopo

Fornire un piccolo form/strumento per testare localmente chiamate verso l'endpoint `findPendenze` (o altri endpoint GovPay) senza includere credenziali nel repository.

## Prima di usare

- Verifica che l'app sia in esecuzione (ad es. via Docker Compose) e che il DocumentRoot punti a `public/`.
- Imposta le variabili d'ambiente richieste dall'app o dal tuo ambiente di esecuzione (come nel `docker-compose.yml`):
  - `GOVPAY_PENDENZE_URL` (es. `https://sandbox-govpay.example/`)
  - `AUTHENTICATION_GOVPAY` (es. `sslheader` se usi TLS client)
  - `GOVPAY_TLS_CERT` (path assoluto al certificato client, solo se necessario)
  - `GOVPAY_TLS_KEY` (path assoluto alla chiave privata, solo se necessario)
  - `GOVPAY_TLS_KEY_PASSWORD` (opzionale)

> Nota: non includere certificati o chiavi private nel repository. Usa variabili d'ambiente o meccanismi di secret management.

## Come aprire il tester

- Browser: visita `https://<host>:<port>/debug/` (il file servito è `index.php` nella cartella `public/debug`).

- Da linea di comando (Windows PowerShell):

```powershell
curl.exe -k -i https://127.0.0.1:8443/debug/
```

- Da linea di comando (Linux/macOS / WSL):

```bash
curl -k -i https://127.0.0.1:8443/debug/
```

## Note pratiche

- `public/debug/index.php` è pensato per uso locale. Non committare file contenenti segreti (certificati, chiavi, credenziali).
- Se vuoi condividere uno script di esempio, crea una versione "esempio" senza dati sensibili, ad es. `index.php.example` o `test.php.example`.
- Questo README permette di mantenere le istruzioni nel repository pur continuando a ignorare tutti i file effettivi di debug tramite `.gitignore`.

## Avvertenze di sicurezza

- Non esporre `public/debug/` in produzione. Rimuovi o disabilita la cartella prima di esporre il servizio pubblicamente.
- Evita di inserire credenziali o chiavi in chiaro nel repository. Usa segreti/variabili d'ambiente.

## Cosa posso fare per te

- Posso committare questo README per te.
- Posso creare `index.php.example` con una versione senza segreti.
- Posso aggiornare il `.gitignore` per chiarire che il README è whitelisted mentre il resto della cartella è ignorato.

Se vuoi che proceda con uno di questi passaggi, dimmi quale preferisci.

---

Contatti

- Mirko D'Addiego — responsabile del repository
