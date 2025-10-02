# # DominioPost

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**ragione_sociale** | **string** | Ragione sociale del beneficiario |
**indirizzo** | **string** | Indirizzo del beneficiario | [optional]
**civico** | **string** | Numero civico del beneficiario | [optional]
**cap** | **string** | Codice avviamento postale del beneficiario | [optional]
**localita** | **string** | Località del beneficiario | [optional]
**provincia** | **string** | Provincia del beneficiario | [optional]
**nazione** | **string** | Nazione del beneficiario | [optional]
**email** | **string** | Posta elettronica ordinaria del beneficiario | [optional]
**pec** | **string** | Posta elettronica certificata del beneficiario | [optional]
**tel** | **string** | Numero di telefono dell&#39;help desk del beneficiario | [optional]
**fax** | **string** | Numero di fax dell&#39;help desk del beneficiario | [optional]
**web** | **string** | Url del sito web | [optional]
**gln** | **string** | Global location number del beneficiario |
**cbill** | **string** | codice cbill del beneficiario | [optional]
**iuv_prefix** | **string** | Prefisso negli IUV generati da GovPay - %(y) Anno di due cifre - %(Y) Anno di quattro cifre - %(a) Valore indicato nel campo codificaIuv dell&#39;applicazione - %(t) Valore indicato nel campo codificaIuv del tipo pendenza - %(p) Valore indicato nel campo codificaIuv del tipo pendenza | [optional]
**stazione** | **string** | Codice stazione PagoPA che intermedia il beneficiario |
**aux_digit** | **string** | Valore della prima cifra dei Numero Avviso generati da GovPay | [optional]
**segregation_code** | **string** | Codice di segregazione utilizzato in caso di beneficiario pluri-intermediato (auxDigit &#x3D; 3) | [optional]
**logo** | **string** | Base64 del logo del beneficiario | [optional]
**abilitato** | **bool** | Indicazione se il creditore è abilitato ad operare sulla piattaforma |
**aut_stampa_poste_italiane** | **string** | numero di autorizzazione per la stampa in proprio rilasciato da poste italiane | [optional]
**area** | **string** | Nome dell&#39;area di competenza del dominio | [optional]
**servizio_my_pivot** | [**\GovPay\Backoffice\Model\ConnettoreNotificaPagamentiMyPivot**](ConnettoreNotificaPagamentiMyPivot.md) |  | [optional]
**servizio_secim** | [**\GovPay\Backoffice\Model\ConnettoreNotificaPagamentiSecim**](ConnettoreNotificaPagamentiSecim.md) |  | [optional]
**servizio_gov_pay** | [**\GovPay\Backoffice\Model\ConnettoreNotificaPagamentiGovPay**](ConnettoreNotificaPagamentiGovPay.md) |  | [optional]
**servizio_hyper_sic_ap_kappa** | [**\GovPay\Backoffice\Model\ConnettoreNotificaPagamentiHyperSicAPKappa**](ConnettoreNotificaPagamentiHyperSicAPKappa.md) |  | [optional]
**servizio_maggioli_jppa** | [**\GovPay\Backoffice\Model\ConnettoreNotificaPagamentiMaggioliJPPA**](ConnettoreNotificaPagamentiMaggioliJPPA.md) |  | [optional]
**intermediato** | **bool** | Indica se il creditore viene configurato per utilizzare una  stazione di intermediazione | [optional]
**tassonomia_pago_pa** | [**\GovPay\Backoffice\Model\TassonomiaPagoPADominio**](TassonomiaPagoPADominio.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
