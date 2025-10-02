# # RiconciliazioneIndex

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**iuv** | **string** |  |
**id_flusso_rendicontazione** | **string** |  |
**id** | **string** | Identificativo della riconciliazione assegnato da GovPay |
**dominio** | [**\GovPay\Ragioneria\Model\Dominio**](Dominio.md) |  |
**stato** | [**\GovPay\Ragioneria\Model\StatoRiconciliazione**](StatoRiconciliazione.md) |  |
**descrizione_stato** | **string** | Dettaglio dello stato riconciliazione | [optional]
**importo** | **float** | Importo del riversamento. Se valorizzato, viene verificato che corrisponda a quello dei pagamenti riconciliati. | [optional]
**data** | **\DateTime** | Data di esecuzione della riconciliazione |
**data_valuta** | **\DateTime** | Data di valuta dell&#39;incasso | [optional]
**data_contabile** | **\DateTime** | Data di contabile dell&#39;incasso | [optional]
**conto_accredito** | **string** | Identificativo del conto di tesoreria su cui sono stati incassati i fondi | [optional]
**sct** | **string** | Identificativo Sepa Credit Transfer | [optional]
**trn** | **string** | Transaction reference number. Se valorizzato viene verificato che corrisponda a quello indicato nel Flusso di Rendicontazione. | [optional]
**causale** | **string** | Causale bancaria dell&#39;SCT di riversamento fondi dal PSP al conto di accredito. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
