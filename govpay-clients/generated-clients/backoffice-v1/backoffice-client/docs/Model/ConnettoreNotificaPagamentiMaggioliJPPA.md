# # ConnettoreNotificaPagamentiMaggioliJPPA

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**abilitato** | **bool** | Indica se il connettore e&#39; abilitato |
**tipo_connettore** | **string** |  |
**versione** | **string** | Versione del servizio. | [optional]
**principal** | **string** | principal autenticato dalla chiamata di Maggioli |
**email_indirizzi** | **string[]** | Indirizzi Email al quale verra&#39; spedito il tracciato | [optional]
**email_subject** | **string** | Subject da inserire nella mail | [optional]
**email_allegato** | **bool** | Indica se inviare il tracciato come allegato all&#39;email oppure se inserire nel messaggio il link al download | [optional]
**download_base_url** | **string** | URL base del link dove scaricare il tracciato | [optional]
**tipi_pendenza** | [**\GovPay\Backoffice\Model\ConnettoreNotificaPagamentiTipiPendenzaInner[]**](ConnettoreNotificaPagamentiTipiPendenzaInner.md) | tipi pendenza da includere nel tracciato | [optional]
**url** | **string** | URL Base del servizio rest di ricezione dei dati | [optional]
**versione_api** | **string** | Versione delle API di integrazione utilizzate. | [optional]
**auth** | [**\GovPay\Backoffice\Model\TipoAutenticazione**](TipoAutenticazione.md) |  | [optional]
**contenuti** | [**\GovPay\Backoffice\Model\ContenutoNotificaPagamentiGovpay[]**](ContenutoNotificaPagamentiGovpay.md) | Lista dei contenuti da inviare al servizio REST | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
