# GovPay\Pagamenti\RicevuteApi

All URIs are relative to http://localhost/govpay/frontend/api/pagamento/rs/basic/v3, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**findRicevute()**](RicevuteApi.md#findRicevute) | **GET** /ricevute/{idDominio}/{iuv} | Ricerca delle ricevute di pagamento per identificativo transazione |
| [**getRicevuta()**](RicevuteApi.md#getRicevuta) | **GET** /ricevute/{idDominio}/{iuv}/{idRicevuta} | Acquisizione di una ricevuta di avvenuto pagamento pagoPA |


## `findRicevute()`

```php
findRicevute($id_dominio, $iuv, $esito): \GovPay\Pagamenti\Model\Ricevute
```

Ricerca delle ricevute di pagamento per identificativo transazione

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Pagamenti\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Pagamenti\Api\RicevuteApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo dell'Ente Creditore in pagoPA. Corrisponde al codice fiscale.
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$esito = new \GovPay\Pagamenti\Model\\GovPay\Pagamenti\Model\EsitoRpp(); // \GovPay\Pagamenti\Model\EsitoRpp | Esito della transazione

try {
    $result = $apiInstance->findRicevute($id_dominio, $iuv, $esito);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RicevuteApi->findRicevute: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo dell&#39;Ente Creditore in pagoPA. Corrisponde al codice fiscale. | |
| **iuv** | **string**| Identificativo univoco di versamento | |
| **esito** | [**\GovPay\Pagamenti\Model\EsitoRpp**](../Model/.md)| Esito della transazione | [optional] |

### Return type

[**\GovPay\Pagamenti\Model\Ricevute**](../Model/Ricevute.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getRicevuta()`

```php
getRicevuta($id_dominio, $iuv, $id_ricevuta): \GovPay\Pagamenti\Model\Ricevuta
```

Acquisizione di una ricevuta di avvenuto pagamento pagoPA

Ricevuta pagoPA, sia questa veicolata nella forma di `RT` o di `recepit`, di esito positivo.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Pagamenti\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Pagamenti\Api\RicevuteApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo dell'Ente Creditore in pagoPA. Corrisponde al codice fiscale.
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$id_ricevuta = 'id_ricevuta_example'; // string | Identificativo della ricevuta di pagamento. Corrisponde al `receiptId` oppure al `ccp` a seconda del modello di pagamento utilizzato

try {
    $result = $apiInstance->getRicevuta($id_dominio, $iuv, $id_ricevuta);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RicevuteApi->getRicevuta: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo dell&#39;Ente Creditore in pagoPA. Corrisponde al codice fiscale. | |
| **iuv** | **string**| Identificativo univoco di versamento | |
| **id_ricevuta** | **string**| Identificativo della ricevuta di pagamento. Corrisponde al &#x60;receiptId&#x60; oppure al &#x60;ccp&#x60; a seconda del modello di pagamento utilizzato | |

### Return type

[**\GovPay\Pagamenti\Model\Ricevuta**](../Model/Ricevuta.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/pdf`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
