# # Pendenza

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id_a2_a** | **string** | Identificativo dell&#39;applicativo chiamante in GovPay |
**id_pendenza** | **string** | Identificativo della pendenza nel gestionale proprietario |
**id_tipo_pendenza** | **string** | Identificativo della tipologia di pendenza | [optional]
**dominio** | [**\GovPay\Ragioneria\Model\Dominio**](Dominio.md) |  | [optional]
**unita_operativa** | [**\GovPay\Ragioneria\Model\UnitaOperativa**](UnitaOperativa.md) |  | [optional]
**causale** | **string** | Descrizione da inserire nell&#39;avviso di pagamento | [optional]
**soggetto_pagatore** | [**\GovPay\Ragioneria\Model\Soggetto**](Soggetto.md) |  | [optional]
**importo** | **float** | Importo della pendenza. Deve corrispondere alla somma delle singole voci. | [optional]
**numero_avviso** | **string** | Identificativo univoco versamento, assegnato se pagabile da psp | [optional]
**data_validita** | **\DateTime** | Data di validita dei dati della pendenza, decorsa la quale la pendenza può subire variazioni. | [optional]
**data_scadenza** | **\DateTime** | Data di scadenza della pendenza, decorsa la quale non è più pagabile. | [optional]
**anno_riferimento** | **float** | Anno di riferimento della pendenza | [optional]
**cartella_pagamento** | **string** | Identificativo della cartella di pagamento a cui afferisce la pendenza | [optional]
**dati_allegati** | **object** | Dati applicativi allegati dal gestionale secondo un formato proprietario. | [optional]
**tassonomia** | **string** | Macro categoria della pendenza secondo la classificazione del creditore | [optional]
**direzione** | **string** | Identificativo della direzione interna all&#39;ente creditore | [optional]
**divisione** | **string** | Identificativo della divisione interna all&#39;ente creditore | [optional]
**documento** | [**\GovPay\Ragioneria\Model\Documento**](Documento.md) |  | [optional]
**uuid** | **string** | Parametro di randomizzazione delle URL di pagamento statiche | [optional]
**proprieta** | [**\GovPay\Ragioneria\Model\ProprietaPendenza**](ProprietaPendenza.md) |  | [optional]
**allegati** | [**\GovPay\Ragioneria\Model\AllegatoPendenza[]**](AllegatoPendenza.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
