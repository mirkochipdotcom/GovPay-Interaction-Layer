# # TransferModel

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id_transfer** | **string** |  |
**amount** | **int** |  |
**organization_fiscal_code** | **string** | Fiscal code related to the organization targeted by this transfer. | [optional]
**remittance_information** | **string** |  |
**category** | **string** |  |
**iban** | **string** | mutual exclusive with stamp | [optional]
**postal_iban** | **string** | optional - can be combined with iban but not with stamp | [optional]
**stamp** | [**\PagoPA\GPD\Model\Stamp**](Stamp.md) |  | [optional]
**company_name** | **string** |  | [optional]
**transfer_metadata** | [**\PagoPA\GPD\Model\TransferMetadataModel[]**](TransferMetadataModel.md) | it can added a maximum of 10 key-value pairs for metadata | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
