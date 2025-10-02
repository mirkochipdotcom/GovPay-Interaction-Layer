# # FlussoRendicontazione

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id_flusso** | **string** | identificativo del flusso di rendicontazione |
**data_flusso** | **\DateTime** | Data di emissione del flusso |
**trn** | **string** | Identificativo dell&#39;operazione di riversamento assegnato dal psp debitore |
**data_regolamento** | **\DateTime** | Data dell&#39;operazione di riversamento fondi |
**id_psp** | **string** | Identificativo del psp che ha emesso la rendicontazione |
**ragione_sociale_psp** | **string** | Nome del PSP che ha emesso il flusso | [optional]
**bic_riversamento** | **string** | Codice Bic della banca che ha generato il riversamento | [optional]
**id_dominio** | **string** | Identificativo del dominio creditore del riversamento |
**ragione_sociale_dominio** | **string** | Nome del Dominio destinatario del flusso | [optional]
**numero_pagamenti** | **int** | numero di pagamenti oggetto della rendicontazione |
**importo_totale** | **float** | somma degli importi rendicontati |
**stato** | [**\GovPay\Backoffice\Model\StatoFlussoRendicontazione**](StatoFlussoRendicontazione.md) |  | [optional]
**segnalazioni** | [**\GovPay\Backoffice\Model\Segnalazione[]**](Segnalazione.md) |  | [optional]
**rendicontazioni** | [**\GovPay\Backoffice\Model\Rendicontazione[]**](Rendicontazione.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
