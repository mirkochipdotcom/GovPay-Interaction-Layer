# GovPay\Ragioneria\RiconciliazioniApi

All URIs are relative to http://localhost/govpay/backend/api/ragioneria/rs/basic/v3, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**addRiconciliazione()**](RiconciliazioniApi.md#addRiconciliazione) | **PUT** /riconciliazioni/{idDominio}/{id} | Riconciliazione di un movimento di cassa |
| [**findRiconciliazioni()**](RiconciliazioniApi.md#findRiconciliazioni) | **GET** /riconciliazioni | Elenco dei movimenti di cassa riconciliati |
| [**getRiconciliazione()**](RiconciliazioniApi.md#getRiconciliazione) | **GET** /riconciliazioni/{idDominio}/{id} | Dettaglio di un movimento di cassa riconciliato |


## `addRiconciliazione()`

```php
addRiconciliazione($id_dominio, $id, $nuova_riconciliazione): \GovPay\Ragioneria\Model\Riconciliazione
```

Riconciliazione di un movimento di cassa

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Ragioneria\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Ragioneria\Api\RiconciliazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo dell'Ente Creditore in pagoPA. Corrisponde al codice fiscale.
$id = 'id_example'; // string | Identificativo dell'operazione di riconciliazione
$nuova_riconciliazione = {"importo":100.01,"dataValuta":"2020-12-31","dataContabile":"2020-12-31","contoAccredito":"IT60X0542811101000000123456","sct":11234,"causale":"000000000000000,00000000000000101,01EUR000000000000000,000000000000000000000000,000000000000,00/ZZ2SATISPAY EUROPE SA/ZZ3Comune Dimostrativo/PUR/LGPE-RIVERSAMENTO/URI/2020-11-21GovPAYPsp-1234567890/ZZ4/4184/ZZ4/ID1357a8662b2256347456532f53f9a9825357a8662b2b911eb8da402f53f9a9825"}; // \GovPay\Ragioneria\Model\NuovaRiconciliazione

try {
    $result = $apiInstance->addRiconciliazione($id_dominio, $id, $nuova_riconciliazione);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RiconciliazioniApi->addRiconciliazione: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo dell&#39;Ente Creditore in pagoPA. Corrisponde al codice fiscale. | |
| **id** | **string**| Identificativo dell&#39;operazione di riconciliazione | |
| **nuova_riconciliazione** | [**\GovPay\Ragioneria\Model\NuovaRiconciliazione**](../Model/NuovaRiconciliazione.md)|  | [optional] |

### Return type

[**\GovPay\Ragioneria\Model\Riconciliazione**](../Model/Riconciliazione.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findRiconciliazioni()`

```php
findRiconciliazioni($pagina, $risultati_per_pagina, $id_dominio, $data_da, $data_a, $metadati_paginazione, $max_risultati, $sct): \GovPay\Ragioneria\Model\Riconciliazioni
```

Elenco dei movimenti di cassa riconciliati

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Ragioneria\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Ragioneria\Api\RiconciliazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina di risultato
$risultati_per_pagina = 25; // int | How many items to return at one time
$id_dominio = 'id_dominio_example'; // string | Identificativo dell'Ente Creditore in pagoPA. Corrisponde al codice fiscale.
$data_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inizio della finestra temporale di osservazione
$data_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Fine della finestra temporale di osservazione
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati
$sct = 'sct_example'; // string | Identificativo del Sepa Credit Transfert di riversamento da PSP a conto di accredito

try {
    $result = $apiInstance->findRiconciliazioni($pagina, $risultati_per_pagina, $id_dominio, $data_da, $data_a, $metadati_paginazione, $max_risultati, $sct);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RiconciliazioniApi->findRiconciliazioni: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina di risultato | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| How many items to return at one time | [optional] [default to 25] |
| **id_dominio** | **string**| Identificativo dell&#39;Ente Creditore in pagoPA. Corrisponde al codice fiscale. | [optional] |
| **data_da** | **\DateTime**| Inizio della finestra temporale di osservazione | [optional] |
| **data_a** | **\DateTime**| Fine della finestra temporale di osservazione | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |
| **sct** | **string**| Identificativo del Sepa Credit Transfert di riversamento da PSP a conto di accredito | [optional] |

### Return type

[**\GovPay\Ragioneria\Model\Riconciliazioni**](../Model/Riconciliazioni.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getRiconciliazione()`

```php
getRiconciliazione($id_dominio, $id): \GovPay\Ragioneria\Model\Riconciliazione
```

Dettaglio di un movimento di cassa riconciliato

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Ragioneria\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Ragioneria\Api\RiconciliazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo dell'Ente Creditore in pagoPA. Corrisponde al codice fiscale.
$id = 'id_example'; // string | Identificativo dell'operazione di riconciliazione

try {
    $result = $apiInstance->getRiconciliazione($id_dominio, $id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RiconciliazioniApi->getRiconciliazione: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo dell&#39;Ente Creditore in pagoPA. Corrisponde al codice fiscale. | |
| **id** | **string**| Identificativo dell&#39;operazione di riconciliazione | |

### Return type

[**\GovPay\Ragioneria\Model\Riconciliazione**](../Model/Riconciliazione.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
