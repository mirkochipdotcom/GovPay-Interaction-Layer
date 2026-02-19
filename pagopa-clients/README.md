# pagoPA clients (OpenAPI)

Questa cartella contiene gli script per generare SDK PHP dai file OpenAPI pubblicati nel repository `pagopa/pagopa-api`.

## Checkout EC API

Spec: `SANP3.10.0/openapi/checkout.json`

### Generazione

Requisiti: `docker`, `jq`, `bash`.

Da `pagopa-clients/`:

- `./generate.sh`

Output:
- `pagopa-clients/generated-clients/checkout-ec-v1/checkout-ec-client`

### Integrazione nel progetto

Il progetto include il client via `composer.json` come repository `path`.

### Configurazione runtime (Frontoffice)

Per usare `POST /carts` (avvio checkout) serve una subscription key di pagoPA (APIM):

- `PAGOPA_CHECKOUT_EC_BASE_URL` (es. `https://api.dev.platform.pagopa.it/checkout/ec/v1`)
- `PAGOPA_CHECKOUT_SUBSCRIPTION_KEY` (header `Ocp-Apim-Subscription-Key`)
- `PAGOPA_CHECKOUT_COMPANY_NAME` (nome ente mostrato nel checkout)

Opzionali (URL di ritorno):
- `PAGOPA_CHECKOUT_RETURN_OK_URL`
- `PAGOPA_CHECKOUT_RETURN_CANCEL_URL`
- `PAGOPA_CHECKOUT_RETURN_ERROR_URL`

---

## Biz Events Service API (Ricevute EC)

Endpoint REST PagoPA per recuperare le ricevute di pagamento on-demand, utilizzato dal Backoffice per visualizzare i dettagli dei pagamenti non presenti in GovPay (pagamenti "orfani").

### API utilizzata

`GET /organizations/{fiscalCode}/receipts/{iur}?iuv={iuv}`

Dove:
- `fiscalCode`: codice fiscale dell'ente creditore (ID Dominio)
- `iur`: Identificativo Univoco Riscossione
- `iuv`: Identificativo Univoco Versamento

### Dati restituiti

Dalla risposta vengono estratti:
- **Debitore**: nome, codice fiscale, tipo (PF/PG)
- **Pagatore**: nome, codice fiscale
- **PSP/Intermediario**: nome PSP, canale, metodo di pagamento
- **Dettagli pagamento**: descrizione, importo, data, esito, numero avviso, ID ricevuta
- **Trasferimenti** (`transferList`): elenco voci con importo, beneficiario (CF/IBAN), descrizione

### Configurazione runtime (Backoffice)

Variabili d'ambiente (in `.env`):

- `BIZ_EVENTS_HOST`: Base URL del servizio
  - DEV: `https://api.dev.platform.pagopa.it/bizevents/service/v1`
  - PROD: `https://api.platform.pagopa.it/bizevents/service/v1`
- `BIZ_EVENTS_API_KEY`: Subscription key (header `Ocp-Apim-Subscription-Key`)

### Endpoint interno (Backoffice)

`GET /api/biz-event?fc={fiscalCode}&iur={iur}&iuv={iuv}`

Chiamato via AJAX dal dettaglio flusso. Restituisce JSON con i dati della ricevuta o un errore:
- `200`: dati ricevuta (inclusi `transfers` e `total_amount`)
- `404`: ricevuta non trovata
- `429`: rate limit superato (con flag `retry: true`)
- `500`: errore generico

