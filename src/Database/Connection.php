<?php
/**
 * SPDX-License-Identifier: EUPL-1.2
 * License: European Union Public Licence v1.2 (EUPL-1.2)
 */
namespace App\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $pdo = null;

    public static function getPDO(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = getenv('DB_HOST') ?: 'db';
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_NAME') ?: 'govpay';
        $user = getenv('DB_USER') ?: 'govpay';
        $pass = getenv('DB_PASSWORD') ?: '';

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbname);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            self::$pdo = new PDO($dsn, $user, $pass, $options);
            // Ensure utf8mb4
            self::$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            return self::$pdo;
        } catch (PDOException $e) {
            throw $e;
        }
    }
}
