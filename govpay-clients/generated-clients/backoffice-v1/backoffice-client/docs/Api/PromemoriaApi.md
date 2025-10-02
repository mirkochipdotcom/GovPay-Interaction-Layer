# GovPay\Backoffice\PromemoriaApi

All URIs are relative to http://localhost/govpay/backend/api/backoffice/rs/basic/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**findPromemoria()**](PromemoriaApi.md#findPromemoria) | **GET** /promemoria | Elenco dei promemoria gestiti dal sistema |


## `findPromemoria()`

```php
findPromemoria($pagina, $risultati_per_pagina, $data_da, $data_a, $stato, $tipo, $metadati_paginazione, $max_risultati): \GovPay\Backoffice\Model\FindPromemoria200Response
```

Elenco dei promemoria gestiti dal sistema

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PromemoriaApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$data_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inizio della finestra temporale di osservazione
$data_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Fine della finestra temporale di osservazione
$stato = new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\StatoPromemoria(); // \GovPay\Backoffice\Model\StatoPromemoria | filtro sullo stato del promemoria
$tipo = new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\TipoPromemoria(); // \GovPay\Backoffice\Model\TipoPromemoria | filtro sul tipo del promemoria
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati

try {
    $result = $apiInstance->findPromemoria($pagina, $risultati_per_pagina, $data_da, $data_a, $stato, $tipo, $metadati_paginazione, $max_risultati);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PromemoriaApi->findPromemoria: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **data_da** | **\DateTime**| Inizio della finestra temporale di osservazione | [optional] |
| **data_a** | **\DateTime**| Fine della finestra temporale di osservazione | [optional] |
| **stato** | [**\GovPay\Backoffice\Model\StatoPromemoria**](../Model/.md)| filtro sullo stato del promemoria | [optional] |
| **tipo** | [**\GovPay\Backoffice\Model\TipoPromemoria**](../Model/.md)| filtro sul tipo del promemoria | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\FindPromemoria200Response**](../Model/FindPromemoria200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
