-- SPDX-License-Identifier: EUPL-1.2
-- Migration: add disabled state to users
-- Descrizione: introduce colonne per gestire lo stato di disabilitazione utenti

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS is_disabled TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER role,
    ADD COLUMN IF NOT EXISTS disabled_at DATETIME NULL AFTER updated_at;
