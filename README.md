# 🇮🇹 GovPay Interaction Layer (GIL)

Piattaforma containerizzata (PHP/Apache + frontend) per integrare e testare flussi di pagamento con GovPay (PagoPA).

Badge e repository:
[![GitHub Repository](https://img.shields.io/badge/GitHub-mirkochipdotcom%2FGovPay--Interaction--Layer-blue?style=flat&logo=github)](https://github.com/mirkochipdotcom/GovPay-Interaction-Layer.git)

---

## 🚀 Avvio rapido (developer)

Questa sezione copre i passaggi tipici per eseguire il progetto in locale usando Docker Compose.

### Prerequisiti
- Docker
- Docker Compose (o il plugin `docker compose` incluso nelle versioni recenti)

### Clona il repository

```powershell
git clone https://github.com/mirkochipdotcom/GovPay-Interaction-Layer.git
cd GovPay-Interaction-Layer
```

### Build & avvio (prima esecuzione)

La prima build può impiegare qualche minuto perché scarica dipendenze e compila asset.

```powershell
# Ricostruisce le immagini e avvia in background
docker-compose up --build -d

# Per seguire i log del servizio PHP/Apache
docker-compose logs --no-color --no-log-prefix php-apache --tail 200
```

### Dove è disponibile l'app

- URL (HTTPS): https://localhost:8443

## � SSL — comportamento automatico

Per semplificare lo sviluppo, se non fornisci certificati TLS nella cartella `ssl/`, il container genererà automaticamente certificati self-signed al primo avvio.

Dettagli:
- File generati (all'interno del container): `/ssl/server.key` e `/ssl/server.crt`
- I certificati generati sono solo per sviluppo/test. Non usarli in produzione.

Se vuoi fornire certificati personalizzati, monta la cartella `ssl/` del tuo host nel container (o copia i file nel contesto prima di buildare). Esempio (docker-compose):

```yaml
services:
    php-apache:
        volumes:
            - ./ssl:/ssl:ro
```

Con il mount, il container userà i certificati forniti dall'host.

## 🛠️ Comandi utili

- Ricostruire senza cache (forza applicare le modifiche Dockerfile):

```powershell
docker-compose build --no-cache
docker-compose up -d
```

- Fermare e rimuovere i container/risorse:

```powershell
docker-compose down --remove-orphans
```


## Troubleshooting rapido

- Errore "exec /usr/local/bin/docker-setup.sh: no such file or directory":
    - Assicurati di aver ricostruito l'immagine dopo eventuali modifiche a `docker-setup.sh` (la Dockerfile copia lo script nella image).
    - Se il problema persiste, verifica line endings (LF) e permessi. È stata aggiunta normalizzazione automatica nel Dockerfile ma puoi anche forzare LF con `.gitattributes`.

## Contatti

Per domande o problemi, apri un'issue sul repository GitHub.
