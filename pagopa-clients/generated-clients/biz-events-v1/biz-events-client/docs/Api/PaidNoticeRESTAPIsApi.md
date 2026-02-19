# PagoPA\BizEvents\PaidNoticeRESTAPIsApi



All URIs are relative to http://localhost:8080, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**disablePaidNotice()**](PaidNoticeRESTAPIsApi.md#disablePaidNotice) | **POST** /paids/{event-id}/disable | Disable the paid notice details given its id. |
| [**enablePaidNotice()**](PaidNoticeRESTAPIsApi.md#enablePaidNotice) | **POST** /paids/{event-id}/enable | Enable the paid notice details given its id. |
| [**generatePDF()**](PaidNoticeRESTAPIsApi.md#generatePDF) | **GET** /paids/{event-id}/pdf | Retrieve the PDF receipt given event id. |
| [**getPaidNoticeDetail()**](PaidNoticeRESTAPIsApi.md#getPaidNoticeDetail) | **GET** /paids/{event-id} | Retrieve the paid notice details given its id. |
| [**getPaidNotices()**](PaidNoticeRESTAPIsApi.md#getPaidNotices) | **GET** /paids | Retrieve the paged transaction list from biz events. |


## `disablePaidNotice()`

```php
disablePaidNotice($x_fiscal_code, $event_id, $x_request_id)
```

Disable the paid notice details given its id.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: ApiKey
$config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\BizEvents\Api\PaidNoticeRESTAPIsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$x_fiscal_code = 'x_fiscal_code_example'; // string
$event_id = 'event_id_example'; // string | The id of the paid event.
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.

try {
    $apiInstance->disablePaidNotice($x_fiscal_code, $event_id, $x_request_id);
} catch (Exception $e) {
    echo 'Exception when calling PaidNoticeRESTAPIsApi->disablePaidNotice: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **x_fiscal_code** | **string**|  | |
| **event_id** | **string**| The id of the paid event. | |
| **x_request_id** | **string**| This header identifies the call, if not passed it is self-generated. This ID is returned in the response. | [optional] |

### Return type

void (empty response body)

### Authorization

[ApiKey](../../README.md#ApiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `enablePaidNotice()`

```php
enablePaidNotice($x_fiscal_code, $event_id, $x_request_id)
```

Enable the paid notice details given its id.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: ApiKey
$config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\BizEvents\Api\PaidNoticeRESTAPIsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$x_fiscal_code = 'x_fiscal_code_example'; // string
$event_id = 'event_id_example'; // string | The id of the paid event.
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.

try {
    $apiInstance->enablePaidNotice($x_fiscal_code, $event_id, $x_request_id);
} catch (Exception $e) {
    echo 'Exception when calling PaidNoticeRESTAPIsApi->enablePaidNotice: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **x_fiscal_code** | **string**|  | |
| **event_id** | **string**| The id of the paid event. | |
| **x_request_id** | **string**| This header identifies the call, if not passed it is self-generated. This ID is returned in the response. | [optional] |

### Return type

void (empty response body)

### Authorization

[ApiKey](../../README.md#ApiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `generatePDF()`

```php
generatePDF($x_fiscal_code, $event_id, $x_request_id): \SplFileObject
```

Retrieve the PDF receipt given event id.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: ApiKey
$config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\BizEvents\Api\PaidNoticeRESTAPIsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$x_fiscal_code = 'x_fiscal_code_example'; // string
$event_id = 'event_id_example'; // string | The id of the paid event.
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.

try {
    $result = $apiInstance->generatePDF($x_fiscal_code, $event_id, $x_request_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PaidNoticeRESTAPIsApi->generatePDF: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **x_fiscal_code** | **string**|  | |
| **event_id** | **string**| The id of the paid event. | |
| **x_request_id** | **string**| This header identifies the call, if not passed it is self-generated. This ID is returned in the response. | [optional] |

### Return type

**\SplFileObject**

### Authorization

[ApiKey](../../README.md#ApiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/pdf`, `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getPaidNoticeDetail()`

```php
getPaidNoticeDetail($x_fiscal_code, $event_id, $x_request_id): \PagoPA\BizEvents\Model\NoticeDetailResponse
```

Retrieve the paid notice details given its id.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: ApiKey
$config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\BizEvents\Api\PaidNoticeRESTAPIsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$x_fiscal_code = 'x_fiscal_code_example'; // string
$event_id = 'event_id_example'; // string | The id of the paid event.
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.

try {
    $result = $apiInstance->getPaidNoticeDetail($x_fiscal_code, $event_id, $x_request_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PaidNoticeRESTAPIsApi->getPaidNoticeDetail: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **x_fiscal_code** | **string**|  | |
| **event_id** | **string**| The id of the paid event. | |
| **x_request_id** | **string**| This header identifies the call, if not passed it is self-generated. This ID is returned in the response. | [optional] |

### Return type

[**\PagoPA\BizEvents\Model\NoticeDetailResponse**](../Model/NoticeDetailResponse.md)

### Authorization

[ApiKey](../../README.md#ApiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getPaidNotices()`

```php
getPaidNotices($x_fiscal_code, $x_continuation_token, $size, $is_payer, $is_debtor, $hidden, $orderby, $ordering, $x_request_id): \PagoPA\BizEvents\Model\NoticeListWrapResponse
```

Retrieve the paged transaction list from biz events.

This operation is deprecated. Use Paid Notice APIs instead

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: ApiKey
$config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\BizEvents\Api\PaidNoticeRESTAPIsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$x_fiscal_code = 'x_fiscal_code_example'; // string
$x_continuation_token = 'x_continuation_token_example'; // string
$size = 10; // int
$is_payer = True; // bool | Filter by payer
$is_debtor = True; // bool | Filter by debtor
$hidden = false; // bool | Filter notices by hidden property
$orderby = 'TRANSACTION_DATE'; // string | Order by TRANSACTION_DATE
$ordering = 'DESC'; // string | Direction of ordering
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.

try {
    $result = $apiInstance->getPaidNotices($x_fiscal_code, $x_continuation_token, $size, $is_payer, $is_debtor, $hidden, $orderby, $ordering, $x_request_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PaidNoticeRESTAPIsApi->getPaidNotices: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **x_fiscal_code** | **string**|  | |
| **x_continuation_token** | **string**|  | [optional] |
| **size** | **int**|  | [optional] [default to 10] |
| **is_payer** | **bool**| Filter by payer | [optional] |
| **is_debtor** | **bool**| Filter by debtor | [optional] |
| **hidden** | **bool**| Filter notices by hidden property | [optional] [default to false] |
| **orderby** | **string**| Order by TRANSACTION_DATE | [optional] [default to &#39;TRANSACTION_DATE&#39;] |
| **ordering** | **string**| Direction of ordering | [optional] [default to &#39;DESC&#39;] |
| **x_request_id** | **string**| This header identifies the call, if not passed it is self-generated. This ID is returned in the response. | [optional] |

### Return type

[**\PagoPA\BizEvents\Model\NoticeListWrapResponse**](../Model/NoticeListWrapResponse.md)

### Authorization

[ApiKey](../../README.md#ApiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `*/*`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
