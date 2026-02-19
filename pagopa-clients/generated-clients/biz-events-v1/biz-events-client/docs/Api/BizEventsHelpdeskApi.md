# PagoPA\BizEvents\BizEventsHelpdeskApi



All URIs are relative to http://localhost:8080, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**getBizEvent()**](BizEventsHelpdeskApi.md#getBizEvent) | **GET** /events/{biz-event-id} | Retrieve the biz-event given its id. |
| [**getBizEventByOrganizationFiscalCodeAndIuv()**](BizEventsHelpdeskApi.md#getBizEventByOrganizationFiscalCodeAndIuv) | **GET** /events/organizations/{organization-fiscal-code}/iuvs/{iuv} | Retrieve the biz-event given the organization fiscal code and IUV. |


## `getBizEvent()`

```php
getBizEvent($biz_event_id, $x_request_id): \PagoPA\BizEvents\Model\BizEvent
```

Retrieve the biz-event given its id.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: ApiKey
$config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\BizEvents\Api\BizEventsHelpdeskApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$biz_event_id = 'biz_event_id_example'; // string | The id of the biz-event.
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.

try {
    $result = $apiInstance->getBizEvent($biz_event_id, $x_request_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling BizEventsHelpdeskApi->getBizEvent: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **biz_event_id** | **string**| The id of the biz-event. | |
| **x_request_id** | **string**| This header identifies the call, if not passed it is self-generated. This ID is returned in the response. | [optional] |

### Return type

[**\PagoPA\BizEvents\Model\BizEvent**](../Model/BizEvent.md)

### Authorization

[ApiKey](../../README.md#ApiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getBizEventByOrganizationFiscalCodeAndIuv()`

```php
getBizEventByOrganizationFiscalCodeAndIuv($organization_fiscal_code, $iuv, $x_request_id): \PagoPA\BizEvents\Model\BizEvent
```

Retrieve the biz-event given the organization fiscal code and IUV.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: ApiKey
$config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\BizEvents\Api\BizEventsHelpdeskApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$organization_fiscal_code = 'organization_fiscal_code_example'; // string | The fiscal code of the Organization.
$iuv = 'iuv_example'; // string | The unique payment identification. Alphanumeric code that uniquely associates and identifies three key elements of a payment: reason, payer, amount
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.

try {
    $result = $apiInstance->getBizEventByOrganizationFiscalCodeAndIuv($organization_fiscal_code, $iuv, $x_request_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling BizEventsHelpdeskApi->getBizEventByOrganizationFiscalCodeAndIuv: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **organization_fiscal_code** | **string**| The fiscal code of the Organization. | |
| **iuv** | **string**| The unique payment identification. Alphanumeric code that uniquely associates and identifies three key elements of a payment: reason, payer, amount | |
| **x_request_id** | **string**| This header identifies the call, if not passed it is self-generated. This ID is returned in the response. | [optional] |

### Return type

[**\PagoPA\BizEvents\Model\BizEvent**](../Model/BizEvent.md)

### Authorization

[ApiKey](../../README.md#ApiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
