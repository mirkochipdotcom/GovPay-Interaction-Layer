# GovPay\Backoffice\RuoliApi

All URIs are relative to http://localhost/govpay/backend/api/backoffice/rs/basic/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**addRuolo()**](RuoliApi.md#addRuolo) | **PUT** /ruoli/{idRuolo} | Aggiunge o aggiorna un ruolo |
| [**findRuoli()**](RuoliApi.md#findRuoli) | **GET** /ruoli | Lista dei ruoli disponibili |
| [**getRuolo()**](RuoliApi.md#getRuolo) | **GET** /ruoli/{idRuolo} | Legge le ACL di un ruolo |
| [**updateRuolo()**](RuoliApi.md#updateRuolo) | **PATCH** /ruoli/{idRuolo} | Aggiorna selettivamente campi di un Ruolo |


## `addRuolo()`

```php
addRuolo($id_ruolo, $ruolo_post)
```

Aggiunge o aggiorna un ruolo

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\RuoliApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_ruolo = 'id_ruolo_example'; // string | Identificativo di un ruolo
$ruolo_post = new \GovPay\Backoffice\Model\RuoloPost(); // \GovPay\Backoffice\Model\RuoloPost

try {
    $apiInstance->addRuolo($id_ruolo, $ruolo_post);
} catch (Exception $e) {
    echo 'Exception when calling RuoliApi->addRuolo: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_ruolo** | **string**| Identificativo di un ruolo | |
| **ruolo_post** | [**\GovPay\Backoffice\Model\RuoloPost**](../Model/RuoloPost.md)|  | [optional] |

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

## `findRuoli()`

```php
findRuoli($pagina, $risultati_per_pagina, $metadati_paginazione): \GovPay\Backoffice\Model\FindRuoli201Response
```

Lista dei ruoli disponibili

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\RuoliApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno

try {
    $result = $apiInstance->findRuoli($pagina, $risultati_per_pagina, $metadati_paginazione);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RuoliApi->findRuoli: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\FindRuoli201Response**](../Model/FindRuoli201Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getRuolo()`

```php
getRuolo($id_ruolo): \GovPay\Backoffice\Model\Ruolo
```

Legge le ACL di un ruolo

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\RuoliApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_ruolo = 'id_ruolo_example'; // string | Identificativo di un ruolo

try {
    $result = $apiInstance->getRuolo($id_ruolo);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RuoliApi->getRuolo: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_ruolo** | **string**| Identificativo di un ruolo | |

### Return type

[**\GovPay\Backoffice\Model\Ruolo**](../Model/Ruolo.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `updateRuolo()`

```php
updateRuolo($id_ruolo, $patch_op): \GovPay\Backoffice\Model\Ruolo
```

Aggiorna selettivamente campi di un Ruolo

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\RuoliApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_ruolo = 'id_ruolo_example'; // string | Identificativo del ruolo
$patch_op = array(new \GovPay\Backoffice\Model\PatchOp()); // \GovPay\Backoffice\Model\PatchOp[]

try {
    $result = $apiInstance->updateRuolo($id_ruolo, $patch_op);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RuoliApi->updateRuolo: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_ruolo** | **string**| Identificativo del ruolo | |
| **patch_op** | [**\GovPay\Backoffice\Model\PatchOp[]**](../Model/PatchOp.md)|  | |

### Return type

[**\GovPay\Backoffice\Model\Ruolo**](../Model/Ruolo.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `application/json-patch+json`
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
