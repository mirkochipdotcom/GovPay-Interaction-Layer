# 🇮🇹 GovPay Interaction Layer (GIL)

Piattaforma containerizzata (PHP/Apache + frontend) per migliorare il flusso di lavoro degli enti che usano GovPay come soluzione PagoPA.
Lo scopo è avere un portale da cui gli uffici possano creare e gestire le pendenze, rendicontare e controllare i flussi di pagamento, in maniera più semplice rispetto alla GUI di GovPay.
Inoltre è possibile esporre un frontend semplificato per i cittadini (in sviluppo), con la possibilità di esporre altri portali di pagamento esterni per alcune tipologie di pagamento.

[![GitHub Repository](https://img.shields.io/badge/GitHub-mirkochipdotcom%2FGovPay--Interaction--Layer-blue?style=flat&logo=github)](https://github.com/mirkochipdotcom/GovPay-Interaction-Layer.git)

License: European Union Public Licence v1.2 (EUPL-1.2)
SPDX-License-Identifier: EUPL-1.2

---

## 🧭 Cos'è e a cosa serve

GovPay Interaction Layer (GIL) funge da livello intermedio tra GovPay e gli operatori dell'ente. In pratica fornisce:
- **Backoffice unificato** per creare/modificare pendenze, consultare lo stato degli incassi e scaricare i flussi di rendicontazione.
- **Strumenti di controllo** (ricerche, filtri, viste dedicate) per individuare rapidamente flussi in errore, stand-in o pagamenti non riconciliati.
- **Front-end semplificato** per cittadini o sportelli, con la possibilità di reindirizzare alcune tipologie di pagamento verso portali esterni.
- **Gestione certificati e API**: la piattaforma si occupa di autenticazione, certificati client e configurazione GovPay così i team applicativi possono concentrarsi sui processi dell'ente.

In sintesi, GIL riduce il carico operativo degli uffici e fornisce un punto di accesso coerente per tutte le attività ricorrenti legate a GovPay/PagoPA.

## 🚀 Avvio rapido (primo utilizzo)

### 0. Prerequisiti
- Docker Desktop (o Docker Engine + plugin `docker compose`)
- Git
- Porta `BACKOFFICE_HTTPS_PORT` libera sul tuo host (default 8443, configurabile via `.env`)

### 1. Clona il repository

```bash
git clone https://github.com/mirkochipdotcom/GovPay-Interaction-Layer.git
cd GovPay-Interaction-Layer
```

### 2. Prepara la configurazione
1. Copia il file di esempio e personalizzalo:
   ```bash
   cp .env.example .env
   ```
2. Imposta le variabili minime:
   - `ADMIN_EMAIL`, `ADMIN_PASSWORD` per il seed del superadmin
   - Parametri DB (`DB_HOST`, `DB_DATABASE`, ...)
   - Configurazione GovPay (`GOVPAY_PENDENZE_URL`, `AUTHENTICATION_GOVPAY`, certificati)
3. (Opzionale) Inserisci i certificati GovPay in `certificate/` e quelli HTTPS in `ssl/` prima della prima esecuzione così non dovrai rebuildare in seguito.

### 3. Avvia i container

```bash
# Prima esecuzione (build automatica)
docker compose up -d

# Quando modifichi Dockerfile/composer/npm
docker compose up -d --build
```

La prima build può impiegare qualche minuto perché scarica dipendenze e compila asset.

### 4. Primo accesso

- **URL principale (default)**: https://localhost:8443 *(configurabile tramite `BACKOFFICE_HTTPS_PORT`)*
- **Debug tool**: https://localhost:8443/debug/ *(solo nel container backoffice, stessa porta)*

Il seed creerà automaticamente un utente `superadmin` con le credenziali impostate nel `.env`. Accedi a `/login` e, subito dopo, crea nuovi utenti o aggiorna la password del seed.

⚠️ **Nota SSL**: se non fornisci certificati personalizzati in `ssl/`, il container genera certificati self‑signed. I browser segnaleranno l'avviso di sicurezza: conferma l'eccezione per ambienti di sviluppo.

## 🛠️ Workflow di sviluppo

### Modifiche al codice
- **Backend PHP**: Modifica i file in `src/` - richiede rebuild: `docker compose up -d --build`
- **Debug/test**: Modifica i file in `debug/` - le modifiche sono immediate (montato **solo** nel servizio backoffice)
- **Template Twig**: Modifica i file in `templates/` - richiede rebuild: `docker compose up -d --build`

### Monitoraggio e debug
```bash
# Visualizza i log in tempo reale
docker compose logs -f govpay-interaction-backoffice

# Accedi al container per debug
docker compose exec govpay-interaction-backoffice bash

# Riavvia solo il servizio PHP senza rebuild
docker compose restart govpay-interaction-backoffice
```
> Sostituisci con `govpay-interaction-frontoffice` per operare sul portale cittadini. Nota: il container frontoffice non monta la cartella `debug/` e non espone lo strumento `/debug`.

## 🔧 Configurazione di avvio

### Variabili d'ambiente
Crea il file `.env` (puoi partire da `.env.example` se presente) e configura le variabili per il tuo ambiente:

```bash
cp .env.example .env
```

Variabili porte di rete:
- `BACKOFFICE_HTTPS_PORT`: porta HTTPS esposta dal container backoffice (default 8443)
- `FRONTOFFICE_HTTPS_PORT`: porta HTTPS esposta dal container frontoffice (default 8444)

### SPID/CIE (frontoffice)

Il frontoffice supporta l'accesso tramite SPID/CIE integrando il progetto `italia/spid-cie-php` (basato su SimpleSAMLphp).

- Abilitazione: imposta `FRONTOFFICE_AUTH_PROVIDER=spid_cie` nel `.env`.
- Per test con DEMO IdP / validator, imposta anche `SPID_CIE_PUBLIC_BASE_URL` (un URL HTTPS pubblico che punti al frontoffice).
- URL runtime SimpleSAMLphp (frontoffice): `https://<host>:<FRONTOFFICE_HTTPS_PORT>/spid-cie/`
- Pagina login applicativa: `https://<host>:<FRONTOFFICE_HTTPS_PORT>/login`
- Endpoint metadata "full" (frontoffice): `https://<host>:<FRONTOFFICE_HTTPS_PORT>/spid-metadata.xml`

Note per validazione metadata SPID:
- `SPID_METADATA_IPA_CODE` è richiesto (il metadata endpoint risponde 500 se mancante).
- Il certificato SPID self-signed viene generato a runtime in SimpleSAMLphp; puoi personalizzare DN/attributi con `SPID_CERT_*` e forzare rigenerazione con `SPID_CIE_FORCE_REGENERATE_CERTS=1`.

Nota: l'immagine frontoffice include il codice di `spid-cie-php`, ma la configurazione (entityID, certificati, metadata, ecc.) va completata secondo la documentazione ufficiale del progetto.

### 🔐 Autenticazione e superadmin

L'applicazione richiede autenticazione. Al primo avvio, uno script di migrazione crea lo schema utenti e inserisce un utente amministratore di default (seed) usando le variabili d'ambiente `ADMIN_EMAIL` e `ADMIN_PASSWORD`.

Passi rapidi:

1) Imposta nel file `.env`:

```env
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=una_password_sicura
```

2) Avvia l'applicazione:

