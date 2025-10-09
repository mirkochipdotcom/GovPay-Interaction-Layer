# Certificati GovPay – Directory di integrazione

Questa directory contiene i certificati e le chiavi private necessari per l'autenticazione verso le API GovPay.

## ⚠️ Sicurezza

- I certificati e le chiavi private NON devono essere committati nel repository.
- Assicurati che siano esclusi da Git (es. regole di `.gitignore`). Esempio:
  ```gitignore
  certificate/*
  !certificate/README.md
  ```

## 🔑 Utilizzo con GovPay

Posiziona in questa directory i file forniti da GovPay/PagoPA:

- Certificato client: `certificate.cer` (o `.crt`, `.pem`)
- Chiave privata: `private_key.key` (o `.pem`)

### 📋 Provenienza dei certificati

I certificati per l'autenticazione con GovPay vengono tipicamente:

- Forniti dall'amministratore dell'istanza GovPay
- Generati tramite interfaccia GovPay (sezione configurazione/certificati)
- Creati dal gestore dell'infrastruttura PagoPA/GovPay

In produzione, non utilizzare certificati self‑signed: usa solo certificati ufficiali forniti dall'istanza.

## 🔧 Configurazione nel file `.env`

Imposta le seguenti variabili:

```bash
# Autenticazione GovPay
AUTHENTICATION_GOVPAY=sslheader

# Percorsi certificati (nel container Docker)
GOVPAY_TLS_CERT=/var/www/certificate/certificate.cer
GOVPAY_TLS_KEY=/var/www/certificate/private_key.key

# Password chiave privata (se richiesta)
GOVPAY_TLS_KEY_PASSWORD=your_key_password

# URL API GovPay
GOVPAY_PENDENZE_URL=https://your-govpay-instance.example.com
```

## 📁 Struttura consigliata

```
certificate/
├─ README.md          # Questo file (tracciato)
├─ certificate.cer    # Certificato client GovPay (ignorato)
├─ private_key.key    # Chiave privata (ignorato)
└─ ca.cer             # CA opzionale (ignorato)
```

## 🛠️ Certificati di test (sviluppo)

Per sviluppo/test puoi generare certificati self‑signed:

```bash
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout private_key.key \
  -out certificate.cer \
  -subj "/CN=govpay-test/O=Development"
```

## 🔄 Utilizzo nel container Docker

Durante la build dell'immagine, se i file sono presenti, vengono copiati nel percorso `/var/www/certificate/` (vedi `Dockerfile`).

## ✅ Checklist

- [ ] Certificato client posizionato in `certificate/`
- [ ] Chiave privata posizionata in `certificate/`
- [ ] Variabili `.env` aggiornate ai percorsi corretti
- [ ] `GOVPAY_PENDENZE_URL` impostata
- [ ] Container riavviato dopo le modifiche (`docker compose restart`)
