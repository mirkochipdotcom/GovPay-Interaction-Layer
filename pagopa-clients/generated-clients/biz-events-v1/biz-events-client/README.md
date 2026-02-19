# PagoPA\BizEvents

Microservice for exposing REST APIs about payment receipts.
### APP ERROR CODES ###


<details><summary>Details</summary>

| Code | Group | Domain | Description |
| ---- | ----- | ------ | ----------- |
| **BZ_404_001** | *NOT FOUND* | biz event | Biz Event not found with IUR and IUV |
| **BZ_404_002** | *NOT FOUND* | biz event | Biz Event not found with IUR |
| **BZ_404_003** | *NOT FOUND* | biz event | Biz Event not found with ID |
| **BZ_404_004** | *NOT FOUND* | biz event | Biz Event not found with CF and IUV |
| **BZ_422_001** | *Unprocessable Entity* | biz event | Multiple BizEvents found with IUR and IUV |
| **BZ_422_002** | *Unprocessable Entity* | biz event | Multiple BizEvents found with CF and IUR |
| **BZ_422_003** | *Unprocessable Entity* | biz event | Multiple BizEvents found with CF and IUV |
| **GN_400_001** | *BAD REQUEST* | generic | - |
| **GN_400_002** | *BAD REQUEST* | generic | Invalid input |
| **GN_400_003** | *BAD REQUEST* | generic | Invalid CF (Tax Code) |
| **GN_400_004** | *BAD REQUEST* | generic | Invalid input type |
| **GN_400_005** | *BAD REQUEST* | generic | Invalid input parameter constraints |
| **GN_500_001** | *Internal Server Error* | generic | Generic Error |
| **GN_500_002** | *Internal Server Error* | generic | Generic Error |
| **GN_500_003** | *Internal Server Error* | generic | Generic Error |
| **GN_500_004** | *Internal Server Error* | generic | Generic Error |
| **FG_000_001** | *Variable* | feign client | Error occurred during call to underlying services |
| **VU_404_001** | *NOT FOUND* | view user | View User not found with CF |
| **VU_404_002** | *NOT FOUND* | view user | View User not found with CF and filters |
| **VU_404_003** | *NOT FOUND* | view user | View User not found with ID |
| **VG_404_001** | *NOT FOUND* | view general | View General not found with ID |
| **VC_404_001** | *NOT FOUND* | view cart | View Cart not found with ID and CF |
| **AT_404_001** | *NOT FOUND* | attachment | Attachment not found |
| **AT_404_002** | *NOT FOUND* | attachment | Attachment not found because it is currently being generated |
| **UN_500_000** | *Internal Server Error* | unknown | Unexpected error |
| **TS_000_000** | *test* | test | used for testing |
</details>


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
require_once('/path/to/PagoPA\BizEvents/vendor/autoload.php');
```

## Getting Started

Please follow the [installation procedure](#installation--usage) and then run the following:

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



// Configure API key authorization: ApiKey
$config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKey('Ocp-Apim-Subscription-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = PagoPA\BizEvents\Configuration::getDefaultConfiguration()->setApiKeyPrefix('Ocp-Apim-Subscription-Key', 'Bearer');


$apiInstance = new PagoPA\BizEvents\Api\BizEventsHelpdeskApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$biz_event_id = 'biz_event_id_example'; // string | The id of the biz-event.
$x_request_id = 'x_request_id_example'; // string | This header identifies the call, if not passed it is self-generated. This ID is returned in the response.

try {
    $result = $apiInstance->getBizEvent($biz_event_id, $x_request_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling BizEventsHelpdeskApi->getBizEvent: ', $e->getMessage(), PHP_EOL;
}

```

## API Endpoints

All URIs are relative to *http://localhost:8080*

