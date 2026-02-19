# PagoPA\GPD

Progetto Gestione Posizioni Debitorie


## Installation & Usage

### Requirements

PHP 8.1 and later.

### Composer

To install the bindings via [Composer](https://getcomposer.org/), add the following to `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/GIT_USER_ID/GIT_REPO_ID.git"
    }
  ],
  "require": {
    "GIT_USER_ID/GIT_REPO_ID": "*@dev"
  }
}
```

Then run `composer install`

### Manual Installation

Download the files and include `autoload.php`:

```php
<?php
require_once('/path/to/PagoPA\GPD/vendor/autoload.php');
```

## Getting Started

Please follow the [installation procedure](#installation--usage) and then run the following:

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



// Configure API key authorization: ApiKey
$config = PagoPA\GPD\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\GPD\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\GPD\Api\DebtPositionActionsAPIApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$organizationfiscalcode = 'organizationfiscalcode_example'; // string | Organization fiscal code, the fiscal code of the Organization.
$iupd = 'iupd_example'; // string | IUPD (Unique identifier of the debt position). Format could be `<Organization fiscal code + UUID>` this would make it unique within the new PD management system. It's the responsibility of the EC to guarantee uniqueness. The pagoPa system shall verify that this is `true` and if not, notify the EC.
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.

try {
    $result = $apiInstance->invalidatePosition($organizationfiscalcode, $iupd, $x_request_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DebtPositionActionsAPIApi->invalidatePosition: ', $e->getMessage(), PHP_EOL;
}

```

## API Endpoints

All URIs are relative to *https://api.platform.pagopa.it/gpd/debt-positions-service/v1*

Class | Method | HTTP request | Description
------------ | ------------- | ------------- | -------------
*DebtPositionActionsAPIApi* | [**invalidatePosition**](docs/Api/DebtPositionActionsAPIApi.md#invalidateposition) | **POST** /organizations/{organizationfiscalcode}/debtpositions/{iupd}/invalidate | The Organization invalidate a debt Position.
*DebtPositionActionsAPIApi* | [**publishPosition**](docs/Api/DebtPositionActionsAPIApi.md#publishposition) | **POST** /organizations/{organizationfiscalcode}/debtpositions/{iupd}/publish | The Organization publish a debt Position.
*DebtPositionsAPIApi* | [**createPosition**](docs/Api/DebtPositionsAPIApi.md#createposition) | **POST** /organizations/{organizationfiscalcode}/debtpositions | The Organization creates a debt Position.
*DebtPositionsAPIApi* | [**deletePosition**](docs/Api/DebtPositionsAPIApi.md#deleteposition) | **DELETE** /organizations/{organizationfiscalcode}/debtpositions/{iupd} | The Organization deletes a debt position
*DebtPositionsAPIApi* | [**getOrganizationDebtPositionByIUPD**](docs/Api/DebtPositionsAPIApi.md#getorganizationdebtpositionbyiupd) | **GET** /organizations/{organizationfiscalcode}/debtpositions/{iupd} | Return the details of a specific debt position.
*DebtPositionsAPIApi* | [**getOrganizationDebtPositions**](docs/Api/DebtPositionsAPIApi.md#getorganizationdebtpositions) | **GET** /organizations/{organizationfiscalcode}/debtpositions | Return the list of the organization debt positions. The due dates interval is mutually exclusive with the payment dates interval.
*DebtPositionsAPIApi* | [**updatePosition**](docs/Api/DebtPositionsAPIApi.md#updateposition) | **PUT** /organizations/{organizationfiscalcode}/debtpositions/{iupd} | The Organization updates a debt position
*DebtPositionsAPIApi* | [**updateTransferIbanMassive**](docs/Api/DebtPositionsAPIApi.md#updatetransferibanmassive) | **PATCH** /organizations/{organizationfiscalcode}/debtpositions/transfers | The Organization updates the IBANs of every updatable payment option&#39;s transfers

## Models

- [AppInfo](docs/Model/AppInfo.md)
- [PageInfo](docs/Model/PageInfo.md)
- [PaymentOptionMetadataModel](docs/Model/PaymentOptionMetadataModel.md)
- [PaymentOptionMetadataModelResponse](docs/Model/PaymentOptionMetadataModelResponse.md)
- [PaymentOptionModel](docs/Model/PaymentOptionModel.md)
- [PaymentOptionModelResponse](docs/Model/PaymentOptionModelResponse.md)
- [PaymentPositionModel](docs/Model/PaymentPositionModel.md)
- [PaymentPositionModelBaseResponse](docs/Model/PaymentPositionModelBaseResponse.md)
- [PaymentPositionsInfo](docs/Model/PaymentPositionsInfo.md)
- [ProblemJson](docs/Model/ProblemJson.md)
- [Stamp](docs/Model/Stamp.md)
- [TransferMetadataModel](docs/Model/TransferMetadataModel.md)
- [TransferMetadataModelResponse](docs/Model/TransferMetadataModelResponse.md)
- [TransferModel](docs/Model/TransferModel.md)
- [TransferModelResponse](docs/Model/TransferModelResponse.md)
- [UpdateTransferIbanMassiveModel](docs/Model/UpdateTransferIbanMassiveModel.md)
- [UpdateTransferIbanMassiveResponse](docs/Model/UpdateTransferIbanMassiveResponse.md)

## Authorization

Authentication schemes defined for the API:
### ApiKey

- **Type**: API key
- **API key parameter name**: Ocp-Apim-Subscription-Key
- **Location**: HTTP header


## Tests

To run the tests, use:

```bash
composer install
vendor/bin/phpunit
```

## Author



## About this package

This PHP package is automatically generated by the [OpenAPI Generator](https://openapi-generator.tech) project:

- API version: `1.0.0`
    - Generator version: `7.21.0-SNAPSHOT`
- Build package: `org.openapitools.codegen.languages.PhpClientCodegen`
