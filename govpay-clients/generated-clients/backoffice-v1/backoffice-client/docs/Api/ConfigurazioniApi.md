# GovPay\Backoffice\ConfigurazioniApi

All URIs are relative to http://localhost/govpay/backend/api/backoffice/rs/basic/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**addConfigurazioni()**](ConfigurazioniApi.md#addConfigurazioni) | **POST** /configurazioni | Aggiorna la configurazione generale del sistema |
| [**aggiornaConfigurazioni()**](ConfigurazioniApi.md#aggiornaConfigurazioni) | **PATCH** /configurazioni | Modifica puntuale di una parte della configurazione |
| [**getConfigurazioni()**](ConfigurazioniApi.md#getConfigurazioni) | **GET** /configurazioni | Legge la configurazione generale del sistema |


## `addConfigurazioni()`

```php
addConfigurazioni($configurazione)
```

Aggiorna la configurazione generale del sistema

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\ConfigurazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$configurazione = new \GovPay\Backoffice\Model\Configurazione(); // \GovPay\Backoffice\Model\Configurazione | Configurazione aggiornata

try {
    $apiInstance->addConfigurazioni($configurazione);
} catch (Exception $e) {
    echo 'Exception when calling ConfigurazioniApi->addConfigurazioni: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **configurazione** | [**\GovPay\Backoffice\Model\Configurazione**](../Model/Configurazione.md)| Configurazione aggiornata | [optional] |

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

## `aggiornaConfigurazioni()`

```php
aggiornaConfigurazioni($patch_op): \GovPay\Backoffice\Model\Configurazione
```

Modifica puntuale di una parte della configurazione

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\ConfigurazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$patch_op = array(new \GovPay\Backoffice\Model\PatchOp()); // \GovPay\Backoffice\Model\PatchOp[]

try {
    $result = $apiInstance->aggiornaConfigurazioni($patch_op);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ConfigurazioniApi->aggiornaConfigurazioni: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **patch_op** | [**\GovPay\Backoffice\Model\PatchOp[]**](../Model/PatchOp.md)|  | |

### Return type

[**\GovPay\Backoffice\Model\Configurazione**](../Model/Configurazione.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `application/json-patch+json`
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getConfigurazioni()`

```php
getConfigurazioni(): \GovPay\Backoffice\Model\Configurazione
```

Legge la configurazione generale del sistema

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\ConfigurazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->getConfigurazioni();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ConfigurazioniApi->getConfigurazioni: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GovPay\Backoffice\Model\Configurazione**](../Model/Configurazione.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
