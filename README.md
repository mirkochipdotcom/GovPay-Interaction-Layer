# 🇮🇹 GovPay Interaction Layer (GIL)

### Piattaforma Completa per l'Inizializzazione dei Pagamenti (Cittadino) e la Gestione Amministrativa (Ufficio) delle Transazioni PagoPA.

[![GitHub Repository](https://img.shields.io/badge/GitHub-mirkochipdotcom%2FGovPay--Interaction--Layer-blue?style=flat&logo=github)](https://github.com/mirkochipdotcom/GovPay-Interaction-Layer.git)

Questo progetto fornisce un'infrastruttura **backend e frontend** containerizzata, basata su **Bootstrap Italia**, per interagire con il sistema di pagamento **GovPay (PagoPA)**. Serve sia i cittadini per avviare i pagamenti, sia gli uffici per la gestione e la ricerca delle pendenze.

---

## 🚀 Avvio Rapido

Il progetto è gestito tramite **Docker Compose** per un ambiente di sviluppo isolato.

### Prerequisiti

Assicurati di avere installati **Docker** e **Docker Compose**.

### Istruzioni

1.  **Clona il Repository:**
    ```bash
    git clone [https://github.com/mirkochipdotcom/GovPay-Interaction-Layer.git](https://github.com/mirkochipdotcom/GovPay-Interaction-Layer.git)
    cd GovPay-Interaction-Layer
    ```

2.  **Configurazione e Build:**
    La prima esecuzione ricostruirà l'immagine PHP/Apache e, internamente, **scaricherà (clonerà)** e compilerà gli asset necessati (ad esempio il front-end di Bootstrap Italia).

    ```bash
    # Costruisce l'immagine e avvia i servizi
    docker compose up --build -d
    ```

3.  **Accesso all'Applicazione:**
    Accedi all'applicazione tramite HTTPS:

| Servizio | URL di Accesso |
| :--- | :--- |
| **Applicazione** | `https://localhost:443` |

### Pulizia
Per fermare i servizi e rimuovere container e reti:
```bash
docker compose down
