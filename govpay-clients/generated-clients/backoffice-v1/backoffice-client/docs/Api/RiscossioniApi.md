# GovPay\Backoffice\RiscossioniApi

All URIs are relative to http://localhost/govpay/backend/api/backoffice/rs/basic/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**findRiscossioni()**](RiscossioniApi.md#findRiscossioni) | **GET** /riscossioni | Elenco dei pagamenti riscossi |
| [**getRiscossione()**](RiscossioniApi.md#getRiscossione) | **GET** /riscossioni/{idDominio}/{iuv}/{iur}/{indice} | Dettaglio di una riscossione |


## `findRiscossioni()`

```php
findRiscossioni($pagina, $risultati_per_pagina, $ordinamento, $campi, $id_dominio, $id_a2_a, $id_pendenza, $id_unita, $id_tipo_pendenza, $stato, $data_da, $data_a, $tipo, $iuv, $direzione, $divisione, $tassonomia, $metadati_paginazione, $max_risultati, $iur): \GovPay\Backoffice\Model\FindRiscossioni201Response
```

Elenco dei pagamenti riscossi

Fornisce la lista delle pendenze filtrata ed ordinata.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\RiscossioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$ordinamento = '+data'; // string | csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * data  * stato  * iuv
$campi = 'campi_example'; // string | csv dei campi da includere nella risposta (default tutti)
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio beneficiario
$id_a2_a = 'id_a2_a_example'; // string | Identificativo del gestionale proprietario della pendenza
$id_pendenza = 'id_pendenza_example'; // string | Identificativo della pendenza nel gestionale proprietario
$id_unita = 'id_unita_example'; // string | Identificativo dell' unita' operativa
$id_tipo_pendenza = IMU; // string | Identificativo della tipologia di pendenza
$stato = new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\StatoRiscossione(); // \GovPay\Backoffice\Model\StatoRiscossione | filtro sullo stato della riscossione
$data_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inizio della finestra temporale di osservazione
$data_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Fine della finestra temporale di osservazione
$tipo = array(new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\TipoRiscossione()); // \GovPay\Backoffice\Model\TipoRiscossione[] | Tipologia della riscossione (default [ENTRATA, MBT] )
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$direzione = array('direzione_example'); // string[] | Filtro per direzione
$divisione = array('divisione_example'); // string[] | Filtro per divisione
$tassonomia = array('tassonomia_example'); // string[] | Filtro per tassonomia
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati
$iur = 'iur_example'; // string | Identificativo univoco riscossione

try {
    $result = $apiInstance->findRiscossioni($pagina, $risultati_per_pagina, $ordinamento, $campi, $id_dominio, $id_a2_a, $id_pendenza, $id_unita, $id_tipo_pendenza, $stato, $data_da, $data_a, $tipo, $iuv, $direzione, $divisione, $tassonomia, $metadati_paginazione, $max_risultati, $iur);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RiscossioniApi->findRiscossioni: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **ordinamento** | **string**| csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * data  * stato  * iuv | [optional] [default to &#39;+data&#39;] |
| **campi** | **string**| csv dei campi da includere nella risposta (default tutti) | [optional] |
| **id_dominio** | **string**| Identificativo del dominio beneficiario | [optional] |
| **id_a2_a** | **string**| Identificativo del gestionale proprietario della pendenza | [optional] |
| **id_pendenza** | **string**| Identificativo della pendenza nel gestionale proprietario | [optional] |
| **id_unita** | **string**| Identificativo dell&#39; unita&#39; operativa | [optional] |
| **id_tipo_pendenza** | **string**| Identificativo della tipologia di pendenza | [optional] |
| **stato** | [**\GovPay\Backoffice\Model\StatoRiscossione**](../Model/.md)| filtro sullo stato della riscossione | [optional] |
| **data_da** | **\DateTime**| Inizio della finestra temporale di osservazione | [optional] |
| **data_a** | **\DateTime**| Fine della finestra temporale di osservazione | [optional] |
| **tipo** | [**\GovPay\Backoffice\Model\TipoRiscossione[]**](../Model/\GovPay\Backoffice\Model\TipoRiscossione.md)| Tipologia della riscossione (default [ENTRATA, MBT] ) | [optional] |
| **iuv** | **string**| Identificativo univoco di versamento | [optional] |
| **direzione** | [**string[]**](../Model/string.md)| Filtro per direzione | [optional] |
| **divisione** | [**string[]**](../Model/string.md)| Filtro per divisione | [optional] |
| **tassonomia** | [**string[]**](../Model/string.md)| Filtro per tassonomia | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |
| **iur** | **string**| Identificativo univoco riscossione | [optional] |

### Return type

[**\GovPay\Backoffice\Model\FindRiscossioni201Response**](../Model/FindRiscossioni201Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getRiscossione()`

```php
getRiscossione($id_dominio, $iuv, $iur, $indice): \GovPay\Backoffice\Model\Riscossione
```

Dettaglio di una riscossione

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\RiscossioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Codice fiscale del dominio beneficiario
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$iur = 'iur_example'; // string | Identificativo univoco di riscossione
$indice = 56; // int | Identificativo univoco di riscossione

try {
    $result = $apiInstance->getRiscossione($id_dominio, $iuv, $iur, $indice);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RiscossioniApi->getRiscossione: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Codice fiscale del dominio beneficiario | |
| **iuv** | **string**| Identificativo univoco di versamento | |
| **iur** | **string**| Identificativo univoco di riscossione | |
| **indice** | **int**| Identificativo univoco di riscossione | |

### Return type

[**\GovPay\Backoffice\Model\Riscossione**](../Model/Riscossione.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
