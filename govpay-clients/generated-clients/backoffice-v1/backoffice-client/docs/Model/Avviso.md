# # Avviso

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**stato** | **string** | Stato dell&#39;avviso |
**importo** | **float** | Importo della pendenza. Deve corrispondere alla somma delle singole voci. | [optional]
**id_dominio** | **string** | Identificativo del creditore dell&#39;avviso | [optional]
**numero_avviso** | **string** | Identificativo univoco versamento, assegnato se pagabile da psp | [optional]
**data_validita** | **\DateTime** | Data di validita dei dati della pendenza, decorsa la quale la pendenza può subire variazioni. | [optional]
**data_scadenza** | **\DateTime** | Data di scadenza della pendenza, decorsa la quale non è più pagabile. | [optional]
**descrizione** | **string** | Descrizione da inserire nell&#39;avviso di pagamento | [optional]
**tassonomia_avviso** | **string** | Macro categoria della pendenza secondo la classificazione AgID | [optional]
**qrcode** | **string** | Testo da codificare nel qr-code dell&#39;avviso | [optional]
**barcode** | **string** | Testo da codificare nel bar-code dell&#39;avviso | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
