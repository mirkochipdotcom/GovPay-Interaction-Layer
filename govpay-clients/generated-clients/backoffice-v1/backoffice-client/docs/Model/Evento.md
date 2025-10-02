# # Evento

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** | Identificativo evento |
**componente** | [**\GovPay\Backoffice\Model\ComponenteEvento**](ComponenteEvento.md) |  |
**categoria_evento** | [**\GovPay\Backoffice\Model\CategoriaEvento**](CategoriaEvento.md) |  |
**ruolo** | [**\GovPay\Backoffice\Model\RuoloEvento**](RuoloEvento.md) |  |
**tipo_evento** | **string** |  |
**esito** | [**\GovPay\Backoffice\Model\EsitoEvento**](EsitoEvento.md) |  |
**data_evento** | **\DateTime** | Data emissione evento |
**durata_evento** | **int** | Durata evento (in millisecondi) |
**sottotipo_evento** | **string** |  | [optional]
**sottotipo_esito** | **string** | Descrizione dell&#39;esito | [optional]
**dettaglio_esito** | **string** |  | [optional]
**id_dominio** | **string** | Identificativo ente creditore | [optional]
**iuv** | **string** | Identificativo univoco di versamento | [optional]
**ccp** | **string** | Codice contesto di pagamento | [optional]
**id_a2_a** | **string** | Identificativo del gestionale responsabile della pendenza | [optional]
**id_pendenza** | **string** | Identificativo della pendenza nel gestionale responsabile | [optional]
**id_pagamento** | **string** | Identificativo del pagamento assegnato da GovPay | [optional]
**dati_pago_pa** | [**\GovPay\Backoffice\Model\DatiPagoPA**](DatiPagoPA.md) |  | [optional]
**severita** | **int** | indica il livello di severita nel caso di evento con esito KO/FAIL | [optional]
**cluster_id** | **string** | Identificativo del nodo dove viene registrata l&#39;operazione | [optional]
**transaction_id** | **string** | Identificativo della transazione registrata | [optional]
**parametri_richiesta** | **object** | Dettaglio del messaggio di richiesta |
**parametri_risposta** | **object** | Dettaglio del messaggio di risposta |

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
