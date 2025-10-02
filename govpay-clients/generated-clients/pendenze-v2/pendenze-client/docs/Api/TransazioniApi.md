# GovPay\Pendenze\TransazioniApi

All URIs are relative to http://localhost/govpay/backend/api/pendenze/rs/basic/v2, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**findRpp()**](TransazioniApi.md#findRpp) | **GET** /rpp | Lista delle richieste di pagamento pendenza |
| [**getRpp()**](TransazioniApi.md#getRpp) | **GET** /rpp/{idDominio}/{iuv}/{ccp} | Dettaglio di una richiesta di pagamento pendenza |
| [**getRpt()**](TransazioniApi.md#getRpt) | **GET** /rpp/{idDominio}/{iuv}/{ccp}/rpt | Acquisizione della richiesta di pagamento pagopa |
| [**getRt()**](TransazioniApi.md#getRt) | **GET** /rpp/{idDominio}/{iuv}/{ccp}/rt | Acquisizione della ricevuta di pagamento |


## `findRpp()`

```php
findRpp($pagina, $risultati_per_pagina, $campi, $ordinamento, $id_dominio, $iuv, $ccp, $id_a2_a, $id_pendenza, $id_debitore, $esito_pagamento, $id_pagamento, $data_rpt_da, $data_rpt_a, $data_rt_da, $data_rt_a, $metadati_paginazione, $max_risultati): \GovPay\Pendenze\Model\Rpps
```

Lista delle richieste di pagamento pendenza

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Pendenze\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Pendenze\Api\TransazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | The zero-ary offset index into the results
$risultati_per_pagina = 25; // int | How many items to return at one time (max 100)
$campi = 'campi_example'; // string | Fields to retrieve
$ordinamento = +name; // string | Sorting order
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio dell'ente
$iuv = 'iuv_example'; // string | Identificativo del versamento
$ccp = 'ccp_example'; // string | Codice contesto pagamento
$id_a2_a = 'id_a2_a_example'; // string | Identificativo del gestionale ente
$id_pendenza = 'id_pendenza_example'; // string | Identificativo della pendenza nel gestionale ente
$id_debitore = RSSMRA30A01H501I; // string | Identificativo del soggetto debitore della pendenza
$esito_pagamento = new \GovPay\Pendenze\Model\\GovPay\Pendenze\Model\EsitoRpp(); // \GovPay\Pendenze\Model\EsitoRpp | Filtro sullo stato del pendenza
$id_pagamento = c8be909b-2feb-4ffa-8f98-704462abbd1d; // string | Identificativo della richiesta di pagamento
$data_rpt_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Filtro sulla data di invio della richiesta di pagamento
$data_rpt_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Filtro sulla data di invio della richiesta di pagamento
$data_rt_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Filtro sulla data di ricezione della ricevuta di pagamento
$data_rt_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Filtro sulla data di ricezione della ricevuta di pagamento
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati

try {
    $result = $apiInstance->findRpp($pagina, $risultati_per_pagina, $campi, $ordinamento, $id_dominio, $iuv, $ccp, $id_a2_a, $id_pendenza, $id_debitore, $esito_pagamento, $id_pagamento, $data_rpt_da, $data_rpt_a, $data_rt_da, $data_rt_a, $metadati_paginazione, $max_risultati);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TransazioniApi->findRpp: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| The zero-ary offset index into the results | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| How many items to return at one time (max 100) | [optional] [default to 25] |
| **campi** | **string**| Fields to retrieve | [optional] |
| **ordinamento** | **string**| Sorting order | [optional] |
| **id_dominio** | **string**| Identificativo del dominio dell&#39;ente | [optional] |
| **iuv** | **string**| Identificativo del versamento | [optional] |
| **ccp** | **string**| Codice contesto pagamento | [optional] |
| **id_a2_a** | **string**| Identificativo del gestionale ente | [optional] |
| **id_pendenza** | **string**| Identificativo della pendenza nel gestionale ente | [optional] |
| **id_debitore** | **string**| Identificativo del soggetto debitore della pendenza | [optional] |
| **esito_pagamento** | [**\GovPay\Pendenze\Model\EsitoRpp**](../Model/.md)| Filtro sullo stato del pendenza | [optional] |
| **id_pagamento** | **string**| Identificativo della richiesta di pagamento | [optional] |
| **data_rpt_da** | **\DateTime**| Filtro sulla data di invio della richiesta di pagamento | [optional] |
| **data_rpt_a** | **\DateTime**| Filtro sulla data di invio della richiesta di pagamento | [optional] |
| **data_rt_da** | **\DateTime**| Filtro sulla data di ricezione della ricevuta di pagamento | [optional] |
| **data_rt_a** | **\DateTime**| Filtro sulla data di ricezione della ricevuta di pagamento | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |

### Return type

[**\GovPay\Pendenze\Model\Rpps**](../Model/Rpps.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getRpp()`

```php
getRpp($id_dominio, $iuv, $ccp): \GovPay\Pendenze\Model\Rpp
```

Dettaglio di una richiesta di pagamento pendenza

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Pendenze\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Pendenze\Api\TransazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Codice fiscale del dominio beneficiario
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$ccp = 'ccp_example'; // string | Codice di contesto pagamento

try {
    $result = $apiInstance->getRpp($id_dominio, $iuv, $ccp);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TransazioniApi->getRpp: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Codice fiscale del dominio beneficiario | |
| **iuv** | **string**| Identificativo univoco di versamento | |
| **ccp** | **string**| Codice di contesto pagamento | |

### Return type

[**\GovPay\Pendenze\Model\Rpp**](../Model/Rpp.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getRpt()`

```php
getRpt($id_dominio, $iuv, $ccp): object
```

Acquisizione della richiesta di pagamento pagopa

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Pendenze\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Pendenze\Api\TransazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Codice fiscale del dominio beneficiario
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$ccp = 'ccp_example'; // string | Codice di contesto pagamento

try {
    $result = $apiInstance->getRpt($id_dominio, $iuv, $ccp);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TransazioniApi->getRpt: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Codice fiscale del dominio beneficiario | |
| **iuv** | **string**| Identificativo univoco di versamento | |
| **ccp** | **string**| Codice di contesto pagamento | |

### Return type

**object**

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/xml`, `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getRt()`

```php
getRt($id_dominio, $iuv, $ccp): \SplFileObject
```

Acquisizione della ricevuta di pagamento

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Pendenze\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Pendenze\Api\TransazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Codice fiscale del dominio beneficiario
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$ccp = 'ccp_example'; // string | Codice di contesto pagamento

try {
    $result = $apiInstance->getRt($id_dominio, $iuv, $ccp);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TransazioniApi->getRt: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Codice fiscale del dominio beneficiario | |
| **iuv** | **string**| Identificativo univoco di versamento | |
| **ccp** | **string**| Codice di contesto pagamento | |

### Return type

**\SplFileObject**

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/pdf`, `application/xml`, `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
