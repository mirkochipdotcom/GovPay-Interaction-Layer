# GovPay\Ragioneria\RiscossioniApi

All URIs are relative to http://localhost/govpay/backend/api/ragioneria/rs/basic/v3, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**findRiscossioni()**](RiscossioniApi.md#findRiscossioni) | **GET** /riscossioni | Elenco degli importi riscossi o stornati |
| [**getRiscossione()**](RiscossioniApi.md#getRiscossione) | **GET** /riscossioni/{idDominio}/{iuv}/{iur}/{indice} | Dettaglio di una riscossione |


## `findRiscossioni()`

```php
findRiscossioni($pagina, $risultati_per_pagina, $campi, $ordinamento, $id_dominio, $data_da, $data_a, $stato, $tipo, $metadati_paginazione, $max_risultati, $iur): \GovPay\Ragioneria\Model\Riscossioni
```

Elenco degli importi riscossi o stornati

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Ragioneria\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Ragioneria\Api\RiscossioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$pagina = 1; // int | Numero di pagina di risultato
$risultati_per_pagina = 25; // int | How many items to return at one time
$campi = 'campi_example'; // string | Fields to retrieve
$ordinamento = 'ordinamento_example'; // string | Sorting order
$id_dominio = 'id_dominio_example'; // string | Identificativo dell'Ente Creditore in pagoPA. Corrisponde al codice fiscale.
$data_da = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inizio della finestra temporale di osservazione
$data_a = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Fine della finestra temporale di osservazione
$stato = new \GovPay\Ragioneria\Model\\GovPay\Ragioneria\Model\StatoRiscossione(); // \GovPay\Ragioneria\Model\StatoRiscossione | Stato della riscossione
$tipo = array(new \GovPay\Ragioneria\Model\\GovPay\Ragioneria\Model\TipoRiscossione()); // \GovPay\Ragioneria\Model\TipoRiscossione[] | Tipologia della riscossione (default [ENTRATA, MBT] )
$metadati_paginazione = true; // bool | Indica se il servizio calcola e valorizza i dati di paginazione o meno
$max_risultati = true; // bool | Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati
$iur = 'iur_example'; // string | Identificativo univoco riscossione

try {
    $result = $apiInstance->findRiscossioni($pagina, $risultati_per_pagina, $campi, $ordinamento, $id_dominio, $data_da, $data_a, $stato, $tipo, $metadati_paginazione, $max_risultati, $iur);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling RiscossioniApi->findRiscossioni: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **pagina** | **int**| Numero di pagina di risultato | [optional] [default to 1] |
| **risultati_per_pagina** | **int**| How many items to return at one time | [optional] [default to 25] |
| **campi** | **string**| Fields to retrieve | [optional] |
| **ordinamento** | **string**| Sorting order | [optional] |
| **id_dominio** | **string**| Identificativo dell&#39;Ente Creditore in pagoPA. Corrisponde al codice fiscale. | [optional] |
| **data_da** | **\DateTime**| Inizio della finestra temporale di osservazione | [optional] |
| **data_a** | **\DateTime**| Fine della finestra temporale di osservazione | [optional] |
| **stato** | [**\GovPay\Ragioneria\Model\StatoRiscossione**](../Model/.md)| Stato della riscossione | [optional] |
| **tipo** | [**\GovPay\Ragioneria\Model\TipoRiscossione[]**](../Model/\GovPay\Ragioneria\Model\TipoRiscossione.md)| Tipologia della riscossione (default [ENTRATA, MBT] ) | [optional] |
| **metadati_paginazione** | **bool**| Indica se il servizio calcola e valorizza i dati di paginazione o meno | [optional] [default to true] |
| **max_risultati** | **bool**| Indica se il servizio deve impostare o meno il limite sul calcolo del numero di risultati | [optional] [default to true] |
| **iur** | **string**| Identificativo univoco riscossione | [optional] |

### Return type

[**\GovPay\Ragioneria\Model\Riscossioni**](../Model/Riscossioni.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getRiscossione()`

```php
getRiscossione($id_dominio, $iuv, $iur, $indice): \GovPay\Ragioneria\Model\Riscossione
```

Dettaglio di una riscossione

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basicAuth
$config = GovPay\Ragioneria\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new GovPay\Ragioneria\Api\RiscossioniApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id_dominio = 'id_dominio_example'; // string | Identificativo dell'Ente Creditore in pagoPA. Corrisponde al codice fiscale.
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
| **id_dominio** | **string**| Identificativo dell&#39;Ente Creditore in pagoPA. Corrisponde al codice fiscale. | |
| **iuv** | **string**| Identificativo univoco di versamento | |
| **iur** | **string**| Identificativo univoco di riscossione | |
| **indice** | **int**| Identificativo univoco di riscossione | |

### Return type

[**\GovPay\Ragioneria\Model\Riscossione**](../Model/Riscossione.md)

### Authorization

[basicAuth](../../README.md#basicAuth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
