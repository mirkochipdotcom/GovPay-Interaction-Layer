-- SPDX-License-Identifier: EUPL-1.2
-- Migration: create pendenze_massive staging table
-- Descrizione: Tabella di staging per inserimento massivo pendenze

CREATE TABLE IF NOT EXISTS pendenze_massive (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  file_batch_id VARCHAR(64) NOT NULL,
  riga INT UNSIGNED NOT NULL,
  stato ENUM('PENDING','PROCESSING','SUCCESS','ERROR') NOT NULL DEFAULT 'PENDING',
  errore TEXT NULL,
  -- Payload originale normalizzato (JSON con chiavi camelCase compatibili con create pendenza)
  payload_json JSON NULL,
  -- Response JSON ricevuta dal Backoffice (per SUCCESS/ERROR dettagli)
  response_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_batch (file_batch_id),
  INDEX idx_stato (stato)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
