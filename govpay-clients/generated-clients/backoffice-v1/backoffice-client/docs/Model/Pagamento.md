# # Pagamento

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **string** | Identificativo del pagamento assegnato da GovPay |
**nome** | **string** | Identificativo del pagamento assegnato da GovPay | [optional]
**data_richiesta_pagamento** | **\DateTime** | Data di richiesta del pagamento | [optional]
**id_sessione_portale** | **string** | Identificativo del pagamento assegnato dal portale chiamante | [optional]
**id_sessione_psp** | **string** | Identificativo del pagamento assegnato dal psp utilizzato | [optional]
**importo** | **float** | Importo del pagamento. Corrisponde alla somma degli importi delle pendenze al momento della richiesta | [optional]
**modello** | [**\GovPay\Backoffice\Model\ModelloPagamento**](ModelloPagamento.md) |  | [optional]
**stato** | [**\GovPay\Backoffice\Model\StatoPagamento**](StatoPagamento.md) |  |
**descrizione_stato** | **string** | Descrizione estesa dello stato del pagamento | [optional]
**psp_redirect_url** | **string** | Url di redirect al psp inviata al versante per perfezionare il pagamento, se previsto dal modello | [optional]
**url_ritorno** | **string** | url di ritorno al portale al termine della sessione di pagamento | [optional]
**conto_addebito** | [**\GovPay\Backoffice\Model\ContoAddebito**](ContoAddebito.md) |  | [optional]
**data_esecuzione_pagamento** | **\DateTime** | data in cui si richiede che venga effettuato il pagamento, se diversa dalla data corrente. | [optional]
**credenziali_pagatore** | **string** | Eventuali credenziali richieste dal PSP necessarie per completare l&#39;operazione (ad esempio un codice bilaterale utilizzabile una sola volta). | [optional]
**soggetto_versante** | [**\GovPay\Backoffice\Model\Soggetto**](Soggetto.md) |  | [optional]
**autenticazione_soggetto** | **string** | modalita&#39; di autenticazione del soggetto versante | [optional]
**lingua** | **string** | Indica il codice della lingua da utilizzare per l’esposizione delle pagine web. | [optional] [default to 'IT']
**rpp** | [**\GovPay\Backoffice\Model\Rpp[]**](Rpp.md) |  | [optional]
**verificato** | **bool** | indicazione se eventuali anomalie sono state verificate da un operatore | [optional]
**severita** | **int** | indica il livello di severita dell&#39;errore che ha portato il pagamento in stato FALLITO | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
