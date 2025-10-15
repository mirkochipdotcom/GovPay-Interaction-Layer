<?php
       
declare(strict_types=1);

// Crea le tabelle base se non esistono. Idempotente.
require __DIR__ . '/../vendor/autoload.php';

use App\Database\Connection;

// Recupera la connessione PDO
try {
    $pdo = Connection::getPDO();
} catch (Throwable $e) {
    fwrite(STDERR, "DB connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

$statements = [
    // users
    "CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('superadmin','admin','user') NOT NULL DEFAULT 'user',
        first_name VARCHAR(255) NOT NULL DEFAULT '',
        last_name VARCHAR(255) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    // entrate_tipologie
    "CREATE TABLE IF NOT EXISTS entrate_tipologie (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    // tipologie_pagamento_esterne
    "CREATE TABLE IF NOT EXISTS tipologie_pagamento_esterne (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        descrizione VARCHAR(255) NOT NULL,
        url VARCHAR(500) NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
];

foreach ($statements as $sql) {
    try {
        $pdo->exec($sql);
    } catch (Throwable $e) {
        fwrite(STDERR, "Error executing SQL: " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "Created tables (if not exists)\n";

// Seeding opzionale: crea superadmin se ADMIN_EMAIL e ADMIN_PASSWORD sono definiti
$adminEmail = getenv('ADMIN_EMAIL') ?: '';
$adminPass = getenv('ADMIN_PASSWORD') ?: '';
if ($adminEmail !== '' && $adminPass !== '') {
    try {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $adminEmail]);
        $exists = $stmt->fetchColumn();
        if (!$exists) {
            $pwdHash = password_hash($adminPass, PASSWORD_BCRYPT);
            $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
            $ins = $pdo->prepare('INSERT INTO users (email, password_hash, role, first_name, last_name, created_at, updated_at) VALUES (:email, :pwd, :role, :first, :last, :created, :updated)');
            $ins->execute([
                ':email' => $adminEmail,
                ':pwd' => $pwdHash,
                ':role' => 'superadmin',
                ':first' => getenv('ADMIN_FIRST_NAME') ?: 'Super',
                ':last' => getenv('ADMIN_LAST_NAME') ?: 'Admin',
                ':created' => $now,
                ':updated' => $now,
            ]);
            echo "Seeded superadmin: {$adminEmail}\n";
        } else {
            echo "Superadmin already exists: {$adminEmail}\n";
        }
    } catch (Throwable $e) {
        fwrite(STDERR, "Error seeding superadmin: " . $e->getMessage() . "\n");
        // non fatal: proseguire
    }
}

exit(0);
?>
