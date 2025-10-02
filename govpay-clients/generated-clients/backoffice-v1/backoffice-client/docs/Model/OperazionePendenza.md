# # OperazionePendenza

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**tipo_operazione** | [**\GovPay\Backoffice\Model\TipoOperazionePendenza**](TipoOperazionePendenza.md) |  |
**stato** | [**\GovPay\Backoffice\Model\StatoOperazionePendenza**](StatoOperazionePendenza.md) |  |
**descrizione_stato** | **string** | Descrizione dello stato operazione | [optional]
**ente_creditore** | [**\GovPay\Backoffice\Model\DominioIndex**](DominioIndex.md) |  | [optional]
**soggetto_pagatore** | [**\GovPay\Backoffice\Model\Soggetto**](Soggetto.md) |  | [optional]
**applicazione** | **string** | Applicazione che ha effettuato l&#39;operazione | [optional]
**identificativo_pendenza** | **string** | Identificativo della pendenza associata all&#39;operazione | [optional]
**numero_avviso** | **string** | Numero Avviso generato | [optional]
**numero** | **int** | Progressivo Operazione |
**richiesta** | [**\GovPay\Backoffice\Model\OperazionePendenzaRichiesta**](OperazionePendenzaRichiesta.md) |  | [optional]
**risposta** | [**\GovPay\Backoffice\Model\EsitoOperazionePendenza**](EsitoOperazionePendenza.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
