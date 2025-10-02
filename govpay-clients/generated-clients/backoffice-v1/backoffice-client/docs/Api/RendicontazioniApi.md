# GovPay\Backoffice\RendicontazioniApi

All URIs are relative to http://localhost/govpay/backend/api/backoffice/rs/basic/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**findFlussiRendicontazione()**](RendicontazioniApi.md#findFlussiRendicontazione) | **GET** /flussiRendicontazione | Elenco dei flussi di rendicontazione acquisitie da PagoPa |
| [**findRendicontazioni()**](RendicontazioniApi.md#findRendicontazioni) | **GET** /rendicontazioni | Ricerche sulle rendicontazioni |
| [**getFlussoRendicontazione()**](RendicontazioniApi.md#getFlussoRendicontazione) | **GET** /flussiRendicontazione/{idFlusso} | Acquisizione di un flusso di rendicontazione |
| [**getFlussoRendicontazioneByDominioIdEData()**](RendicontazioniApi.md#getFlussoRendicontazioneByDominioIdEData) | **GET** /flussiRendicontazione/{idDominio}/{idFlusso}/{dataOraFlusso} | Acquisizione di un flusso di rendicontazione |
| [**getFlussoRendicontazioneByIdEData()**](RendicontazioniApi.md#getFlussoRendicontazioneByIdEData) | **GET** /flussiRendicontazione/{idFlusso}/{dataOraFlusso} | Acquisizione di un flusso di rendicontazione |


## `findFlussiRendicontazione()`

```php
findFlussiRendicontazione($pagina, $risultati_per_pagina, $ordinamento, $data_da, $data_a, $id_dominio, $incassato, $id_flusso, $stato, $iuv, $metadati_paginazione, $max_risultati, $escludi_obsoleti): \GovPay\Backoffice\Model\FindFlussiRendicontazione200Response
```

Elenco dei flussi di rendicontazione acquisitie da PagoPa

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\RendicontazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$ordinamento = '+data'; // string | csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * data
$data_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inizio della finestra temporale di osservazione
$data_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Fine della finestra temporale di osservazione
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio beneficiario
$incassato = True; // bool | filtro sullo stato di incasso del flusso
$id_flusso = 'id_flusso_example'; // string | Identificativo flusso
$stato = new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\StatoFlussoRendicontazione(); // \GovPay\Backoffice\Model\StatoFlussoRendicontazione | Filtro sullo stato del flusso
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati
$escludi_obsoleti = false; // bool | Esclude dai risultati della ricerca i flussi di rendicontazione obsoleti

try {
    $result = $apiInstance->findFlussiRendicontazione($pagina, $risultati_per_pagina, $ordinamento, $data_da, $data_a, $id_dominio, $incassato, $id_flusso, $stato, $iuv, $metadati_paginazione, $max_risultati, $escludi_obsoleti);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RendicontazioniApi->findFlussiRendicontazione: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **ordinamento** | **string**| csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * data | [optional] [default to &#39;+data&#39;] |
| **data_da** | **\DateTime**| Inizio della finestra temporale di osservazione | [optional] |
| **data_a** | **\DateTime**| Fine della finestra temporale di osservazione | [optional] |
| **id_dominio** | **string**| Identificativo del dominio beneficiario | [optional] |
| **incassato** | **bool**| filtro sullo stato di incasso del flusso | [optional] |
| **id_flusso** | **string**| Identificativo flusso | [optional] |
| **stato** | [**\GovPay\Backoffice\Model\StatoFlussoRendicontazione**](../Model/.md)| Filtro sullo stato del flusso | [optional] |
| **iuv** | **string**| Identificativo univoco di versamento | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |
| **escludi_obsoleti** | **bool**| Esclude dai risultati della ricerca i flussi di rendicontazione obsoleti | [optional] [default to false] |

### Return type

[**\GovPay\Backoffice\Model\FindFlussiRendicontazione200Response**](../Model/FindFlussiRendicontazione200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findRendicontazioni()`

```php
findRendicontazioni($pagina, $risultati_per_pagina, $ordinamento, $campi, $flusso_rendicontazione_data_flusso_da, $flusso_rendicontazione_data_flusso_a, $data_da, $data_a, $id_dominio, $id_flusso, $iuv, $direzione, $divisione, $metadati_paginazione, $max_risultati, $escludi_obsoleti): \GovPay\Backoffice\Model\FindRendicontazioni200Response
```

Ricerche sulle rendicontazioni

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\RendicontazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$ordinamento = '+data'; // string | csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * data
$campi = 'campi_example'; // string | csv dei campi da includere nella risposta (default tutti)
$flusso_rendicontazione_data_flusso_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Filtro sulla data di acquisizione del flusso rendicontazioni
$flusso_rendicontazione_data_flusso_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Filtro sulla data di acquisizione del flusso rendicontazioni
$data_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inizio della finestra temporale di osservazione
$data_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Fine della finestra temporale di osservazione
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio beneficiario
$id_flusso = 'id_flusso_example'; // string | Identificativo flusso
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$direzione = array('direzione_example'); // string[] | Filtro per direzione
$divisione = array('divisione_example'); // string[] | Filtro per divisione
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati
$escludi_obsoleti = true; // bool | Esclude dai risultati della ricerca i flussi di rendicontazione obsoleti

try {
    $result = $apiInstance->findRendicontazioni($pagina, $risultati_per_pagina, $ordinamento, $campi, $flusso_rendicontazione_data_flusso_da, $flusso_rendicontazione_data_flusso_a, $data_da, $data_a, $id_dominio, $id_flusso, $iuv, $direzione, $divisione, $metadati_paginazione, $max_risultati, $escludi_obsoleti);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RendicontazioniApi->findRendicontazioni: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **ordinamento** | **string**| csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * data | [optional] [default to &#39;+data&#39;] |
| **campi** | **string**| csv dei campi da includere nella risposta (default tutti) | [optional] |
| **flusso_rendicontazione_data_flusso_da** | **\DateTime**| Filtro sulla data di acquisizione del flusso rendicontazioni | [optional] |
| **flusso_rendicontazione_data_flusso_a** | **\DateTime**| Filtro sulla data di acquisizione del flusso rendicontazioni | [optional] |
| **data_da** | **\DateTime**| Inizio della finestra temporale di osservazione | [optional] |
| **data_a** | **\DateTime**| Fine della finestra temporale di osservazione | [optional] |
| **id_dominio** | **string**| Identificativo del dominio beneficiario | [optional] |
| **id_flusso** | **string**| Identificativo flusso | [optional] |
| **iuv** | **string**| Identificativo univoco di versamento | [optional] |
| **direzione** | [**string[]**](../Model/string.md)| Filtro per direzione | [optional] |
| **divisione** | [**string[]**](../Model/string.md)| Filtro per divisione | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |
| **escludi_obsoleti** | **bool**| Esclude dai risultati della ricerca i flussi di rendicontazione obsoleti | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\FindRendicontazioni200Response**](../Model/FindRendicontazioni200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getFlussoRendicontazione()`

```php
getFlussoRendicontazione($id_flusso): string
```

Acquisizione di un flusso di rendicontazione

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\RendicontazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_flusso = 'id_flusso_example'; // string | Identificativo del flusso di rendicontazione

try {
    $result = $apiInstance->getFlussoRendicontazione($id_flusso);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RendicontazioniApi->getFlussoRendicontazione: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_flusso** | **string**| Identificativo del flusso di rendicontazione | |

### Return type

**string**

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/xml`, `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getFlussoRendicontazioneByDominioIdEData()`

```php
getFlussoRendicontazioneByDominioIdEData($id_dominio, $id_flusso, $data_ora_flusso): string
```

Acquisizione di un flusso di rendicontazione

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\RendicontazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo dominio
$id_flusso = 'id_flusso_example'; // string | Identificativo del flusso di rendicontazione
$data_ora_flusso = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Data di emissione del flusso

try {
    $result = $apiInstance->getFlussoRendicontazioneByDominioIdEData($id_dominio, $id_flusso, $data_ora_flusso);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RendicontazioniApi->getFlussoRendicontazioneByDominioIdEData: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo dominio | |
| **id_flusso** | **string**| Identificativo del flusso di rendicontazione | |
| **data_ora_flusso** | **\DateTime**| Data di emissione del flusso | |

### Return type

**string**

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/xml`, `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getFlussoRendicontazioneByIdEData()`

```php
getFlussoRendicontazioneByIdEData($id_flusso, $data_ora_flusso): string
```

Acquisizione di un flusso di rendicontazione

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\RendicontazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_flusso = 'id_flusso_example'; // string | Identificativo del flusso di rendicontazione
$data_ora_flusso = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Data di emissione del flusso

try {
    $result = $apiInstance->getFlussoRendicontazioneByIdEData($id_flusso, $data_ora_flusso);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RendicontazioniApi->getFlussoRendicontazioneByIdEData: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_flusso** | **string**| Identificativo del flusso di rendicontazione | |
| **data_ora_flusso** | **\DateTime**| Data di emissione del flusso | |

### Return type

**string**

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/xml`, `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
