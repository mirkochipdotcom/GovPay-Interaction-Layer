# # PaymentOptionModel

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**nav** | **string** |  | [optional]
**iuv** | **string** |  |
**amount** | **int** |  |
**description** | **string** |  |
**is_partial_payment** | **bool** |  |
**due_date** | **\DateTime** |  |
**retention_date** | **\DateTime** |  | [optional]
**fee** | **int** |  | [optional]
**notification_fee** | **int** |  | [optional] [readonly]
**transfer** | [**\PagoPA\GPD\Model\TransferModel[]**](TransferModel.md) |  | [optional]
**payment_option_metadata** | [**\PagoPA\GPD\Model\PaymentOptionMetadataModel[]**](PaymentOptionMetadataModel.md) | it can added a maximum of 10 key-value pairs for metadata | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
