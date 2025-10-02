# GovPay\Backoffice\TracciatiApi

All URIs are relative to http://localhost/govpay/backend/api/backoffice/rs/basic/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**getTracciatoNotificaPagamenti()**](TracciatiApi.md#getTracciatoNotificaPagamenti) | **GET** /tracciatiNotificaPagamenti/{id} | Tracciato Notifica Pagamenti in formato zip |


## `getTracciatoNotificaPagamenti()`

```php
getTracciatoNotificaPagamenti($id, $sec_id): object
```

Tracciato Notifica Pagamenti in formato zip

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\TracciatiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | identificativo di un tracciato
$sec_id = 'sec_id_example'; // string | chiave di accesso al tracciato

try {
    $result = $apiInstance->getTracciatoNotificaPagamenti($id, $sec_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TracciatiApi->getTracciatoNotificaPagamenti: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| identificativo di un tracciato | |
| **sec_id** | **string**| chiave di accesso al tracciato | |

### Return type

**object**

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/zip`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
