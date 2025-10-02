# # Rendicontazione

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**iuv** | **string** | Identificativo univoco di versamento |
**iur** | **string** | Identificativo univoco di riscossione |
**indice** | **float** | Indice dell&#39;occorrenza del pagamento all’interno della struttura datiSingoloPagamento della Ricevuta Telematica. |
**importo** | **float** | Importo rendicontato. |
**esito** | **float** | Codice di esito dell&#39;operazione rendicontata  * 0 &#x3D; Pagamento eseguito  * 3 &#x3D; Pagamento revocato  * 9 &#x3D; Pagamento eseguito in assenza di RPT |
**data** | **\DateTime** | Data di esito |
**stato** | [**\GovPay\Ragioneria\Model\StatoRendicontazione**](StatoRendicontazione.md) |  |
**segnalazioni** | [**\GovPay\Ragioneria\Model\Segnalazione[]**](Segnalazione.md) |  | [optional]
**riscossione** | [**\GovPay\Ragioneria\Model\Riscossione**](Riscossione.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
