# Generazione client GovPay (generate.sh)

Questo script genera i client PHP a partire dalle specifiche OpenAPI configurate in `api_config.json`.
È pensato per aggiornare i client quando cambiano le API. I client generati fanno parte del repository.

## Panoramica
Lo script esegue, per ogni API definita in `api_config.json`:
- Download dei file OpenAPI principali e di supporto
- Applicazione di eventuali correzioni via `sed` (patch testuali)
- Bundling OpenAPI tramite container `redocly/cli`
- Generazione del client PHP tramite container `openapitools/openapi-generator-cli`
- Fix automatico del `composer.json` del client (campo `name` e mapping `autoload` PSR-4)

L'output è scritto in `generated-clients/<api>-<version>/<client_dir>`.

## Prerequisiti
- Docker installato e funzionante
- `jq` installato sull'host (usato dallo script)
  - macOS: `brew install jq`
  - Ubuntu/Debian: `sudo apt-get install jq`
  - Windows (PowerShell): `choco install jq` (oppure usa Git Bash/WSL)

Nota: lo script usa `docker run` per eseguire Redocly e OpenAPI Generator, e usa `id -u`/`id -g` per mappare l'UID/GID. Su Windows, se usi PowerShell, valuta di eseguirlo in Git Bash o WSL per avere `id` disponibile; altrimenti puoi rimuovere le opzioni `--user "$(id -u):$(id -g)"` dallo script (per uso locale).

## Configurazione (`api_config.json`)
Ogni voce dell'array rappresenta un'API da generare. Campi supportati:
- `name`: nome logico dell'API (es. `backoffice`)
- `version`: versione logica (es. `v1`)
- `base_url`: base URL da cui scaricare i file YAML/JSON
- `main_file`: file principale OpenAPI (es. `backoffice.bundled.yaml` o simili)
- `support_files`: array dei file di supporto da scaricare (opzionale)
- `hidden_dependencies`: array di URL aggiuntivi da scaricare (opzionale)
- `client_dir`: directory di output del client (es. `backoffice-client`)
- `client_namespace`: namespace PHP radice del client (es. `GovPay\\Backoffice`)
- `package_name`: nome del pacchetto Composer da iniettare nel client (es. `govpay/backoffice-client`)
- `corrections`: array di comandi `sed` da applicare al file principale (opzionale)

Comportamenti speciali:
- Se `name` è `ragioneria`, il bundling usa `--force` per ignorare eventuali riferimenti rotti.

## Come si usa
Esegui dalla root del repository o dalla cartella `govpay-clients`.

- macOS / Linux:
```bash
cd govpay-clients
chmod +x generate.sh
./generate.sh
```

- Windows (Git Bash):
```bash
cd govpay-clients
chmod +x generate.sh
./generate.sh
```

- Windows (PowerShell via WSL):
```powershell
wsl bash -lc "cd /mnt/c/Users/<tuo_utente>/Documents/GovPay-Interaction-Layer/govpay-clients && ./generate.sh"
```

Se non hai `id` su Windows PowerShell puro e non vuoi usare WSL/Git Bash, puoi modificare temporaneamente lo script rimuovendo `--user "$(id -u):$(id -g)"` nelle due invocazioni `docker run`.

## Struttura output
```
 govpay-clients/
 ├─ generated-clients/
 │  ├─ backoffice-v1/
 │  │  ├─ backoffice.bundled.yaml
 │  │  └─ backoffice-client/    # client PHP generato (con composer.json patchato)
 │  └─ ...
```

## Troubleshooting
- "Errore: 'jq' non è installato": installa `jq` (vedi Prerequisiti).
- Errori di permessi sui file generati (Windows): prova a rimuovere le opzioni `--user` dai comandi `docker run` o usa Git Bash/WSL.
- 403/404 durante il download: verifica `base_url`, `main_file` e `support_files` in `api_config.json`.
- Sed non applica la patch: controlla le virgolette nell'espressione, su macOS l'in-place è gestito da `SED_INPLACE` nello script.
- OpenAPI bundle fallisce su `ragioneria`: è previsto l'uso di `--force` (già gestito dallo script).

## Nota su CI
Nel progetto, i client generati sono versionati. Lo script serve ad aggiornarli quando cambiano le specifiche: non è necessario eseguirlo in CI.
