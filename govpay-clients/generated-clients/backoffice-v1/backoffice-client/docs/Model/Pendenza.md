# # Pendenza

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
**id_a2_a** | **string** | Identificativo del gestionale responsabile della pendenza |
**id_pendenza** | **string** | Identificativo della pendenza nel gestionale responsabile |
**tipo_pendenza** | [**\GovPay\Backoffice\Model\TipoPendenzaIndex**](TipoPendenzaIndex.md) |  | [optional]
**dominio** | [**\GovPay\Backoffice\Model\DominioIndex**](DominioIndex.md) |  |
**unita_operativa** | [**\GovPay\Backoffice\Model\UnitaOperativa**](UnitaOperativa.md) |  | [optional]
**stato** | [**\GovPay\Backoffice\Model\StatoPendenza**](StatoPendenza.md) |  |
**descrizione_stato** | **string** | Descrizione estesa dello stato di elaborazione della pendenza | [optional]
**iuv_avviso** | **string** | Iuv avviso, assegnato se pagabile da psp | [optional]
**data_ultimo_aggiornamento** | **\DateTime** | Data di ultimo aggiornamento della pendenza | [optional]
**data_pagamento** | **\DateTime** | Data di pagamento della pendenza | [optional]
**importo_pagato** | **float** | Importo Pagato. | [optional]
**importo_incassato** | **float** | Importo Incassato. | [optional]
**iuv_pagamento** | **string** | Iuv dell&#39;ultimo pagamento eseguito con successo | [optional]
**anomalo** | **bool** | indicazione se sono presenti eventuali anomalie | [optional]
**verificato** | **bool** | indicazione se eventuali anomalie sono state verificate da un operatore | [optional]
**tipo** | [**\GovPay\Backoffice\Model\TipoPendenzaTipologia**](TipoPendenzaTipologia.md) |  |
**uuid** | **string** | Parametro di randomizzazione delle URL di pagamento statiche | [optional]
**data_ultima_modifica_aca** | **\DateTime** | Data di ultimo aggiornamento dei dati da inviare ad ACA | [optional]
**data_ultima_comunicazione_aca** | **\DateTime** | Data ultima comunicazione verso il servizio ACA | [optional]
**voci** | [**\GovPay\Backoffice\Model\VocePendenza[]**](VocePendenza.md) |  |
**rpp** | [**\GovPay\Backoffice\Model\Rpp[]**](Rpp.md) |  |
**pagamenti** | [**\GovPay\Backoffice\Model\Pagamento[]**](Pagamento.md) |  |
**allegati** | [**\GovPay\Backoffice\Model\AllegatoPendenza[]**](AllegatoPendenza.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
