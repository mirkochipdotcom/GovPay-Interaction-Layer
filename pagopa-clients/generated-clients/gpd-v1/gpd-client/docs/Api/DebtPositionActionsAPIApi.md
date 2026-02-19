# PagoPA\GPD\DebtPositionActionsAPIApi



All URIs are relative to https://api.platform.pagopa.it/gpd/debt-positions-service/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**invalidatePosition()**](DebtPositionActionsAPIApi.md#invalidatePosition) | **POST** /organizations/{organizationfiscalcode}/debtpositions/{iupd}/invalidate | The Organization invalidate a debt Position. |
| [**publishPosition()**](DebtPositionActionsAPIApi.md#publishPosition) | **POST** /organizations/{organizationfiscalcode}/debtpositions/{iupd}/publish | The Organization publish a debt Position. |


## `invalidatePosition()`

```php
invalidatePosition($organizationfiscalcode, $iupd, $x_request_id): \PagoPA\GPD\Model\PaymentPositionModel
```

The Organization invalidate a debt Position.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: ApiKey
$config = PagoPA\GPD\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\GPD\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\GPD\Api\DebtPositionActionsAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$organizationfiscalcode = 'organizationfiscalcode_example'; // string | Organization fiscal code, the fiscal code of the Organization.
$iupd = 'iupd_example'; // string | IUPD (Unique identifier of the debt position). Format could be `<Organization fiscal code + UUID>` this would make it unique within the new PD management system. It's the responsibility of the EC to guarantee uniqueness. The pagoPa system shall verify that this is `true` and if not, notify the EC.
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.

try {
    $result = $apiInstance->invalidatePosition($organizationfiscalcode, $iupd, $x_request_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DebtPositionActionsAPIApi->invalidatePosition: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **organizationfiscalcode** | **string**| Organization fiscal code, the fiscal code of the Organization. | |
| **iupd** | **string**| IUPD (Unique identifier of the debt position). Format could be &#x60;&lt;Organization fiscal code + UUID&gt;&#x60; this would make it unique within the new PD management system. It&#39;s the responsibility of the EC to guarantee uniqueness. The pagoPa system shall verify that this is &#x60;true&#x60; and if not, notify the EC. | |
| **x_request_id** | **string**| This header identifies the call, if not passed it is self-generated. This ID is returned in the response. | [optional] |

### Return type

[**\PagoPA\GPD\Model\PaymentPositionModel**](../Model/PaymentPositionModel.md)

### Authorization

[ApiKey](../../README.md#ApiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `publishPosition()`

```php
publishPosition($organizationfiscalcode, $iupd, $x_request_id): \PagoPA\GPD\Model\PaymentPositionModel
```

The Organization publish a debt Position.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: ApiKey
$config = PagoPA\GPD\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\GPD\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\GPD\Api\DebtPositionActionsAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$organizationfiscalcode = 'organizationfiscalcode_example'; // string | Organization fiscal code, the fiscal code of the Organization.
$iupd = 'iupd_example'; // string | IUPD (Unique identifier of the debt position). Format could be `<Organization fiscal code + UUID>` this would make it unique within the new PD management system. It's the responsibility of the EC to guarantee uniqueness. The pagoPa system shall verify that this is `true` and if not, notify the EC.
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.

try {
    $result = $apiInstance->publishPosition($organizationfiscalcode, $iupd, $x_request_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DebtPositionActionsAPIApi->publishPosition: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **organizationfiscalcode** | **string**| Organization fiscal code, the fiscal code of the Organization. | |
| **iupd** | **string**| IUPD (Unique identifier of the debt position). Format could be &#x60;&lt;Organization fiscal code + UUID&gt;&#x60; this would make it unique within the new PD management system. It&#39;s the responsibility of the EC to guarantee uniqueness. The pagoPa system shall verify that this is &#x60;true&#x60; and if not, notify the EC. | |
| **x_request_id** | **string**| This header identifies the call, if not passed it is self-generated. This ID is returned in the response. | [optional] |

### Return type

[**\PagoPA\GPD\Model\PaymentPositionModel**](../Model/PaymentPositionModel.md)

### Authorization

[ApiKey](../../README.md#ApiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
