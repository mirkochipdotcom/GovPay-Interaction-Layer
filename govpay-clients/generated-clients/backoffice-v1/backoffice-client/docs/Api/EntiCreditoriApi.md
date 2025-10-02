# GovPay\Backoffice\EntiCreditoriApi

All URIs are relative to http://localhost/govpay/backend/api/backoffice/rs/basic/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**addContiAccredito()**](EntiCreditoriApi.md#addContiAccredito) | **PUT** /domini/{idDominio}/contiAccredito/{ibanAccredito} | Aggiunge o aggiorna un iban di accredito al dominio beneficiario |
| [**addDominio()**](EntiCreditoriApi.md#addDominio) | **PUT** /domini/{idDominio} | Aggiunge o aggiorna un dominio beneficiario |
| [**addEntrata()**](EntiCreditoriApi.md#addEntrata) | **PUT** /entrate/{idEntrata} | Aggiunge o aggiorna una entrata |
| [**addEntrataDominio()**](EntiCreditoriApi.md#addEntrataDominio) | **PUT** /domini/{idDominio}/entrate/{idEntrata} | Aggiunge o aggiorna una entrata al dominio beneficiario |
| [**addIntermediario()**](EntiCreditoriApi.md#addIntermediario) | **PUT** /intermediari/{idIntermediario} | Aggiunge o aggiorna un intermediario |
| [**addStazione()**](EntiCreditoriApi.md#addStazione) | **PUT** /intermediari/{idIntermediario}/stazioni/{idStazione} | Aggiunge o aggiorna una stazione |
| [**addTipoPendenza()**](EntiCreditoriApi.md#addTipoPendenza) | **PUT** /tipiPendenza/{idTipoPendenza} | Aggiunge o aggiorna una tipologia di pendenza |
| [**addTipoPendenzaDominio()**](EntiCreditoriApi.md#addTipoPendenzaDominio) | **PUT** /domini/{idDominio}/tipiPendenza/{idTipoPendenza} | Aggiunge o aggiorna una tipologia di pendenza al dominio beneficiario |
| [**addUnitaOperativa()**](EntiCreditoriApi.md#addUnitaOperativa) | **PUT** /domini/{idDominio}/unitaOperative/{idUnitaOperativa} | Aggiunge o aggiorna un&#39;unità operativa al dominio beneficiario |
| [**findContiAccredito()**](EntiCreditoriApi.md#findContiAccredito) | **GET** /domini/{idDominio}/contiAccredito | Elenco degli iban di accredito del beneficiario |
| [**findDomini()**](EntiCreditoriApi.md#findDomini) | **GET** /domini | Elenco dei domini beneficiari censiti |
| [**findEntrate()**](EntiCreditoriApi.md#findEntrate) | **GET** /entrate | Elenco delle tipologie di entrata |
| [**findEntrateDominio()**](EntiCreditoriApi.md#findEntrateDominio) | **GET** /domini/{idDominio}/entrate | Elenco delle tipologie di entrata del dominio |
| [**findIntermediari()**](EntiCreditoriApi.md#findIntermediari) | **GET** /intermediari | Elenco degli intermediari |
| [**findStazioni()**](EntiCreditoriApi.md#findStazioni) | **GET** /intermediari/{idIntermediario}/stazioni | Elenco delle stazioni di un intermediario |
| [**findTipiPendenza()**](EntiCreditoriApi.md#findTipiPendenza) | **GET** /tipiPendenza | Elenco delle tipologie di pendenza |
| [**findTipiPendenzaDominio()**](EntiCreditoriApi.md#findTipiPendenzaDominio) | **GET** /domini/{idDominio}/tipiPendenza | Elenco delle tipologie di pendenza del dominio |
| [**findUnitaOperative()**](EntiCreditoriApi.md#findUnitaOperative) | **GET** /domini/{idDominio}/unitaOperative | Elenco delle unità operative del beneficiario |
| [**getContiAccredito()**](EntiCreditoriApi.md#getContiAccredito) | **GET** /domini/{idDominio}/contiAccredito/{ibanAccredito} | Lettura dei dati di un iban di accredito |
| [**getDominio()**](EntiCreditoriApi.md#getDominio) | **GET** /domini/{idDominio} | Lettura dei dati di un dominio beneficiario |
| [**getEntrata()**](EntiCreditoriApi.md#getEntrata) | **GET** /entrate/{idEntrata} | Lettura dei dati di una tipologia di entrata |
| [**getEntrataDominio()**](EntiCreditoriApi.md#getEntrataDominio) | **GET** /domini/{idDominio}/entrate/{idEntrata} | Lettura dei dati di una entrata |
| [**getIntermediario()**](EntiCreditoriApi.md#getIntermediario) | **GET** /intermediari/{idIntermediario} | Informazioni di un intermediario |
| [**getStazione()**](EntiCreditoriApi.md#getStazione) | **GET** /intermediari/{idIntermediario}/stazioni/{idStazione} | Informazioni di una stazione intermediario |
| [**getTipoPendenza()**](EntiCreditoriApi.md#getTipoPendenza) | **GET** /tipiPendenza/{idTipoPendenza} | Lettura dei dati di una tipologia di pendenza |
| [**getTipoPendenzaDominio()**](EntiCreditoriApi.md#getTipoPendenzaDominio) | **GET** /domini/{idDominio}/tipiPendenza/{idTipoPendenza} | Lettura dei dati di una tipologia di pendenza del dominio |
| [**getUnitaOperativa()**](EntiCreditoriApi.md#getUnitaOperativa) | **GET** /domini/{idDominio}/unitaOperative/{idUnitaOperativa} | Lettura dei dati di una unità operativa |


## `addContiAccredito()`

```php
addContiAccredito($id_dominio, $iban_accredito, $conti_accredito_post)
```

Aggiunge o aggiorna un iban di accredito al dominio beneficiario

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Codice fiscale del beneficiario
$iban_accredito = 'iban_accredito_example'; // string | Iban di accredito
$conti_accredito_post = new \GovPay\Backoffice\Model\ContiAccreditoPost(); // \GovPay\Backoffice\Model\ContiAccreditoPost

try {
    $apiInstance->addContiAccredito($id_dominio, $iban_accredito, $conti_accredito_post);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->addContiAccredito: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Codice fiscale del beneficiario | |
| **iban_accredito** | **string**| Iban di accredito | |
| **conti_accredito_post** | [**\GovPay\Backoffice\Model\ContiAccreditoPost**](../Model/ContiAccreditoPost.md)|  | [optional] |

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

## `addDominio()`

```php
addDominio($id_dominio, $dominio_post)
```

Aggiunge o aggiorna un dominio beneficiario

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Codice fiscale del beneficiario
$dominio_post = new \GovPay\Backoffice\Model\DominioPost(); // \GovPay\Backoffice\Model\DominioPost

try {
    $apiInstance->addDominio($id_dominio, $dominio_post);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->addDominio: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Codice fiscale del beneficiario | |
| **dominio_post** | [**\GovPay\Backoffice\Model\DominioPost**](../Model/DominioPost.md)|  | [optional] |

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

## `addEntrata()`

```php
addEntrata($id_entrata, $tipo_entrata_post)
```

Aggiunge o aggiorna una entrata

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_entrata = 'id_entrata_example'; // string | Identificativo della tipologia di entrata
$tipo_entrata_post = new \GovPay\Backoffice\Model\TipoEntrataPost(); // \GovPay\Backoffice\Model\TipoEntrataPost

try {
    $apiInstance->addEntrata($id_entrata, $tipo_entrata_post);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->addEntrata: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_entrata** | **string**| Identificativo della tipologia di entrata | |
| **tipo_entrata_post** | [**\GovPay\Backoffice\Model\TipoEntrataPost**](../Model/TipoEntrataPost.md)|  | [optional] |

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

## `addEntrataDominio()`

```php
addEntrataDominio($id_dominio, $id_entrata, $entrata_post)
```

Aggiunge o aggiorna una entrata al dominio beneficiario

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Codice fiscale del beneficiario
$id_entrata = 'id_entrata_example'; // string | Identificativo della tipologia di entrata
$entrata_post = new \GovPay\Backoffice\Model\EntrataPost(); // \GovPay\Backoffice\Model\EntrataPost

try {
    $apiInstance->addEntrataDominio($id_dominio, $id_entrata, $entrata_post);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->addEntrataDominio: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Codice fiscale del beneficiario | |
| **id_entrata** | **string**| Identificativo della tipologia di entrata | |
| **entrata_post** | [**\GovPay\Backoffice\Model\EntrataPost**](../Model/EntrataPost.md)|  | [optional] |

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

## `addIntermediario()`

```php
addIntermediario($id_intermediario, $intermediario_post)
```

Aggiunge o aggiorna un intermediario

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_intermediario = 'id_intermediario_example'; // string | Identificativo dell'intermediario
$intermediario_post = new \GovPay\Backoffice\Model\IntermediarioPost(); // \GovPay\Backoffice\Model\IntermediarioPost

try {
    $apiInstance->addIntermediario($id_intermediario, $intermediario_post);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->addIntermediario: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_intermediario** | **string**| Identificativo dell&#39;intermediario | |
| **intermediario_post** | [**\GovPay\Backoffice\Model\IntermediarioPost**](../Model/IntermediarioPost.md)|  | [optional] |

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

## `addStazione()`

```php
addStazione($id_intermediario, $id_stazione, $stazione_post)
```

Aggiunge o aggiorna una stazione

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_intermediario = 'id_intermediario_example'; // string | Identificativo dell'intermediario
$id_stazione = 'id_stazione_example'; // string | Identificativo della stazione
$stazione_post = new \GovPay\Backoffice\Model\StazionePost(); // \GovPay\Backoffice\Model\StazionePost

try {
    $apiInstance->addStazione($id_intermediario, $id_stazione, $stazione_post);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->addStazione: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_intermediario** | **string**| Identificativo dell&#39;intermediario | |
| **id_stazione** | **string**| Identificativo della stazione | |
| **stazione_post** | [**\GovPay\Backoffice\Model\StazionePost**](../Model/StazionePost.md)|  | [optional] |

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

## `addTipoPendenza()`

```php
addTipoPendenza($id_tipo_pendenza, $tipo_pendenza_post)
```

Aggiunge o aggiorna una tipologia di pendenza

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_tipo_pendenza = 'id_tipo_pendenza_example'; // string | Identificativo della tipologia di entrata
$tipo_pendenza_post = new \GovPay\Backoffice\Model\TipoPendenzaPost(); // \GovPay\Backoffice\Model\TipoPendenzaPost

try {
    $apiInstance->addTipoPendenza($id_tipo_pendenza, $tipo_pendenza_post);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->addTipoPendenza: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_tipo_pendenza** | **string**| Identificativo della tipologia di entrata | |
| **tipo_pendenza_post** | [**\GovPay\Backoffice\Model\TipoPendenzaPost**](../Model/TipoPendenzaPost.md)|  | [optional] |

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

## `addTipoPendenzaDominio()`

```php
addTipoPendenzaDominio($id_dominio, $id_tipo_pendenza, $tipo_pendenza_dominio_post)
```

Aggiunge o aggiorna una tipologia di pendenza al dominio beneficiario

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Codice fiscale del beneficiario
$id_tipo_pendenza = 'id_tipo_pendenza_example'; // string | Identificativo della tipologia di pendenza
$tipo_pendenza_dominio_post = new \GovPay\Backoffice\Model\TipoPendenzaDominioPost(); // \GovPay\Backoffice\Model\TipoPendenzaDominioPost

try {
    $apiInstance->addTipoPendenzaDominio($id_dominio, $id_tipo_pendenza, $tipo_pendenza_dominio_post);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->addTipoPendenzaDominio: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Codice fiscale del beneficiario | |
| **id_tipo_pendenza** | **string**| Identificativo della tipologia di pendenza | |
| **tipo_pendenza_dominio_post** | [**\GovPay\Backoffice\Model\TipoPendenzaDominioPost**](../Model/TipoPendenzaDominioPost.md)|  | [optional] |

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

## `addUnitaOperativa()`

```php
addUnitaOperativa($id_dominio, $id_unita_operativa, $unita_operativa_post)
```

Aggiunge o aggiorna un'unità operativa al dominio beneficiario

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Codice fiscale del beneficiario
$id_unita_operativa = 'id_unita_operativa_example'; // string | Identificativo dell'unita' operativa del dominio beneficiario
$unita_operativa_post = new \GovPay\Backoffice\Model\UnitaOperativaPost(); // \GovPay\Backoffice\Model\UnitaOperativaPost

try {
    $apiInstance->addUnitaOperativa($id_dominio, $id_unita_operativa, $unita_operativa_post);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->addUnitaOperativa: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Codice fiscale del beneficiario | |
| **id_unita_operativa** | **string**| Identificativo dell&#39;unita&#39; operativa del dominio beneficiario | |
| **unita_operativa_post** | [**\GovPay\Backoffice\Model\UnitaOperativaPost**](../Model/UnitaOperativaPost.md)|  | [optional] |

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

## `findContiAccredito()`

```php
findContiAccredito($id_dominio, $pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $iban, $metadati_paginazione, $max_risultati): \GovPay\Backoffice\Model\FindContiAccredito200Response
```

Elenco degli iban di accredito del beneficiario

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$ordinamento = '+ibanAccredito'; // string | csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * ragioneSociale
$campi = 'campi_example'; // string | csv dei campi da includere nella risposta (default tutti)
$abilitato = True; // bool | Restrizione ai soli elementi abilitati o disabilitati
$iban = 'iban_example'; // string | filtro per Iban (anche parziale)
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati

try {
    $result = $apiInstance->findContiAccredito($id_dominio, $pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $iban, $metadati_paginazione, $max_risultati);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->findContiAccredito: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo del dominio | |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **ordinamento** | **string**| csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * ragioneSociale | [optional] [default to &#39;+ibanAccredito&#39;] |
| **campi** | **string**| csv dei campi da includere nella risposta (default tutti) | [optional] |
| **abilitato** | **bool**| Restrizione ai soli elementi abilitati o disabilitati | [optional] |
| **iban** | **string**| filtro per Iban (anche parziale) | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\FindContiAccredito200Response**](../Model/FindContiAccredito200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findDomini()`

```php
findDomini($pagina, $risultati_per_pagina, $campi, $abilitato, $ordinamento, $id_stazione, $associati, $form, $id_dominio, $ragione_sociale, $metadati_paginazione, $max_risultati, $intermediato): \GovPay\Backoffice\Model\FindDomini200Response
```

Elenco dei domini beneficiari censiti

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$campi = 'campi_example'; // string | csv dei campi da includere nella risposta (default tutti)
$abilitato = True; // bool | Restrizione ai soli elementi abilitati o disabilitati
$ordinamento = '+ragioneSociale'; // string | csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * ragioneSociale
$id_stazione = 'id_stazione_example'; // string | Restrizione ai soli domini associati alla stazione indicata
$associati = True; // bool | Restrizione ai soli elementi associati all'utenza chiamante
$form = True; // bool | Restrizione ai soli elementi che mettono a disposizione la form di inserimento custom
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio beneficiario
$ragione_sociale = 'ragione_sociale_example'; // string | filtro per Ragione Sociale (anche parziale)
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati
$intermediato = True; // bool | filtro sui domini intermediati

try {
    $result = $apiInstance->findDomini($pagina, $risultati_per_pagina, $campi, $abilitato, $ordinamento, $id_stazione, $associati, $form, $id_dominio, $ragione_sociale, $metadati_paginazione, $max_risultati, $intermediato);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->findDomini: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **campi** | **string**| csv dei campi da includere nella risposta (default tutti) | [optional] |
| **abilitato** | **bool**| Restrizione ai soli elementi abilitati o disabilitati | [optional] |
| **ordinamento** | **string**| csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * ragioneSociale | [optional] [default to &#39;+ragioneSociale&#39;] |
| **id_stazione** | **string**| Restrizione ai soli domini associati alla stazione indicata | [optional] |
| **associati** | **bool**| Restrizione ai soli elementi associati all&#39;utenza chiamante | [optional] |
| **form** | **bool**| Restrizione ai soli elementi che mettono a disposizione la form di inserimento custom | [optional] |
| **id_dominio** | **string**| Identificativo del dominio beneficiario | [optional] |
| **ragione_sociale** | **string**| filtro per Ragione Sociale (anche parziale) | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |
| **intermediato** | **bool**| filtro sui domini intermediati | [optional] |

### Return type

[**\GovPay\Backoffice\Model\FindDomini200Response**](../Model/FindDomini200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findEntrate()`

```php
findEntrate($pagina, $risultati_per_pagina, $ordinamento, $campi, $metadati_paginazione, $max_risultati): \GovPay\Backoffice\Model\FindEntrate200Response
```

Elenco delle tipologie di entrata

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$ordinamento = '+idEntrata'; // string | csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * idEntrata
$campi = 'campi_example'; // string | csv dei campi da includere nella risposta (default tutti)
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati

try {
    $result = $apiInstance->findEntrate($pagina, $risultati_per_pagina, $ordinamento, $campi, $metadati_paginazione, $max_risultati);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->findEntrate: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **ordinamento** | **string**| csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * idEntrata | [optional] [default to &#39;+idEntrata&#39;] |
| **campi** | **string**| csv dei campi da includere nella risposta (default tutti) | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\FindEntrate200Response**](../Model/FindEntrate200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findEntrateDominio()`

```php
findEntrateDominio($id_dominio, $pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $descrizione, $metadati_paginazione, $max_risultati): \GovPay\Backoffice\Model\FindEntrateDominio200Response
```

Elenco delle tipologie di entrata del dominio

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$ordinamento = '+idEntrata'; // string | csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * idEntrata
$campi = 'campi_example'; // string | csv dei campi da includere nella risposta (default tutti)
$abilitato = True; // bool | Restrizione ai soli elementi abilitati o disabilitati
$descrizione = Imposta Municipale; // string | Filtro sulla descrizione dell'elemento
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati

try {
    $result = $apiInstance->findEntrateDominio($id_dominio, $pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $descrizione, $metadati_paginazione, $max_risultati);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->findEntrateDominio: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo del dominio | |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **ordinamento** | **string**| csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * idEntrata | [optional] [default to &#39;+idEntrata&#39;] |
| **campi** | **string**| csv dei campi da includere nella risposta (default tutti) | [optional] |
| **abilitato** | **bool**| Restrizione ai soli elementi abilitati o disabilitati | [optional] |
| **descrizione** | **string**| Filtro sulla descrizione dell&#39;elemento | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\FindEntrateDominio200Response**](../Model/FindEntrateDominio200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findIntermediari()`

```php
findIntermediari($pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $metadati_paginazione, $max_risultati): \GovPay\Backoffice\Model\FindIntermediari200Response
```

Elenco degli intermediari

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$ordinamento = '+denominazione'; // string | csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * denominazione
$campi = 'campi_example'; // string | csv dei campi da includere nella risposta (default tutti)
$abilitato = True; // bool | Restrizione ai soli elementi abilitati o disabilitati
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati

try {
    $result = $apiInstance->findIntermediari($pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $metadati_paginazione, $max_risultati);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->findIntermediari: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **ordinamento** | **string**| csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * denominazione | [optional] [default to &#39;+denominazione&#39;] |
| **campi** | **string**| csv dei campi da includere nella risposta (default tutti) | [optional] |
| **abilitato** | **bool**| Restrizione ai soli elementi abilitati o disabilitati | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\FindIntermediari200Response**](../Model/FindIntermediari200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findStazioni()`

```php
findStazioni($id_intermediario, $pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $metadati_paginazione, $max_risultati): \GovPay\Backoffice\Model\FindStazioni200Response
```

Elenco delle stazioni di un intermediario

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_intermediario = 'id_intermediario_example'; // string | Identificativo dell'intermediario
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$ordinamento = '+idStazione'; // string | csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * idStazione
$campi = 'campi_example'; // string | csv dei campi da includere nella risposta (default tutti)
$abilitato = True; // bool | Restrizione ai soli elementi abilitati o disabilitati
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati

try {
    $result = $apiInstance->findStazioni($id_intermediario, $pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $metadati_paginazione, $max_risultati);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->findStazioni: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_intermediario** | **string**| Identificativo dell&#39;intermediario | |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **ordinamento** | **string**| csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * idStazione | [optional] [default to &#39;+idStazione&#39;] |
| **campi** | **string**| csv dei campi da includere nella risposta (default tutti) | [optional] |
| **abilitato** | **bool**| Restrizione ai soli elementi abilitati o disabilitati | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\FindStazioni200Response**](../Model/FindStazioni200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findTipiPendenza()`

```php
findTipiPendenza($pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $associati, $form, $id_tipo_pendenza, $descrizione, $trasformazione, $non_associati, $metadati_paginazione, $max_risultati): \GovPay\Backoffice\Model\FindTipiPendenza200Response
```

Elenco delle tipologie di pendenza

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$ordinamento = '+idEntrata'; // string | csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * idEntrata
$campi = 'campi_example'; // string | csv dei campi da includere nella risposta (default tutti)
$abilitato = True; // bool | Restrizione ai soli elementi abilitati o disabilitati
$associati = True; // bool | Restrizione ai soli elementi associati all'utenza chiamante
$form = True; // bool | Restrizione ai soli elementi che mettono a disposizione la form di inserimento custom
$id_tipo_pendenza = IMU; // string | Identificativo della tipologia di pendenza
$descrizione = Imposta Municipale; // string | Filtro sulla descrizione dell'elemento
$trasformazione = True; // bool | Restrizione ai soli elementi che mettono a disposizione i template di trasformazione per i tracciati CSV.
$non_associati = 'non_associati_example'; // string | Restrizione ai soli elementi non sono associati al dominio indicato come parametro.   Se il dominio e' inesistente vengono restituiti tutti i risultati.
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati

try {
    $result = $apiInstance->findTipiPendenza($pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $associati, $form, $id_tipo_pendenza, $descrizione, $trasformazione, $non_associati, $metadati_paginazione, $max_risultati);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->findTipiPendenza: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **ordinamento** | **string**| csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * idEntrata | [optional] [default to &#39;+idEntrata&#39;] |
| **campi** | **string**| csv dei campi da includere nella risposta (default tutti) | [optional] |
| **abilitato** | **bool**| Restrizione ai soli elementi abilitati o disabilitati | [optional] |
| **associati** | **bool**| Restrizione ai soli elementi associati all&#39;utenza chiamante | [optional] |
| **form** | **bool**| Restrizione ai soli elementi che mettono a disposizione la form di inserimento custom | [optional] |
| **id_tipo_pendenza** | **string**| Identificativo della tipologia di pendenza | [optional] |
| **descrizione** | **string**| Filtro sulla descrizione dell&#39;elemento | [optional] |
| **trasformazione** | **bool**| Restrizione ai soli elementi che mettono a disposizione i template di trasformazione per i tracciati CSV. | [optional] |
| **non_associati** | **string**| Restrizione ai soli elementi non sono associati al dominio indicato come parametro.   Se il dominio e&#39; inesistente vengono restituiti tutti i risultati. | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\FindTipiPendenza200Response**](../Model/FindTipiPendenza200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findTipiPendenzaDominio()`

```php
findTipiPendenzaDominio($id_dominio, $pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $associati, $form, $trasformazione, $descrizione, $metadati_paginazione, $max_risultati): \GovPay\Backoffice\Model\FindTipiPendenzaDominio200Response
```

Elenco delle tipologie di pendenza del dominio

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$ordinamento = '+descrizione'; // string | csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * idEntrata
$campi = 'campi_example'; // string | csv dei campi da includere nella risposta (default tutti)
$abilitato = True; // bool | Restrizione ai soli elementi abilitati o disabilitati
$associati = True; // bool | Restrizione ai soli elementi associati all'utenza chiamante
$form = True; // bool | Restrizione ai soli elementi che mettono a disposizione la form di inserimento custom
$trasformazione = True; // bool | Restrizione ai soli elementi che mettono a disposizione i template di trasformazione per i tracciati CSV.
$descrizione = Imposta Municipale; // string | Filtro sulla descrizione dell'elemento
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati

try {
    $result = $apiInstance->findTipiPendenzaDominio($id_dominio, $pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $associati, $form, $trasformazione, $descrizione, $metadati_paginazione, $max_risultati);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->findTipiPendenzaDominio: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo del dominio | |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **ordinamento** | **string**| csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * idEntrata | [optional] [default to &#39;+descrizione&#39;] |
| **campi** | **string**| csv dei campi da includere nella risposta (default tutti) | [optional] |
| **abilitato** | **bool**| Restrizione ai soli elementi abilitati o disabilitati | [optional] |
| **associati** | **bool**| Restrizione ai soli elementi associati all&#39;utenza chiamante | [optional] |
| **form** | **bool**| Restrizione ai soli elementi che mettono a disposizione la form di inserimento custom | [optional] |
| **trasformazione** | **bool**| Restrizione ai soli elementi che mettono a disposizione i template di trasformazione per i tracciati CSV. | [optional] |
| **descrizione** | **string**| Filtro sulla descrizione dell&#39;elemento | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\FindTipiPendenzaDominio200Response**](../Model/FindTipiPendenzaDominio200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `findUnitaOperative()`

```php
findUnitaOperative($id_dominio, $pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $associati, $ragione_sociale, $metadati_paginazione, $max_risultati): \GovPay\Backoffice\Model\FindUnitaOperative200Response
```

Elenco delle unità operative del beneficiario

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$ordinamento = '+ragioneSociale'; // string | csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * ragioneSociale
$campi = 'campi_example'; // string | csv dei campi da includere nella risposta (default tutti)
$abilitato = True; // bool | Restrizione ai soli elementi abilitati o disabilitati
$associati = True; // bool | Restrizione ai soli elementi associati all'utenza chiamante
$ragione_sociale = 'ragione_sociale_example'; // string | filtro per Ragione Sociale (anche parziale)
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati

try {
    $result = $apiInstance->findUnitaOperative($id_dominio, $pagina, $risultati_per_pagina, $ordinamento, $campi, $abilitato, $associati, $ragione_sociale, $metadati_paginazione, $max_risultati);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->findUnitaOperative: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Identificativo del dominio | |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **ordinamento** | **string**| csv dei campi su cui ordinare i risultati, preceduti da + o - per ascendente o discendente (default ascendente)  * ragioneSociale | [optional] [default to &#39;+ragioneSociale&#39;] |
| **campi** | **string**| csv dei campi da includere nella risposta (default tutti) | [optional] |
| **abilitato** | **bool**| Restrizione ai soli elementi abilitati o disabilitati | [optional] |
| **associati** | **bool**| Restrizione ai soli elementi associati all&#39;utenza chiamante | [optional] |
| **ragione_sociale** | **string**| filtro per Ragione Sociale (anche parziale) | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |

### Return type

[**\GovPay\Backoffice\Model\FindUnitaOperative200Response**](../Model/FindUnitaOperative200Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getContiAccredito()`

```php
getContiAccredito($id_dominio, $iban_accredito): \GovPay\Backoffice\Model\ContiAccredito
```

Lettura dei dati di un iban di accredito

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Codice fiscale del beneficiario
$iban_accredito = 'iban_accredito_example'; // string | Iban di accredito

try {
    $result = $apiInstance->getContiAccredito($id_dominio, $iban_accredito);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->getContiAccredito: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Codice fiscale del beneficiario | |
| **iban_accredito** | **string**| Iban di accredito | |

### Return type

[**\GovPay\Backoffice\Model\ContiAccredito**](../Model/ContiAccredito.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getDominio()`

```php
getDominio($id_dominio): \GovPay\Backoffice\Model\Dominio
```

Lettura dei dati di un dominio beneficiario

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Codice fiscale del beneficiario

try {
    $result = $apiInstance->getDominio($id_dominio);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->getDominio: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Codice fiscale del beneficiario | |

### Return type

[**\GovPay\Backoffice\Model\Dominio**](../Model/Dominio.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getEntrata()`

```php
getEntrata($id_entrata): \GovPay\Backoffice\Model\TipoEntrata
```

Lettura dei dati di una tipologia di entrata

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_entrata = 'id_entrata_example'; // string | Identificativo della tipologia di entrata

try {
    $result = $apiInstance->getEntrata($id_entrata);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->getEntrata: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_entrata** | **string**| Identificativo della tipologia di entrata | |

### Return type

[**\GovPay\Backoffice\Model\TipoEntrata**](../Model/TipoEntrata.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getEntrataDominio()`

```php
getEntrataDominio($id_dominio, $id_entrata): \GovPay\Backoffice\Model\Entrata
```

Lettura dei dati di una entrata

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Codice fiscale del beneficiario
$id_entrata = 'id_entrata_example'; // string | Identificativo della tipologia di entrata

try {
    $result = $apiInstance->getEntrataDominio($id_dominio, $id_entrata);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->getEntrataDominio: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Codice fiscale del beneficiario | |
| **id_entrata** | **string**| Identificativo della tipologia di entrata | |

### Return type

[**\GovPay\Backoffice\Model\Entrata**](../Model/Entrata.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getIntermediario()`

```php
getIntermediario($id_intermediario): \GovPay\Backoffice\Model\Intermediario
```

Informazioni di un intermediario

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_intermediario = 'id_intermediario_example'; // string | Identificativo dell'intermediario

try {
    $result = $apiInstance->getIntermediario($id_intermediario);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->getIntermediario: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_intermediario** | **string**| Identificativo dell&#39;intermediario | |

### Return type

[**\GovPay\Backoffice\Model\Intermediario**](../Model/Intermediario.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getStazione()`

```php
getStazione($id_intermediario, $id_stazione): \GovPay\Backoffice\Model\Stazione
```

Informazioni di una stazione intermediario

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_intermediario = 'id_intermediario_example'; // string | Identificativo dell'intermediario
$id_stazione = 'id_stazione_example'; // string | Identificativo della stazione

try {
    $result = $apiInstance->getStazione($id_intermediario, $id_stazione);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->getStazione: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_intermediario** | **string**| Identificativo dell&#39;intermediario | |
| **id_stazione** | **string**| Identificativo della stazione | |

### Return type

[**\GovPay\Backoffice\Model\Stazione**](../Model/Stazione.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getTipoPendenza()`

```php
getTipoPendenza($id_tipo_pendenza): \GovPay\Backoffice\Model\TipoPendenza
```

Lettura dei dati di una tipologia di pendenza

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_tipo_pendenza = 'id_tipo_pendenza_example'; // string | Identificativo della tipologia di entrata

try {
    $result = $apiInstance->getTipoPendenza($id_tipo_pendenza);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->getTipoPendenza: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_tipo_pendenza** | **string**| Identificativo della tipologia di entrata | |

### Return type

[**\GovPay\Backoffice\Model\TipoPendenza**](../Model/TipoPendenza.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getTipoPendenzaDominio()`

```php
getTipoPendenzaDominio($id_dominio, $id_tipo_pendenza): \GovPay\Backoffice\Model\TipoPendenzaDominio
```

Lettura dei dati di una tipologia di pendenza del dominio

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Codice fiscale del beneficiario
$id_tipo_pendenza = 'id_tipo_pendenza_example'; // string | Identificativo della tipologia di pendenza

try {
    $result = $apiInstance->getTipoPendenzaDominio($id_dominio, $id_tipo_pendenza);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->getTipoPendenzaDominio: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Codice fiscale del beneficiario | |
| **id_tipo_pendenza** | **string**| Identificativo della tipologia di pendenza | |

### Return type

[**\GovPay\Backoffice\Model\TipoPendenzaDominio**](../Model/TipoPendenzaDominio.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getUnitaOperativa()`

```php
getUnitaOperativa($id_dominio, $id_unita_operativa): \GovPay\Backoffice\Model\UnitaOperativa
```

Lettura dei dati di una unità operativa

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\EntiCreditoriApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Codice fiscale del beneficiario
$id_unita_operativa = 'id_unita_operativa_example'; // string | Identificativo dell'unità operativa

try {
    $result = $apiInstance->getUnitaOperativa($id_dominio, $id_unita_operativa);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling EntiCreditoriApi->getUnitaOperativa: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id_dominio** | **string**| Codice fiscale del beneficiario | |
| **id_unita_operativa** | **string**| Identificativo dell&#39;unità operativa | |

### Return type

[**\GovPay\Backoffice\Model\UnitaOperativa**](../Model/UnitaOperativa.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
