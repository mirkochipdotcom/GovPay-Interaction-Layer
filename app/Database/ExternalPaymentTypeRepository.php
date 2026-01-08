<?php
/**
 * SPDX-License-Identifier: EUPL-1.2
 * License: European Union Public Licence v1.2 (EUPL-1.2)
 */
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
     * @return array<int,array{id:int,descrizione:string,descrizione_estesa:?string,url:string,created_at:string,updated_at:string}>
     */
    public function listAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, descrizione, descrizione_estesa, url, created_at, updated_at FROM tipologie_pagamento_esterne ORDER BY descrizione ASC');
        return $stmt->fetchAll();
    }

    public function create(string $descrizione, ?string $descrizioneEstesa, string $url): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('INSERT INTO tipologie_pagamento_esterne (descrizione, descrizione_estesa, url, created_at, updated_at) VALUES (:descrizione, :descrizione_estesa, :url, :created_at, :updated_at)');
        $stmt->execute([
            ':descrizione' => $descrizione,
            ':descrizione_estesa' => ($descrizioneEstesa === null || trim($descrizioneEstesa) === '') ? null : $descrizioneEstesa,
            ':url' => $url,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
    }

    public function update(int $id, string $descrizione, ?string $descrizioneEstesa, string $url): void
    {
        $stmt = $this->pdo->prepare('UPDATE tipologie_pagamento_esterne SET descrizione = :descrizione, descrizione_estesa = :descrizione_estesa, url = :url, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':descrizione' => $descrizione,
            ':descrizione_estesa' => ($descrizioneEstesa === null || trim($descrizioneEstesa) === '') ? null : $descrizioneEstesa,
            ':url' => $url,
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM tipologie_pagamento_esterne WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

}
