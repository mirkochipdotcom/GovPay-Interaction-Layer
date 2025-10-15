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
        $stmt = $this->pdo->prepare('SELECT id, email, password_hash, role, first_name, last_name, created_at, updated_at FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function insertUser(string $email, string $password, string $role = 'superadmin', string $firstName = '', string $lastName = ''): int
    {
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $stmt = $this->pdo->prepare('INSERT INTO users (email, password_hash, role, first_name, last_name, created_at, updated_at) VALUES (:email, :password_hash, :role, :first_name, :last_name, NOW(), NOW())');
        $stmt->execute([
            ':email' => $email,
            ':password_hash' => $hash,
            ':role' => $role,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
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
        $stmt = $this->pdo->query('SELECT id, email, role, first_name, last_name, created_at, updated_at FROM users ORDER BY email ASC');
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, email, password_hash, role, first_name, last_name, created_at, updated_at FROM users WHERE id = :id LIMIT 1');
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

    public function deleteById(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
