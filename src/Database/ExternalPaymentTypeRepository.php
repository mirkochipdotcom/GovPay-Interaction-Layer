<?php
namespace App\Database;

use PDO;

class ExternalPaymentTypeRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getPDO();
    }

    /**
     * @return array<int,array{id:int,descrizione:string,url:string,created_at:string,updated_at:string}>
     */
    public function listAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, descrizione, url, created_at, updated_at FROM tipologie_pagamento_esterne ORDER BY descrizione ASC');
        return $stmt->fetchAll();
    }

    public function create(string $descrizione, string $url): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('INSERT INTO tipologie_pagamento_esterne (descrizione, url, created_at, updated_at) VALUES (:descrizione, :url, :created_at, :updated_at)');
        $stmt->execute([
            ':descrizione' => $descrizione,
            ':url' => $url,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM tipologie_pagamento_esterne WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

}
