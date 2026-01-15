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
