# # Operatore

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**principal** | **string** | Username dell&#39;operatore |
**password** | **bool** | Indica se e&#39; stata configurata una password per l&#39;accesso con HTTP-Basic. | [optional]
**ragione_sociale** | **string** | Nome e cognome dell&#39;operatore |
**domini** | [**\GovPay\Backoffice\Model\DominioProfiloIndex[]**](DominioProfiloIndex.md) | domini su cui e&#39; abilitato ad operare |
**tipi_pendenza** | [**\GovPay\Backoffice\Model\TipoPendenza[]**](TipoPendenza.md) | tipologie di pendenza su cui e&#39; abilitato ad operare |
**acl** | [**\GovPay\Backoffice\Model\AclPost[]**](AclPost.md) | lista delle acl attive sull&#39;applicazione | [optional]
**ruoli** | [**\GovPay\Backoffice\Model\Ruolo[]**](Ruolo.md) | lista dei ruoli attivi sull&#39;operatore | [optional]
**abilitato** | **bool** | Indicazione se l&#39;operatore è abilitato ad operare sulla piattaforma | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
