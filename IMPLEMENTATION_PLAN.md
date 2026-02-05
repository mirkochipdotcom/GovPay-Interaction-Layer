# Piano di Implementazione IAM Proxy Italia - SPID

## Problemi Identificati

1. **Script setup-demo-idp.sh non eseguito**
   - Il file `iam-proxy/iam-proxy-italia-project/setup-demo-idp.sh` esiste ma non viene montato/copiato
   - L'entrypoint.sh cerca `/satosa_proxy/setup-demo-idp.sh` ma non lo trova
   
2. **Metadata demo.spid.gov.it mancante**
   - Il file `/satosa_proxy/metadata/idp/spid-demo.xml` non viene creato
   - SATOSA non riconosce l'entityID `https://demo.spid.gov.it`

3. **Configurazione non allineata alla documentazione ufficiale**
   - La documentazione raccomanda di usare la struttura `iam-proxy-italia-project/`
   - Il progetto usa una struttura custom `iam-proxy/`

## Soluzione Proposta

### Opzione A: Fix Minimo (Quick Fix)
1. Copiare `setup-demo-idp.sh` nel Dockerfile
2. Eseguirlo nell'entrypoint
3. Assicurarsi che scarichi i metadata di demo.spid.gov.it

### Opzione B: Refactoring Completo (Raccomandato)
1. Riorganizzare la struttura per seguire le best practices ufficiali
2. Usare i volumi come da documentazione
3. Implementare correttamente l'init process

## Implementazione Scelta: Opzione A (Quick Fix)

### Step 1: Modificare il Dockerfile
Assicurarsi che setup-demo-idp.sh sia copiato e eseguibile

### Step 2: Modificare docker-compose.yml  
Aggiungere variabili d'ambiente mancanti

### Step 3: Verificare l'entrypoint
Assicurarsi che chiami correttamente setup-demo-idp.sh

### Step 4: Test
Riavviare e verificare che demo.spid.gov.it funzioni
