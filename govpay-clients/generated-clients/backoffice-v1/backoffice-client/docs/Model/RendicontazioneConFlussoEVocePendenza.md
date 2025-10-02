# # RendicontazioneConFlussoEVocePendenza

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**iuv** | **string** | Identificativo univoco di versamento |
**iur** | **string** | Identificativo univoco di riscossione |
**indice** | **int** | Indice dell&#39;occorrenza del pagamento all’interno della struttura datiSingoloPagamento della Ricevuta Telematica. |
**importo** | **float** | Importo rendicontato. |
**esito** | **int** | Codice di esito dell&#39;operazione rendicontata  * 0 &#x3D; Pagamento eseguito  * 3 &#x3D; Pagamento revocato  * 4 &#x3D; Pagamento eseguito tramite Standin  * 8 &#x3D; Pagamento eseguito tramite Standin in assenza di RPT  * 9 &#x3D; Pagamento eseguito in assenza di RPT |
**data** | **\DateTime** | Data di esito |
**segnalazioni** | [**\GovPay\Backoffice\Model\Segnalazione[]**](Segnalazione.md) |  | [optional]
**flusso_rendicontazione** | [**\GovPay\Backoffice\Model\FlussoRendicontazioneIndex**](FlussoRendicontazioneIndex.md) |  | [optional]
**voce_pendenza** | [**\GovPay\Backoffice\Model\VocePendenzaRendicontazione**](VocePendenzaRendicontazione.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
