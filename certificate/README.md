# Certificate directory

Questo repository potrebbe contenere certificati TLS o chiavi private usati per lo sviluppo o il testing locale.

Attenzione: i certificati e le chiavi private NON dovrebbero essere committati in un repository pubblico.

Scopo di questa cartella:

- Fornire un posto per inserire temporaneamente certificati e chiavi per l'uso locale o nei container Docker.
- Fornire istruzioni su come rigenerare o ottenere i certificati se necessario.

Contenuto consigliato (non committare):

- file.crt, file.key — certificato e chiave privata
- ca.pem — certificato CA locale

Se hai bisogno di aggiungere certificati per il testing locale, salvali qui ma ricorda che sono ignorati da git (ad eccezione di questo README).

Come rigenerare certificati di sviluppo (esempio rapido con openssl):

```sh
openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout dev.key -out dev.crt -subj "/CN=localhost"
```

Per ulteriori dettagli, vedere il `README.md` principale del progetto.
