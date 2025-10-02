# GovPay\Ragioneria\RendicontazioniApi

All URIs are relative to http://localhost/govpay/backend/api/ragioneria/rs/basic/v3, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**findFlussiRendicontazione()**](RendicontazioniApi.md#findFlussiRendicontazione) | **GET** /flussiRendicontazione | Elenco dei flussi di rendicontazione acquisite da pagoPa |
| [**getFlussoRendicontazione()**](RendicontazioniApi.md#getFlussoRendicontazione) | **GET** /flussiRendicontazione/{idDominio}/{idFlusso} | Acquisizione di un flusso di rendicontazione |
| [**getFlussoRendicontazioneByIdEData()**](RendicontazioniApi.md#getFlussoRendicontazioneByIdEData) | **GET** /flussiRendicontazione/{idDominio}/{idFlusso}/{dataOraFlusso} | Acquisizione di un flusso di rendicontazione |


## `findFlussiRendicontazione()`

```php
findFlussiRendicontazione($pagina, $risultati_per_pagina, $ordinamento, $id_dominio, $data_da, $data_a, $stato, $metadati_paginazione, $max_risultati, $iuv, $id_flusso, $escludi_obsoleti): \GovPay\Ragioneria\Model\FlussiRendicontazione
```

Elenco dei flussi di rendicontazione acquisite da pagoPa

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Ragioneria\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Ragioneria\Api\RendicontazioniApi(
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
$stato = new \GovPay\Ragioneria\Model\\GovPay\Ragioneria\Model\StatoFlussoRendicontazione(); // \GovPay\Ragioneria\Model\StatoFlussoRendicontazione | Stato del flusso di rendicontazione
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati
$iuv = 'iuv_example'; // string | Identificativo del versamento
$id_flusso = 'id_flusso_example'; // string | Identificativo flusso
$escludi_obsoleti = false; // bool | Esclude dai risultati della ricerca i flussi di rendicontazione obsoleti

try {
    $result = $apiInstance->findFlussiRendicontazione($pagina, $risultati_per_pagina, $ordinamento, $id_dominio, $data_da, $data_a, $stato, $metadati_paginazione, $max_risultati, $iuv, $id_flusso, $escludi_obsoleti);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RendicontazioniApi->findFlussiRendicontazione: ', $e->getMessage(), PHP_EOL;
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
| **stato** | [**\GovPay\Ragioneria\Model\StatoFlussoRendicontazione**](../Model/.md)| Stato del flusso di rendicontazione | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |
| **iuv** | **string**| Identificativo del versamento | [optional] |
| **id_flusso** | **string**| Identificativo flusso | [optional] |
| **escludi_obsoleti** | **bool**| Esclude dai risultati della ricerca i flussi di rendicontazione obsoleti | [optional] [default to false] |

### Return type

[**\GovPay\Ragioneria\Model\FlussiRendicontazione**](../Model/FlussiRendicontazione.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getFlussoRendicontazione()`

```php
getFlussoRendicontazione($id_dominio, $id_flusso): string
```

Acquisizione di un flusso di rendicontazione

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Ragioneria\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Ragioneria\Api\RendicontazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo del titolare del flusso
$id_flusso = 'id_flusso_example'; // string | Identificativo del flusso di rendicontazione

try {
    $result = $apiInstance->getFlussoRendicontazione($id_dominio, $id_flusso);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RendicontazioniApi->getFlussoRendicontazione: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo del titolare del flusso | |
| **id_flusso** | **string**| Identificativo del flusso di rendicontazione | |

### Return type

**string**

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/xml`, `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getFlussoRendicontazioneByIdEData()`

```php
getFlussoRendicontazioneByIdEData($id_dominio, $id_flusso, $data_ora_flusso): string
```

Acquisizione di un flusso di rendicontazione

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Ragioneria\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Ragioneria\Api\RendicontazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo del titolare del flusso
$id_flusso = 'id_flusso_example'; // string | Identificativo del flusso di rendicontazione
$data_ora_flusso = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Data di emissione del flusso

try {
    $result = $apiInstance->getFlussoRendicontazioneByIdEData($id_dominio, $id_flusso, $data_ora_flusso);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RendicontazioniApi->getFlussoRendicontazioneByIdEData: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo del titolare del flusso | |
| **id_flusso** | **string**| Identificativo del flusso di rendicontazione | |
| **data_ora_flusso** | **\DateTime**| Data di emissione del flusso | |

### Return type

**string**

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/xml`, `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
