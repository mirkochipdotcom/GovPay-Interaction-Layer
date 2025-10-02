# # Avviso

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**stato** | [**\GovPay\Pendenze\Model\StatoAvviso**](StatoAvviso.md) |  |
**importo** | **float** | Importo della pendenza associata all&#39;avviso. | [optional]
**id_dominio** | **string** | Identificativo del creditore dell&#39;avviso | [optional]
**numero_avviso** | **string** | Numero identificativo dell&#39;avviso di pagamento | [optional]
**data_validita** | **\DateTime** | Data di validita dei dati dell&#39;avviso, decorsa la quale l&#39;importo può subire variazioni. | [optional]
**data_scadenza** | **\DateTime** | Data di scadenza dell&#39;avviso, decorsa la quale non è più pagabile. | [optional]
**data_pagamento** | **\DateTime** | Data di pagamento dell&#39;avviso. | [optional]
**descrizione** | **string** | Descrizione da inserire nell&#39;avviso di pagamento | [optional]
**tassonomia_avviso** | [**\GovPay\Pendenze\Model\TassonomiaAvviso**](TassonomiaAvviso.md) |  | [optional]
**qrcode** | **string** | Testo da codificare nel qr-code dell&#39;avviso | [optional]
**barcode** | **string** | Testo da codificare nel bar-code dell&#39;avviso | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
