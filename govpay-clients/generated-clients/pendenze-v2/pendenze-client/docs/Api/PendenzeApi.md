# GovPay\Pendenze\PendenzeApi

All URIs are relative to http://localhost/govpay/backend/api/pendenze/rs/basic/v2, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**addPendenza()**](PendenzeApi.md#addPendenza) | **PUT** /pendenze/{idA2A}/{idPendenza} | Inserimento o aggiornamento di una pendenza |
| [**findPendenze()**](PendenzeApi.md#findPendenze) | **GET** /pendenze | Elenco delle pendenze |
| [**getAllegatoPendenza()**](PendenzeApi.md#getAllegatoPendenza) | **GET** /allegati/{id} | Allegato di una pendenza |
| [**getAvvisiDocumento()**](PendenzeApi.md#getAvvisiDocumento) | **GET** /documenti/{idDominio}/{numeroDocumento}/avvisi | Documento di pagamento |
| [**getAvviso()**](PendenzeApi.md#getAvviso) | **GET** /avvisi/{idDominio}/{numeroAvviso} | Avviso di pagamento |
| [**getPendenza()**](PendenzeApi.md#getPendenza) | **GET** /pendenze/{idA2A}/{idPendenza} | Dettaglio di una pendenza per identificativo |
| [**getPendenzaByAvviso()**](PendenzeApi.md#getPendenzaByAvviso) | **GET** /pendenze/byAvviso/{idDominio}/{numeroAvviso} | Dettaglio di una pendenza per riferimento avviso |
| [**updatePendenza()**](PendenzeApi.md#updatePendenza) | **PATCH** /pendenze/{idA2A}/{idPendenza} | Aggiornamento di uno o più campi di una pendenza |


## `addPendenza()`

```php
addPendenza($id_a2_a, $id_pendenza, $stampa_avviso, $data_avvisatura, $nuova_pendenza): \GovPay\Pendenze\Model\PendenzaCreata
```

Inserimento o aggiornamento di una pendenza

Inserisce una nuova pendenza o la aggiorna se gia' presente in archivio

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Pendenze\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Pendenze\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_a2_a = 'id_a2_a_example'; // string | Identificativo del gestionale
$id_pendenza = 'id_pendenza_example'; // string | Identificativo della pendenza
$stampa_avviso = false; // bool | Indica se nella risposta deve essere inclusa la stampa dell'avviso in standard AgID
$data_avvisatura = new \GovPay\Pendenze\Model\\GovPay\Pendenze\Model\AddPendenzaDataAvvisaturaParameter(); // \GovPay\Pendenze\Model\AddPendenzaDataAvvisaturaParameter | Indica quando notificare l'avviso di pagamento
$nuova_pendenza = new \GovPay\Pendenze\Model\NuovaPendenza(); // \GovPay\Pendenze\Model\NuovaPendenza

try {
    $result = $apiInstance->addPendenza($id_a2_a, $id_pendenza, $stampa_avviso, $data_avvisatura, $nuova_pendenza);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->addPendenza: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_a2_a** | **string**| Identificativo del gestionale | |
| **id_pendenza** | **string**| Identificativo della pendenza | |
| **stampa_avviso** | **bool**| Indica se nella risposta deve essere inclusa la stampa dell&#39;avviso in standard AgID | [optional] [default to false] |
| **data_avvisatura** | [**\GovPay\Pendenze\Model\AddPendenzaDataAvvisaturaParameter**](../Model/.md)| Indica quando notificare l&#39;avviso di pagamento | [optional] |
| **nuova_pendenza** | [**\GovPay\Pendenze\Model\NuovaPendenza**](../Model/NuovaPendenza.md)|  | [optional] |

### Return type

[**\GovPay\Pendenze\Model\PendenzaCreata**](../Model/PendenzaCreata.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findPendenze()`

```php
findPendenze($pagina, $risultati_per_pagina, $campi, $ordinamento, $data_da, $data_a, $id_dominio, $id_a2_a, $id_pendenza, $id_debitore, $stato, $id_pagamento, $direzione, $divisione, $mostra_spontanei_non_pagati, $metadati_paginazione, $max_risultati): \GovPay\Pendenze\Model\Pendenze
```

Elenco delle pendenze

Fornisce la lista delle pendenze filtrata ed ordinata.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Pendenze\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Pendenze\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | The zero-ary offset index into the results
$risultati_per_pagina = 25; // int | How many items to return at one time (max 100)
$campi = 'campi_example'; // string | Fields to retrieve
$ordinamento = +name; // string | Sorting order
$data_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inizio della finestra temporale di osservazione
$data_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Fine della finestra temporale di osservazione
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio dell'ente
$id_a2_a = 'id_a2_a_example'; // string | Identificativo del gestionale ente
$id_pendenza = 'id_pendenza_example'; // string | Identificativo della pendenza nel gestionale ente
$id_debitore = RSSMRA30A01H501I; // string | Identificativo del soggetto debitore della pendenza
$stato = new \GovPay\Pendenze\Model\\GovPay\Pendenze\Model\StatoPendenza(); // \GovPay\Pendenze\Model\StatoPendenza | Filtro sullo stato del pendenza
$id_pagamento = c8be909b-2feb-4ffa-8f98-704462abbd1d; // string | Identificativo della richiesta di pagamento
$direzione = Direzione ABC; // string | Identificativo della direzione interna all'ente creditore
$divisione = Divisione001; // string | Identificativo della divisione interna all'ente creditore
$mostra_spontanei_non_pagati = false; // bool | Visualizza solo le pendenze di tipo Spontaneo non pagate
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati

try {
    $result = $apiInstance->findPendenze($pagina, $risultati_per_pagina, $campi, $ordinamento, $data_da, $data_a, $id_dominio, $id_a2_a, $id_pendenza, $id_debitore, $stato, $id_pagamento, $direzione, $divisione, $mostra_spontanei_non_pagati, $metadati_paginazione, $max_risultati);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->findPendenze: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| The zero-ary offset index into the results | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| How many items to return at one time (max 100) | [optional] [default to 25] |
| **campi** | **string**| Fields to retrieve | [optional] |
| **ordinamento** | **string**| Sorting order | [optional] |
| **data_da** | **\DateTime**| Inizio della finestra temporale di osservazione | [optional] |
| **data_a** | **\DateTime**| Fine della finestra temporale di osservazione | [optional] |
| **id_dominio** | **string**| Identificativo del dominio dell&#39;ente | [optional] |
| **id_a2_a** | **string**| Identificativo del gestionale ente | [optional] |
| **id_pendenza** | **string**| Identificativo della pendenza nel gestionale ente | [optional] |
| **id_debitore** | **string**| Identificativo del soggetto debitore della pendenza | [optional] |
| **stato** | [**\GovPay\Pendenze\Model\StatoPendenza**](../Model/.md)| Filtro sullo stato del pendenza | [optional] |
| **id_pagamento** | **string**| Identificativo della richiesta di pagamento | [optional] |
| **direzione** | **string**| Identificativo della direzione interna all&#39;ente creditore | [optional] |
| **divisione** | **string**| Identificativo della divisione interna all&#39;ente creditore | [optional] |
| **mostra_spontanei_non_pagati** | **bool**| Visualizza solo le pendenze di tipo Spontaneo non pagate | [optional] [default to false] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |

### Return type

[**\GovPay\Pendenze\Model\Pendenze**](../Model/Pendenze.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getAllegatoPendenza()`

```php
getAllegatoPendenza($id): \SplFileObject
```

Allegato di una pendenza

Fornisce l'allegato di una pendenza

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Pendenze\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Pendenze\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Identificativo dell'allegato

try {
    $result = $apiInstance->getAllegatoPendenza($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->getAllegatoPendenza: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Identificativo dell&#39;allegato | |

### Return type

**\SplFileObject**

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `*/*`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getAvvisiDocumento()`

```php
getAvvisiDocumento($id_dominio, $numero_documento, $lingua_secondaria, $numeri_avviso): \SplFileObject
```

Documento di pagamento

Fornisce un documento di pagamento e gli avvisi ad esso associati

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Pendenze\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Pendenze\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio dell'ente
$numero_documento = 'numero_documento_example'; // string | Identificativo del documento di pagamento
$lingua_secondaria = new \GovPay\Pendenze\Model\\GovPay\Pendenze\Model\LinguaSecondaria(); // \GovPay\Pendenze\Model\LinguaSecondaria | Indica se creare l'avviso in modalita' multilingua e quale seconda lingua affiancare all'italiano all'interno dell'avviso
$numeri_avviso = ["123456789012345678"]; // string[] | Indica i numeri avviso da includere nelle stampe dei documenti

try {
    $result = $apiInstance->getAvvisiDocumento($id_dominio, $numero_documento, $lingua_secondaria, $numeri_avviso);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->getAvvisiDocumento: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo del dominio dell&#39;ente | |
| **numero_documento** | **string**| Identificativo del documento di pagamento | |
| **lingua_secondaria** | [**\GovPay\Pendenze\Model\LinguaSecondaria**](../Model/.md)| Indica se creare l&#39;avviso in modalita&#39; multilingua e quale seconda lingua affiancare all&#39;italiano all&#39;interno dell&#39;avviso | [optional] |
| **numeri_avviso** | [**string[]**](../Model/string.md)| Indica i numeri avviso da includere nelle stampe dei documenti | [optional] |

### Return type

**\SplFileObject**

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/pdf`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getAvviso()`

```php
getAvviso($id_dominio, $numero_avviso, $lingua_secondaria): \GovPay\Pendenze\Model\Avviso
```

Avviso di pagamento

Fornisce un avviso di pagamento o la pendenza ad esso associata

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Pendenze\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Pendenze\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio dell'ente
$numero_avviso = 'numero_avviso_example'; // string | Identificativo dell'avviso di pagamento pagoPA
$lingua_secondaria = new \GovPay\Pendenze\Model\\GovPay\Pendenze\Model\LinguaSecondaria(); // \GovPay\Pendenze\Model\LinguaSecondaria | Indica se creare l'avviso in modalita' multilingua e quale seconda lingua affiancare all'italiano all'interno dell'avviso

try {
    $result = $apiInstance->getAvviso($id_dominio, $numero_avviso, $lingua_secondaria);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->getAvviso: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo del dominio dell&#39;ente | |
| **numero_avviso** | **string**| Identificativo dell&#39;avviso di pagamento pagoPA | |
| **lingua_secondaria** | [**\GovPay\Pendenze\Model\LinguaSecondaria**](../Model/.md)| Indica se creare l&#39;avviso in modalita&#39; multilingua e quale seconda lingua affiancare all&#39;italiano all&#39;interno dell&#39;avviso | [optional] |

### Return type

[**\GovPay\Pendenze\Model\Avviso**](../Model/Avviso.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/pdf`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getPendenza()`

```php
getPendenza($id_a2_a, $id_pendenza): \GovPay\Pendenze\Model\Pendenza
```

Dettaglio di una pendenza per identificativo

Acquisisce il dettaglio di una pendenza, comprensivo dei dati di pagamento.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Pendenze\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Pendenze\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_a2_a = 'id_a2_a_example'; // string | Identificativo del gestionale
$id_pendenza = 'id_pendenza_example'; // string | Identificativo della pendenza

try {
    $result = $apiInstance->getPendenza($id_a2_a, $id_pendenza);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->getPendenza: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_a2_a** | **string**| Identificativo del gestionale | |
| **id_pendenza** | **string**| Identificativo della pendenza | |

### Return type

[**\GovPay\Pendenze\Model\Pendenza**](../Model/Pendenza.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getPendenzaByAvviso()`

```php
getPendenzaByAvviso($id_dominio, $numero_avviso): \GovPay\Pendenze\Model\Pendenza
```

Dettaglio di una pendenza per riferimento avviso

Acquisisce il dettaglio di una pendenza, comprensivo dei dati di pagamento.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Pendenze\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Pendenze\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio dell'ente
$numero_avviso = 'numero_avviso_example'; // string | Identificativo dell'avviso di pagamento

try {
    $result = $apiInstance->getPendenzaByAvviso($id_dominio, $numero_avviso);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->getPendenzaByAvviso: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo del dominio dell&#39;ente | |
| **numero_avviso** | **string**| Identificativo dell&#39;avviso di pagamento | |

### Return type

[**\GovPay\Pendenze\Model\Pendenza**](../Model/Pendenza.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `updatePendenza()`

```php
updatePendenza($id_a2_a, $id_pendenza, $patch_op)
```

Aggiornamento di uno o più campi di una pendenza

aggiornamento puntuale di una pendenza

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Pendenze\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Pendenze\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_a2_a = 'id_a2_a_example'; // string | Identificativo del gestionale
$id_pendenza = 'id_pendenza_example'; // string | Identificativo della pendenza
$patch_op = array(new \GovPay\Pendenze\Model\PatchOp()); // \GovPay\Pendenze\Model\PatchOp[]

try {
    $apiInstance->updatePendenza($id_a2_a, $id_pendenza, $patch_op);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->updatePendenza: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_a2_a** | **string**| Identificativo del gestionale | |
| **id_pendenza** | **string**| Identificativo della pendenza | |
| **patch_op** | [**\GovPay\Pendenze\Model\PatchOp[]**](../Model/PatchOp.md)|  | |

### Return type

void (empty response body)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `application/json-patch+json`
- **Accept**: `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
