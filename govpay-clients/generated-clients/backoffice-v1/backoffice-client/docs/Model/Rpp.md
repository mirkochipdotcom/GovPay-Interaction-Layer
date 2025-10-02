# # Rpp

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**stato** | **string** | Stato della richiesta di pagamento sulla piattaforma PagoPA. |
**dettaglio_stato** | **string** | Dettaglio fornito dal Nodo dei Pagamenti sullo stato della richiesta. | [optional]
**bloccante** | **bool** | Indica se la richiesta di pagamento deve essere bloccata quando viene inviata mentre e&#39; ancora in corso il tentativo precedente | [optional] [default to true]
**segnalazioni** | [**\GovPay\Backoffice\Model\Segnalazione[]**](Segnalazione.md) |  | [optional]
**rpt** | **object** | Rpt inviata a PagoPa. {http://www.digitpa.gov.it/schemas/2011/Pagamenti/} ctRichiestaPagamentoTelematico |
**rt** | **object** | Rt inviata da PagoPa. {http://www.digitpa.gov.it/schemas/2011/Pagamenti/} ctRicevutaTelematica | [optional]
**pendenza** | [**\GovPay\Backoffice\Model\PendenzaIndex**](PendenzaIndex.md) |  | [optional]
**modello** | [**\GovPay\Backoffice\Model\ModelloPagamento**](ModelloPagamento.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
