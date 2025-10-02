# GovPay\Backoffice\OperatoriApi

All URIs are relative to http://localhost/govpay/backend/api/backoffice/rs/basic/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**addOperatore()**](OperatoriApi.md#addOperatore) | **PUT** /operatori/{principal} | Aggiunge o aggiorna un operatore |
| [**findOperatori()**](OperatoriApi.md#findOperatori) | **GET** /operatori | Elenco degli operatori censiti sul sistema |
| [**getOperatore()**](OperatoriApi.md#getOperatore) | **GET** /operatori/{principal} | Lettura dei dati di un operatore |
| [**updateOperatore()**](OperatoriApi.md#updateOperatore) | **PATCH** /operatori/{principal} | Aggiorna selettivamente campi di un Operatore |


## `addOperatore()`

```php
addOperatore($principal, $operatore_post)
```

Aggiunge o aggiorna un operatore

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\OperatoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$principal = 'principal_example'; // string | Username dell'operatore
$operatore_post = new \GovPay\Backoffice\Model\OperatorePost(); // \GovPay\Backoffice\Model\OperatorePost

try {
    $apiInstance->addOperatore($principal, $operatore_post);
} catch (Exception $e) {
    echo 'Exception when calling OperatoriApi->addOperatore: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **principal** | **string**| Username dell&#39;operatore | |
| **operatore_post** | [**\GovPay\Backoffice\Model\OperatorePost**](../Model/OperatorePost.md)|  | [optional] |

### Return type

void (empty response body)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findOperatori()`

```php
findOperatori($pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $metadati_paginazione, $max_risultati): \GovPay\Backoffice\Model\FindOperatori200Response
```

Elenco degli operatori censiti sul sistema

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\OperatoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$ordinamento = '+ragioneSociale'; // string | csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * principal  * ragioneSociale
$campi = 'campi_example'; // string | csv dei campi da includere nella risposta (default tutti)
$abilitato = True; // bool | Restrizione ai soli elementi abilitati o disabilitati
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati

try {
    $result = $apiInstance->findOperatori($pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $metadati_paginazione, $max_risultati);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OperatoriApi->findOperatori: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **ordinamento** | **string**| csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * principal  * ragioneSociale | [optional] [default to &#39;+ragioneSociale&#39;] |
| **campi** | **string**| csv dei campi da includere nella risposta (default tutti) | [optional] |
| **abilitato** | **bool**| Restrizione ai soli elementi abilitati o disabilitati | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\FindOperatori200Response**](../Model/FindOperatori200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getOperatore()`

```php
getOperatore($principal): \GovPay\Backoffice\Model\Operatore
```

Lettura dei dati di un operatore

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\OperatoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$principal = 'principal_example'; // string | Username dell'operatore

try {
    $result = $apiInstance->getOperatore($principal);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OperatoriApi->getOperatore: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **principal** | **string**| Username dell&#39;operatore | |

### Return type

[**\GovPay\Backoffice\Model\Operatore**](../Model/Operatore.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `updateOperatore()`

```php
updateOperatore($principal, $patch_op): \GovPay\Backoffice\Model\Operatore
```

Aggiorna selettivamente campi di un Operatore

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\OperatoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$principal = 'principal_example'; // string | Identificativo dell'operatore
$patch_op = array(new \GovPay\Backoffice\Model\PatchOp()); // \GovPay\Backoffice\Model\PatchOp[]

try {
    $result = $apiInstance->updateOperatore($principal, $patch_op);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OperatoriApi->updateOperatore: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **principal** | **string**| Identificativo dell&#39;operatore | |
| **patch_op** | [**\GovPay\Backoffice\Model\PatchOp[]**](../Model/PatchOp.md)|  | |

### Return type

[**\GovPay\Backoffice\Model\Operatore**](../Model/Operatore.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `application/json-patch+json`
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