Class | Method | HTTP request | Description
------------ | ------------- | ------------- | -------------
*BizEventsHelpdeskApi* | [**getBizEvent**](docs/Api/BizEventsHelpdeskApi.md#getbizevent) | **GET** /events/{biz-event-id} | Retrieve the biz-event given its id.
*BizEventsHelpdeskApi* | [**getBizEventByOrganizationFiscalCodeAndIuv**](docs/Api/BizEventsHelpdeskApi.md#getbizeventbyorganizationfiscalcodeandiuv) | **GET** /events/organizations/{organization-fiscal-code}/iuvs/{iuv} | Retrieve the biz-event given the organization fiscal code and IUV.
*HomeApi* | [**healthCheck**](docs/Api/HomeApi.md#healthcheck) | **GET** /info | health check
*PaidNoticeRESTAPIsApi* | [**disablePaidNotice**](docs/Api/PaidNoticeRESTAPIsApi.md#disablepaidnotice) | **POST** /paids/{event-id}/disable | Disable the paid notice details given its id.
*PaidNoticeRESTAPIsApi* | [**enablePaidNotice**](docs/Api/PaidNoticeRESTAPIsApi.md#enablepaidnotice) | **POST** /paids/{event-id}/enable | Enable the paid notice details given its id.
*PaidNoticeRESTAPIsApi* | [**generatePDF**](docs/Api/PaidNoticeRESTAPIsApi.md#generatepdf) | **GET** /paids/{event-id}/pdf | Retrieve the PDF receipt given event id.
*PaidNoticeRESTAPIsApi* | [**getPaidNoticeDetail**](docs/Api/PaidNoticeRESTAPIsApi.md#getpaidnoticedetail) | **GET** /paids/{event-id} | Retrieve the paid notice details given its id.
*PaidNoticeRESTAPIsApi* | [**getPaidNotices**](docs/Api/PaidNoticeRESTAPIsApi.md#getpaidnotices) | **GET** /paids | Retrieve the paged transaction list from biz events.
*PaymentReceiptsRESTAPIsApi* | [**getOrganizationReceiptIur**](docs/Api/PaymentReceiptsRESTAPIsApi.md#getorganizationreceiptiur) | **GET** /organizations/{organizationfiscalcode}/receipts/{iur} | The organization get the receipt for the creditor institution using IUR.
*PaymentReceiptsRESTAPIsApi* | [**getOrganizationReceiptIuvIur**](docs/Api/PaymentReceiptsRESTAPIsApi.md#getorganizationreceiptiuviur) | **GET** /organizations/{organizationfiscalcode}/receipts/{iur}/paymentoptions/{iuv} | The organization get the receipt for the creditor institution using IUV and IUR.

## Models

- [AppInfo](docs/Model/AppInfo.md)
- [AuthRequest](docs/Model/AuthRequest.md)
- [BizEvent](docs/Model/BizEvent.md)
- [CartItem](docs/Model/CartItem.md)
- [Creditor](docs/Model/Creditor.md)
- [CtReceiptModelResponse](docs/Model/CtReceiptModelResponse.md)
- [Debtor](docs/Model/Debtor.md)
- [DebtorPosition](docs/Model/DebtorPosition.md)
- [Details](docs/Model/Details.md)
- [Info](docs/Model/Info.md)
- [InfoNotice](docs/Model/InfoNotice.md)
- [InfoTransaction](docs/Model/InfoTransaction.md)
- [MapEntry](docs/Model/MapEntry.md)
- [NoticeDetailResponse](docs/Model/NoticeDetailResponse.md)
- [NoticeListItem](docs/Model/NoticeListItem.md)
- [NoticeListWrapResponse](docs/Model/NoticeListWrapResponse.md)
- [Payer](docs/Model/Payer.md)
- [PaymentAuthorizationRequest](docs/Model/PaymentAuthorizationRequest.md)
- [PaymentInfo](docs/Model/PaymentInfo.md)
- [ProblemJson](docs/Model/ProblemJson.md)
- [Psp](docs/Model/Psp.md)
- [Transaction](docs/Model/Transaction.md)
- [TransactionDetails](docs/Model/TransactionDetails.md)
- [TransactionPsp](docs/Model/TransactionPsp.md)
- [Transfer](docs/Model/Transfer.md)
- [TransferPA](docs/Model/TransferPA.md)
- [User](docs/Model/User.md)
- [UserDetail](docs/Model/UserDetail.md)
- [WalletInfo](docs/Model/WalletInfo.md)
- [WalletItem](docs/Model/WalletItem.md)

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

- API version: `0.4.1`
    - Generator version: `7.21.0-SNAPSHOT`
- Build package: `org.openapitools.codegen.languages.PhpClientCodegen`
