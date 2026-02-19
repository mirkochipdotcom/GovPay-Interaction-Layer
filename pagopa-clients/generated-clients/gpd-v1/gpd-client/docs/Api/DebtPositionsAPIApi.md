# PagoPA\GPD\DebtPositionsAPIApi



All URIs are relative to https://api.platform.pagopa.it/gpd/debt-positions-service/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**createPosition()**](DebtPositionsAPIApi.md#createPosition) | **POST** /organizations/{organizationfiscalcode}/debtpositions | The Organization creates a debt Position. |
| [**deletePosition()**](DebtPositionsAPIApi.md#deletePosition) | **DELETE** /organizations/{organizationfiscalcode}/debtpositions/{iupd} | The Organization deletes a debt position |
| [**getOrganizationDebtPositionByIUPD()**](DebtPositionsAPIApi.md#getOrganizationDebtPositionByIUPD) | **GET** /organizations/{organizationfiscalcode}/debtpositions/{iupd} | Return the details of a specific debt position. |
| [**getOrganizationDebtPositions()**](DebtPositionsAPIApi.md#getOrganizationDebtPositions) | **GET** /organizations/{organizationfiscalcode}/debtpositions | Return the list of the organization debt positions. The due dates interval is mutually exclusive with the payment dates interval. |
| [**updatePosition()**](DebtPositionsAPIApi.md#updatePosition) | **PUT** /organizations/{organizationfiscalcode}/debtpositions/{iupd} | The Organization updates a debt position |
| [**updateTransferIbanMassive()**](DebtPositionsAPIApi.md#updateTransferIbanMassive) | **PATCH** /organizations/{organizationfiscalcode}/debtpositions/transfers | The Organization updates the IBANs of every updatable payment option&#39;s transfers |


## `createPosition()`

```php
createPosition($organizationfiscalcode, $payment_position_model, $x_request_id, $to_publish): \PagoPA\GPD\Model\PaymentPositionModel
```

The Organization creates a debt Position.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: ApiKey
$config = PagoPA\GPD\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\GPD\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\GPD\Api\DebtPositionsAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$organizationfiscalcode = 'organizationfiscalcode_example'; // string | Organization fiscal code, the fiscal code of the Organization.
$payment_position_model = new \PagoPA\GPD\Model\PaymentPositionModel(); // \PagoPA\GPD\Model\PaymentPositionModel
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.
$to_publish = false; // bool

try {
    $result = $apiInstance->createPosition($organizationfiscalcode, $payment_position_model, $x_request_id, $to_publish);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DebtPositionsAPIApi->createPosition: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **organizationfiscalcode** | **string**| Organization fiscal code, the fiscal code of the Organization. | |
| **payment_position_model** | [**\PagoPA\GPD\Model\PaymentPositionModel**](../Model/PaymentPositionModel.md)|  | |
| **x_request_id** | **string**| This header identifies the call, if not passed it is self-generated. This ID is returned in the response. | [optional] |
| **to_publish** | **bool**|  | [optional] [default to false] |

### Return type

[**\PagoPA\GPD\Model\PaymentPositionModel**](../Model/PaymentPositionModel.md)

### Authorization

[ApiKey](../../README.md#ApiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `deletePosition()`

```php
deletePosition($organizationfiscalcode, $iupd, $x_request_id): string
```

The Organization deletes a debt position

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: ApiKey
$config = PagoPA\GPD\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\GPD\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\GPD\Api\DebtPositionsAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$organizationfiscalcode = 'organizationfiscalcode_example'; // string | Organization fiscal code, the fiscal code of the Organization.
$iupd = 'iupd_example'; // string | IUPD (Unique identifier of the debt position). Format could be `<Organization fiscal code + UUID>` this would make it unique within the new PD management system. It's the responsibility of the EC to guarantee uniqueness. The pagoPa system shall verify that this is `true` and if not, notify the EC.
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.

try {
    $result = $apiInstance->deletePosition($organizationfiscalcode, $iupd, $x_request_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DebtPositionsAPIApi->deletePosition: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **organizationfiscalcode** | **string**| Organization fiscal code, the fiscal code of the Organization. | |
| **iupd** | **string**| IUPD (Unique identifier of the debt position). Format could be &#x60;&lt;Organization fiscal code + UUID&gt;&#x60; this would make it unique within the new PD management system. It&#39;s the responsibility of the EC to guarantee uniqueness. The pagoPa system shall verify that this is &#x60;true&#x60; and if not, notify the EC. | |
| **x_request_id** | **string**| This header identifies the call, if not passed it is self-generated. This ID is returned in the response. | [optional] |

### Return type

**string**

### Authorization

[ApiKey](../../README.md#ApiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getOrganizationDebtPositionByIUPD()`

```php
getOrganizationDebtPositionByIUPD($organizationfiscalcode, $iupd, $x_request_id): \PagoPA\GPD\Model\PaymentPositionModelBaseResponse
```

Return the details of a specific debt position.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: ApiKey
$config = PagoPA\GPD\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\GPD\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\GPD\Api\DebtPositionsAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$organizationfiscalcode = 'organizationfiscalcode_example'; // string | Organization fiscal code, the fiscal code of the Organization.
$iupd = 'iupd_example'; // string | IUPD (Unique identifier of the debt position). Format could be `<Organization fiscal code + UUID>` this would make it unique within the new PD management system. It's the responsibility of the EC to guarantee uniqueness. The pagoPa system shall verify that this is `true` and if not, notify the EC.
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.

try {
    $result = $apiInstance->getOrganizationDebtPositionByIUPD($organizationfiscalcode, $iupd, $x_request_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DebtPositionsAPIApi->getOrganizationDebtPositionByIUPD: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **organizationfiscalcode** | **string**| Organization fiscal code, the fiscal code of the Organization. | |
| **iupd** | **string**| IUPD (Unique identifier of the debt position). Format could be &#x60;&lt;Organization fiscal code + UUID&gt;&#x60; this would make it unique within the new PD management system. It&#39;s the responsibility of the EC to guarantee uniqueness. The pagoPa system shall verify that this is &#x60;true&#x60; and if not, notify the EC. | |
| **x_request_id** | **string**| This header identifies the call, if not passed it is self-generated. This ID is returned in the response. | [optional] |

### Return type

[**\PagoPA\GPD\Model\PaymentPositionModelBaseResponse**](../Model/PaymentPositionModelBaseResponse.md)

### Authorization

[ApiKey](../../README.md#ApiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getOrganizationDebtPositions()`

```php
getOrganizationDebtPositions($organizationfiscalcode, $x_request_id, $limit, $page, $due_date_from, $due_date_to, $payment_date_from, $payment_date_to, $status, $orderby, $ordering): \PagoPA\GPD\Model\PaymentPositionsInfo
```

Return the list of the organization debt positions. The due dates interval is mutually exclusive with the payment dates interval.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: ApiKey
$config = PagoPA\GPD\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\GPD\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\GPD\Api\DebtPositionsAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$organizationfiscalcode = 'organizationfiscalcode_example'; // string | Organization fiscal code, the fiscal code of the Organization.
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.
$limit = 10; // int | Number of elements on one page. Default = 50
$page = 0; // int | Page number. Page value starts from 0
$due_date_from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Filter from due_date (if provided use the format yyyy-MM-dd). If not provided will be set to 30 days before the due_date_to.
$due_date_to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Filter to due_date (if provided use the format yyyy-MM-dd). If not provided will be set to 30 days after the due_date_from.
$payment_date_from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Filter from payment_date (if provided use the format yyyy-MM-dd). If not provided will be set to 30 days before the payment_date_to.
$payment_date_to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Filter to payment_date (if provided use the format yyyy-MM-dd). If not provided will be set to 30 days after the payment_date_from
$status = 'status_example'; // string | Filter by debt position status
$orderby = 'INSERTED_DATE'; // string | Order by INSERTED_DATE, COMPANY_NAME, IUPD or STATUS
$ordering = 'DESC'; // string | Direction of ordering

try {
    $result = $apiInstance->getOrganizationDebtPositions($organizationfiscalcode, $x_request_id, $limit, $page, $due_date_from, $due_date_to, $payment_date_from, $payment_date_to, $status, $orderby, $ordering);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DebtPositionsAPIApi->getOrganizationDebtPositions: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **organizationfiscalcode** | **string**| Organization fiscal code, the fiscal code of the Organization. | |
| **x_request_id** | **string**| This header identifies the call, if not passed it is self-generated. This ID is returned in the response. | [optional] |
| **limit** | **int**| Number of elements on one page. Default &#x3D; 50 | [optional] [default to 10] |
| **page** | **int**| Page number. Page value starts from 0 | [optional] [default to 0] |
| **due_date_from** | **\DateTime**| Filter from due_date (if provided use the format yyyy-MM-dd). If not provided will be set to 30 days before the due_date_to. | [optional] |
| **due_date_to** | **\DateTime**| Filter to due_date (if provided use the format yyyy-MM-dd). If not provided will be set to 30 days after the due_date_from. | [optional] |
| **payment_date_from** | **\DateTime**| Filter from payment_date (if provided use the format yyyy-MM-dd). If not provided will be set to 30 days before the payment_date_to. | [optional] |
| **payment_date_to** | **\DateTime**| Filter to payment_date (if provided use the format yyyy-MM-dd). If not provided will be set to 30 days after the payment_date_from | [optional] |
| **status** | **string**| Filter by debt position status | [optional] |
| **orderby** | **string**| Order by INSERTED_DATE, COMPANY_NAME, IUPD or STATUS | [optional] [default to &#39;INSERTED_DATE&#39;] |
| **ordering** | **string**| Direction of ordering | [optional] [default to &#39;DESC&#39;] |

### Return type

[**\PagoPA\GPD\Model\PaymentPositionsInfo**](../Model/PaymentPositionsInfo.md)

### Authorization

[ApiKey](../../README.md#ApiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `updatePosition()`

```php
updatePosition($organizationfiscalcode, $iupd, $payment_position_model, $x_request_id, $to_publish): \PagoPA\GPD\Model\PaymentPositionModel
```

The Organization updates a debt position

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: ApiKey
$config = PagoPA\GPD\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\GPD\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\GPD\Api\DebtPositionsAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$organizationfiscalcode = 'organizationfiscalcode_example'; // string | Organization fiscal code, the fiscal code of the Organization.
$iupd = 'iupd_example'; // string | IUPD (Unique identifier of the debt position). Format could be `<Organization fiscal code + UUID>` this would make it unique within the new PD management system. It's the responsibility of the EC to guarantee uniqueness. The pagoPa system shall verify that this is `true` and if not, notify the EC.
$payment_position_model = new \PagoPA\GPD\Model\PaymentPositionModel(); // \PagoPA\GPD\Model\PaymentPositionModel
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.
$to_publish = false; // bool

try {
    $result = $apiInstance->updatePosition($organizationfiscalcode, $iupd, $payment_position_model, $x_request_id, $to_publish);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DebtPositionsAPIApi->updatePosition: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **organizationfiscalcode** | **string**| Organization fiscal code, the fiscal code of the Organization. | |
| **iupd** | **string**| IUPD (Unique identifier of the debt position). Format could be &#x60;&lt;Organization fiscal code + UUID&gt;&#x60; this would make it unique within the new PD management system. It&#39;s the responsibility of the EC to guarantee uniqueness. The pagoPa system shall verify that this is &#x60;true&#x60; and if not, notify the EC. | |
| **payment_position_model** | [**\PagoPA\GPD\Model\PaymentPositionModel**](../Model/PaymentPositionModel.md)|  | |
| **x_request_id** | **string**| This header identifies the call, if not passed it is self-generated. This ID is returned in the response. | [optional] |
| **to_publish** | **bool**|  | [optional] [default to false] |

### Return type

[**\PagoPA\GPD\Model\PaymentPositionModel**](../Model/PaymentPositionModel.md)

### Authorization

[ApiKey](../../README.md#ApiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `updateTransferIbanMassive()`

```php
updateTransferIbanMassive($organizationfiscalcode, $old_iban, $update_transfer_iban_massive_model, $x_request_id, $limit): \PagoPA\GPD\Model\UpdateTransferIbanMassiveResponse
```

The Organization updates the IBANs of every updatable payment option's transfers

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: ApiKey
$config = PagoPA\GPD\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\GPD\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\GPD\Api\DebtPositionsAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$organizationfiscalcode = 'organizationfiscalcode_example'; // string | Organization fiscal code, the fiscal code of the Organization.
$old_iban = 'old_iban_example'; // string | The old iban to replace
$update_transfer_iban_massive_model = new \PagoPA\GPD\Model\UpdateTransferIbanMassiveModel(); // \PagoPA\GPD\Model\UpdateTransferIbanMassiveModel
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.
$limit = 1000; // int | Number of Transfer to update (max = 1000, default = 1000)

try {
    $result = $apiInstance->updateTransferIbanMassive($organizationfiscalcode, $old_iban, $update_transfer_iban_massive_model, $x_request_id, $limit);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DebtPositionsAPIApi->updateTransferIbanMassive: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **organizationfiscalcode** | **string**| Organization fiscal code, the fiscal code of the Organization. | |
| **old_iban** | **string**| The old iban to replace | |
| **update_transfer_iban_massive_model** | [**\PagoPA\GPD\Model\UpdateTransferIbanMassiveModel**](../Model/UpdateTransferIbanMassiveModel.md)|  | |
| **x_request_id** | **string**| This header identifies the call, if not passed it is self-generated. This ID is returned in the response. | [optional] |
| **limit** | **int**| Number of Transfer to update (max &#x3D; 1000, default &#x3D; 1000) | [optional] [default to 1000] |

### Return type

[**\PagoPA\GPD\Model\UpdateTransferIbanMassiveResponse**](../Model/UpdateTransferIbanMassiveResponse.md)

### Authorization

[ApiKey](../../README.md#ApiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
