<?php
declare(strict_types=1);

// Simple migration runner: ensures users table exists and seeds a superadmin

require __DIR__ . '/../vendor/autoload.php';

use App\Database\Connection;
use App\Auth\UserRepository;

function waitForDb(int $timeoutSeconds = 30): void {
    $start = time();
    while (true) {
        try {
            Connection::getPDO()->query('SELECT 1');
            return;
        } catch (Throwable $e) {
            if ((time() - $start) > $timeoutSeconds) {
                throw $e;
            }
            usleep(300_000); // 300ms
        }
    }
}

function migrate(): void {
    $pdo = Connection::getPDO();
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM("superadmin","admin","user") NOT NULL DEFAULT "user",
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    // Tipologie di entrata (override locale rispetto a Backoffice)
    $pdo->exec('CREATE TABLE IF NOT EXISTS entrate_tipologie (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_dominio VARCHAR(64) NOT NULL,
        id_entrata VARCHAR(128) NOT NULL,
        descrizione VARCHAR(255) NULL,
        iban_accredito VARCHAR(34) NULL,
        codice_contabilita VARCHAR(128) NULL,
        abilitato_backoffice TINYINT(1) NOT NULL DEFAULT 0,
        override_locale TINYINT(1) NULL,
        effective_enabled TINYINT(1) NOT NULL DEFAULT 0,
        sorgente VARCHAR(32) NOT NULL DEFAULT "backoffice",
        updated_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uniq_dom_entrata (id_dominio, id_entrata)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    // Seed superadmin if env provided
    $adminEmail = getenv('ADMIN_EMAIL') ?: '';
    $adminPassword = getenv('ADMIN_PASSWORD') ?: '';
    if ($adminEmail !== '' && $adminPassword !== '') {
        $repo = new UserRepository();
        $existing = $repo->findByEmail($adminEmail);
        if (!$existing) {
            $repo->insertUser($adminEmail, $adminPassword, 'superadmin');
            error_log("[MIGRATIONS] Seeded superadmin: {$adminEmail}");
        } else {
            error_log('[MIGRATIONS] Superadmin already exists, skip seeding');
        }
    } else {
        error_log('[MIGRATIONS] ADMIN_EMAIL/ADMIN_PASSWORD not provided; skip seeding');
    }
}

try {
    waitForDb(45);
    migrate();
    echo "Migrations completed\n";
} catch (Throwable $e) {
    // Non-fatal: print and allow container to start anyway
    fwrite(STDERR, "Migration error: " . $e->getMessage() . "\n");
}
