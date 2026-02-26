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

### Gestione Profilo Utente
- [ ] **Interfaccia**: Miglioramento dell'interfaccia dell'area profilo.
- [ ] **Sicurezza**: Funzione di cambio password.
- [ ] **Personalizzazione**: Possibilità di associare template all'utente.

### Manutenzione e Sistema
- [ ] **Configurazione**: Funzionalità di backup e importazione della configurazione di sistema.

### Integrazioni Esterne
- [ ] **PagoPA Checkout**: Implementazione API PagoPA per avviare il checkout di pagamenti non generati da GovPay (simulazione portale checkout.pagopa.it).
