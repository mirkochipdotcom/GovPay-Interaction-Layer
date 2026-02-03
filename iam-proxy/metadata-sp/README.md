# Metadata Service Provider (frontoffice)

Questa directory contiene i metadata SAML del Service Provider (frontoffice cittadini) richiesti da SATOSA.

## File generati

- `frontoffice_sp.xml` - Metadata SAML2 SPSSODescriptor
- `frontoffice_sp` - Copia senza estensione (pysaml2 legge entrambi)

## Generazione automatica

I metadata vengono generati automaticamente se non esistono. Per rigenerarli manualmente, segui le istruzioni nel README principale del progetto.

## Note

- **Non versionare questi file**: Sono specifici per l'ambiente (dev/prod) e dipendono da `FRONTOFFICE_PUBLIC_BASE_URL`
- Entity ID: `{FRONTOFFICE_PUBLIC_BASE_URL}/saml/sp`
- Assertion Consumer Service: `{FRONTOFFICE_PUBLIC_BASE_URL}/spid/callback`
- Single Logout Service: `{FRONTOFFICE_PUBLIC_BASE_URL}/spid/logout`