```bash
docker compose up -d --build
```

3) Accedi a https://localhost:8443/login e usa le credenziali impostate.

4) (Consigliato) Dopo l'accesso, vai su “Utenti” per creare altri utenti o aggiornare la password dell'admin.

Ruoli disponibili:
- `user`: utente base
- `admin`: può gestire utenti
- `superadmin`: privilegi equivalenti ad admin, con aggiunta della configurazione di GovPay e delle opzioni di pagamento

Note importanti:
- Il seed è idempotente: viene creato un utente `superadmin` solo se non è già presente nel database. Al primo avvio lo script di first‑run crea l'account usando `ADMIN_EMAIL` e `ADMIN_PASSWORD` presenti in `.env`.
- Regola di sicurezza (importante): l'app impedisce la rimozione dell'ultimo account con ruolo `superadmin`. Questo significa che non puoi cancellare l'admin seed se non esiste già un altro `superadmin` attivo. Per sostituirlo:
   1. Accedi con l'admin seed e crea un nuovo account con ruolo `superadmin` dalla sezione “Utenti”.
   2. Verifica l'accesso col nuovo superadmin.
   3. Solo a questo punto elimina o modifica l'utente seed.
- Per forzare la rigenerazione dell'account seed con nuove credenziali, elimina manualmente il superadmin esistente (dopo averne creato un altro) o, in ambienti di sviluppo, cancella la relativa riga nel database e riavvia il servizio (operazione distruttiva).
- Nota su autofill: alcuni browser potrebbero proporre l'autocompletamento dei campi credenziali. Se necessario, disabilita l'autofill o usa una finestra in incognito.
- Flash messages: i messaggi di esito compaiono nella barra notifiche superiore dopo i redirect; controllala dopo ogni azione.

