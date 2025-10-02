# GovPay\Backoffice\EnumerazioniApi

All URIs are relative to http://localhost/govpay/backend/api/backoffice/rs/basic/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**findEnumerazioniServiziACL()**](EnumerazioniApi.md#findEnumerazioniServiziACL) | **GET** /enumerazioni/serviziACL | Lista delle tipologie di servizio delle ACL |
| [**findEnumerazioniVersioneConnettore()**](EnumerazioniApi.md#findEnumerazioniVersioneConnettore) | **GET** /enumerazioni/versioneConnettore | Lista delle versioni supportate per il connettore |


## `findEnumerazioniServiziACL()`

```php
findEnumerazioniServiziACL(): string[]
```

Lista delle tipologie di servizio delle ACL

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EnumerazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->findEnumerazioniServiziACL();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EnumerazioniApi->findEnumerazioniServiziACL: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

**string[]**

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findEnumerazioniVersioneConnettore()`

```php
findEnumerazioniVersioneConnettore(): string[]
```

Lista delle versioni supportate per il connettore

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EnumerazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->findEnumerazioniVersioneConnettore();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EnumerazioniApi->findEnumerazioniVersioneConnettore: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

**string[]**

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
