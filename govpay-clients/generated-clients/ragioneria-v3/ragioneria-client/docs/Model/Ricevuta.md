# # Ricevuta

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**dominio** | [**\GovPay\Ragioneria\Model\Dominio**](Dominio.md) |  |
**iuv** | **string** | Identificativo univoco di versamento |
**id_ricevuta** | **string** | Corrisponde al &#x60;receiptId&#x60; oppure al &#x60;ccp&#x60; a seconda del modello di pagamento |
**importo** | **float** | Importo della transazione di pagamento. |
**esito** | [**\GovPay\Ragioneria\Model\EsitoRpp**](EsitoRpp.md) |  | [optional]
**id_pagamento** | **string** | Identificativo GovPay della sessione di pagamento | [optional]
**id_sessione_psp** | **string** | Identificativo pagoPA della sessione di pagamento | [optional]
**istituto_attestante** | [**\GovPay\Ragioneria\Model\RicevutaIstitutoAttestante**](RicevutaIstitutoAttestante.md) |  |
**versante** | [**\GovPay\Ragioneria\Model\Soggetto**](Soggetto.md) |  | [optional]
**pendenza** | [**\GovPay\Ragioneria\Model\PendenzaPagata**](PendenzaPagata.md) |  |
**data** | **\DateTime** | Data di acquisizione della ricevuta | [optional]
**data_pagamento** | **\DateTime** | Data di esecuzione della riscossione | [optional]
**rpt** | [**\GovPay\Ragioneria\Model\RicevutaRpt**](RicevutaRpt.md) |  | [optional]
**rt** | [**\GovPay\Ragioneria\Model\RicevutaRt**](RicevutaRt.md) |  | [optional]
**modello** | [**\GovPay\Ragioneria\Model\ModelloPagamento**](ModelloPagamento.md) |  |

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
