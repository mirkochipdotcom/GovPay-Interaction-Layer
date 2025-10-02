# GovPay\Backoffice\RicevuteApi

All URIs are relative to http://localhost/govpay/backend/api/backoffice/rs/basic/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**addRicevuta()**](RicevuteApi.md#addRicevuta) | **POST** /ricevute | Acquisizione di una RT in formato xml |


## `addRicevuta()`

```php
addRicevuta($body): \GovPay\Backoffice\Model\RppIndex
```

Acquisizione di una RT in formato xml

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\RicevuteApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$body = array('key' => new \stdClass); // object | RT in formato XML

try {
    $result = $apiInstance->addRicevuta($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RicevuteApi->addRicevuta: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **body** | **object**| RT in formato XML | |

### Return type

[**\GovPay\Backoffice\Model\RppIndex**](../Model/RppIndex.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `text/xml`, `multipart/form-data`
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
