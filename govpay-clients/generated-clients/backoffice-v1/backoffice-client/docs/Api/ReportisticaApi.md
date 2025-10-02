# GovPay\Backoffice\ReportisticaApi

All URIs are relative to http://localhost/govpay/backend/api/backoffice/rs/basic/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**findQuadratureRendicontazioni()**](ReportisticaApi.md#findQuadratureRendicontazioni) | **GET** /quadrature/rendicontazioni | Statistiche e aggregazioni sulle rendicontazioni |
| [**findQuadratureRiscossioni()**](ReportisticaApi.md#findQuadratureRiscossioni) | **GET** /quadrature/riscossioni | Statistiche e aggregazioni sui pagamenti |
| [**getReportEntratePreviste()**](ReportisticaApi.md#getReportEntratePreviste) | **GET** /reportistiche/entrate-previste | Report sulle entrate previste |


## `findQuadratureRendicontazioni()`

```php
findQuadratureRendicontazioni($gruppi, $pagina, $risultati_per_pagina, $flusso_rendicontazione_data_flusso_da, $flusso_rendicontazione_data_flusso_a, $data_da, $data_a, $id_flusso, $iuv, $direzione, $divisione): \GovPay\Backoffice\Model\FindQuadratureRendicontazioni200Response
```

Statistiche e aggregazioni sulle rendicontazioni

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\ReportisticaApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$gruppi = array(new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\RaggruppamentoStatisticaRendicontazione()); // \GovPay\Backoffice\Model\RaggruppamentoStatisticaRendicontazione[] | Indica i gruppi da creare
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$flusso_rendicontazione_data_flusso_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Filtro sulla data di acquisizione del flusso rendicontazioni
$flusso_rendicontazione_data_flusso_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Filtro sulla data di acquisizione del flusso rendicontazioni
$data_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inizio della finestra temporale di osservazione
$data_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Fine della finestra temporale di osservazione
$id_flusso = 'id_flusso_example'; // string | Identificativo flusso
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$direzione = array('direzione_example'); // string[] | Filtro per direzione
$divisione = array('divisione_example'); // string[] | Filtro per divisione

try {
    $result = $apiInstance->findQuadratureRendicontazioni($gruppi, $pagina, $risultati_per_pagina, $flusso_rendicontazione_data_flusso_da, $flusso_rendicontazione_data_flusso_a, $data_da, $data_a, $id_flusso, $iuv, $direzione, $divisione);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ReportisticaApi->findQuadratureRendicontazioni: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **gruppi** | [**\GovPay\Backoffice\Model\RaggruppamentoStatisticaRendicontazione[]**](../Model/\GovPay\Backoffice\Model\RaggruppamentoStatisticaRendicontazione.md)| Indica i gruppi da creare | |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **flusso_rendicontazione_data_flusso_da** | **\DateTime**| Filtro sulla data di acquisizione del flusso rendicontazioni | [optional] |
| **flusso_rendicontazione_data_flusso_a** | **\DateTime**| Filtro sulla data di acquisizione del flusso rendicontazioni | [optional] |
| **data_da** | **\DateTime**| Inizio della finestra temporale di osservazione | [optional] |
| **data_a** | **\DateTime**| Fine della finestra temporale di osservazione | [optional] |
| **id_flusso** | **string**| Identificativo flusso | [optional] |
| **iuv** | **string**| Identificativo univoco di versamento | [optional] |
| **direzione** | [**string[]**](../Model/string.md)| Filtro per direzione | [optional] |
| **divisione** | [**string[]**](../Model/string.md)| Filtro per divisione | [optional] |

### Return type

[**\GovPay\Backoffice\Model\FindQuadratureRendicontazioni200Response**](../Model/FindQuadratureRendicontazioni200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findQuadratureRiscossioni()`

```php
findQuadratureRiscossioni($gruppi, $pagina, $risultati_per_pagina, $data_da, $data_a, $id_dominio, $id_unita, $id_tipo_pendenza, $id_a2_a, $direzione, $divisione, $tassonomia, $tipo): \GovPay\Backoffice\Model\FindQuadratureRiscossioni200Response
```

Statistiche e aggregazioni sui pagamenti

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\ReportisticaApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$gruppi = array(new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\RaggruppamentoStatistica()); // \GovPay\Backoffice\Model\RaggruppamentoStatistica[] | Indica i gruppi da creare
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$data_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inizio della finestra temporale di osservazione
$data_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Fine della finestra temporale di osservazione
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio beneficiario
$id_unita = 'id_unita_example'; // string | Identificativo dell' unita' operativa
$id_tipo_pendenza = IMU; // string | Identificativo della tipologia di pendenza
$id_a2_a = 'id_a2_a_example'; // string | Identificativo del gestionale proprietario della pendenza
$direzione = array('direzione_example'); // string[] | Filtro per direzione
$divisione = array('divisione_example'); // string[] | Filtro per divisione
$tassonomia = array('tassonomia_example'); // string[] | Filtro per tassonomia
$tipo = array(new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\TipoRiscossione()); // \GovPay\Backoffice\Model\TipoRiscossione[] | Tipologia della riscossione (default [ENTRATA, MBT] )

try {
    $result = $apiInstance->findQuadratureRiscossioni($gruppi, $pagina, $risultati_per_pagina, $data_da, $data_a, $id_dominio, $id_unita, $id_tipo_pendenza, $id_a2_a, $direzione, $divisione, $tassonomia, $tipo);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ReportisticaApi->findQuadratureRiscossioni: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **gruppi** | [**\GovPay\Backoffice\Model\RaggruppamentoStatistica[]**](../Model/\GovPay\Backoffice\Model\RaggruppamentoStatistica.md)| Indica i gruppi da creare | |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **data_da** | **\DateTime**| Inizio della finestra temporale di osservazione | [optional] |
| **data_a** | **\DateTime**| Fine della finestra temporale di osservazione | [optional] |
| **id_dominio** | **string**| Identificativo del dominio beneficiario | [optional] |
| **id_unita** | **string**| Identificativo dell&#39; unita&#39; operativa | [optional] |
| **id_tipo_pendenza** | **string**| Identificativo della tipologia di pendenza | [optional] |
| **id_a2_a** | **string**| Identificativo del gestionale proprietario della pendenza | [optional] |
| **direzione** | [**string[]**](../Model/string.md)| Filtro per direzione | [optional] |
| **divisione** | [**string[]**](../Model/string.md)| Filtro per divisione | [optional] |
| **tassonomia** | [**string[]**](../Model/string.md)| Filtro per tassonomia | [optional] |
| **tipo** | [**\GovPay\Backoffice\Model\TipoRiscossione[]**](../Model/\GovPay\Backoffice\Model\TipoRiscossione.md)| Tipologia della riscossione (default [ENTRATA, MBT] ) | [optional] |

### Return type

[**\GovPay\Backoffice\Model\FindQuadratureRiscossioni200Response**](../Model/FindQuadratureRiscossioni200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getReportEntratePreviste()`

```php
getReportEntratePreviste($pagina, $risultati_per_pagina, $id_dominio, $data_da, $data_a): \GovPay\Backoffice\Model\GetReportEntratePreviste200Response
```

Report sulle entrate previste

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\ReportisticaApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio beneficiario
$data_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inizio della finestra temporale di osservazione
$data_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Fine della finestra temporale di osservazione

try {
    $result = $apiInstance->getReportEntratePreviste($pagina, $risultati_per_pagina, $id_dominio, $data_da, $data_a);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ReportisticaApi->getReportEntratePreviste: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **id_dominio** | **string**| Identificativo del dominio beneficiario | [optional] |
| **data_da** | **\DateTime**| Inizio della finestra temporale di osservazione | [optional] |
| **data_a** | **\DateTime**| Fine della finestra temporale di osservazione | [optional] |

### Return type

[**\GovPay\Backoffice\Model\GetReportEntratePreviste200Response**](../Model/GetReportEntratePreviste200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/pdf`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
