# PagoPA\BizEvents\PaymentReceiptsRESTAPIsApi



All URIs are relative to http://localhost:8080, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**getOrganizationReceiptIur()**](PaymentReceiptsRESTAPIsApi.md#getOrganizationReceiptIur) | **GET** /organizations/{organizationfiscalcode}/receipts/{iur} | The organization get the receipt for the creditor institution using IUR. |
| [**getOrganizationReceiptIuvIur()**](PaymentReceiptsRESTAPIsApi.md#getOrganizationReceiptIuvIur) | **GET** /organizations/{organizationfiscalcode}/receipts/{iur}/paymentoptions/{iuv} | The organization get the receipt for the creditor institution using IUV and IUR. |


## `getOrganizationReceiptIur()`

```php
getOrganizationReceiptIur($organizationfiscalcode, $iur, $x_request_id): \PagoPA\BizEvents\Model\CtReceiptModelResponse
```

The organization get the receipt for the creditor institution using IUR.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: ApiKey
$config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\BizEvents\Api\PaymentReceiptsRESTAPIsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$organizationfiscalcode = 'organizationfiscalcode_example'; // string | The fiscal code of the Organization.
$iur = 'iur_example'; // string | The unique reference of the operation assigned to the payment (Payment Token).
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.

try {
    $result = $apiInstance->getOrganizationReceiptIur($organizationfiscalcode, $iur, $x_request_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PaymentReceiptsRESTAPIsApi->getOrganizationReceiptIur: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **organizationfiscalcode** | **string**| The fiscal code of the Organization. | |
| **iur** | **string**| The unique reference of the operation assigned to the payment (Payment Token). | |
| **x_request_id** | **string**| This header identifies the call, if not passed it is self-generated. This ID is returned in the response. | [optional] |

### Return type

[**\PagoPA\BizEvents\Model\CtReceiptModelResponse**](../Model/CtReceiptModelResponse.md)

### Authorization

[ApiKey](../../README.md#ApiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getOrganizationReceiptIuvIur()`

```php
getOrganizationReceiptIuvIur($organizationfiscalcode, $iur, $iuv, $x_request_id): \PagoPA\BizEvents\Model\CtReceiptModelResponse
```

The organization get the receipt for the creditor institution using IUV and IUR.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: ApiKey
$config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\BizEvents\Api\PaymentReceiptsRESTAPIsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$organizationfiscalcode = 'organizationfiscalcode_example'; // string | The fiscal code of the Organization.
$iur = 'iur_example'; // string | The unique reference of the operation assigned to the payment (Payment Token).
$iuv = 'iuv_example'; // string | The unique payment identification. Alphanumeric code that uniquely associates and identifies three key elements of a payment: reason, payer, amount
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.

try {
    $result = $apiInstance->getOrganizationReceiptIuvIur($organizationfiscalcode, $iur, $iuv, $x_request_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PaymentReceiptsRESTAPIsApi->getOrganizationReceiptIuvIur: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **organizationfiscalcode** | **string**| The fiscal code of the Organization. | |
| **iur** | **string**| The unique reference of the operation assigned to the payment (Payment Token). | |
| **iuv** | **string**| The unique payment identification. Alphanumeric code that uniquely associates and identifies three key elements of a payment: reason, payer, amount | |
| **x_request_id** | **string**| This header identifies the call, if not passed it is self-generated. This ID is returned in the response. | [optional] |

### Return type

[**\PagoPA\BizEvents\Model\CtReceiptModelResponse**](../Model/CtReceiptModelResponse.md)

### Authorization

[ApiKey](../../README.md#ApiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
