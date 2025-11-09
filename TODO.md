# TODO

- [ ] Migliorie inserimento pendenza
  - Impostare dei check su validità di codice fiscale in accoppiata con nome e cognome, e validità partita iva.

- [ ] Gestione pendenza
  - Gestire l'annullamento di una pendenza
  - Gestire il crud sulle pendenze

- [ ] Select tipologia pendenza ricercabile
  - Rendere la select della tipologia pendenza nelle mascherhe di ricerca pendenze ricercabile (autocomplete o select2/bootstrap-select), per facilitare la selezione in presenza di molte tipologie.

- [ ] Creare sezione ricerca flussi
  - Implementare una sezione dedicata alla ricerca dei flussi, con filtri avanzati e risultati tabellari.

- [ ] Creare sezione ricerca incassi
  - Implementare una sezione dedicata alla ricerca degli incassi, con filtri avanzati e risultati tabellari.

- [ ] Impedire navigazione directory /assets
  - Bloccare il listing della directory /assets tramite .htaccess o configurazione Apache, consentendo solo l’accesso diretto ai file.

- [ ] Configurazione
  - Completare le viste
  - Miglioramento e ampliamento del log
  - Gestire le modifiche alla configurazione

# LONG RUN

- [ ] Notifiche
  - Integrazione AppIO https://github.com/pagopa/io-functions-services/blob/master/openapi/index.yaml
  - Inserire la configurazione di AppIO connessa alla diverse tipologie
  - Inserire le notifiche via email e AppIO per nuove pendenze

- [ ] Creare un container seperato (e opzionale) per il frontend pubblico
  - Connettere lo stesso DB
  - Creare un interfaccia di creazione pendenza o pagamento bollettino
  - Integrare https://github.com/italia/spid-cie-php per l'accesso del cittadino ad un vista di storico pagamenti

