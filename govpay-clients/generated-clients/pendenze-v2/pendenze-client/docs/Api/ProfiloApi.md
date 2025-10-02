# GovPay\Pendenze\ProfiloApi

All URIs are relative to http://localhost/govpay/backend/api/pendenze/rs/basic/v2, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**getProfilo()**](ProfiloApi.md#getProfilo) | **GET** /profilo | Elenco delle acl associate all&#39;utenza chiamante |


## `getProfilo()`

```php
getProfilo(): \GovPay\Pendenze\Model\Profilo
```

Elenco delle acl associate all'utenza chiamante

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Pendenze\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Pendenze\Api\ProfiloApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->getProfilo();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ProfiloApi->getProfilo: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GovPay\Pendenze\Model\Profilo**](../Model/Profilo.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
