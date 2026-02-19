# # CtReceiptModelResponse

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**receipt_id** | **string** |  |
**notice_number** | **string** |  |
**fiscal_code** | **string** |  |
**outcome** | **string** |  |
**creditor_reference_id** | **string** |  |
**payment_amount** | **float** |  |
**description** | **string** |  |
**company_name** | **string** |  |
**office_name** | **string** |  | [optional]
**debtor** | [**\PagoPA\BizEvents\Model\Debtor**](Debtor.md) |  |
**transfer_list** | [**\PagoPA\BizEvents\Model\TransferPA[]**](TransferPA.md) |  |
**id_psp** | **string** |  |
**psp_fiscal_code** | **string** |  | [optional]
**psp_partita_iva** | **string** |  | [optional]
**psp_company_name** | **string** |  |
**id_channel** | **string** |  |
**channel_description** | **string** |  | [optional]
**payer** | [**\PagoPA\BizEvents\Model\Payer**](Payer.md) |  | [optional]
**payment_method** | **string** |  | [optional]
**fee** | **float** |  | [optional]
**primary_ci_incurred_fee** | **float** |  | [optional]
**id_bundle** | **string** |  | [optional]
**id_ci_bundle** | **string** |  | [optional]
**payment_date_time** | **\DateTime** |  | [optional]
**payment_date_time_formatted** | **\DateTime** |  | [optional]
**application_date** | **\DateTime** |  | [optional]
**transfer_date** | **\DateTime** |  | [optional]
**metadata** | [**\PagoPA\BizEvents\Model\MapEntry[]**](MapEntry.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
