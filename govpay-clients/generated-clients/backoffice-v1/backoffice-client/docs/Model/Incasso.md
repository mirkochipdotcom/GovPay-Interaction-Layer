# # Incasso

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**dominio** | [**\GovPay\Backoffice\Model\DominioIndex**](DominioIndex.md) |  |
**id_incasso** | **string** | Identificativo dell&#39;incasso |
**causale** | **string** | Causale dell&#39;operazione di riversamento dal PSP alla Banca Tesoriera. |
**importo** | **float** |  |
**data** | **\DateTime** | Data incasso | [optional]
**data_valuta** | **\DateTime** | Data di valuta dell&#39;incasso | [optional]
**data_contabile** | **\DateTime** | Data di contabile dell&#39;incasso | [optional]
**iban_accredito** | **string** | Identificativo del conto di tesoreria su cui sono stati incassati i fondi | [optional]
**sct** | **string** | Identificativo Sepa Credit Transfer | [optional]
**riscossioni** | [**\GovPay\Backoffice\Model\Riscossione[]**](Riscossione.md) |  | [optional]
**stato** | [**\GovPay\Backoffice\Model\StatoIncasso**](StatoIncasso.md) |  |
**descrizione_stato** | **string** | Descrizione estesta dello stato della riconciliazione | [optional]
**iuv** | **string** |  |
**id_flusso** | **string** |  |

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
