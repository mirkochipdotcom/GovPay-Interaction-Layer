<?php
/**
 * SPDX-License-Identifier: EUPL-1.2
 * License: European Union Public Licence v1.2 (EUPL-1.2)
 */
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
        $stmt = $this->pdo->prepare('SELECT id, email, password_hash, role, first_name, last_name, is_disabled, disabled_at, created_at, updated_at FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function insertUser(string $email, string $password, string $role = 'superadmin', string $firstName = '', string $lastName = '', bool $disabled = false): int
    {
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $stmt = $this->pdo->prepare('INSERT INTO users (email, password_hash, role, first_name, last_name, is_disabled, created_at, updated_at, disabled_at) VALUES (:email, :password_hash, :role, :first_name, :last_name, :is_disabled, NOW(), NOW(), :disabled_at)');
        $stmt->execute([
            ':email' => $email,
            ':password_hash' => $hash,
            ':role' => $role,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':is_disabled' => $disabled ? 1 : 0,
            ':disabled_at' => $disabled ? (new \DateTimeImmutable())->format('Y-m-d H:i:s') : null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /** @return array<int, array{id:int,email:string,role:string,created_at:string,updated_at:string}> */
    public function listAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, email, role, first_name, last_name, is_disabled, disabled_at, created_at, updated_at FROM users ORDER BY is_disabled ASC, email ASC');
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, email, password_hash, role, first_name, last_name, is_disabled, disabled_at, created_at, updated_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updatePasswordById(int $id, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_ARGON2ID);
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':hash' => $hash, ':id' => $id]);
    }

    public function updateUser(int $id, string $email, string $role, string $firstName = '', string $lastName = ''): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET email = :email, role = :role, first_name = :first_name, last_name = :last_name, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':email' => $email, ':role' => $role, ':first_name' => $firstName, ':last_name' => $lastName, ':id' => $id]);
    }

    public function setDisabled(int $id, bool $disabled): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET is_disabled = :is_disabled, disabled_at = :disabled_at, updated_at = NOW() WHERE id = :id');
        $disabledAt = $disabled ? (new \DateTimeImmutable())->format('Y-m-d H:i:s') : null;
        $stmt->execute([
            ':is_disabled' => $disabled ? 1 : 0,
            ':disabled_at' => $disabledAt,
            ':id' => $id,
        ]);
    }

    public function countByRole(string $role, bool $includeDisabled = true): int
    {
        $sql = 'SELECT COUNT(*) as c FROM users WHERE role = :role';
        $params = [':role' => $role];
        if (!$includeDisabled) {
            $sql .= ' AND (is_disabled IS NULL OR is_disabled = 0)';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['c'] ?? 0);
    }
}
