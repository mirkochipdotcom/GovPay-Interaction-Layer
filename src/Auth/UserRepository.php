<?php
namespace App\Auth;

use App\Database\Connection;
use PDO;

class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getPDO();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, email, password_hash, role, created_at, updated_at FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function insertUser(string $email, string $password, string $role = 'superadmin'): int
    {
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $stmt = $this->pdo->prepare('INSERT INTO users (email, password_hash, role, created_at, updated_at) VALUES (:email, :password_hash, :role, NOW(), NOW())');
        $stmt->execute([
            ':email' => $email,
            ':password_hash' => $hash,
            ':role' => $role,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
