# # RppBase

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**stato** | **string** | Stato della richiesta di pagamento sulla piattaforma PagoPA. |
**dettaglio_stato** | **string** | Dettaglio fornito dal Nodo dei Pagamenti sullo stato della richiesta. | [optional]
**segnalazioni** | [**\GovPay\Pendenze\Model\Segnalazione[]**](Segnalazione.md) |  | [optional]
**rpt** | **object** | Rpt inviata a PagoPa. {http://www.digitpa.gov.it/schemas/2011/Pagamenti/} ctRichiestaPagamentoTelematico |
**rt** | **object** | Rt inviata da PagoPa. {http://www.digitpa.gov.it/schemas/2011/Pagamenti/} ctRicevutaTelematica | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
