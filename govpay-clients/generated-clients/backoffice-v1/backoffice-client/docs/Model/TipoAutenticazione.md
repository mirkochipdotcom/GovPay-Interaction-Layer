# # TipoAutenticazione

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**username** | **string** |  |
**password** | **string** |  |
**tipo** | **string** |  |
**ks_location** | **string** | Location del keystore | [optional]
**ks_password** | **string** | Password del keystore | [optional]
**ks_type** | [**\GovPay\Backoffice\Model\KeystoreType**](KeystoreType.md) |  | [optional]
**ks_p_key_passwd** | **string** | Password della chiave privata del keystore | [optional]
**ts_location** | **string** | Location del truststore |
**ts_password** | **string** | Password del truststore |
**ts_type** | [**\GovPay\Backoffice\Model\KeystoreType**](KeystoreType.md) |  |
**ssl_type** | [**\GovPay\Backoffice\Model\SslConfigType**](SslConfigType.md) |  |
**header_name** | **string** |  |
**header_value** | **string** |  |
**api_id** | **string** | valore da inserire all&#39;interno dell&#39;header previsto per l&#39;API-ID |
**api_key** | **string** | valore da inserire all&#39;interno dell&#39;header previsto per l&#39;API-KEY |
**client_id** | **string** | Identificativo dell&#39;applicazione da inviare all&#39;authorization server |
**client_secret** | **string** | Password assegnata all&#39;applicazione da inviare all&#39;authorization server |
**url_token_endpoint** | **string** | URL del server dove fare la chiamata di richiesta del token |
**scope** | **string** | Livello di accesso richiesto per l&#39;operazione da eseguire | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
