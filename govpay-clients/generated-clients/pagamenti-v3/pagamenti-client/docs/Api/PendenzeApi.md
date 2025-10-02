# GovPay\Pagamenti\PendenzeApi

All URIs are relative to http://localhost/govpay/frontend/api/pagamento/rs/basic/v3, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**findPendenze()**](PendenzeApi.md#findPendenze) | **GET** /pendenze | Elenco delle pendenze |
| [**getAllegatoPendenza()**](PendenzeApi.md#getAllegatoPendenza) | **GET** /allegati/{id} | Allegato di una pendenza |
| [**getPendenza()**](PendenzeApi.md#getPendenza) | **GET** /pendenze/{idA2A}/{idPendenza} | Dettaglio di una pendenza per identificativo |


## `findPendenze()`

```php
findPendenze($pagina, $risultati_per_pagina, $ordinamento, $id_dominio, $data_da, $data_a, $iuv, $id_a2_a, $id_pendenza, $id_debitore, $stato, $id_pagamento, $direzione, $divisione, $mostra_spontanei_non_pagati, $metadati_paginazione, $max_risultati): \GovPay\Pagamenti\Model\PosizioneDebitoria
```

Elenco delle pendenze

Fornisce la lista delle pendenze filtrata ed ordinata.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Pagamenti\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Pagamenti\Api\PendenzeApi(
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
$iuv = 'iuv_example'; // string | Identificativo del versamento
$id_a2_a = 'id_a2_a_example'; // string | Identificativo del gestionale ente
$id_pendenza = 'id_pendenza_example'; // string | Identificativo della pendenza nel gestionale ente
$id_debitore = RSSMRA30A01H501I; // string | Identificativo del soggetto debitore della pendenza
$stato = new \GovPay\Pagamenti\Model\\GovPay\Pagamenti\Model\StatoPendenza(); // \GovPay\Pagamenti\Model\StatoPendenza | Filtro sullo stato del pendenza
$id_pagamento = c8be909b-2feb-4ffa-8f98-704462abbd1d; // string | Identificativo della richiesta di pagamento
$direzione = Direzione ABC; // string | Identificativo della direzione interna all'ente creditore
$divisione = Divisione001; // string | Identificativo della divisione interna all'ente creditore
$mostra_spontanei_non_pagati = false; // bool | Visualizza solo le pendenze di tipo Spontaneo non pagate
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati

try {
    $result = $apiInstance->findPendenze($pagina, $risultati_per_pagina, $ordinamento, $id_dominio, $data_da, $data_a, $iuv, $id_a2_a, $id_pendenza, $id_debitore, $stato, $id_pagamento, $direzione, $divisione, $mostra_spontanei_non_pagati, $metadati_paginazione, $max_risultati);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->findPendenze: ', $e->getMessage(), PHP_EOL;
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
| **iuv** | **string**| Identificativo del versamento | [optional] |
| **id_a2_a** | **string**| Identificativo del gestionale ente | [optional] |
| **id_pendenza** | **string**| Identificativo della pendenza nel gestionale ente | [optional] |
| **id_debitore** | **string**| Identificativo del soggetto debitore della pendenza | [optional] |
| **stato** | [**\GovPay\Pagamenti\Model\StatoPendenza**](../Model/.md)| Filtro sullo stato del pendenza | [optional] |
| **id_pagamento** | **string**| Identificativo della richiesta di pagamento | [optional] |
| **direzione** | **string**| Identificativo della direzione interna all&#39;ente creditore | [optional] |
| **divisione** | **string**| Identificativo della divisione interna all&#39;ente creditore | [optional] |
| **mostra_spontanei_non_pagati** | **bool**| Visualizza solo le pendenze di tipo Spontaneo non pagate | [optional] [default to false] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |

### Return type

[**\GovPay\Pagamenti\Model\PosizioneDebitoria**](../Model/PosizioneDebitoria.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

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
$config = GovPay\Pagamenti\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Pagamenti\Api\PendenzeApi(
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
- **Accept**: `*/*`, `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getPendenza()`

```php
getPendenza($id_a2_a, $id_pendenza): \GovPay\Pagamenti\Model\PendenzaArchivio
```

Dettaglio di una pendenza per identificativo

Acquisisce il dettaglio di una pendenza, comprensivo dei dati di pagamento.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Pagamenti\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Pagamenti\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_a2_a = 'id_a2_a_example'; // string | Identificativo del gestionale ente
$id_pendenza = 'id_pendenza_example'; // string | Identificativo della pendenza nel gestionale ente

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
| **id_a2_a** | **string**| Identificativo del gestionale ente | |
| **id_pendenza** | **string**| Identificativo della pendenza nel gestionale ente | |

### Return type

[**\GovPay\Pagamenti\Model\PendenzaArchivio**](../Model/PendenzaArchivio.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
