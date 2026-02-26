# Progetto GovPay Interaction Layer - TODO List

### Notifiche Pendenze
- [ ] **Email**
    - [ ] Lato Backoffice: Invio mail al cittadino al momento della creazione pendenza.
    - [ ] Lato Frontoffice: Inserire tasto "Invia per email" post-creazione spontaneo (accanto a "Paga ora" e "Stampa").
- [ ] **App IO**
    - [ ] Implementazione delle medesime notifiche (Backoffice/Frontoffice) tramite App IO.
    - [ ] **Configurazione API**: Implementazione API App IO e gestione chiavi/servizi per ogni tipologia di pendenza.
- [ ] **Integrazione Dati e UI**
    - [ ] Inserimento esiti/log notifiche nei `datiAllegati` della pendenza (es. timestamp mail, ID notifica IO).
    - [ ] Modifica del dettaglio pendenza per visualizzare una scheda/tab dedicata alle notifiche inviate.

### Sistema di Rendicontazione
- [ ] **Automazione**
    - [ ] Cron job per la scansione dei flussi non rendicontati.
    - [ ] Invio notifiche email ai destinatari configurati per ogni flusso elaborato.
    - [ ] Implementazione del meccanismo di rendicontazione automatica del flusso al completamento del processing/notifica di tutte le pendenze.
- [ ] **Webhook Agnostici**
    - [ ] Meccanismo di notifica verso sistemi terzi basato su regole configurabili (per tipologia di pendenza, ecc.).
- [ ] **Interfaccia Backoffice**
    - [ ] Inserimento del tasto "Rendiconta flusso" nel dettaglio flusso per permettere il bypass manuale.

### Gestione Profilo Utente
- [ ] **Interfaccia**: Miglioramento dell'interfaccia dell'area profilo.
- [ ] **Sicurezza**: Funzione di cambio password.
- [ ] **Personalizzazione**: Possibilità di associare template all'utente.

### Autenticazione e Identity
- [ ] **IAM Proxy**: Sistemazione integrazione proxy IAM.
- [ ] **CIE**: Bugfixing autenticazione con CIE.
- [ ] **Discovery Page**: Sistemazione grafica della pagina `disco.html`.

### Manutenzione e Sistema
- [ ] **Configurazione**: Funzionalità di backup e importazione della configurazione di sistema.
- [x] **Documentazione**: Sistemazione documentazione relativa ai cron (attualmente massivo, in futuro rendicontazione).

### Integrazioni Esterne
- [ ] **PagoPA Checkout**: Implementazione API PagoPA per avviare il checkout di pagamenti non generati da GovPay (simulazione portale checkout.pagopa.it).

### Ottimizzazione Infrastruttura e Cleanup
- [ ] **Snellimento Build**
    - [ ] Semplificazione degli script di build.
    - [ ] Rimozione dei container effimeri che terminano dopo la build (es. `sync-iam-proxy`).
    - [ ] Valutazione sostituzione bind-mount con istruzioni `COPY` (o `docker cp`) per le cartelle statiche.
    - [ ] Rimozione di `chown` e operazioni simili dagli script di entrypoint/build per velocizzare l'avvio.
- [ ] **Pulizia Repository**
    - [ ] Rimozione degli script di migrazione orfani.
    - [ ] Eliminazione definitiva della cartella `debug/`.
    - [ ] Cleanup finale e modernizzazione della struttura del repository.
