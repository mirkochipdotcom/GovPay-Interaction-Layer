# # PendenzaBase

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id_a2_a** | **string** | Identificativo del gestionale responsabile della pendenza |
**id_pendenza** | **string** | Identificativo della pendenza nel gestionale responsabile |
**id_tipo_pendenza** | **string** | Identificativo della tipologia pendenza | [optional]
**dominio** | [**\GovPay\Pendenze\Model\Dominio**](Dominio.md) |  |
**unita_operativa** | [**\GovPay\Pendenze\Model\UnitaOperativa**](UnitaOperativa.md) |  | [optional]
**stato** | [**\GovPay\Pendenze\Model\StatoPendenza**](StatoPendenza.md) |  |
**descrizione_stato** | **string** | Descrizione estesa dello stato di elaborazione della pendenza | [optional]
**segnalazioni** | [**\GovPay\Pendenze\Model\Segnalazione[]**](Segnalazione.md) |  | [optional]
**iuv_avviso** | **string** | Iuv avviso, assegnato se pagabile da psp | [optional]
**iuv_pagamento** | **string** | Iuv dell&#39;ultimo pagamento eseguito con successo | [optional]
**data_pagamento** | **\DateTime** | Data di pagamento della pendenza | [optional]
**causale** | **string** | Descrizione da inserire nell&#39;avviso di pagamento | [optional]
**soggetto_pagatore** | [**\GovPay\Pendenze\Model\Soggetto**](Soggetto.md) |  |
**importo** | **float** | Importo della pendenza. Deve corrispondere alla somma delle singole voci. |
**numero_avviso** | **string** | Identificativo univoco versamento, assegnato se pagabile da psp | [optional]
**data_caricamento** | **\DateTime** | Data di emissione della pendenza |
**data_validita** | **\DateTime** | Data di validita dei dati della pendenza, decorsa la quale la pendenza può subire variazioni. | [optional]
**data_scadenza** | **\DateTime** | Data di scadenza della pendenza, decorsa la quale non è più pagabile. | [optional]
**anno_riferimento** | **int** | Anno di riferimento della pendenza | [optional]
**cartella_pagamento** | **string** | Identificativo della cartella di pagamento a cui afferisce la pendenza | [optional]
**dati_allegati** | **object** | Dati applicativi allegati dal gestionale secondo un formato proprietario. | [optional]
**tassonomia** | **string** | Macro categoria della pendenza secondo la classificazione del creditore | [optional]
**tassonomia_avviso** | [**\GovPay\Pendenze\Model\TassonomiaAvviso**](TassonomiaAvviso.md) |  | [optional]
**direzione** | **string** | Identificativo della direzione interna all&#39;ente creditore | [optional]
**divisione** | **string** | Identificativo della divisione interna all&#39;ente creditore | [optional]
**documento** | [**\GovPay\Pendenze\Model\Documento**](Documento.md) |  | [optional]
**tipo** | [**\GovPay\Pendenze\Model\TipoPendenzaTipologia**](TipoPendenzaTipologia.md) |  |
**uuid** | **string** | Parametro di randomizzazione delle URL di pagamento statiche | [optional]
**proprieta** | [**\GovPay\Pendenze\Model\ProprietaPendenza**](ProprietaPendenza.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
