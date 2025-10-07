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

⚠️ **Nota SSL**: Al primo avvio vengono generati certificati self-signed. Il browser mostrerà un avviso di sicurezza che puoi ignorare per lo sviluppo.

## 🛠️ Workflow di sviluppo

### Modifiche al codice
- **Backend PHP**: Modifica i file in `src/` - le modifiche sono immediate (volume montato)
- **Debug/test**: Modifica i file in `public/debug/` - le modifiche sono immediate (volume montato)
- **Template Twig**: Modifica i file in `templates/` - richiede rebuild: `docker compose up -d --build`

### Monitoraggio e debug
```bash
# Visualizza i log in tempo reale
docker compose logs -f php-apache

# Accedi al container per debug
docker exec -it govpay-interaction-layer bash

# Riavvia solo il servizio PHP senza rebuild
docker compose restart php-apache
```

## 🔧 Configurazione

### Variabili d'ambiente
Copia `.env.example` in `.env` e configura le variabili per il tuo ambiente:

```bash
cp .env.example .env
```

Le principali variabili da configurare:
- `GOVPAY_PENDENZE_URL`: URL dell'API GovPay
- `AUTHENTICATION_GOVPAY`: Metodo di autenticazione
- `DB_*`: Configurazione database MariaDB

### Certificati SSL personalizzati
Per usare certificati personalizzati, posiziona i file nella cartella `ssl/`:
- `ssl/server.key` - Chiave privata
- `ssl/server.crt` - Certificato

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
├── src/                   # Codice sorgente PHP (montato come volume)
├── templates/             # Template Twig
├── public/debug/          # Tool di debug (montato come volume)
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
