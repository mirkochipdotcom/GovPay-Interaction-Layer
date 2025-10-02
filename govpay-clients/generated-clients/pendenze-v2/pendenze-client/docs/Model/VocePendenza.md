# # VocePendenza

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**cod_entrata** | **string** |  |
**iban_accredito** | **string** |  |
**iban_appoggio** | **string** |  | [optional]
**tipo_contabilita** | [**\GovPay\Pendenze\Model\TipoContabilita**](TipoContabilita.md) |  |
**codice_contabilita** | **string** | Codifica del capitolo di bilancio |
**tipo_bollo** | **string** | Tipologia di Bollo digitale |
**hash_documento** | **string** | Digest in base64 del documento informatico associato alla marca da bollo |
**provincia_residenza** | **string** | Sigla automobilistica della provincia di residenza del soggetto pagatore |
**id_voce_pendenza** | **string** | Identificativo della voce di pedenza nel gestionale proprietario |
**importo** | **float** | Importo della voce |
**descrizione** | **string** | descrizione della voce di pagamento |
**dati_allegati** | **object** | Dati applicativi allegati dal gestionale secondo un formato proprietario. | [optional]
**descrizione_causale_rpt** | **string** | Testo libero per la causale versamento | [optional]
**contabilita** | [**\GovPay\Pendenze\Model\Contabilita**](Contabilita.md) |  | [optional]
**metadata** | [**\GovPay\Pendenze\Model\Metadata**](Metadata.md) |  | [optional]
**indice** | **int** | indice di voce all&#39;interno della pendenza |
**stato** | [**\GovPay\Pendenze\Model\StatoVocePendenza**](StatoVocePendenza.md) |  |
**dominio** | [**\GovPay\Pendenze\Model\Dominio**](Dominio.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
