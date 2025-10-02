# GovPay\Backoffice\PagamentiApi

All URIs are relative to http://localhost/govpay/backend/api/backoffice/rs/basic/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**findPagamenti()**](PagamentiApi.md#findPagamenti) | **GET** /pagamenti | Lista dei pagamenti |
| [**findRpps()**](PagamentiApi.md#findRpps) | **GET** /rpp | Lista delle richieste di pagamento pendenza |
| [**getPagamento()**](PagamentiApi.md#getPagamento) | **GET** /pagamenti/{id} | Dettaglio di un pagamento |
| [**getRpp()**](PagamentiApi.md#getRpp) | **GET** /rpp/{idDominio}/{iuv}/{ccp} | Dettaglio di una richiesta di pagamento pendenza |
| [**getRpt()**](PagamentiApi.md#getRpt) | **GET** /rpp/{idDominio}/{iuv}/{ccp}/rpt | Dettaglio della richiesta di pagamento pagopa |
| [**getRt()**](PagamentiApi.md#getRt) | **GET** /rpp/{idDominio}/{iuv}/{ccp}/rt | Dettaglio della ricevuta di pagamento |
| [**updatePagamento()**](PagamentiApi.md#updatePagamento) | **PATCH** /pagamenti/{id} | Aggiorna selettivamente campi di un pagamento |
| [**updateRpp()**](PagamentiApi.md#updateRpp) | **PATCH** /rpp/{idDominio}/{iuv}/{ccp} | Aggiorna selettivamente campi di una richiesta di pagamento |


## `findPagamenti()`

```php
findPagamenti($pagina, $risultati_per_pagina, $ordinamento, $campi, $stato, $versante, $id_sessione_portale, $verificato, $data_da, $data_a, $id_debitore, $id, $metadati_paginazione, $max_risultati, $severita_da, $severita_a, $id_dominio, $iuv, $id_a2_a, $id_pendenza): \GovPay\Backoffice\Model\FindPagamenti200Response
```

Lista dei pagamenti

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PagamentiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$ordinamento = '+dataRichiestaPagamento'; // string | csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * dataRichiestaPagamento  * stato
$campi = 'campi_example'; // string | csv dei campi da includere nella risposta (default tutti)
$stato = new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\StatoPagamento(); // \GovPay\Backoffice\Model\StatoPagamento | Filtro sullo stato del pagamento
$versante = RSSMRA30A01H501I; // string | Identificativo del soggetto versante del pagamento
$id_sessione_portale = c8be909b-2feb-4ffa-8f98-704462abbd1d; // string | Identificativo della sessione di pagamento assegnato dall'EC
$verificato = false; // bool | Filtro sui pagamenti verificati o meno
$data_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inizio della finestra temporale di osservazione
$data_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Fine della finestra temporale di osservazione
$id_debitore = RSSMRA30A01H501I; // string | Identificativo del soggetto debitore della pendenza
$id = c8be909b-2feb-4ffa-8f98-704462abbd1d; // string | Identificativo della richiesta di pagamento
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati
$severita_da = 56; // int | filtro per severita errore
$severita_a = 56; // int | filtro per severita errore
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio beneficiario
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$id_a2_a = 'id_a2_a_example'; // string | Identificativo del gestionale proprietario della pendenza
$id_pendenza = 'id_pendenza_example'; // string | Identificativo della pendenza nel gestionale proprietario

try {
    $result = $apiInstance->findPagamenti($pagina, $risultati_per_pagina, $ordinamento, $campi, $stato, $versante, $id_sessione_portale, $verificato, $data_da, $data_a, $id_debitore, $id, $metadati_paginazione, $max_risultati, $severita_da, $severita_a, $id_dominio, $iuv, $id_a2_a, $id_pendenza);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PagamentiApi->findPagamenti: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **ordinamento** | **string**| csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * dataRichiestaPagamento  * stato | [optional] [default to &#39;+dataRichiestaPagamento&#39;] |
| **campi** | **string**| csv dei campi da includere nella risposta (default tutti) | [optional] |
| **stato** | [**\GovPay\Backoffice\Model\StatoPagamento**](../Model/.md)| Filtro sullo stato del pagamento | [optional] |
| **versante** | **string**| Identificativo del soggetto versante del pagamento | [optional] |
| **id_sessione_portale** | **string**| Identificativo della sessione di pagamento assegnato dall&#39;EC | [optional] |
| **verificato** | **bool**| Filtro sui pagamenti verificati o meno | [optional] |
| **data_da** | **\DateTime**| Inizio della finestra temporale di osservazione | [optional] |
| **data_a** | **\DateTime**| Fine della finestra temporale di osservazione | [optional] |
| **id_debitore** | **string**| Identificativo del soggetto debitore della pendenza | [optional] |
| **id** | **string**| Identificativo della richiesta di pagamento | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |
| **severita_da** | **int**| filtro per severita errore | [optional] |
| **severita_a** | **int**| filtro per severita errore | [optional] |
| **id_dominio** | **string**| Identificativo del dominio beneficiario | [optional] |
| **iuv** | **string**| Identificativo univoco di versamento | [optional] |
| **id_a2_a** | **string**| Identificativo del gestionale proprietario della pendenza | [optional] |
| **id_pendenza** | **string**| Identificativo della pendenza nel gestionale proprietario | [optional] |

### Return type

[**\GovPay\Backoffice\Model\FindPagamenti200Response**](../Model/FindPagamenti200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findRpps()`

```php
findRpps($pagina, $risultati_per_pagina, $ordinamento, $campi, $id_dominio, $iuv, $ccp, $id_a2_a, $id_pendenza, $esito, $id_pagamento, $id_debitore, $data_rpt_da, $data_rpt_a, $data_rt_da, $data_rt_a, $direzione, $divisione, $tassonomia, $id_unita, $id_tipo_pendenza, $anagrafica_debitore, $metadati_paginazione, $max_risultati, $retrocompatibilita_messaggi_pago_pav1): \GovPay\Backoffice\Model\FindRpps200Response
```

Lista delle richieste di pagamento pendenza

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PagamentiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$ordinamento = '+dataRichiesta'; // string | csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * dataRichiesta  * stato
$campi = 'campi_example'; // string | csv dei campi da includere nella risposta (default tutti)
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio beneficiario
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$ccp = 'ccp_example'; // string | Codice contesto pagamento
$id_a2_a = 'id_a2_a_example'; // string | Identificativo del gestionale proprietario della pendenza
$id_pendenza = 'id_pendenza_example'; // string | Identificativo della pendenza nel gestionale proprietario
$esito = new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\EsitoRpt(); // \GovPay\Backoffice\Model\EsitoRpt | Esito della richiesta di pagamento
$id_pagamento = c8be909b-2feb-4ffa-8f98-704462abbd1d; // string | Identificativo della richiesta di pagamento
$id_debitore = RSSMRA30A01H501I; // string | Identificativo del soggetto debitore della pendenza
$data_rpt_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Filtro sulla data di invio della richiesta di pagamento
$data_rpt_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Filtro sulla data di invio della richiesta di pagamento
$data_rt_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Filtro sulla data di ricezione della ricevuta di pagamento
$data_rt_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Filtro sulla data di ricezione della ricevuta di pagamento
$direzione = array('direzione_example'); // string[] | Filtro per direzione
$divisione = array('divisione_example'); // string[] | Filtro per divisione
$tassonomia = 'tassonomia_example'; // string | Filtro per tassonomia
$id_unita = 'id_unita_example'; // string | Identificativo dell' unita' operativa
$id_tipo_pendenza = IMU; // string | Identificativo della tipologia di pendenza
$anagrafica_debitore = 'anagrafica_debitore_example'; // string | Filtro per anagrafica del soggetto debitore
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati
$retrocompatibilita_messaggi_pago_pav1 = false; // bool | abilita la conversione dei messaggi PagoPA nel formato V1

try {
    $result = $apiInstance->findRpps($pagina, $risultati_per_pagina, $ordinamento, $campi, $id_dominio, $iuv, $ccp, $id_a2_a, $id_pendenza, $esito, $id_pagamento, $id_debitore, $data_rpt_da, $data_rpt_a, $data_rt_da, $data_rt_a, $direzione, $divisione, $tassonomia, $id_unita, $id_tipo_pendenza, $anagrafica_debitore, $metadati_paginazione, $max_risultati, $retrocompatibilita_messaggi_pago_pav1);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PagamentiApi->findRpps: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **ordinamento** | **string**| csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * dataRichiesta  * stato | [optional] [default to &#39;+dataRichiesta&#39;] |
| **campi** | **string**| csv dei campi da includere nella risposta (default tutti) | [optional] |
| **id_dominio** | **string**| Identificativo del dominio beneficiario | [optional] |
| **iuv** | **string**| Identificativo univoco di versamento | [optional] |
| **ccp** | **string**| Codice contesto pagamento | [optional] |
| **id_a2_a** | **string**| Identificativo del gestionale proprietario della pendenza | [optional] |
| **id_pendenza** | **string**| Identificativo della pendenza nel gestionale proprietario | [optional] |
| **esito** | [**\GovPay\Backoffice\Model\EsitoRpt**](../Model/.md)| Esito della richiesta di pagamento | [optional] |
| **id_pagamento** | **string**| Identificativo della richiesta di pagamento | [optional] |
| **id_debitore** | **string**| Identificativo del soggetto debitore della pendenza | [optional] |
| **data_rpt_da** | **\DateTime**| Filtro sulla data di invio della richiesta di pagamento | [optional] |
| **data_rpt_a** | **\DateTime**| Filtro sulla data di invio della richiesta di pagamento | [optional] |
| **data_rt_da** | **\DateTime**| Filtro sulla data di ricezione della ricevuta di pagamento | [optional] |
| **data_rt_a** | **\DateTime**| Filtro sulla data di ricezione della ricevuta di pagamento | [optional] |
| **direzione** | [**string[]**](../Model/string.md)| Filtro per direzione | [optional] |
| **divisione** | [**string[]**](../Model/string.md)| Filtro per divisione | [optional] |
| **tassonomia** | **string**| Filtro per tassonomia | [optional] |
| **id_unita** | **string**| Identificativo dell&#39; unita&#39; operativa | [optional] |
| **id_tipo_pendenza** | **string**| Identificativo della tipologia di pendenza | [optional] |
| **anagrafica_debitore** | **string**| Filtro per anagrafica del soggetto debitore | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |
| **retrocompatibilita_messaggi_pago_pav1** | **bool**| abilita la conversione dei messaggi PagoPA nel formato V1 | [optional] |

### Return type

[**\GovPay\Backoffice\Model\FindRpps200Response**](../Model/FindRpps200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getPagamento()`

```php
getPagamento($id): \GovPay\Backoffice\Model\Pagamento
```

Dettaglio di un pagamento

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PagamentiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 'id_example'; // string | Identificativo del pagamento

try {
    $result = $apiInstance->getPagamento($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PagamentiApi->getPagamento: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **string**| Identificativo del pagamento | |

### Return type

[**\GovPay\Backoffice\Model\Pagamento**](../Model/Pagamento.md)

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
getRpp($id_dominio, $iuv, $ccp, $retrocompatibilita_messaggi_pago_pav1): \GovPay\Backoffice\Model\Rpp
```

Dettaglio di una richiesta di pagamento pendenza

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PagamentiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Codice fiscale del dominio beneficiario
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$ccp = 'ccp_example'; // string | Codice di contesto pagamento
$retrocompatibilita_messaggi_pago_pav1 = false; // bool | abilita la conversione dei messaggi PagoPA nel formato V1

try {
    $result = $apiInstance->getRpp($id_dominio, $iuv, $ccp, $retrocompatibilita_messaggi_pago_pav1);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PagamentiApi->getRpp: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Codice fiscale del dominio beneficiario | |
| **iuv** | **string**| Identificativo univoco di versamento | |
| **ccp** | **string**| Codice di contesto pagamento | |
| **retrocompatibilita_messaggi_pago_pav1** | **bool**| abilita la conversione dei messaggi PagoPA nel formato V1 | [optional] |

### Return type

[**\GovPay\Backoffice\Model\Rpp**](../Model/Rpp.md)

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
getRpt($id_dominio, $iuv, $ccp, $retrocompatibilita_messaggi_pago_pav1): string
```

Dettaglio della richiesta di pagamento pagopa

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PagamentiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Codice fiscale del dominio beneficiario
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$ccp = 'ccp_example'; // string | Codice di contesto pagamento
$retrocompatibilita_messaggi_pago_pav1 = false; // bool | abilita la conversione dei messaggi PagoPA nel formato V1

try {
    $result = $apiInstance->getRpt($id_dominio, $iuv, $ccp, $retrocompatibilita_messaggi_pago_pav1);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PagamentiApi->getRpt: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Codice fiscale del dominio beneficiario | |
| **iuv** | **string**| Identificativo univoco di versamento | |
| **ccp** | **string**| Codice di contesto pagamento | |
| **retrocompatibilita_messaggi_pago_pav1** | **bool**| abilita la conversione dei messaggi PagoPA nel formato V1 | [optional] |

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

## `getRt()`

```php
getRt($id_dominio, $iuv, $ccp, $retrocompatibilita_messaggi_pago_pav1): \SplFileObject
```

Dettaglio della ricevuta di pagamento

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PagamentiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Codice fiscale del dominio beneficiario
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$ccp = 'ccp_example'; // string | Codice di contesto pagamento
$retrocompatibilita_messaggi_pago_pav1 = false; // bool | abilita la conversione dei messaggi PagoPA nel formato V1

try {
    $result = $apiInstance->getRt($id_dominio, $iuv, $ccp, $retrocompatibilita_messaggi_pago_pav1);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PagamentiApi->getRt: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Codice fiscale del dominio beneficiario | |
| **iuv** | **string**| Identificativo univoco di versamento | |
| **ccp** | **string**| Codice di contesto pagamento | |
| **retrocompatibilita_messaggi_pago_pav1** | **bool**| abilita la conversione dei messaggi PagoPA nel formato V1 | [optional] |

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

## `updatePagamento()`

```php
updatePagamento($id, $patch_op): \GovPay\Backoffice\Model\Pagamento
```

Aggiorna selettivamente campi di un pagamento

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PagamentiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 'id_example'; // string | Identificativo del pagamento
$patch_op = array(new \GovPay\Backoffice\Model\PatchOp()); // \GovPay\Backoffice\Model\PatchOp[]

try {
    $result = $apiInstance->updatePagamento($id, $patch_op);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PagamentiApi->updatePagamento: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **string**| Identificativo del pagamento | |
| **patch_op** | [**\GovPay\Backoffice\Model\PatchOp[]**](../Model/PatchOp.md)|  | |

### Return type

[**\GovPay\Backoffice\Model\Pagamento**](../Model/Pagamento.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `application/json-patch+json`
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `updateRpp()`

```php
updateRpp($id_dominio, $iuv, $ccp, $patch_op): \GovPay\Backoffice\Model\Rpp
```

Aggiorna selettivamente campi di una richiesta di pagamento

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PagamentiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Codice fiscale del dominio beneficiario
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$ccp = 'ccp_example'; // string | Codice di contesto pagamento
$patch_op = array(new \GovPay\Backoffice\Model\PatchOp()); // \GovPay\Backoffice\Model\PatchOp[]

try {
    $result = $apiInstance->updateRpp($id_dominio, $iuv, $ccp, $patch_op);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PagamentiApi->updateRpp: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Codice fiscale del dominio beneficiario | |
| **iuv** | **string**| Identificativo univoco di versamento | |
| **ccp** | **string**| Codice di contesto pagamento | |
| **patch_op** | [**\GovPay\Backoffice\Model\PatchOp[]**](../Model/PatchOp.md)|  | |

### Return type

[**\GovPay\Backoffice\Model\Rpp**](../Model/Rpp.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `application/json-patch+json`
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
