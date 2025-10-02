# # OperatorePost

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**ragione_sociale** | **string** | Nome e cognome dell&#39;operatore | [optional]
**password** | **string** | password per l&#39;autenticazione HTTP-Basic | [optional]
**domini** | [**\GovPay\Backoffice\Model\OperatorePostDominiInner[]**](OperatorePostDominiInner.md) | domini su cui e&#39; abilitato ad operare. Se la lista e&#39; vuota, l&#39;abilitazione e&#39; per nessun dominio | [optional]
**tipi_pendenza** | **string[]** | tipologie di pendenza su cui e&#39; abilitato ad operare. Se la lista e&#39; vuota, l&#39;abilitazione e&#39; per tutte le entrate | [optional]
**acl** | [**\GovPay\Backoffice\Model\AclPost[]**](AclPost.md) | lista delle acl attive sull&#39;operatore | [optional]
**ruoli** | **string[]** | lista dei ruoli attivi sull&#39;operatore | [optional]
**abilitato** | **bool** | Indicazione se l&#39;operatore è abilitato ad operare sulla piattaforma | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
