# GovPay Clients - Generatore Automatico di Client PHP

## Descrizione

Questo modulo contiene lo script `generate.sh` che automatizza la generazione di client PHP per le API di GovPay. Lo script scarica le specifiche OpenAPI dalle repository ufficiali di GovPay, le processa e genera client PHP pronti all'uso utilizzando OpenAPI Generator.

## A Cosa Serve

Lo script `generate.sh` è uno strumento di automazione che:

1. **Scarica le specifiche OpenAPI** dalle repository ufficiali di GovPay (diverse versioni e API)
2. **Risolve le dipendenze** tra i file YAML (file principali e file di supporto)
3. **Applica correzioni** necessarie per risolvere problemi noti nelle specifiche
4. **Crea un bundle unico** di tutte le specifiche utilizzando Redocly
5. **Genera client PHP** utilizzando OpenAPI Generator
6. **Corregge i file composer.json** generati per garantire la compatibilità con Composer

Il risultato finale sono client PHP completamente funzionali per interagire con le API di GovPay, organizzati nella cartella `generated-clients/`.

## API Supportate

Lo script genera client per le seguenti API di GovPay:

- **Pagamenti v3** - API per la gestione dei pagamenti
- **Ragioneria v3** - API per la gestione della ragioneria
- **Pendenze v2** - API per la gestione delle pendenze
- **Backoffice v1** - API per il backoffice

## Prerequisiti

Prima di eseguire lo script, assicurarsi di avere installati:

### Software Richiesto

1. **Docker** - Per eseguire i container di Redocly e OpenAPI Generator
   ```bash
   docker --version
   ```

2. **jq** - Per manipolare file JSON
   ```bash
   # Ubuntu/Debian
   sudo apt install jq
   
   # macOS
   brew install jq
   
   # Verifica installazione
   jq --version
   ```

3. **curl** - Per scaricare i file dalle repository remote (solitamente già installato)

### Permessi Docker

Lo script deve poter eseguire container Docker senza sudo. Assicurarsi che l'utente corrente appartenga al gruppo `docker`:

```bash
sudo usermod -aG docker $USER
# Riavviare la sessione per applicare le modifiche
```

## File di Configurazione

Lo script utilizza il file `api_config.json` per definire le API da generare. La struttura è la seguente:

```json
[
    {
        "name": "pagamenti",
        "version": "v3",
        "package_name": "govpay/pagamenti-client",
        "base_url": "https://raw.githubusercontent.com/link-it/govpay/master/wars/api-pagamento/src/main/webapp/v3",
        "main_file": "govpay-api-pagamento.yaml",
        "client_dir": "pagamenti-client",
        "client_namespace": "GovPay\\Pagamenti",
        "support_files": [
            "govpay-api-responses.yaml",
            "govpay-api-errors.yaml",
            "govpay-api-schemas.yaml",
            "govpay-api-parameters.yaml",
            "govpay-api-examples.yaml"
        ],
        "corrections": []
    }
]
```

### Campi del File di Configurazione

| Campo | Descrizione |
|-------|-------------|
| `name` | Nome identificativo dell'API |
| `version` | Versione dell'API |
| `package_name` | Nome del pacchetto Composer generato |
| `base_url` | URL base da cui scaricare i file YAML |
| `main_file` | File principale delle specifiche OpenAPI |
| `client_dir` | Directory di output per il client generato |
| `client_namespace` | Namespace PHP per il client |
| `support_files` | Array di file YAML di supporto da scaricare |
| `hidden_dependencies` | (Opzionale) Dipendenze nascoste da scaricare |
| `corrections` | (Opzionale) Espressioni SED per correggere i file YAML |

## Come Funziona lo Script

Lo script esegue i seguenti passaggi per ogni API configurata:

### 1. Preparazione Ambiente

- Elimina la directory `generated-clients/` esistente
- Crea una nuova directory per ogni API: `generated-clients/{name}-{version}/`

### 2. Download File Specifiche

- Scarica il file principale dell'API (es. `govpay-api-pagamento.yaml`)
- Scarica tutti i file di supporto definiti in `support_files`
- Scarica eventuali dipendenze nascoste (campo `hidden_dependencies`)

### 3. Applicazione Correzioni (se presenti)

Alcune API richiedono correzioni alle specifiche OpenAPI. Lo script applica automaticamente le correzioni definite nel campo `corrections` utilizzando `sed`.

**Esempio di correzione per l'API Ragioneria:**
```bash
s|govpay-api-responses.yaml#/responses/|govpay-api-responses.yaml#/components/responses/|g
```

### 4. Bundling con Redocly

Lo script utilizza Redocly CLI per creare un file YAML unificato che include tutte le dipendenze:

```bash
docker run --rm \
    --user "$(id -u):$(id -g)" \
    -v "$(pwd)/$WORKING_DIR:/data" \
    redocly/cli:latest bundle \
    "/data/$MAIN_FILE" \
    --output "/data/$BUNDLED_FILE"
```

**Nota speciale:** L'API Ragioneria viene processata con il flag `--force` per ignorare riferimenti rotti noti.

### 5. Generazione Client PHP

