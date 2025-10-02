# GovPay\Backoffice\NotificheApi

All URIs are relative to http://localhost/govpay/backend/api/backoffice/rs/basic/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**findNotifiche()**](NotificheApi.md#findNotifiche) | **GET** /notifiche | Elenco delle notifiche gestite dal sistema |


## `findNotifiche()`

```php
findNotifiche($pagina, $risultati_per_pagina, $data_da, $data_a, $stato, $tipo, $metadati_paginazione, $max_risultati): \GovPay\Backoffice\Model\FindNotifiche200Response
```

Elenco delle notifiche gestite dal sistema

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\NotificheApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$data_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inizio della finestra temporale di osservazione
$data_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Fine della finestra temporale di osservazione
$stato = new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\StatoNotifica(); // \GovPay\Backoffice\Model\StatoNotifica | filtro sullo stato della notifica
$tipo = new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\TipoNotifica(); // \GovPay\Backoffice\Model\TipoNotifica | filtro sul tipo della notifica
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati

try {
    $result = $apiInstance->findNotifiche($pagina, $risultati_per_pagina, $data_da, $data_a, $stato, $tipo, $metadati_paginazione, $max_risultati);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling NotificheApi->findNotifiche: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **data_da** | **\DateTime**| Inizio della finestra temporale di osservazione | [optional] |
| **data_a** | **\DateTime**| Fine della finestra temporale di osservazione | [optional] |
| **stato** | [**\GovPay\Backoffice\Model\StatoNotifica**](../Model/.md)| filtro sullo stato della notifica | [optional] |
| **tipo** | [**\GovPay\Backoffice\Model\TipoNotifica**](../Model/.md)| filtro sul tipo della notifica | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\FindNotifiche200Response**](../Model/FindNotifiche200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
