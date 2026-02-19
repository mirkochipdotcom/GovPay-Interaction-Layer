# PagoPA\CheckoutEc\DefaultApi



All URIs are relative to https://api.platform.pagopa.it/checkout/ec/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**postCarts()**](DefaultApi.md#postCarts) | **POST** /carts | PostCarts |


## `postCarts()`

```php
postCarts($cart_request)
```

PostCarts

create a cart

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: apiKeyQuery
$config = PagoPA\CheckoutEc\Configuration::getDefaultConfiguration()->setApiKey('subscription-key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\CheckoutEc\Configuration::getDefaultConfiguration()->setApiKeyPrefix('subscription-key', 'Bearer');

// Configure API key authorization: apiKeyHeader
$config = PagoPA\CheckoutEc\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\CheckoutEc\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\CheckoutEc\Api\DefaultApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$cart_request = {"emailNotice":"my_email@mail.it","paymentNotices":[{"noticeNumber":"302012387654312384","fiscalCode":"77777777777","amount":1000,"companyName":"Università degli Studi di Roma La Sapienza","description":"Pagamento test PostePay"},{"noticeNumber":"302012387654312385","fiscalCode":"77777777777","amount":2000,"companyName":"Università degli Studi di Roma La Sapienza","description":"Pagamento test PostePay"}],"returnUrls":{"returnOkUrl":"www.comune.di.prova.it/pagopa/success.html","returnCancelUrl":"www.comune.di.prova.it/pagopa/cancel.html","returnErrorUrl":"www.comune.di.prova.it/pagopa/error.html"},"idCart":"id_cart","allCCP":"false"}; // \PagoPA\CheckoutEc\Model\CartRequest | New Cart related to payment requests

try {
    $apiInstance->postCarts($cart_request);
} catch (Exception $e) {
    echo 'Exception when calling DefaultApi->postCarts: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **cart_request** | [**\PagoPA\CheckoutEc\Model\CartRequest**](../Model/CartRequest.md)| New Cart related to payment requests | [optional] |

### Return type

void (empty response body)

### Authorization

[apiKeyQuery](../../README.md#apiKeyQuery), [apiKeyHeader](../../README.md#apiKeyHeader)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
