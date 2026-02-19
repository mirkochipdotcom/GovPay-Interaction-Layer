# # PaymentPositionModel

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**iupd** | **string** |  |
**type** | **string** |  |
**pay_stand_in** | **bool** | feature flag to enable a debt position in stand-in mode | [optional] [default to true]
**fiscal_code** | **string** |  |
**full_name** | **string** |  |
**street_name** | **string** |  | [optional]
**civic_number** | **string** |  | [optional]
**postal_code** | **string** |  | [optional]
**city** | **string** |  | [optional]
**province** | **string** |  | [optional]
**region** | **string** |  | [optional]
**country** | **string** |  | [optional]
**email** | **string** |  | [optional]
**phone** | **string** |  | [optional]
**switch_to_expired** | **bool** | feature flag to enable the debt position to expire after the due date | [default to false]
**company_name** | **string** |  |
**office_name** | **string** |  | [optional]
**validity_date** | **\DateTime** |  | [optional]
**payment_date** | **\DateTime** |  | [optional] [readonly]
**status** | **string** |  | [optional] [readonly]
**payment_option** | [**\PagoPA\GPD\Model\PaymentOptionModel[]**](PaymentOptionModel.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
