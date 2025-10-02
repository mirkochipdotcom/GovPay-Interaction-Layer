# GovPay\Backoffice\RiconciliazioniApi

All URIs are relative to http://localhost/govpay/backend/api/backoffice/rs/basic/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**addRiconciliazione()**](RiconciliazioniApi.md#addRiconciliazione) | **POST** /incassi/{idDominio} | Registrazione di un movimento di cassa |
| [**findRiconciliazioni()**](RiconciliazioniApi.md#findRiconciliazioni) | **GET** /incassi | Elenco degli incassi registrati |
| [**getRiconciliazione()**](RiconciliazioniApi.md#getRiconciliazione) | **GET** /incassi/{idDominio}/{idIncasso} | Acquisizione dei dati di un incasso |


## `addRiconciliazione()`

```php
addRiconciliazione($id_dominio, $id_flusso_case_insensitive, $incasso_post): \GovPay\Backoffice\Model\Incasso
```

Registrazione di un movimento di cassa

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\RiconciliazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo del creditore
$id_flusso_case_insensitive = True; // bool | Indica se effettuare la ricerca per Identificativo flusso in modalita' case insensitive
$incasso_post = new \GovPay\Backoffice\Model\IncassoPost(); // \GovPay\Backoffice\Model\IncassoPost

try {
    $result = $apiInstance->addRiconciliazione($id_dominio, $id_flusso_case_insensitive, $incasso_post);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RiconciliazioniApi->addRiconciliazione: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo del creditore | |
| **id_flusso_case_insensitive** | **bool**| Indica se effettuare la ricerca per Identificativo flusso in modalita&#39; case insensitive | [optional] |
| **incasso_post** | [**\GovPay\Backoffice\Model\IncassoPost**](../Model/IncassoPost.md)|  | [optional] |

### Return type

[**\GovPay\Backoffice\Model\Incasso**](../Model/Incasso.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findRiconciliazioni()`

```php
findRiconciliazioni($pagina, $risultati_per_pagina, $ordinamento, $data_da, $data_a, $id_dominio, $metadati_paginazione, $max_risultati, $sct, $id_flusso, $iuv, $stato): \GovPay\Backoffice\Model\FindRiconciliazioni201Response
```

Elenco degli incassi registrati

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\RiconciliazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$ordinamento = '+data'; // string | csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * data
$data_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inizio della finestra temporale di osservazione
$data_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Fine della finestra temporale di osservazione
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio beneficiario
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati
$sct = abc123...; // string | filtro per codice SCT (anche parziale)
$id_flusso = 'id_flusso_example'; // string | Identificativo flusso
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$stato = new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\StatoIncasso(); // \GovPay\Backoffice\Model\StatoIncasso | Filtro sullo stato della riconciliazione

try {
    $result = $apiInstance->findRiconciliazioni($pagina, $risultati_per_pagina, $ordinamento, $data_da, $data_a, $id_dominio, $metadati_paginazione, $max_risultati, $sct, $id_flusso, $iuv, $stato);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RiconciliazioniApi->findRiconciliazioni: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **ordinamento** | **string**| csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * data | [optional] [default to &#39;+data&#39;] |
| **data_da** | **\DateTime**| Inizio della finestra temporale di osservazione | [optional] |
| **data_a** | **\DateTime**| Fine della finestra temporale di osservazione | [optional] |
| **id_dominio** | **string**| Identificativo del dominio beneficiario | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |
| **sct** | **string**| filtro per codice SCT (anche parziale) | [optional] |
| **id_flusso** | **string**| Identificativo flusso | [optional] |
| **iuv** | **string**| Identificativo univoco di versamento | [optional] |
| **stato** | [**\GovPay\Backoffice\Model\StatoIncasso**](../Model/.md)| Filtro sullo stato della riconciliazione | [optional] |

### Return type

[**\GovPay\Backoffice\Model\FindRiconciliazioni201Response**](../Model/FindRiconciliazioni201Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getRiconciliazione()`

```php
getRiconciliazione($id_dominio, $id_incasso, $riscossioni_tipo): \GovPay\Backoffice\Model\Incasso
```

Acquisizione dei dati di un incasso

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\RiconciliazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo del creditore
$id_incasso = 'id_incasso_example'; // string | Identificativo dell'idFlusso o iuv, a seconda della modalita di riversamento
$riscossioni_tipo = array(new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\TipoRiscossione()); // \GovPay\Backoffice\Model\TipoRiscossione[] | Tipologia della riscossione (default [ENTRATA, MBT] )

try {
    $result = $apiInstance->getRiconciliazione($id_dominio, $id_incasso, $riscossioni_tipo);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RiconciliazioniApi->getRiconciliazione: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo del creditore | |
| **id_incasso** | **string**| Identificativo dell&#39;idFlusso o iuv, a seconda della modalita di riversamento | |
| **riscossioni_tipo** | [**\GovPay\Backoffice\Model\TipoRiscossione[]**](../Model/\GovPay\Backoffice\Model\TipoRiscossione.md)| Tipologia della riscossione (default [ENTRATA, MBT] ) | [optional] |

### Return type

[**\GovPay\Backoffice\Model\Incasso**](../Model/Incasso.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
