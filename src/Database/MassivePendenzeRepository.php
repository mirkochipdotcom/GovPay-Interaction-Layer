<?php
declare(strict_types=1);

namespace App\Database;

use PDO;
use DateTimeImmutable;

class MassivePendenzeRepository
{
    public function __construct(private ?PDO $pdo = null)
    {
        $this->pdo = $this->pdo ?: Connection::getPDO();
    }

    public function insertPending(string $fileBatchId, int $riga, array $payload, ?string $errore = null): int
    {
        $sql = 'INSERT INTO pendenze_massive (file_batch_id, riga, stato, errore, payload_json, created_at, updated_at)
                VALUES (:batch, :riga, :stato, :errore, :payload, :created, :updated)';
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':batch' => $fileBatchId,
            ':riga' => $riga,
            ':stato' => $errore ? 'ERROR' : 'PENDING',
            ':errore' => $errore,
            ':payload' => json_encode($payload, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
            ':created' => $now,
            ':updated' => $now,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function setProcessing(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE pendenze_massive SET stato = "PROCESSING", updated_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function setResult(int $id, bool $ok, ?array $response = null, ?string $errore = null): void
    {
        $stmt = $this->pdo->prepare('UPDATE pendenze_massive SET stato = :stato, response_json = :resp, errore = :err, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':stato' => $ok ? 'SUCCESS' : 'ERROR',
            ':resp' => $response ? json_encode($response, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) : null,
            ':err' => $errore,
            ':id' => $id,
        ]);
    }

    public function listByBatch(string $fileBatchId, ?string $stato = null, int $page = 1, int $perPage = 50): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $params = [':batch' => $fileBatchId, ':limit' => $perPage, ':offset' => $offset];
        $sql = 'SELECT * FROM pendenze_massive WHERE file_batch_id = :batch';
        if ($stato) { $sql .= ' AND stato = :stato'; $params[':stato'] = $stato; }
        $sql .= ' ORDER BY riga ASC LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            if (in_array($k, [':limit', ':offset'], true)) {
                $stmt->bindValue($k, (int)$v, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($k, $v);
            }
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return $rows;
    }

    public function countByBatch(string $fileBatchId, ?string $stato = null): int
    {
        $sql = 'SELECT COUNT(*) FROM pendenze_massive WHERE file_batch_id = :batch';
        $params = [':batch' => $fileBatchId];
        if ($stato) { $sql .= ' AND stato = :stato'; $params[':stato'] = $stato; }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}
