# # RiscossioneIndex

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id_dominio** | **string** | Identificativo ente creditore |
**iuv** | **string** | Identificativo univoco di versamento |
**iur** | **string** | Identificativo univoco di riscossione. |
**indice** | **int** | indice posizionale della voce pendenza riscossa |
**pendenza** | **string** | Url della pendenza oggetto della riscossione | [optional]
**id_voce_pendenza** | **string** | Identificativo della voce di pedenza,interno alla pendenza, nel gestionale proprietario a cui si riferisce la riscossione | [optional]
**rpp** | **string** | Url richiesta di pagamento che ha realizzato la riscossione. Se non valorizzato, si tratta di un pagamento senza RPT | [optional]
**stato** | [**\GovPay\Backoffice\Model\StatoRiscossione**](StatoRiscossione.md) |  | [optional]
**tipo** | [**\GovPay\Backoffice\Model\TipoRiscossione**](TipoRiscossione.md) |  |
**importo** | **float** | Importo riscosso. |
**data** | **\DateTime** | Data di esecuzione della riscossione |
**commissioni** | **float** | Importo delle commissioni applicate al pagamento dal PSP | [optional]
**allegato** | [**\GovPay\Backoffice\Model\Allegato**](Allegato.md) |  | [optional]
**incasso** | **string** | Riferimento all&#39;operazione di incasso | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