### Configurazione GovPay
Per l'integrazione con GovPay, configura le seguenti variabili nel file `.env`:

```bash
# URL dell'istanza GovPay
GOVPAY_PENDENZE_URL=https://your-govpay-instance.example.com

# Metodo di autenticazione (tipicamente 'sslheader' per certificati client)
AUTHENTICATION_GOVPAY=sslheader

# Percorsi certificati GovPay (all'interno del container)
GOVPAY_TLS_CERT=/var/www/certificate/certificate.cer
GOVPAY_TLS_KEY=/var/www/certificate/private_key.key
# Password della chiave privata (se richiesta)
GOVPAY_TLS_KEY_PASSWORD=your_key_password
```

> Nota: in questa versione l'integrazione è stata testata solo con la modalità `sslheader` (autenticazione tramite certificato client). Le altre modalità documentate da GovPay potrebbero richiedere adattamenti o test ulteriori.

### Certificati GovPay
I certificati per l'autenticazione con le API GovPay devono essere posizionati nella directory `certificate/`:

1. **Ottieni i certificati** dall'amministratore dell'istanza GovPay o generali tramite l'interfaccia GovPay
2. **Posiziona i file** in `certificate/`:
   - `certificate.cer` - Certificato client GovPay
   - `private_key.key` - Chiave privata
3. **Configura le variabili** nel file `.env` (vedi sezione sopra)
4. **Riavvia il container**: `docker compose restart`

📝 **Nota**: Consulta `certificate/README.md` per istruzioni dettagliate.

### Altre configurazioni
- `DB_*`: Configurazione database MariaDB
- `APACHE_SERVER_NAME`: Nome server Apache

### Logo ente personalizzato
- Default a runtime: simbolo PA dello sprite Bootstrap Italia (`/assets/bootstrap-italia/svg/sprites.svg#it-pa`).
- Personalizzazione: aggiungi `img/stemma_ente.png` (ignorato da Git) per usare il tuo stemma.
- Se lo stemma non è presente, verrà mostrato il simbolo PA di default.

### Icona del sito (favicon)
- Personalizzazione: puoi aggiungere `img/favicon.ico` oppure `img/favicon.png` (sono ignorati da Git).
- Logica di risoluzione: prima `img/favicon.ico`, altrimenti `img/favicon.png`, altrimenti fallback automatico su `img/favicon_default.png` incluso nel repository.

### Certificati SSL per HTTPS (opzionale)
Per certificati SSL personalizzati del server web, posiziona i file nella cartella `ssl/`:
- `ssl/server.key` - Chiave privata del server
- `ssl/server.crt` - Certificato del server

⚠️ **Distingui tra**:
- **Certificati `ssl/`**: Per HTTPS del server web (connessioni browser → applicazione)
- **Certificati `certificate/`**: Per autenticazione client con API GovPay (applicazione → GovPay)

## 🎯 Testing e Debug

### Debug Tool integrato
Accedi (solo dal container backoffice) a https://localhost:8443/debug/ per:
- Testare chiamate API GovPay
- Verificare configurazione ambiente
- Debug delle pendenze

### Database
Il database MariaDB è accessibile su `localhost:3306` con le credenziali configurate in `.env`.

