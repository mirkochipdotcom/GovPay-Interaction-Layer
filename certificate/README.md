# Certifi## 🔑 Utilizzo con GovPay

Posiziona in questa directory i certificati e le chiavi private forniti da GovPay/PagoPA:

- **Certificato client**: `certificate.cer` (o `.crt`, `.pem`)
- **Chiave privata**: `private_key.key` (o `.pem`)

### 📋 Provenienza dei certificati

I certificati per l'autenticazione con GovPay vengono tipicamente:

- **Forniti dall'amministratore** dell'istanza GovPay
- **Generati tramite interfaccia GovPay** (sezione configurazione/certificati)
- **Creati dal gestore** dell'infrastruttura PagoPA/GovPay

**Non generare certificati self-signed per la produzione** - usa solo quelli ufficiali forniti dall'istanza GovPay.irectory - GovPay API Integration

Questa directory è destinata a contenere i certificati e le chiavi private necessari per l'autenticazione con le API GovPay.

## ⚠️ Attenzione Sicurezza

**I certificati e le chiavi private NON devono essere committati nel repository.**  
Tutti i file in questa directory (eccetto questo README) sono automaticamente ignorati da Git.

## 🔑 Utilizzo con GovPay

Posiziona in questa directory i file di certificato forniti da GovPay/PagoPA:

- **Certificato client**: `certificate.cer` (o `.crt`, `.pem`)
- **Chiave privata**: `private_key.key` (o `.pem`)

## 🔧 Configurazione nel file .env

Dopo aver posizionato i certificati, configura le seguenti variabili nel file `.env`:

```bash
# Autenticazione GovPay
AUTHENTICATION_GOVPAY=sslheader

# Percorsi certificati (all'interno del container Docker)
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
├── README.md          # Questo file (tracciato da Git)
├── certificate.cer    # Certificato client GovPay (ignorato da Git)
├── private_key.key    # Chiave privata (ignorato da Git)
└── ca.cer            # Certificato CA (opzionale, ignorato da Git)
```

## 🛠️ Generazione certificati di test

Per l'ambiente di sviluppo/test, puoi generare certificati self-signed:

```bash
# Genera certificato e chiave per test
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout private_key.key \
  -out certificate.cer \
  -subj "/CN=govpay-test/O=Development"
```

## 🔄 Utilizzo nel container Docker

I certificati vengono automaticamente montati nel container Docker nel percorso `/var/www/certificate/`, come configurato nel `Dockerfile`.

## 📋 Checklist configurazione

- [ ] Certificato client posizionato in `certificate/`
- [ ] Chiave privata posizionata in `certificate/`
- [ ] Variabili `.env` configurate con i percorsi corretti
- [ ] `GOVPAY_PENDENZE_URL` impostata con l'URL corretto
- [ ] Container riavviato dopo modifiche: `docker compose restart`

Per ulteriori dettagli, consulta il README principale del progetto.
