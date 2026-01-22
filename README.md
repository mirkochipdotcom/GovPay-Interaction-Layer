# GovPay Interaction Layer (GIL)

Piattaforma containerizzata (PHP/Apache + UI) per migliorare il flusso di lavoro degli enti che usano GovPay come soluzione PagoPA.
Include:
- **Backoffice** per operatori (gestione pendenze, incassi, rendicontazioni)
- **Frontoffice** cittadini/sportello
- (Opzionale) **proxy SPID/CIE** integrabile via profilo Docker Compose

Repository: https://github.com/mirkochipdotcom/GovPay-Interaction-Layer.git

License: European Union Public Licence v1.2 (EUPL-1.2)
SPDX-License-Identifier: EUPL-1.2

---

## Indice

- [Cos’è e a cosa serve](#cosè-e-a-cosa-serve)
- [Avvio rapido (primo utilizzo)](#avvio-rapido-primo-utilizzo)
- [Configurazione: file .env](#configurazione-file-env)
- [SPID/CIE](#spidcie)
- [Metadata SPID/CIE (freeze + next)](#metadata-spidcie-freeze--next)
- [Setup produzione](#setup-produzione)
- [Workflow di sviluppo](#workflow-di-sviluppo)
- [Troubleshooting](#troubleshooting)
- [Struttura del progetto](#struttura-del-progetto)

---

## Cos’è e a cosa serve

GovPay Interaction Layer (GIL) funge da livello intermedio tra GovPay e gli operatori dell’ente, fornendo un portale più “operativo” rispetto alla GUI standard.
In pratica offre:

- **Backoffice unificato** per creare/modificare pendenze, consultare lo stato degli incassi e gestire rendicontazioni.
- **Strumenti di controllo** (ricerche/filtri/vista dedicate) per individuare rapidamente flussi in errore, stand‑in o pagamenti non riconciliati.
- **Frontoffice semplificato** per cittadini o sportelli, con possibilità di reindirizzare alcune tipologie verso portali esterni.
- **Gestione integrazione** (certificati e configurazione) per isolare la complessità di GovPay.

Opzionalmente include un **proxy SPID/CIE** (interno o esterno) per gestire login/logout e la pubblicazione/rotazione dei metadata.

## Avvio rapido (primo utilizzo)

### 0) Prerequisiti

- Docker Desktop (o Docker Engine + plugin `docker compose`)
- Git
- Porte libere sul tuo host (default):
  - Backoffice: `8443`
  - Frontoffice: `8444`
  - Proxy SPID (se abilitato): `8445`

### 1) Clona il repository

```bash
git clone https://github.com/mirkochipdotcom/GovPay-Interaction-Layer.git
cd GovPay-Interaction-Layer
```

### 2) Crea i file di configurazione

Questo repository usa 3 file separati per mantenere la configurazione più leggibile:

```bash
cp .env.example .env
cp .env.proxyspid.example .env.proxyspid
cp .env.metadata.example .env.metadata
```

Note:
- `.env` è sempre necessario.
- `.env.proxyspid` è richiesto perché viene caricato dal servizio frontoffice (anche se non abiliti SPID).
- Se non usi SPID/CIE, puoi lasciare `.env.proxyspid` con i valori dell'example (non verranno usati dalle rotte UI quando `COMPOSE_PROFILES=none`).
- `.env.metadata` è necessario solo se avvii il proxy interno (`COMPOSE_PROFILES=spid-proxy`) o il generator metadata (`--profile spid-proxy-metadata`).

#### Dati minimi necessari (GovPay e PagoPA Checkout)

L'app si avvia anche lasciando gli example, ma per usare davvero le funzioni principali servono endpoint e credenziali reali.

GovPay:

- Identità ente/app: `ID_DOMINIO` (CF/P.IVA ente creditore) e `ID_A2A` (id applicazione).
- Endpoint API: compila i `GOVPAY_*_URL` nel `.env` (pendenze/pagamenti/ragioneria/backoffice).
- Autenticazione: scegli `AUTHENTICATION_GOVPAY` e configura di conseguenza:
   - `basic` / `form`: imposta `GOVPAY_USER` e `GOVPAY_PASSWORD`.
   - `ssl` / `sslheader`: monta i certificati client in `certificate/` e imposta `GOVPAY_TLS_CERT` e `GOVPAY_TLS_KEY` (opzionale `GOVPAY_TLS_KEY_PASSWORD`).

PagoPA Checkout (EC API):

- `PAGOPA_CHECKOUT_EC_BASE_URL` (DEV/PROD).
- `PAGOPA_CHECKOUT_SUBSCRIPTION_KEY` (header `Ocp-Apim-Subscription-Key`, deve restare server-side).
- `PAGOPA_CHECKOUT_COMPANY_NAME` (nome ente mostrato nel checkout).
- (Opzionale) `PAGOPA_CHECKOUT_RETURN_OK_URL`, `PAGOPA_CHECKOUT_RETURN_CANCEL_URL`, `PAGOPA_CHECKOUT_RETURN_ERROR_URL`.

Approfondimenti:
- certificati GovPay: `certificate/README.md`
- configurazione Checkout: `pagopa-clients/README.md`

### 3) Avvia i container

```bash
docker compose up -d

# quando modifichi Dockerfile / composer / asset
docker compose up -d --build
```

### 4) Primo accesso

- Backoffice: https://localhost:8443
- Frontoffice: https://localhost:8444

Se non fornisci certificati personalizzati in `ssl/`, verranno generati certificati self-signed: il browser mostrerà un warning.

### 5) Superadmin (seed)

Al primo avvio uno script crea lo schema utenti e inserisce un utente `superadmin` usando:

```env
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=una_password_sicura
```

Il seed è idempotente: viene creato solo se non esiste già un superadmin nel DB.

---

## Configurazione: file .env

### `.env` (compose + app)

Contiene:
- porte esposte (`BACKOFFICE_HTTPS_PORT`, `FRONTOFFICE_HTTPS_PORT`, …)
- profili Docker Compose (`COMPOSE_PROFILES`)
- configurazione DB (`DB_*`)
- configurazione GovPay (URL, auth, certificati)
- base URL pubblico del proxy (quando SPID/CIE è abilitato):
  - `SPID_PROXY_PUBLIC_BASE_URL`

### `.env.proxyspid` (integrazione frontoffice ↔ proxy)

Contiene parametri “di integrazione” (client_id, redirect URI, branding, cifratura). I principali:

- `SPID_PROXY_CLIENT_ID`
- `SPID_PROXY_REDIRECT_URIS`
- `FRONTOFFICE_PUBLIC_BASE_URL`
- `FRONTOFFICE_SPID_CALLBACK_PATH`
- (opzionale) `FRONTOFFICE_SPID_REDIRECT_URI`

Response firmata/cifrata:
- `SPID_PROXY_SIGN_RESPONSE` (default `1`)
- `SPID_PROXY_ENCRYPT_RESPONSE` (default `0`)
- `SPID_PROXY_CLIENT_SECRET` (necessaria se `SPID_PROXY_ENCRYPT_RESPONSE=1`)

Nota su `SPID_PROXY_CLIENT_SECRET`:
- deve essere **uguale** lato frontoffice (per decifrare) e lato proxy (per cifrare)

### `.env.metadata` (dati che determinano i metadata)

Contiene i campi organizzazione/attributi (e in generale ciò che vuoi “freezare” per attestazione) che determinano i metadata SPID/CIE.
Modificare questi valori cambia il metadata.

---

## SPID/CIE

Il supporto SPID/CIE del frontoffice dipende da `COMPOSE_PROFILES` nel `.env`:

- **No SPID**: `COMPOSE_PROFILES=none` (disabilita login SPID nel frontoffice)
- **Proxy interno**: `COMPOSE_PROFILES=spid-proxy` (avvia anche il servizio `spid-proxy`)
- **Proxy esterno**: `COMPOSE_PROFILES=external` (non avvia `spid-proxy`, ma il frontoffice punta a un proxy esterno)

In tutti i casi in cui SPID/CIE è abilitato (interno/esterno), devi impostare in `.env`:
- `SPID_PROXY_PUBLIC_BASE_URL`: base URL pubblico del proxy (usato dal browser)

### Regola d’oro: redirect URI “match esatto”

Se cliccando “Accedi” vieni rimandato a `/metadata.xml`, quasi sempre è un mismatch nella whitelist `redirect_uri` del proxy.
Verifica (match esatto, stesso schema/host/porta/path):
- `SPID_PROXY_CLIENT_ID`
- `SPID_PROXY_REDIRECT_URIS`
- `SPID_PROXY_CLIENT_SECRET` (se cifratura)
- `SPID_PROXY_SIGN_RESPONSE` / `SPID_PROXY_ENCRYPT_RESPONSE`

### Rigenerare la configurazione persistita del proxy (interno)

Il proxy salva su volume alcuni file runtime (es. `spid-php-proxy.json`) e **non li riscrive automaticamente** se cambi le env dopo il primo avvio.

Soluzione: rigenera i file persistiti e ricrea il container.

- Windows:
  - task VS Code: `regenerate-spid-proxy-config`
  - oppure: `powershell -ExecutionPolicy Bypass -File .\scripts\regenerate-spid-proxy-config.ps1 -Restart`
- Linux/macOS:
  - `./scripts/regenerate-spid-proxy-config.sh --restart`
  - (opzionale) `./scripts/regenerate-spid-proxy-config.sh --reset-setup --restart`

Nota: lo script `.sh` richiede `bash`. Se lo lanci con `sh ...` si ri-esegue automaticamente con bash.

---

## Metadata SPID/CIE (freeze + next)

Scenario tipico AgID:
- dopo attestazione, il metadata **non deve cambiare** fino a scadenza
- poco prima della scadenza si prepara un nuovo metadata (“next”) senza toccare quello in produzione (“current”)

Questo repository supporta il pattern “freeze + next generator”:

### Cartelle usate dal proxy (spid-proxy/)

- `data/`: stato persistente runtime del container `spid-proxy`
- `metadata-work/`: working copy usata solo dal generator (non tocca `data/`)
- `metadata/`: output del generator (snapshot + file `*-metadata-next.xml`)
- `metadata-current/`: unica cartella montata dal runtime per servire i metadata correnti (`*-metadata-current.xml`)
- `metadata-archive/`: archivio locale

### 1) Congelare il metadata attestato (CURRENT)

- Il runtime serve **solo** i file presenti in `spid-proxy/metadata-current/`.
- Copia i file attestati in `spid-proxy/metadata-current/` con questi nomi:
   - `spid-metadata-current.xml`
   - `cie-metadata-current.xml` (se CIE è abilitato)

### 2) Generare un metadata “NEXT” (senza toccare CURRENT)

Esegui il generator (non modifica `metadata-current/`):

```bash
docker compose --profile spid-proxy-metadata run --rm spid-proxy-metadata
```

Output:
- `spid-proxy/metadata/spid-metadata-next.xml`
- `spid-proxy/metadata/cie-metadata-next.xml` (se CIE)

### 3) Cutover (quando AgID attesta NEXT)

- Promuovi `*-metadata-next.xml` a `*-metadata-current.xml`:
  - Windows: `scripts/promote-spid-metadata.ps1` (o task VS Code `promote-spid-metadata-current`)
  - Linux/macOS: `scripts/promote-spid-metadata.sh`
- Ricrea `spid-proxy`.

---

## Setup produzione

Questa sezione è una checklist operativa per portare GIL in un ambiente reale (dominio pubblico, reverse proxy, certificati validi) e per ridurre i problemi tipici di SPID/CIE.

### Domini e URL pubblici

Definisci chiaramente gli URL pubblici (quelli che useranno gli utenti e/o AgID):

- Backoffice (es. `https://backoffice.ente.it`)
- Frontoffice (es. `https://pagamenti.ente.it`)
- Proxy SPID/CIE (es. `https://login.ente.it`)

Poi imposta:
- in `.env`:
   - `SPID_PROXY_PUBLIC_BASE_URL=https://login.ente.it`
- in `.env.proxyspid`:
   - `FRONTOFFICE_PUBLIC_BASE_URL=https://pagamenti.ente.it`

Evita `localhost`/`127.0.0.1` in produzione: finiscono nei redirect e/o nei metadata.

### TLS / certificati

- Per HTTPS lato browser → applicazione, usa certificati validi (non self-signed) in `ssl/`:
   - `ssl/server.key`
   - `ssl/server.crt`
- I certificati per le API GovPay (client certificate) restano in `certificate/`.

Se usi un reverse proxy esterno (Nginx/Traefik/Apache), puoi terminare TLS lì e pubblicare i container su rete interna: l’importante è che gli URL “pubblici” in env siano coerenti con ciò che vede l’utente.

### Reverse proxy (raccomandato)

Pattern tipico:
- reverse proxy pubblico (porta 443) → inoltra a `localhost:8443/8444/8445` (o alle porte interne dei container)
- header standard preservati:
   - `Host`
   - `X-Forwarded-Proto=https`
   - `X-Forwarded-For`

Obiettivo: far sì che i redirect generati puntino sempre agli URL pubblici corretti.

### SPID/CIE: checklist “non rompere il login”

- `SPID_PROXY_REDIRECT_URIS` deve contenere **esattamente** la callback del frontoffice (schema/host/porta/path).
- Se cambi `client_id`, `redirect_uri`, cifratura o modalità firma, rigenera la config persistita del proxy:
   - Windows: `powershell -ExecutionPolicy Bypass -File .\scripts\regenerate-spid-proxy-config.ps1 -Restart`
   - Linux: `./scripts/regenerate-spid-proxy-config.sh --restart`
- Se abiliti cifratura (`SPID_PROXY_ENCRYPT_RESPONSE=1`), assicurati che `SPID_PROXY_CLIENT_SECRET` sia identica su frontoffice e proxy.

### Proxy esterno (COMPOSE_PROFILES=external)

Con `COMPOSE_PROFILES=external` il servizio `spid-proxy` **non** viene avviato in questo Compose: il frontoffice reindirizza a un proxy pubblicato altrove.

Checklist (frontoffice):
- in `.env`: `COMPOSE_PROFILES=external`
- in `.env`: `SPID_PROXY_PUBLIC_BASE_URL=https://login.ente.it` (URL pubblico del proxy)
- in `.env.proxyspid`:
   - `FRONTOFFICE_PUBLIC_BASE_URL=https://pagamenti.ente.it`
   - callback coerente (`FRONTOFFICE_SPID_CALLBACK_PATH` / eventuale `FRONTOFFICE_SPID_REDIRECT_URI`)
   - `SPID_PROXY_CLIENT_SECRET` valorizzata se usi cifratura

Checklist (istanza proxy esterna):
- deve essere configurata con gli stessi valori usati dal frontoffice:
   - `SPID_PROXY_CLIENT_ID`
   - `SPID_PROXY_REDIRECT_URIS` (includendo la callback del frontoffice, match esatto)
   - `SPID_PROXY_SIGN_RESPONSE` / `SPID_PROXY_ENCRYPT_RESPONSE`
   - `SPID_PROXY_CLIENT_SECRET` (se cifratura)
- se cambi env dopo il primo avvio, anche sul proxy esterno può servire rigenerare la config persistita (stesso problema del volume).

Nota: il workflow metadata (generate/freeze/cutover) va eseguito **sul deploy del proxy** (quello che espone `https://login.ente.it/*`), non sul frontoffice.

### Metadata SPID/CIE: checklist pre-attestazione AgID

- Verifica che `SPID_PROXY_PUBLIC_BASE_URL` sia l’URL pubblico reale.
- Verifica che questi endpoint rispondano `200` e scarichino un XML valido:
   - `https://login.ente.it/spid-metadata.xml`
   - `https://login.ente.it/cie-metadata.xml` (se CIE)
- Compila correttamente i dati in `.env.metadata` (campi `SPID_PROXY_ORG_*`) e tienili stabili: una variazione cambia il metadata.
- Se usi branding (`SPID_PROXY_CLIENT_*`), assicurati che `SPID_PROXY_CLIENT_LOGO` sia raggiungibile pubblicamente.
- Genera e archivia il file “da consegna” (non affidarti solo al metadata dinamico):
   - puoi scaricare direttamente l’URL pubblico, oppure
   - eseguire il generator `spid-proxy-metadata` e prelevare `spid-metadata-next.xml`.

### Flusso consigliato (produzione)

1) Prepara “next” senza impattare prod:

```bash
docker compose --profile spid-proxy-metadata run --rm spid-proxy-metadata
```

2) Invia `spid-proxy/metadata/spid-metadata-next.xml` (e `cie-metadata-next.xml` se serve) per attestazione.

3) Dopo attestazione, fai freeze del current:
- copia i file attestati in `spid-proxy/metadata-current/*-metadata-current.xml`

Comando rapido (da `GovPay-Interaction-Layer/`):

- Windows (PowerShell):
   - `Copy-Item -Force .\spid-proxy\metadata\spid-metadata-next.xml .\spid-proxy\metadata-current\spid-metadata-current.xml; if (Test-Path .\spid-proxy\metadata\cie-metadata-next.xml) { Copy-Item -Force .\spid-proxy\metadata\cie-metadata-next.xml .\spid-proxy\metadata-current\cie-metadata-current.xml }`
- Linux/macOS (bash):
   - `cp -f spid-proxy/metadata/spid-metadata-next.xml spid-proxy/metadata-current/spid-metadata-current.xml; [ -f spid-proxy/metadata/cie-metadata-next.xml ] && cp -f spid-proxy/metadata/cie-metadata-next.xml spid-proxy/metadata-current/cie-metadata-current.xml`

- ricrea `spid-proxy`

4) Al rinnovo:
- genera un nuovo next
- quando attestato, promuovi next → current e ricrea `spid-proxy`

## Workflow di sviluppo

### Modifiche al codice

- Backoffice: cartella `backoffice/`
- Frontoffice: cartella `frontoffice/`
- Libreria/app PHP condivisa: cartella `app/`
- Template Twig: cartelle `templates/` (root) e `frontoffice/templates/`

In generale:

```bash
# rebuild quando cambi PHP/composer o asset
docker compose up -d --build
```

### Log e debug

```bash
docker compose logs -f
docker compose exec govpay-interaction-backoffice bash
docker compose restart govpay-interaction-backoffice
```

---

## Troubleshooting

### "Accedi" → redirect a `/metadata.xml`

Quasi sempre:
- `redirect_uri` non è in whitelist (`SPID_PROXY_REDIRECT_URIS`), oppure
- il proxy sta usando una configurazione persistita vecchia (volume `spid-proxy/data/`).

Azioni consigliate:
- verifica il match esatto della callback del frontoffice
- rigenera la configurazione proxy con gli script in `scripts/regenerate-spid-proxy-config.*`

### Variabili “annidate” nei file env

Docker Compose **non espande** variabili del tipo `FOO="${BAR}"` dentro gli `env_file`.
Se vuoi un fallback, lascia la variabile vuota oppure valorizzala esplicitamente.

### Script `.sh` e line ending

Gli script `.sh` devono essere in LF (non CRLF) per evitare errori su Linux/Docker.
Questo repository forza i line ending via `.gitattributes`.

---

## Struttura del progetto

```
GovPay-Interaction-Layer/
├── docker-compose.yml
├── Dockerfile
├── backoffice/
├── frontoffice/
├── spid-proxy/               # proxy SPID/CIE (opzionale)
├── app/                      # codice PHP condiviso
├── templates/                # template backoffice/shared
├── debug/                    # tool debug (montati solo nel backoffice)
├── govpay-clients/           # client API generati
├── pagopa-clients/           # client API generati
├── migrations/
├── ssl/                      # cert HTTPS del server (browser → app)
├── certificate/              # cert client GovPay (app → GovPay)
├── .env                      # da creare (base)
├── .env.proxyspid            # da creare (SPID/CIE, integrazione)
└── .env.metadata             # da creare (SPID/CIE, metadata)
```

---

## Contribuire

1) Fork del repository
2) Crea un branch: `git checkout -b feature/nuova-funzionalita`
3) Commit: `git commit -m "Aggiunge nuova funzionalità"`
4) Push: `git push origin feature/nuova-funzionalita`
5) Apri una Pull Request

## Supporto

- Issues: https://github.com/mirkochipdotcom/GovPay-Interaction-Layer/issues
