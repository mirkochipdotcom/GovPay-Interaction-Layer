# # TracciatoPendenzeEsito

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** | Identificativo numerico del tracciato |
**nome_file** | **string** | Nome del file tracciato |
**dominio** | [**\GovPay\Backoffice\Model\DominioIndex**](DominioIndex.md) |  | [optional]
**data_ora_caricamento** | **\DateTime** | Data di caricamento del tracciato |
**stato** | [**\GovPay\Backoffice\Model\StatoTracciatoPendenza**](StatoTracciatoPendenza.md) |  |
**descrizione_stato** | **string** | Descrizione dello stato del tracciato | [optional]
**numero_operazioni_totali** | **int** | Numero totale di operazioni previste | [optional]
**numero_operazioni_eseguite** | **int** | Numero totale di operazioni eseguite con successo | [optional]
**numero_operazioni_fallite** | **int** | Numero totale di operazioni fallite | [optional]
**numero_avvisi_totali** | **int** | Numero totale di stampe previste | [optional]
**numero_avvisi_stampati** | **int** | Numero totale di stampe eseguite con successo | [optional]
**numero_avvisi_falliti** | **int** | Numero totale di stampe non eseguite a causa di errori | [optional]
**operatore_mittente** | **string** | Nome operatore del cruscotto che ha effettuato l&#39;operazione di caricamento | [optional]
**data_ora_ultimo_aggiornamento** | **\DateTime** | Data ultimo aggiornamento stato elaborazione del tracciato | [optional]
**stampa_avvisi** | **bool** | indica se sono disponibili le stampe degli avvisi caricati con il tracciato | [optional]
**esito** | [**\GovPay\Backoffice\Model\DettaglioTracciatoPendenzeEsito**](DettaglioTracciatoPendenzeEsito.md) |  |

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
