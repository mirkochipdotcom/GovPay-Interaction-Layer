# # VocePendenza

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**cod_entrata** | **string** |  |
**iban_accredito** | **string** |  |
**iban_appoggio** | **string** |  | [optional]
**codice_tassonomico_pago_pa** | **string** | Tassonomia pagoPA |
**tipo_bollo** | **string** | Tipologia di Bollo digitale |
**hash_documento** | **string** | Digest in base64 del documento informatico associato alla marca da bollo |
**provincia_residenza** | **string** | Sigla automobilistica della provincia di residenza del soggetto pagatore |
**dominio** | [**\GovPay\Pagamenti\Model\Dominio**](Dominio.md) |  | [optional]
**id_voce_pendenza** | **string** | Identificativo della voce di pedenza nel gestionale proprietario |
**descrizione** | **string** | descrizione della voce di pagamento | [optional]
**dati_allegati** | **object** | Dati applicativi allegati dal gestionale secondo un formato proprietario. | [optional]
**descrizione_causale_rpt** | **string** | Testo libero per la causale versamento | [optional]
**contabilita** | [**\GovPay\Pagamenti\Model\Contabilita**](Contabilita.md) |  | [optional]
**metadata** | [**\GovPay\Pagamenti\Model\Metadata**](Metadata.md) |  | [optional]
**pendenza** | [**\GovPay\Pagamenti\Model\Pendenza**](Pendenza.md) |  |

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
