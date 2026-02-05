// Wallets configuration template
// Generated from environment variables in .env
// This file is auto-generated during sync - do not edit directly

window.WALLETS_CONFIG = {
  // Digital Identity methods - controlled by .env variables
  digital_id: {
    spid: $ENABLE_SPID,
    cie: $ENABLE_CIE,
    cie_oidc: $ENABLE_CIE_OIDC,
    it_wallet: $ENABLE_IT_WALLET
  },
  // Alternative authentication methods
  alternative_id: {
    idem: $ENABLE_IDEM,
    eidas: $ENABLE_EIDAS
  }
};

