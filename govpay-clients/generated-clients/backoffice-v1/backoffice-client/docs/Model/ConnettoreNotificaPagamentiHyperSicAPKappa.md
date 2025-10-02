# # ConnettoreNotificaPagamentiHyperSicAPKappa

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**abilitato** | **bool** | Indica se il connettore e&#39; abilitato |
**tipo_connettore** | **string** |  |
**versione_csv** | **string** | Versione del CSV prodotto. |
**email_indirizzi** | **string[]** | Indirizzi Email al quale verra&#39; spedito il tracciato | [optional]
**email_subject** | **string** | Subject da inserire nella mail | [optional]
**email_allegato** | **bool** | Indica se inviare il tracciato come allegato all&#39;email oppure se inserire nel messaggio il link al download | [optional]
**download_base_url** | **string** | URL base del link dove scaricare il tracciato | [optional]
**file_system_path** | **string** | Path nel quale verra&#39; salvato il tracciato | [optional]
**tipi_pendenza** | [**\GovPay\Backoffice\Model\ConnettoreNotificaPagamentiTipiPendenzaInner[]**](ConnettoreNotificaPagamentiTipiPendenzaInner.md) | tipi pendenza da includere nel tracciato | [optional]
**intervallo_creazione_tracciato** | **int** | intervallo di creazione del tracciato in ore |

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
