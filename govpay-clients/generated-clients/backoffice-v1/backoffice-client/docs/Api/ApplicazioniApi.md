# GovPay\Backoffice\ApplicazioniApi

All URIs are relative to http://localhost/govpay/backend/api/backoffice/rs/basic/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**addApplicazione()**](ApplicazioniApi.md#addApplicazione) | **PUT** /applicazioni/{idA2A} | Aggiunge o aggiorna un&#39;applicazione |
| [**findApplicazioni()**](ApplicazioniApi.md#findApplicazioni) | **GET** /applicazioni | Elenco delle applicazioni censite su sistema |
| [**getApplicazione()**](ApplicazioniApi.md#getApplicazione) | **GET** /applicazioni/{idA2A} | Lettura dei dati di una applicazione |
| [**updateApplicazione()**](ApplicazioniApi.md#updateApplicazione) | **PATCH** /applicazioni/{idA2A} | Aggiorna selettivamente campi di un Applicazione |


## `addApplicazione()`

```php
addApplicazione($id_a2_a, $applicazione_post)
```

Aggiunge o aggiorna un'applicazione

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\ApplicazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_a2_a = 'id_a2_a_example'; // string | Identificativo dell'applicazione
$applicazione_post = new \GovPay\Backoffice\Model\ApplicazionePost(); // \GovPay\Backoffice\Model\ApplicazionePost

try {
    $apiInstance->addApplicazione($id_a2_a, $applicazione_post);
} catch (Exception $e) {
    echo 'Exception when calling ApplicazioniApi->addApplicazione: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_a2_a** | **string**| Identificativo dell&#39;applicazione | |
| **applicazione_post** | [**\GovPay\Backoffice\Model\ApplicazionePost**](../Model/ApplicazionePost.md)|  | [optional] |

### Return type

void (empty response body)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findApplicazioni()`

```php
findApplicazioni($pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $id_a2_a, $principal, $metadati_paginazione, $max_risultati): \GovPay\Backoffice\Model\FindApplicazioni200Response
```

Elenco delle applicazioni censite su sistema

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\ApplicazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$ordinamento = '+idA2A'; // string | csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * idA2A
$campi = 'campi_example'; // string | csv dei campi da includere nella risposta (default tutti)
$abilitato = True; // bool | Restrizione ai soli elementi abilitati o disabilitati
$id_a2_a = 'id_a2_a_example'; // string | Identificativo del gestionale proprietario della pendenza
$principal = 'principal_example'; // string | Restrizione ai soli elementi con principal indicato
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati

try {
    $result = $apiInstance->findApplicazioni($pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $id_a2_a, $principal, $metadati_paginazione, $max_risultati);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ApplicazioniApi->findApplicazioni: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **ordinamento** | **string**| csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * idA2A | [optional] [default to &#39;+idA2A&#39;] |
| **campi** | **string**| csv dei campi da includere nella risposta (default tutti) | [optional] |
| **abilitato** | **bool**| Restrizione ai soli elementi abilitati o disabilitati | [optional] |
| **id_a2_a** | **string**| Identificativo del gestionale proprietario della pendenza | [optional] |
| **principal** | **string**| Restrizione ai soli elementi con principal indicato | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\FindApplicazioni200Response**](../Model/FindApplicazioni200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getApplicazione()`

```php
getApplicazione($id_a2_a): \GovPay\Backoffice\Model\Applicazione
```

Lettura dei dati di una applicazione

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\ApplicazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_a2_a = 'id_a2_a_example'; // string | Identificativo dell'applicazione

try {
    $result = $apiInstance->getApplicazione($id_a2_a);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ApplicazioniApi->getApplicazione: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_a2_a** | **string**| Identificativo dell&#39;applicazione | |

### Return type

[**\GovPay\Backoffice\Model\Applicazione**](../Model/Applicazione.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `updateApplicazione()`

```php
updateApplicazione($id_a2_a, $patch_op): \GovPay\Backoffice\Model\Applicazione
```

Aggiorna selettivamente campi di un Applicazione

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\ApplicazioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_a2_a = 'id_a2_a_example'; // string | Identificativo dell'applicazione
$patch_op = array(new \GovPay\Backoffice\Model\PatchOp()); // \GovPay\Backoffice\Model\PatchOp[]

try {
    $result = $apiInstance->updateApplicazione($id_a2_a, $patch_op);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ApplicazioniApi->updateApplicazione: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_a2_a** | **string**| Identificativo dell&#39;applicazione | |
| **patch_op** | [**\GovPay\Backoffice\Model\PatchOp[]**](../Model/PatchOp.md)|  | |

### Return type

[**\GovPay\Backoffice\Model\Applicazione**](../Model/Applicazione.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: `application/json-patch+json`
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
