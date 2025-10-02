# GovPay\Ragioneria\PendenzeApi

All URIs are relative to http://localhost/govpay/backend/api/ragioneria/rs/basic/v3, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**getAllegatoPendenza()**](PendenzeApi.md#getAllegatoPendenza) | **GET** /allegati/{id} | Allegato di una pendenza |
| [**getPendenzaByAvviso()**](PendenzeApi.md#getPendenzaByAvviso) | **GET** /pendenze/byAvviso/{idDominio}/{numeroAvviso} | Dettaglio di una pendenza per riferimento avviso |


## `getAllegatoPendenza()`

```php
getAllegatoPendenza($id): \SplFileObject
```

Allegato di una pendenza

Fornisce l'allegato di una pendenza

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Ragioneria\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Ragioneria\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Identificativo dell'allegato

try {
    $result = $apiInstance->getAllegatoPendenza($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->getAllegatoPendenza: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Identificativo dell&#39;allegato | |

### Return type

**\SplFileObject**

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `*/*`, `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getPendenzaByAvviso()`

```php
getPendenzaByAvviso($id_dominio, $numero_avviso): \GovPay\Ragioneria\Model\PendenzaPagata
```

Dettaglio di una pendenza per riferimento avviso

Acquisisce il dettaglio di una pendenza, comprensivo dei dati di pagamento.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Ragioneria\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Ragioneria\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio dell'ente
$numero_avviso = 'numero_avviso_example'; // string | Identificativo dell'avviso di pagamento

try {
    $result = $apiInstance->getPendenzaByAvviso($id_dominio, $numero_avviso);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->getPendenzaByAvviso: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo del dominio dell&#39;ente | |
| **numero_avviso** | **string**| Identificativo dell&#39;avviso di pagamento | |

### Return type

[**\GovPay\Ragioneria\Model\PendenzaPagata**](../Model/PendenzaPagata.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
