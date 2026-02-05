# IAM Proxy Italia - Personalizzazione e Override

Questo directory contiene i file di personalizzazione e override per l'IAM Proxy SATOSA, mantenendo il repository di iam-proxy-italia pulito da modifiche locali.

## Abilitare Demo SPID

Per abilitare il provider SPID di test (demo.spid.gov.it) nel tuo ambiente locale:

1. Modifica il `.env`:
```bash
SATOSA_USE_DEMO_SPID_IDP=true
```

2. Riavvia i container:
```bash
docker compose --profile iam-proxy up -d --force-recreate
```

Lo script di sincronizzazione (`scripts/sync-iam-proxy-italia.sh`) applicherà automaticamente:
- Download dei metadati demo SPID da `https://demo.spid.gov.it/metadata.xml`
- Patching del routing SATOSA per riconoscere demo.spid.gov.it
- Applicazione dell'override dei wallets JSON per configurare il flusso demo

## File di Override

### `wallets-spid-demo-override.json.template`

Personalizzazione della configurazione SPID quando demo SPID è abilitato. Include:
- URL di login personalizzato che punta a demo.spid.gov.it
- Label "SPID (Demo)" per chiarire che è un ambiente di test
- Ritorno automatico sulla pagina disco dopo login

Template variables:
- `${IAM_PROXY_PUBLIC_BASE_URL}` - URL publico del proxy (da .env)

### Modalità di Applicazione

L'override viene applicato **automaticamente** dallo script `scripts/sync-iam-proxy-italia.sh` quando:
- `SATOSA_USE_DEMO_SPID_IDP=true` nel .env
- Durante l'esecuzione del container `sync-iam-proxy`

## Note Importanti

⚠️ **ATTENZIONE**: Demo SPID è SOLO per sviluppo e test locale!

- Non usare demo.spid.gov.it in ambienti di produzione
- I metadati demo potrebbero non essere sempre disponibili
- Per ambienti di produzione, usa sempre provider SPID ufficiali dalla registry AGID

## File Coinvolti

- `.env` - Configurazione SATOSA_USE_DEMO_SPID_IDP
- `scripts/sync-iam-proxy-italia.sh` - Logica di sincronizzazione degli override
- `wallets-spid-demo-override.json.template` - Override demo SPID
- `iam-proxy-italia-project/` - Repository pulito, NON modificare

## Debugging

Se gli override non vengono applicati:

1. Verifica che `SATOSA_USE_DEMO_SPID_IDP=true` nel .env
2. Controlla i log del container `sync-iam-proxy`:
```bash
docker compose logs sync-iam-proxy
```
3. Verifica che il file generato esiste:
```bash
docker compose exec iam-proxy-italia ls -la /satosa_proxy/static/config/
```
