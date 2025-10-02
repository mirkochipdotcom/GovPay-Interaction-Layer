# # FlussoRendicontazione

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id_flusso** | **string** | Identificativo del flusso di rendicontazione |
**data_flusso** | **\DateTime** | Data di emissione del flusso |
**data** | **\DateTime** | Data di acquisizione del flusso |
**trn** | **string** | Identificativo dell&#39;operazione di riversamento assegnato dal psp debitore |
**data_regolamento** | **\DateTime** | Data dell&#39;operazione di riversamento fondi |
**id_psp** | **string** | Identificativo dell&#39;istituto mittente |
**bic_riversamento** | **string** | Codice Bic della banca che ha generato il riversamento | [optional]
**dominio** | [**\GovPay\Ragioneria\Model\Dominio**](Dominio.md) |  |
**numero_pagamenti** | **float** | numero di pagamenti oggetto della rendicontazione |
**importo_totale** | **float** | somma degli importi rendicontati |
**stato** | [**\GovPay\Ragioneria\Model\StatoFlussoRendicontazione**](StatoFlussoRendicontazione.md) |  |
**segnalazioni** | [**\GovPay\Ragioneria\Model\Segnalazione[]**](Segnalazione.md) |  | [optional]
**rendicontazioni** | [**\GovPay\Ragioneria\Model\Rendicontazione[]**](Rendicontazione.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
