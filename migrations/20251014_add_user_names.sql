-- Migration: aggiunge first_name e last_name alla tabella users
-- ATTENZIONE: adattare i tipi/length a seconda del DB e degli standard del progetto

-- MySQL / MariaDB
ALTER TABLE users
  ADD COLUMN first_name VARCHAR(255) NOT NULL DEFAULT '' AFTER role,
  ADD COLUMN last_name VARCHAR(255) NOT NULL DEFAULT '' AFTER first_name;

-- PostgreSQL (decommentare se necessario)
-- ALTER TABLE users ADD COLUMN first_name VARCHAR(255) DEFAULT '';
-- ALTER TABLE users ADD COLUMN last_name VARCHAR(255) DEFAULT '';