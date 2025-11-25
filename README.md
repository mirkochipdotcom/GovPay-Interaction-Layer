# 🇮🇹 GovPay Interaction Layer (GIL)
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

La prima build può richiedere alcuni minuti per scaricare dipendenze e compilare gli asset.

### 4. Primo accesso

- Portale operatori: https://localhost:8443
- Debug tool: https://localhost:8443/debug/

Il seed creerà automaticamente un utente `superadmin` con le credenziali impostate nel `.env`. Accedi a `/login` e, subito dopo, crea nuovi utenti o aggiorna la password del seed.

⚠️ **Nota SSL**: se non fornisci certificati personalizzati in `ssl/`, il container genera certificati self‑signed. I browser segnaleranno l'avviso di sicurezza: conferma l'eccezione per ambienti di sviluppo.

## 🛠️ Workflow di sviluppo

### Modifiche al codice
- **Backend PHP**: Modifica i file in `src/` - richiede rebuild: `docker compose up -d --build`
- **Debug/test**: Modifica i file in `debug/` - le modifiche sono immediate (volume montato)
- **Template Twig**: Modifica i file in `templates/` - richiede rebuild: `docker compose up -d --build`

### Monitoraggio e debug
```bash
# Visualizza i log in tempo reale
docker compose logs -f govpay-interaction-layer

# Accedi al container per debug
docker compose exec govpay-interaction-layer bash

# Riavvia solo il servizio PHP senza rebuild
docker compose restart govpay-interaction-layer
```

## 🔧 Configurazione di avvio

### Variabili d'ambiente
Crea il file `.env` (puoi partire da `.env.example` se presente) e configura le variabili per il tuo ambiente:

```bash
cp .env.example .env
```

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

- Regola di sicurezza (importante): l'app impedisce la rimozione dell'ultimo account con ruolo `superadmin`. Questo significa che non puoi cancellare l'admin seed se non esiste già un altro `superadmin` attivo. Se desideri sostituire l'admin seed procedi così in sicurezza:
   1. Accedi con l'admin seed (creato al primo avvio) e crea un nuovo account con ruolo `superadmin` dalla sezione “Utenti”.
   2. Effettua il logout e verifica l'accesso col nuovo superadmin.
   3. A questo punto puoi eliminare l'admin seed o modificarne la password/ruolo.

- Se invece vuoi forzare la rigenerazione dell'account seed con le credenziali in `.env`, elimina prima manualmente il superadmin esistente (dopo aver creato un altro superadmin) o, in ambienti di sviluppo, puoi cancellare la riga corrispondente nella tabella utenti e riavviare il servizio (attenzione: questa operazione è distruttiva).

- Nota su autofill e sicurezza: alcuni browser possono proporre l'autocompletamento dei campi credenziali. L'app fornisce attributi per disabilitare l'autofill sui form sensibili ma il comportamento dipende anche dalle impostazioni del browser; se necessario, cancella le credenziali salvate o usa una finestra di navigazione privata per testare il login.

- Comportamento delle notifiche: i messaggi di successo o errore sono mostrati come flash message nell'area superiore della pagina dopo i redirect; assicurati di controllare la barra delle notifiche subito dopo le operazioni di creazione/aggiornamento.

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
Accedi a https://localhost:8443/debug/ per:
- Testare chiamate API GovPay
- Verificare configurazione ambiente
- Debug delle pendenze

### Database
Il database MariaDB è accessibile su `localhost:3306` con le credenziali configurate in `.env`.

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
docker inspect govpay-interaction-layer

# Controlla logs di tutti i servizi
docker compose logs

# Accesso diretto al filesystem del container
docker exec -it govpay-interaction-layer find /var/www/html -name "*.php" | head -10
```

---

## 📚 Struttura del progetto

```
GovPay-Interaction-Layer/
├── docker-compose.yml      # Configurazione servizi Docker
├── Dockerfile             # Build dell'immagine PHP/Apache
├── src/                   # Codice sorgente PHP (copiato in build)
├── templates/             # Template Twig (copiati in build)
├── debug/                 # Tool di debug (montato come volume)
├── govpay-clients/        # Client API generati da OpenAPI
├── ssl/                   # Certificati SSL personalizzati
└── .env                   # Configurazione ambiente (da creare)
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
[![TODO](https://img.shields.io/badge/TODO-Lista%20attivit%C3%A0-blue)](docs/TODO.md)



**Nota**: Questo progetto è sviluppato per facilitare l'integrazione con GovPay/PagoPA negli Enti.
