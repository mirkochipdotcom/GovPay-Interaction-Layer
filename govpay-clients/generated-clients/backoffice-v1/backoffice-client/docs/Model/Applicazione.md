# # Applicazione

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id_a2_a** | **string** | Identificativo dell&#39;applicazione |
**principal** | **string** | Identificativo di autenticazione |
**password** | **bool** | Indica se e&#39; stata configurata una password per l&#39;accesso con HTTP-Basic. | [optional]
**codifica_avvisi** | [**\GovPay\Backoffice\Model\CodificaAvvisi**](CodificaAvvisi.md) |  | [optional]
**domini** | [**\GovPay\Backoffice\Model\DominioProfiloIndex[]**](DominioProfiloIndex.md) | domini su cui e&#39; abilitato ad operare | [optional]
**tipi_pendenza** | [**\GovPay\Backoffice\Model\TipoPendenza[]**](TipoPendenza.md) | tipologie di pendenza su cui e&#39; abilitato ad operare | [optional]
**api_pagamenti** | **bool** | Indicazione l&#39;applicazione e&#39; abitata all&#39;utilizzo delle API-Pagamento | [optional] [default to false]
**api_pendenze** | **bool** | Indicazione l&#39;applicazione e&#39; abitata all&#39;utilizzo delle API-Pendenze | [optional] [default to false]
**api_ragioneria** | **bool** | Indicazione l&#39;applicazione e&#39; abitata all&#39;utilizzo delle API-Ragioneria | [optional] [default to false]
**acl** | [**\GovPay\Backoffice\Model\AclPost[]**](AclPost.md) | lista delle acl attive sull&#39;applicazione | [optional]
**ruoli** | [**\GovPay\Backoffice\Model\Ruolo[]**](Ruolo.md) | lista dei ruoli attivi sull&#39;applicazione | [optional]
**servizio_integrazione** | [**\GovPay\Backoffice\Model\Connector**](Connector.md) |  | [optional]
**abilitato** | **bool** | Indicazione se il creditore è abilitato ad operare sulla piattaforma | [default to true]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