Al primo avvio il container DB esegue automaticamente gli script in `docker/db-init/` e garantisce che l'utente `DB_USER_CITTADINI` abbia password e permessi `SELECT` aggiornati sul database `MYSQL_DATABASE`. Se hai già un volume dati creato in precedenza, esegui `docker compose down -v` prima di ricostruire per rilanciare gli script di init.

## 🛑 Fermare l'applicazione

```bash
# Ferma i container mantenendo i dati
docker compose down

# Ferma e rimuove tutto (inclusi volumi dati)
docker compose down -v
```

## 🛠️ Comandi utili

### Build e manutenzione
```bash
# Ricostruire con cache pulita (per problemi o aggiornamenti Dockerfile)
docker compose build --no-cache
docker compose up -d

# Vedere lo stato dei container
docker compose ps

# Visualizzare risorse Docker
docker system df
```

### Reset completo
```bash
# Reset completo dell'ambiente (attenzione: rimuove tutto!)
docker compose down -v --remove-orphans
docker system prune -f
```

## 🐛 Troubleshooting

### Problemi comuni

**Porte già in uso**:
- Cambia la porta in `docker-compose.yml` se 8443 è occupata
- Oppure ferma altri servizi che usano la porta

**Problemi di permessi**:
- Su Linux/Mac: `sudo chown -R $USER:$USER .`
- Su Windows: verifica che Docker Desktop abbia accesso al drive

### Debug avanzato
```bash
# Ispeziona configurazione container
docker inspect govpay-interaction-backoffice

# Controlla logs di tutti i servizi
docker compose logs

# Accesso diretto al filesystem del container
docker exec -it govpay-interaction-backoffice find /var/www/html -name "*.php" | head -10
```
> Anche qui puoi usare `govpay-interaction-frontoffice` per verificare il container cittadini.

--- 

## 📚 Struttura del progetto

```
GovPay-Interaction-Layer/
├── docker-compose.yml      # Configurazione servizi Docker
├── Dockerfile              # Build dell'immagine PHP/Apache
├── src/                    # Codice sorgente PHP (copiato in build)
├── templates/              # Template Twig (copiati in build)
├── debug/                  # Tool di debug (montato come volume solo nel backoffice)
├── govpay-clients/         # Client API generati da OpenAPI
├── ssl/                    # Certificati SSL personalizzati
└── .env                    # Configurazione ambiente (da creare)
```

## 🤝 Contribuire

1. Fork del repository
2. Crea un branch: `git checkout -b feature/nuova-funzionalita`
3. Commit delle modifiche: `git commit -m 'Aggiunge nuova funzionalità'`
4. Push del branch: `git push origin feature/nuova-funzionalita`
5. Apri una Pull Request

## 📞 Supporto

Per domande, problemi o suggerimenti:
- 🐛 **Issues**: [GitHub Issues](https://github.com/mirkochipdotcom/GovPay-Interaction-Layer/issues)
- 📧 **Email**: Contatta il maintainer del progetto

--- 

# Stato del progetto

Breve riepilogo dello stato corrente (aggiornamento):

- DB/migrations: script di first-run e migrations implementati per creare tabelle e seed idempotente (superadmin seed).
- Template/Twig: correzioni ai template (incluso `templates/partials/header.html.twig`) per risolvere errori di parsing e fix del menu hamburger.
- Frontend: aggiunto `assets/js/app.js` con fallback per il toggler della navbar su mobile e miglioramenti alla lista utenti.
- Logging: logger su file in `storage/logs/app.log` e integrazione delle eccezioni con log.
- Sicurezza: guardie server-side per prevenire la rimozione involontaria dell'ultimo superadmin e miglioramenti degli accessi.

## TODO - Elenco degli sviluppi successivi
[![TODO](https://img.shields.io/badge/TODO-Lista%20attivit%C3%A0-blue)](.github/TODO.md)



**Nota**: Questo progetto è sviluppato per facilitare l'integrazione con GovPay/PagoPA negli Enti.
docker compose down -v
docker compose build --no-cache
docker compose up -d
docker compose ps
docker system df
docker compose down -v --remove-orphans
docker system prune -f
docker inspect govpay-interaction-backoffice
docker compose logs
docker exec -it govpay-interaction-backoffice find /var/www/html -name "*.php" | head -10
