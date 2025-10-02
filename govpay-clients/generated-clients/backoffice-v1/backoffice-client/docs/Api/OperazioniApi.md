# GovPay\Backoffice\OperazioniApi

All URIs are relative to http://localhost/govpay/backend/api/backoffice/rs/basic/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**findOperazioni()**](OperazioniApi.md#findOperazioni) | **GET** /operazioni | Lista delle operazioni disponibili |
| [**getOperazione()**](OperazioniApi.md#getOperazione) | **GET** /operazioni/{idOperazione} | Esecuzione di una operazione interne |
| [**getStatoOperazione()**](OperazioniApi.md#getStatoOperazione) | **GET** /operazioni/stato/{id} | Stato di elaborazione di una operazione |


## `findOperazioni()`

```php
findOperazioni($pagina, $risultati_per_pagina): \GovPay\Backoffice\Model\FindOperazioni201Response
```

Lista delle operazioni disponibili

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\OperazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)

try {
    $result = $apiInstance->findOperazioni($pagina, $risultati_per_pagina);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OperazioniApi->findOperazioni: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |

### Return type

[**\GovPay\Backoffice\Model\FindOperazioni201Response**](../Model/FindOperazioni201Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getOperazione()`

```php
getOperazione($id_operazione): \GovPay\Backoffice\Model\Operazione
```

Esecuzione di una operazione interne

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\OperazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_operazione = 'id_operazione_example'; // string | Identificativo dell'operazione da eseguire

try {
    $result = $apiInstance->getOperazione($id_operazione);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OperazioniApi->getOperazione: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_operazione** | **string**| Identificativo dell&#39;operazione da eseguire | |

### Return type

[**\GovPay\Backoffice\Model\Operazione**](../Model/Operazione.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getStatoOperazione()`

```php
getStatoOperazione($id): \GovPay\Backoffice\Model\EsitoOperazione
```

Stato di elaborazione di una operazione

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\OperazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 'id_example'; // string | Identificativo dell'istanza di operazione eseguita

try {
    $result = $apiInstance->getStatoOperazione($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OperazioniApi->getStatoOperazione: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **string**| Identificativo dell&#39;istanza di operazione eseguita | |

### Return type

[**\GovPay\Backoffice\Model\EsitoOperazione**](../Model/EsitoOperazione.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
