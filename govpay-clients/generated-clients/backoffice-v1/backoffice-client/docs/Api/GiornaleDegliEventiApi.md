# GovPay\Backoffice\GiornaleDegliEventiApi

All URIs are relative to http://localhost/govpay/backend/api/backoffice/rs/basic/v1, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**findEventi()**](GiornaleDegliEventiApi.md#findEventi) | **GET** /eventi | Giornale degli eventi |
| [**getEvento()**](GiornaleDegliEventiApi.md#getEvento) | **GET** /eventi/{id} | Dettaglio di un evento |


## `findEventi()`

```php
findEventi($pagina, $risultati_per_pagina, $id_dominio, $iuv, $ccp, $id_a2_a, $id_pendenza, $id_pagamento, $esito, $data_da, $data_a, $categoria_evento, $tipo_evento, $sottotipo_evento, $componente, $ruolo, $messaggi, $metadati_paginazione, $max_risultati, $severita_da, $severita_a): \GovPay\Backoffice\Model\FindEventi201Response
```

Giornale degli eventi

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\GiornaleDegliEventiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina dei risultati
$risultati_per_pagina = 25; // int | Numero di risultati richiesti (max 5000)
$id_dominio = 'id_dominio_example'; // string | Identificativo del dominio beneficiario
$iuv = 'iuv_example'; // string | Identificativo univoco di versamento
$ccp = 'ccp_example'; // string | Codice contesto pagamento
$id_a2_a = 'id_a2_a_example'; // string | Identificativo del gestionale proprietario della pendenza
$id_pendenza = 'id_pendenza_example'; // string | Identificativo della pendenza nel gestionale proprietario
$id_pagamento = c8be909b-2feb-4ffa-8f98-704462abbd1d; // string | Identificativo della richiesta di pagamento
$esito = new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\EsitoEvento(); // \GovPay\Backoffice\Model\EsitoEvento | Filtro per esito evento
$data_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inizio della finestra temporale di osservazione
$data_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Fine della finestra temporale di osservazione
$categoria_evento = new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\CategoriaEvento(); // \GovPay\Backoffice\Model\CategoriaEvento | Filtro per categoria evento
$tipo_evento = 'tipo_evento_example'; // string | filtro per tipologia evento
$sottotipo_evento = 'sottotipo_evento_example'; // string | filtro per sottotipo evento
$componente = new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\ComponenteEvento(); // \GovPay\Backoffice\Model\ComponenteEvento | Filtro per componente evento
$ruolo = new \GovPay\Backoffice\Model\\GovPay\Backoffice\Model\RuoloEvento(); // \GovPay\Backoffice\Model\RuoloEvento | filtro per ruolo evento
$messaggi = True; // bool | Include nella risposta le informazioni sui messaggi scambiati
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati
$severita_da = 56; // int | filtro per severita errore
$severita_a = 56; // int | filtro per severita errore

try {
    $result = $apiInstance->findEventi($pagina, $risultati_per_pagina, $id_dominio, $iuv, $ccp, $id_a2_a, $id_pendenza, $id_pagamento, $esito, $data_da, $data_a, $categoria_evento, $tipo_evento, $sottotipo_evento, $componente, $ruolo, $messaggi, $metadati_paginazione, $max_risultati, $severita_da, $severita_a);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling GiornaleDegliEventiApi->findEventi: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina dei risultati | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| Numero di risultati richiesti (max 5000) | [optional] [default to 25] |
| **id_dominio** | **string**| Identificativo del dominio beneficiario | [optional] |
| **iuv** | **string**| Identificativo univoco di versamento | [optional] |
| **ccp** | **string**| Codice contesto pagamento | [optional] |
| **id_a2_a** | **string**| Identificativo del gestionale proprietario della pendenza | [optional] |
| **id_pendenza** | **string**| Identificativo della pendenza nel gestionale proprietario | [optional] |
| **id_pagamento** | **string**| Identificativo della richiesta di pagamento | [optional] |
| **esito** | [**\GovPay\Backoffice\Model\EsitoEvento**](../Model/.md)| Filtro per esito evento | [optional] |
| **data_da** | **\DateTime**| Inizio della finestra temporale di osservazione | [optional] |
| **data_a** | **\DateTime**| Fine della finestra temporale di osservazione | [optional] |
| **categoria_evento** | [**\GovPay\Backoffice\Model\CategoriaEvento**](../Model/.md)| Filtro per categoria evento | [optional] |
| **tipo_evento** | **string**| filtro per tipologia evento | [optional] |
| **sottotipo_evento** | **string**| filtro per sottotipo evento | [optional] |
| **componente** | [**\GovPay\Backoffice\Model\ComponenteEvento**](../Model/.md)| Filtro per componente evento | [optional] |
| **ruolo** | [**\GovPay\Backoffice\Model\RuoloEvento**](../Model/.md)| filtro per ruolo evento | [optional] |
| **messaggi** | **bool**| Include nella risposta le informazioni sui messaggi scambiati | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |
| **severita_da** | **int**| filtro per severita errore | [optional] |
| **severita_a** | **int**| filtro per severita errore | [optional] |

### Return type

[**\GovPay\Backoffice\Model\FindEventi201Response**](../Model/FindEventi201Response.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getEvento()`

```php
getEvento($id): \GovPay\Backoffice\Model\Evento
```

Dettaglio di un evento

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Backoffice\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Backoffice\Api\GiornaleDegliEventiApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 'id_example'; // string | Identificativo Evento

try {
    $result = $apiInstance->getEvento($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling GiornaleDegliEventiApi->getEvento: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **string**| Identificativo Evento | |

### Return type

[**\GovPay\Backoffice\Model\Evento**](../Model/Evento.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`, `application/problem+json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
