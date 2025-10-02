# # PendenzaPut

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**nome** | **string** | Nome della pendenza da visualizzare sui portali di pagamento e console di gestione. | [optional]
**causale** | **string** | Descrizione da inserire nell&#39;avviso di pagamento |
**soggetto_pagatore** | [**\GovPay\Backoffice\Model\Soggetto**](Soggetto.md) |  | [optional]
**importo** | **float** | Importo della pendenza. Deve corrispondere alla somma delle singole voci. |
**numero_avviso** | **string** | Numero avviso, assegnato se pagabile da psp | [optional]
**data_caricamento** | **\DateTime** | Data di emissione della pendenza | [optional]
**data_validita** | **\DateTime** | Data di validita dei dati della pendenza, decorsa la quale la pendenza può subire variazioni. | [optional]
**data_scadenza** | **\DateTime** | Data di scadenza della pendenza, decorsa la quale non è più pagabile. | [optional]
**anno_riferimento** | **int** | Anno di riferimento della pendenza | [optional]
**cartella_pagamento** | **string** | Identificativo della cartella di pagamento a cui afferisce la pendenza | [optional]
**dati_allegati** | **object** | Dati applicativi allegati dal gestionale secondo un formato proprietario. | [optional]
**tassonomia** | **string** | Macro categoria della pendenza secondo la classificazione del creditore | [optional]
**tassonomia_avviso** | [**\GovPay\Backoffice\Model\TassonomiaAvviso**](TassonomiaAvviso.md) |  | [optional]
**direzione** | **string** | Identificativo della direzione interna all&#39;ente creditore | [optional]
**divisione** | **string** | Identificativo della divisione interna all&#39;ente creditore | [optional]
**documento** | [**\GovPay\Backoffice\Model\Documento**](Documento.md) |  | [optional]
**data_notifica_avviso** | **\DateTime** | Data in cui inviare il promemoria di pagamento. | [optional]
**data_promemoria_scadenza** | **\DateTime** | Data in cui inviare il promemoria di scadenza della pendenza. | [optional]
**proprieta** | [**\GovPay\Backoffice\Model\ProprietaPendenza**](ProprietaPendenza.md) |  | [optional]
**id_dominio** | **string** | Identificativo del dominio creditore |
**id_unita_operativa** | **string** | Identificativo dell&#39;unita&#39; operativa | [optional]
**id_tipo_pendenza** | **string** | Identificativo della tipologia pendenza | [optional]
**voci** | [**\GovPay\Backoffice\Model\NuovaVocePendenza[]**](NuovaVocePendenza.md) |  |
**allegati** | [**\GovPay\Backoffice\Model\NuovoAllegatoPendenza[]**](NuovoAllegatoPendenza.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
