# 🇮🇹 GovPay Interaction Layer (GIL)

Piattaforma containerizzata (PHP/Apache + frontend) per integrare e testare flussi di pagamento con GovPay (PagoPA).

[![GitHub Repository](https://img.shields.io/badge/GitHub-mirkochipdotcom%2FGovPay--Interaction--Layer-blue?style=flat&logo=github)](https://github.com/mirkochipdotcom/GovPay-Interaction-Layer.git)

---

## 🚀 Avvio rapido (primo utilizzo)

### Prerequisiti
- Docker
- Docker Compose (o il plugin `docker compose` incluso nelle versioni recenti)

### 1. Clona il repository

```bash
git clone https://github.com/mirkochipdotcom/GovPay-Interaction-Layer.git
cd GovPay-Interaction-Layer
```

### 2. Avvia l'applicazione

**Prima esecuzione** (build automatica):
```bash
docker compose up -d
```

**Se hai modifiche al Dockerfile** (forza rebuild):
```bash
docker compose up -d --build
```

La prima build può impiegare qualche minuto perché scarica dipendenze e compila asset.

### 3. Accedi all'applicazione

- **URL principale**: https://localhost:8443
- **Debug tool**: https://localhost:8443/debug/

⚠️ **Nota SSL**: Se non fornisci certificati personalizzati in `ssl/`, al primo avvio verranno generati certificati self-signed. Il browser mostrerà un avviso di sicurezza che puoi ignorare per lo sviluppo.

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

## 🔧 Configurazione

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
- `superadmin`: privilegi equivalenti ad admin nell'app attuale

Note importanti:
- Il seed crea l'utente admin solo se non esiste già. Se vuoi rigenerarlo con nuove credenziali, puoi:
   - Eliminare l'utente dalla sezione “Utenti” e riavviare il container (verrà ricreato dal seed), oppure
   - Aggiornare la password dell'utente direttamente dalla pagina “Modifica utente”.
- I messaggi di successo/errore compaiono come notifiche (flash) nella parte alta della pagina dopo i redirect.

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

**Errore Twig LoaderError**: 
- ✅ Risolto nelle versioni recenti - i template sono ora correttamente configurati

**Errore "exec /usr/local/bin/docker-setup.sh"**:
- Ricostruisci l'immagine: `docker compose up -d --build`
- Il problema è spesso legato ai line endings (automaticamente risolto nel Dockerfile)

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

**Nota**: Questo progetto è sviluppato per facilitare l'integrazione con GovPay/PagoPA in ambiente di sviluppo e test.
