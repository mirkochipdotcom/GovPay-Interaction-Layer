-- Aggiunge un campo descrizione_estesa per uso locale (es. tooltip/testo lungo).

ALTER TABLE entrate_tipologie
  ADD COLUMN IF NOT EXISTS descrizione_estesa TEXT NULL AFTER descrizione_locale;

ALTER TABLE tipologie_pagamento_esterne
  ADD COLUMN IF NOT EXISTS descrizione_estesa TEXT NULL AFTER descrizione;