Utilizza OpenAPI Generator per creare il client PHP:

```bash
docker run --rm \
    --user "$(id -u):$(id -g)" \
    -v "$(pwd)/$WORKING_DIR:/local" \
    openapitools/openapi-generator-cli generate \
    -i "/local/$BUNDLED_FILE" \
    -g php \
    -o "/local/$CLIENT_DIR" \
    --invoker-package "$CLIENT_NAMESPACE"
```

### 6. Correzione composer.json

Il file `composer.json` generato viene corretto per:

1. **Aggiungere il campo `name`** richiesto da Composer
2. **Configurare l'autoload PSR-4** per mappare il namespace corretto

Queste correzioni vengono applicate utilizzando `jq` per garantire la manipolazione sicura dei file JSON.

## Utilizzo

### Esecuzione Base

```bash
cd govpay-clients
./generate.sh
```

### Output Atteso

Lo script produce output verboso che mostra il progresso per ogni API:

```
=====================================================================
🏁 INIZIO PROCESSO: pagamenti (v3) - Pacchetto: govpay/pagamenti-client
=====================================================================
   > Creato ambiente isolato: generated-clients/pagamenti-v3
   > Download govpay-api-pagamento.yaml da https://raw.githubusercontent.com/...
   > Download file di supporto: govpay-api-responses.yaml
   > Esecuzione Bundling (govpay-api-pagamento.yaml)...
   > Generazione Client PHP (pagamenti-client)...
   > 🔨 CORREZIONE FINALE: Iniezione del campo "name" e "autoload"
      -> Nome iniettato: govpay/pagamenti-client
      -> Autoload iniettato: GovPay\Pagamenti
✅ CLIENT pagamenti GENERATO E CORRETTO CON SUCCESSO in generated-clients/pagamenti-v3/pagamenti-client
```

### Struttura Output

I client generati si trovano in:

```
generated-clients/
├── pagamenti-v3/
│   ├── pagamenti-client/
│   │   ├── lib/              # Codice sorgente del client
│   │   ├── docs/             # Documentazione API
│   │   ├── test/             # Test di esempio
│   │   └── composer.json     # File Composer corretto
│   └── pagamenti.bundled.yaml
├── ragioneria-v3/
│   └── ragioneria-client/
├── pendenze-v2/
│   └── pendenze-client/
└── backoffice-v1/
    └── backoffice-client/
```

## Gestione Errori

Lo script è configurato con `set -e` per uscire immediatamente in caso di errore.

### Problemi Comuni

1. **`jq` non installato**
   ```
   Errore: 'jq' non è installato. Installalo (es. sudo apt install jq).
   ```
   **Soluzione:** Installare jq come indicato nei prerequisiti

2. **Docker non disponibile**
   ```
   docker: command not found
   ```
   **Soluzione:** Installare Docker e verificare che sia in esecuzione

3. **Permessi insufficienti**
   ```
   Got permission denied while trying to connect to the Docker daemon socket
   ```
   **Soluzione:** Aggiungere l'utente al gruppo docker

## Compatibilità

### Sistemi Operativi

Lo script è compatibile con:
- **Linux** (Ubuntu, Debian, CentOS, ecc.)
- **macOS** (con gestione specifica dei flag `sed`)
- **Windows** (tramite WSL2)

Il codice include una gestione speciale per macOS:
```bash
SED_INPLACE=""
if [[ "$OSTYPE" == "darwin"* ]]; then
    SED_INPLACE="''"
fi
```

## Manutenzione

### Aggiungere una Nuova API

Per aggiungere una nuova API al processo di generazione:

1. Aprire il file `api_config.json`
2. Aggiungere un nuovo oggetto nell'array con tutti i campi richiesti
3. Eseguire `./generate.sh`

### Modificare le Specifiche

Se le specifiche OpenAPI upstream cambiano:
- Lo script scarica sempre le versioni più recenti dai repository ufficiali
- Nessuna modifica necessaria a meno che non cambino URL o struttura

### Aggiungere Correzioni

Se una nuova API richiede correzioni:

1. Identificare le correzioni necessarie (espressioni `sed`)
2. Aggiungerle nell'array `corrections` della configurazione API
3. Le correzioni vengono applicate automaticamente prima del bundling

## Note Tecniche

- **Isolamento:** Ogni API viene processata in una directory isolata per evitare conflitti
- **Idempotenza:** Lo script può essere eseguito più volte in sicurezza (cancella e ricrea l'output)
- **Container Rootless:** Utilizza l'UID/GID dell'utente corrente per evitare problemi di permessi
- **JSON Safety:** Usa `jq` invece di manipolazione testuale per garantire validità JSON

## Risorse Aggiuntive

- [GovPay Repository Ufficiale](https://github.com/link-it/govpay)
- [OpenAPI Generator](https://openapi-generator.tech/)
- [Redocly CLI](https://redocly.com/docs/cli/)
- [Documentazione jq](https://stedolan.github.io/jq/)

## Supporto

Per problemi o domande relative allo script di generazione, fare riferimento alla documentazione ufficiale di GovPay o aprire una issue nel repository del progetto.
