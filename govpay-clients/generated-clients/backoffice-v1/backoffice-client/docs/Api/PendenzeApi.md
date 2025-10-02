# GovPay\Backoffice\PendenzeApi

All URIs are relative to http://localhost/govpay/backend/api/backoffice/rs/basic/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**addPendenza()**](PendenzeApi.md#addPendenza) | **PUT** /pendenze/{idA2A}/{idPendenza} | Aggiunge una nuova Pendenza |
| [**addPendenzaCustom()**](PendenzeApi.md#addPendenzaCustom) | **POST** /pendenze/{idDominio}/{idTipoPendenza} | Aggiunge una nuova Pendenza in formato custom |
| [**addPendenzaPOST()**](PendenzeApi.md#addPendenzaPOST) | **POST** /pendenze | Aggiunge una nuova Pendenza |
| [**addTracciatoPendenze()**](PendenzeApi.md#addTracciatoPendenze) | **POST** /pendenze/tracciati | Aggiunge un nuovo Tracciato di Pendenze |
| [**addTracciatoPendenzeDominio()**](PendenzeApi.md#addTracciatoPendenzeDominio) | **POST** /pendenze/tracciati/{idDominio} | Aggiunge un nuovo Tracciato di Pendenze in formato csv |
| [**addTracciatoPendenzeDominioTipoPendenza()**](PendenzeApi.md#addTracciatoPendenzeDominioTipoPendenza) | **POST** /pendenze/tracciati/{idDominio}/{idTipoPendenza} | Aggiunge un nuovo Tracciato di Pendenze in formato csv |
| [**findOperazioniTracciatoPendenze()**](PendenzeApi.md#findOperazioniTracciatoPendenze) | **GET** /pendenze/tracciati/{id}/operazioni | Elenco delle Operazioni relative ad un Tracciato |
| [**findPendenze()**](PendenzeApi.md#findPendenze) | **GET** /pendenze | Elenco delle pendenze |
| [**findTracciatiPendenze()**](PendenzeApi.md#findTracciatiPendenze) | **GET** /pendenze/tracciati | Elenco dei Tracciati caricati |
| [**getAllegatoPendenza()**](PendenzeApi.md#getAllegatoPendenza) | **GET** /allegati/{id} | Allegato di una pendenza |
| [**getAvvisiDocumento()**](PendenzeApi.md#getAvvisiDocumento) | **GET** /documenti/{idA2A}/{idDominio}/{numeroDocumento}/avvisi | Documento di pagamento |
| [**getAvviso()**](PendenzeApi.md#getAvviso) | **GET** /avvisi/{idDominio}/{numeroAvviso} | Avviso di pagamento |
| [**getEsitoTracciatoPendenze()**](PendenzeApi.md#getEsitoTracciatoPendenze) | **GET** /pendenze/tracciati/{id}/esito | Dettaglio esito di un Tracciato |
| [**getPendenza()**](PendenzeApi.md#getPendenza) | **GET** /pendenze/{idA2A}/{idPendenza} | Dettaglio di una Pendenza per identificativo |
| [**getPendenzaByAvviso()**](PendenzeApi.md#getPendenzaByAvviso) | **GET** /pendenze/byAvviso/{idDominio}/{numeroAvviso} | Dettaglio di una pendenza per riferimento Avviso |
| [**getRichiestaTracciatoPendenze()**](PendenzeApi.md#getRichiestaTracciatoPendenze) | **GET** /pendenze/tracciati/{id}/richiesta | Tracciato di richiesta |
| [**getStampeTracciatoPendenze()**](PendenzeApi.md#getStampeTracciatoPendenze) | **GET** /pendenze/tracciati/{id}/stampe | Avvisi di pagamento relativi al Tracciato |
| [**getTracciatoPendenze()**](PendenzeApi.md#getTracciatoPendenze) | **GET** /pendenze/tracciati/{id} | Dettaglio di un Tracciato |
| [**updatePendenza()**](PendenzeApi.md#updatePendenza) | **PATCH** /pendenze/{idA2A}/{idPendenza} | Annullamento o ripristino di una Pendenza |


## `addPendenza()`

```php
addPendenza($id_a2_a, $id_pendenza, $stampa_avviso, $pendenza_put): \GovPay\Backoffice\Model\PendenzaCreata
```

Aggiunge una nuova Pendenza

Per ciascuna pendenza viene richiesto un identificativo (_idPendenza_) valido per il gestionale che ne effettua il caricamento (_idA2A_). L'operazione è idempotente rispetto a questo identificativo e nel caso venga eseguito il caricamento di una pendenza con un identificativo già presente nell'archivio di GovPay, questa sarà gestita come un aggiornamento. <br/> Ciascuna pendenza può essere composta da più voci fino ad un massimo di cinque, ciascuna avente un identificativo _idVocePendenza_ assegnato dal gestionale come riferimento interno.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_a2_a = 'id_a2_a_example'; // string | Identificativo del gestionale
$id_pendenza = 'id_pendenza_example'; // string | Identificativo della pendenza
$stampa_avviso = false; // bool | Indica se nella risposta deve essere inclusa la stampa dell'avviso in standard AgID
$pendenza_put = new \GovPay\Backoffice\Model\PendenzaPut(); // \GovPay\Backoffice\Model\PendenzaPut

try {
    $result = $apiInstance->addPendenza($id_a2_a, $id_pendenza, $stampa_avviso, $pendenza_put);
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
| **pendenza_put** | [**\GovPay\Backoffice\Model\PendenzaPut**](../Model/PendenzaPut.md)|  | [optional] |

### Return type

[**\GovPay\Backoffice\Model\PendenzaCreata**](../Model/PendenzaCreata.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `addPendenzaCustom()`

```php
addPendenzaCustom($id_dominio, $id_tipo_pendenza, $id_unita_operativa, $stampa_avviso, $body): \GovPay\Backoffice\Model\PendenzaCreata
```

Aggiunge una nuova Pendenza in formato custom

Operazione di caricamento della pendenza in formato custom

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo dell'ente creditore
$id_tipo_pendenza = 'id_tipo_pendenza_example'; // string | Identificativo della tipologia pendenza
$id_unita_operativa = 'id_unita_operativa_example'; // string | Identificativo dell'unita' operativa
$stampa_avviso = false; // bool | Indica se nella risposta deve essere inclusa la stampa dell'avviso in standard AgID
$body = array('key' => new \stdClass); // object | Pendenza di tipo custom

try {
    $result = $apiInstance->addPendenzaCustom($id_dominio, $id_tipo_pendenza, $id_unita_operativa, $stampa_avviso, $body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->addPendenzaCustom: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo dell&#39;ente creditore | |
| **id_tipo_pendenza** | **string**| Identificativo della tipologia pendenza | |
| **id_unita_operativa** | **string**| Identificativo dell&#39;unita&#39; operativa | [optional] |
| **stampa_avviso** | **bool**| Indica se nella risposta deve essere inclusa la stampa dell&#39;avviso in standard AgID | [optional] [default to false] |
| **body** | **object**| Pendenza di tipo custom | [optional] |

### Return type

[**\GovPay\Backoffice\Model\PendenzaCreata**](../Model/PendenzaCreata.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `addPendenzaPOST()`

```php
addPendenzaPOST($stampa_avviso, $pendenza_post): \GovPay\Backoffice\Model\PendenzaCreata
```

Aggiunge una nuova Pendenza

Per ciascuna pendenza viene richiesto un identificativo (_idPendenza_) valido per il gestionale che ne effettua il caricamento (_idA2A_). L'operazione è idempotente rispetto a questo identificativo e nel caso venga eseguito il caricamento di una pendenza con un identificativo già presente nell'archivio di GovPay, questa sarà gestita come un aggiornamento. <br/> Ciascuna pendenza può essere composta da più voci fino ad un massimo di cinque, ciascuna avente un identificativo _idVocePendenza_ assegnato dal gestionale come riferimento interno.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$stampa_avviso = false; // bool | Indica se nella risposta deve essere inclusa la stampa dell'avviso in standard AgID
$pendenza_post = new \GovPay\Backoffice\Model\PendenzaPost(); // \GovPay\Backoffice\Model\PendenzaPost

try {
    $result = $apiInstance->addPendenzaPOST($stampa_avviso, $pendenza_post);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->addPendenzaPOST: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **stampa_avviso** | **bool**| Indica se nella risposta deve essere inclusa la stampa dell&#39;avviso in standard AgID | [optional] [default to false] |
| **pendenza_post** | [**\GovPay\Backoffice\Model\PendenzaPost**](../Model/PendenzaPost.md)|  | [optional] |

### Return type

[**\GovPay\Backoffice\Model\PendenzaCreata**](../Model/PendenzaCreata.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `addTracciatoPendenze()`

```php
addTracciatoPendenze($tracciato_pendenze_post, $stampa_avvisi): \GovPay\Backoffice\Model\TracciatoPendenzeIndex
```

Aggiunge un nuovo Tracciato di Pendenze

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$tracciato_pendenze_post = new \GovPay\Backoffice\Model\TracciatoPendenzePost(); // \GovPay\Backoffice\Model\TracciatoPendenzePost | Tracciato Pendenze in formato JSON
$stampa_avvisi = true; // bool | indica se effettuare la stampa degli avvisi associati alle pendenze caricate col tracciato

try {
    $result = $apiInstance->addTracciatoPendenze($tracciato_pendenze_post, $stampa_avvisi);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->addTracciatoPendenze: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **tracciato_pendenze_post** | [**\GovPay\Backoffice\Model\TracciatoPendenzePost**](../Model/TracciatoPendenzePost.md)| Tracciato Pendenze in formato JSON | |
| **stampa_avvisi** | **bool**| indica se effettuare la stampa degli avvisi associati alle pendenze caricate col tracciato | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\TracciatoPendenzeIndex**](../Model/TracciatoPendenzeIndex.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `application/json`, `multipart/form-data`
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `addTracciatoPendenzeDominio()`

```php
addTracciatoPendenzeDominio($id_dominio, $body, $stampa_avvisi): \GovPay\Backoffice\Model\TracciatoPendenzeIndex
```

Aggiunge un nuovo Tracciato di Pendenze in formato csv

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo dente creditore
$body = array('key' => new \stdClass); // object | Tracciato Pendenze in formato CSV
$stampa_avvisi = true; // bool | indica se effettuare la stampa degli avvisi associati alle pendenze caricate col tracciato

try {
    $result = $apiInstance->addTracciatoPendenzeDominio($id_dominio, $body, $stampa_avvisi);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->addTracciatoPendenzeDominio: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo dente creditore | |
| **body** | **object**| Tracciato Pendenze in formato CSV | |
| **stampa_avvisi** | **bool**| indica se effettuare la stampa degli avvisi associati alle pendenze caricate col tracciato | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\TracciatoPendenzeIndex**](../Model/TracciatoPendenzeIndex.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `text/csv`, `multipart/form-data`
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `addTracciatoPendenzeDominioTipoPendenza()`

```php
addTracciatoPendenzeDominioTipoPendenza($id_dominio, $id_tipo_pendenza, $body, $stampa_avvisi): \GovPay\Backoffice\Model\TracciatoPendenzeIndex
```

Aggiunge un nuovo Tracciato di Pendenze in formato csv

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo dente creditore
$id_tipo_pendenza = 'id_tipo_pendenza_example'; // string | Identificativo della tipologia pendenza
$body = array('key' => new \stdClass); // object | Tracciato Pendenze in formato CSV
$stampa_avvisi = true; // bool | indica se effettuare la stampa degli avvisi associati alle pendenze caricate col tracciato

try {
    $result = $apiInstance->addTracciatoPendenzeDominioTipoPendenza($id_dominio, $id_tipo_pendenza, $body, $stampa_avvisi);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->addTracciatoPendenzeDominioTipoPendenza: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo dente creditore | |
| **id_tipo_pendenza** | **string**| Identificativo della tipologia pendenza | |
| **body** | **object**| Tracciato Pendenze in formato CSV | |
| **stampa_avvisi** | **bool**| indica se effettuare la stampa degli avvisi associati alle pendenze caricate col tracciato | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\TracciatoPendenzeIndex**](../Model/TracciatoPendenzeIndex.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `text/csv`, `multipart/form-data`
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findOperazioniTracciatoPendenze()`

```php
findOperazioniTracciatoPendenze($id, $pagina, $risultati_per_pagina, $metadati_paginazione, $max_risultati): \GovPay\Backoffice\Model\FindOperazioniTracciatoPendenze200Response
```

Elenco delle Operazioni relative ad un Tracciato

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | identificativo di un tracciato
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati

try {
    $result = $apiInstance->findOperazioniTracciatoPendenze($id, $pagina, $risultati_per_pagina, $metadati_paginazione, $max_risultati);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->findOperazioniTracciatoPendenze: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| identificativo di un tracciato | |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\FindOperazioniTracciatoPendenze200Response**](../Model/FindOperazioniTracciatoPendenze200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findPendenze()`

```php
findPendenze($pagina, $risultati_per_pagina, $ordinamento, $campi, $id_dominio, $id_a2_a, $id_debitore, $stato, $id_pagamento, $id_pendenza, $data_da, $data_a, $id_tipo_pendenza, $direzione, $divisione, $iuv, $mostra_spontanei_non_pagati, $metadati_paginazione, $max_risultati): \GovPay\Backoffice\Model\FindPendenze200Response
```

Elenco delle pendenze

Fornisce la lista delle pendenze filtrata ed ordinata.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$ordinamento = '+dataCaricamento'; // string | csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default discendente)  * dataCaricamento  * dataValidita  * dataScadenza  * stato  * smart (solo se attivo il filtro 'idDebitore')
$campi = 'campi_example'; // string | csv dei campi da includere nella risposta (default tutti)
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio beneficiario
$id_a2_a = 'id_a2_a_example'; // string | Identificativo del gestionale proprietario della pendenza
$id_debitore = RSSMRA30A01H501I; // string | Identificativo del soggetto debitore della pendenza
$stato = new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\StatoPendenza(); // \GovPay\Backoffice\Model\StatoPendenza | Filtro sullo stato della pendenza
$id_pagamento = c8be909b-2feb-4ffa-8f98-704462abbd1d; // string | Identificativo della richiesta di pagamento
$id_pendenza = 'id_pendenza_example'; // string | Identificativo della pendenza nel gestionale proprietario
$data_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inizio della finestra temporale di osservazione
$data_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Fine della finestra temporale di osservazione
$id_tipo_pendenza = array('id_tipo_pendenza_example'); // string[] | Filtra per uno o piu' identificativi di tipologia di pendenza
$direzione = Direzione ABC; // string | Identificativo della direzione interna all'ente creditore
$divisione = Divisione001; // string | Identificativo della divisione interna all'ente creditore
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$mostra_spontanei_non_pagati = false; // bool | Visualizza solo le pendenze di tipo Spontaneo non pagate
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati

try {
    $result = $apiInstance->findPendenze($pagina, $risultati_per_pagina, $ordinamento, $campi, $id_dominio, $id_a2_a, $id_debitore, $stato, $id_pagamento, $id_pendenza, $data_da, $data_a, $id_tipo_pendenza, $direzione, $divisione, $iuv, $mostra_spontanei_non_pagati, $metadati_paginazione, $max_risultati);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->findPendenze: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **ordinamento** | **string**| csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default discendente)  * dataCaricamento  * dataValidita  * dataScadenza  * stato  * smart (solo se attivo il filtro &#39;idDebitore&#39;) | [optional] [default to &#39;+dataCaricamento&#39;] |
| **campi** | **string**| csv dei campi da includere nella risposta (default tutti) | [optional] |
| **id_dominio** | **string**| Identificativo del dominio beneficiario | [optional] |
| **id_a2_a** | **string**| Identificativo del gestionale proprietario della pendenza | [optional] |
| **id_debitore** | **string**| Identificativo del soggetto debitore della pendenza | [optional] |
| **stato** | [**\GovPay\Backoffice\Model\StatoPendenza**](../Model/.md)| Filtro sullo stato della pendenza | [optional] |
| **id_pagamento** | **string**| Identificativo della richiesta di pagamento | [optional] |
| **id_pendenza** | **string**| Identificativo della pendenza nel gestionale proprietario | [optional] |
| **data_da** | **\DateTime**| Inizio della finestra temporale di osservazione | [optional] |
| **data_a** | **\DateTime**| Fine della finestra temporale di osservazione | [optional] |
| **id_tipo_pendenza** | [**string[]**](../Model/string.md)| Filtra per uno o piu&#39; identificativi di tipologia di pendenza | [optional] |
| **direzione** | **string**| Identificativo della direzione interna all&#39;ente creditore | [optional] |
| **divisione** | **string**| Identificativo della divisione interna all&#39;ente creditore | [optional] |
| **iuv** | **string**| Identificativo univoco di versamento | [optional] |
| **mostra_spontanei_non_pagati** | **bool**| Visualizza solo le pendenze di tipo Spontaneo non pagate | [optional] [default to false] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\FindPendenze200Response**](../Model/FindPendenze200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findTracciatiPendenze()`

```php
findTracciatiPendenze($pagina, $risultati_per_pagina, $id_dominio, $stato_tracciato_pendenza, $metadati_paginazione, $max_risultati): \GovPay\Backoffice\Model\FindTracciatiPendenze200Response
```

Elenco dei Tracciati caricati

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio beneficiario
$stato_tracciato_pendenza = new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\StatoTracciatoPendenza(); // \GovPay\Backoffice\Model\StatoTracciatoPendenza | Filtro sullo stato del tracciato
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati

try {
    $result = $apiInstance->findTracciatiPendenze($pagina, $risultati_per_pagina, $id_dominio, $stato_tracciato_pendenza, $metadati_paginazione, $max_risultati);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->findTracciatiPendenze: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **id_dominio** | **string**| Identificativo del dominio beneficiario | [optional] |
| **stato_tracciato_pendenza** | [**\GovPay\Backoffice\Model\StatoTracciatoPendenza**](../Model/.md)| Filtro sullo stato del tracciato | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\FindTracciatiPendenze200Response**](../Model/FindTracciatiPendenze200Response.md)

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
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
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
getAvvisiDocumento($id_a2_a, $id_dominio, $numero_documento, $lingua_secondaria, $numeri_avviso): \SplFileObject
```

Documento di pagamento

Fornisce un documento di pagamento e gli avvisi ad esso associati

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_a2_a = 'id_a2_a_example'; // string | Identificativo del gestionale
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio dell'ente
$numero_documento = 'numero_documento_example'; // string | Identificativo del documento di pagamento
$lingua_secondaria = new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\LinguaSecondaria(); // \GovPay\Backoffice\Model\LinguaSecondaria | Indica se creare l'avviso in modalita' multilingua e quale seconda lingua affiancare all'italiano all'interno dell'avviso
$numeri_avviso = ["123456789012345678"]; // string[] | Indica i numeri avviso da includere nelle stampe dei documenti

try {
    $result = $apiInstance->getAvvisiDocumento($id_a2_a, $id_dominio, $numero_documento, $lingua_secondaria, $numeri_avviso);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->getAvvisiDocumento: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_a2_a** | **string**| Identificativo del gestionale | |
| **id_dominio** | **string**| Identificativo del dominio dell&#39;ente | |
| **numero_documento** | **string**| Identificativo del documento di pagamento | |
| **lingua_secondaria** | [**\GovPay\Backoffice\Model\LinguaSecondaria**](../Model/.md)| Indica se creare l&#39;avviso in modalita&#39; multilingua e quale seconda lingua affiancare all&#39;italiano all&#39;interno dell&#39;avviso | [optional] |
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
getAvviso($id_dominio, $numero_avviso, $lingua_secondaria): \GovPay\Backoffice\Model\Avviso
```

Avviso di pagamento

Fornisce un avviso di pagamento o la pendenza ad esso associata

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio dell'ente
$numero_avviso = 'numero_avviso_example'; // string | Identificativo dell'avviso di pagamento
$lingua_secondaria = new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\LinguaSecondaria(); // \GovPay\Backoffice\Model\LinguaSecondaria | Indica se creare l'avviso in modalita' multilingua e quale seconda lingua affiancare all'italiano all'interno dell'avviso

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
| **numero_avviso** | **string**| Identificativo dell&#39;avviso di pagamento | |
| **lingua_secondaria** | [**\GovPay\Backoffice\Model\LinguaSecondaria**](../Model/.md)| Indica se creare l&#39;avviso in modalita&#39; multilingua e quale seconda lingua affiancare all&#39;italiano all&#39;interno dell&#39;avviso | [optional] |

### Return type

[**\GovPay\Backoffice\Model\Avviso**](../Model/Avviso.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/pdf`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getEsitoTracciatoPendenze()`

```php
getEsitoTracciatoPendenze($id): \GovPay\Backoffice\Model\TracciatoPendenzeEsito
```

Dettaglio esito di un Tracciato

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | identificativo di un tracciato

try {
    $result = $apiInstance->getEsitoTracciatoPendenze($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->getEsitoTracciatoPendenze: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| identificativo di un tracciato | |

### Return type

[**\GovPay\Backoffice\Model\TracciatoPendenzeEsito**](../Model/TracciatoPendenzeEsito.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `text/csv`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getPendenza()`

```php
getPendenza($id_a2_a, $id_pendenza): \GovPay\Backoffice\Model\Pendenza
```

Dettaglio di una Pendenza per identificativo

Acquisisce il dettaglio di una pendenza, comprensivo dei dati di pagamento.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
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

[**\GovPay\Backoffice\Model\Pendenza**](../Model/Pendenza.md)

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
getPendenzaByAvviso($id_dominio, $numero_avviso): \GovPay\Backoffice\Model\Pendenza
```

Dettaglio di una pendenza per riferimento Avviso

Fornisce il dettaglio di una pendenza.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
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

[**\GovPay\Backoffice\Model\Pendenza**](../Model/Pendenza.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getRichiestaTracciatoPendenze()`

```php
getRichiestaTracciatoPendenze($id): \GovPay\Backoffice\Model\TracciatoPendenzePost
```

Tracciato di richiesta

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | identificativo di un tracciato

try {
    $result = $apiInstance->getRichiestaTracciatoPendenze($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->getRichiestaTracciatoPendenze: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| identificativo di un tracciato | |

### Return type

[**\GovPay\Backoffice\Model\TracciatoPendenzePost**](../Model/TracciatoPendenzePost.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `text/csv`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getStampeTracciatoPendenze()`

```php
getStampeTracciatoPendenze($id): object
```

Avvisi di pagamento relativi al Tracciato

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | identificativo di un tracciato

try {
    $result = $apiInstance->getStampeTracciatoPendenze($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->getStampeTracciatoPendenze: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| identificativo di un tracciato | |

### Return type

**object**

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/zip`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getTracciatoPendenze()`

```php
getTracciatoPendenze($id): \GovPay\Backoffice\Model\TracciatoPendenze
```

Dettaglio di un Tracciato

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | identificativo di un tracciato

try {
    $result = $apiInstance->getTracciatoPendenze($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PendenzeApi->getTracciatoPendenze: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| identificativo di un tracciato | |

### Return type

[**\GovPay\Backoffice\Model\TracciatoPendenze**](../Model/TracciatoPendenze.md)

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

Annullamento o ripristino di una Pendenza

L'aggiornamento dello stato di una pendenza in `Annullata` può essere completato solo se la pendenza si trova in stato `Da pagare`. Viceversa il ripristino dello stato `Da pagare` può essere disposto solo in caso di pendenza `Annullata`. L'aggiornamento è consentito solo se di dispone di ACL per il servizio Pendenze di tipo scrittura.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\PendenzeApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_a2_a = 'id_a2_a_example'; // string | Identificativo del gestionale
$id_pendenza = 'id_pendenza_example'; // string | Identificativo della pendenza
$patch_op = array(new \GovPay\Backoffice\Model\PatchOp()); // \GovPay\Backoffice\Model\PatchOp[]

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
| **patch_op** | [**\GovPay\Backoffice\Model\PatchOp[]**](../Model/PatchOp.md)|  | |

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
