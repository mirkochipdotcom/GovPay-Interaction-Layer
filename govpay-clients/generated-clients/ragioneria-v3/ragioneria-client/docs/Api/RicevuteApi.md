# GovPay\Ragioneria\RicevuteApi

All URIs are relative to http://localhost/govpay/backend/api/ragioneria/rs/basic/v3, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**findRicevute()**](RicevuteApi.md#findRicevute) | **GET** /ricevute | Ricerca delle ricevute di pagamento |
| [**getRicevuta()**](RicevuteApi.md#getRicevuta) | **GET** /ricevute/{idDominio}/{iuv}/{idRicevuta} | Acquisizione di una ricevuta di avvenuto pagamento pagoPA |


## `findRicevute()`

```php
findRicevute($pagina, $risultati_per_pagina, $ordinamento, $id_dominio, $data_da, $data_a, $metadati_paginazione, $max_risultati, $iuv, $id_ricevuta, $numero_avviso): \GovPay\Ragioneria\Model\Ricevute
```

Ricerca delle ricevute di pagamento

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Ragioneria\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Ragioneria\Api\RicevuteApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina di risultato
$risultati_per_pagina = 25; // int | How many items to return at one time
$ordinamento = 'ordinamento_example'; // string | Sorting order
$id_dominio = 'id_dominio_example'; // string | Identificativo dell'Ente Creditore in pagoPA. Corrisponde al codice fiscale.
$data_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inizio della finestra temporale di osservazione
$data_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Fine della finestra temporale di osservazione
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati
$iuv = 'iuv_example'; // string | Identificativo del versamento
$id_ricevuta = 'id_ricevuta_example'; // string | Identificativo della ricevuta
$numero_avviso = 'numero_avviso_example'; // string | Numero identificativo dell'avviso pagoPA

try {
    $result = $apiInstance->findRicevute($pagina, $risultati_per_pagina, $ordinamento, $id_dominio, $data_da, $data_a, $metadati_paginazione, $max_risultati, $iuv, $id_ricevuta, $numero_avviso);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RicevuteApi->findRicevute: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina di risultato | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| How many items to return at one time | [optional] [default to 25] |
| **ordinamento** | **string**| Sorting order | [optional] |
| **id_dominio** | **string**| Identificativo dell&#39;Ente Creditore in pagoPA. Corrisponde al codice fiscale. | [optional] |
| **data_da** | **\DateTime**| Inizio della finestra temporale di osservazione | [optional] |
| **data_a** | **\DateTime**| Fine della finestra temporale di osservazione | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |
| **iuv** | **string**| Identificativo del versamento | [optional] |
| **id_ricevuta** | **string**| Identificativo della ricevuta | [optional] |
| **numero_avviso** | **string**| Numero identificativo dell&#39;avviso pagoPA | [optional] |

### Return type

[**\GovPay\Ragioneria\Model\Ricevute**](../Model/Ricevute.md)

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
getRicevuta($id_dominio, $iuv, $id_ricevuta): \GovPay\Ragioneria\Model\Ricevuta
```

Acquisizione di una ricevuta di avvenuto pagamento pagoPA

Ricevuta pagoPA, sia questa veicolata nella forma di `RT` o di `recepit`, di esito positivo.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Ragioneria\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Ragioneria\Api\RicevuteApi(
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

[**\GovPay\Ragioneria\Model\Ricevuta**](../Model/Ricevuta.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/pdf`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
