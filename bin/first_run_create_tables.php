<?php
declare(strict_types=1);

// Crea le tabelle base se non esistono. Idempotente.
require __DIR__ . '/../vendor/autoload.php';

use App\Database\Connection;

function createTables(): void {
    $pdo = Connection::getPDO();

    $pdo->beginTransaction();
    try {
        // users
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('superadmin','admin','user') NOT NULL DEFAULT 'user',
            first_name VARCHAR(255) NOT NULL DEFAULT '',
            last_name VARCHAR(255) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // entrate_tipologie
        $pdo->exec("CREATE TABLE IF NOT EXISTS entrate_tipologie (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            id_dominio VARCHAR(64) NOT NULL,
            id_entrata VARCHAR(128) NOT NULL,
            descrizione VARCHAR(255) NULL,
            iban_accredito VARCHAR(34) NULL,
            codice_contabilita VARCHAR(128) NULL,
            abilitato_backoffice TINYINT(1) NOT NULL DEFAULT 0,
            override_locale TINYINT(1) NULL,
            external_url VARCHAR(500) NULL,
            effective_enabled TINYINT(1) NOT NULL DEFAULT 0,
            sorgente VARCHAR(32) NOT NULL DEFAULT 'backoffice',
            updated_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY uniq_dom_entrata (id_dominio, id_entrata)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // tipologie_pagamento_esterne
        $pdo->exec("CREATE TABLE IF NOT EXISTS tipologie_pagamento_esterne (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            descrizione VARCHAR(255) NOT NULL,
            url VARCHAR(500) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->commit();
        echo "Created tables (if not exists)\n";
    } catch (Throwable $e) {
        $pdo->rollBack();
        fwrite(STDERR, "Error creating tables: " . $e->getMessage() . "\n");
        exit(1);
    }
}

try {
    createTables();
} catch (Throwable $e) {
    fwrite(STDERR, "Fatal: " . $e->getMessage() . "\n");
    exit(1);
}

exit(0);
