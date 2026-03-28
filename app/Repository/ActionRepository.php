<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\Database;
use PDO;

final class ActionRepository
{
    public function __construct(private readonly ?PDO $connection = null)
    {
    }

    private function db(): PDO
    {
        return $this->connection ?? Database::connection();
    }

    public function record(string $action, int $userId, string $targetType, int $targetId, ?string $reason = null): void
    {
        $table = match ($action) {
            'follow' => 'follows',
            'block' => 'blocks',
            'report' => 'reports',
            default => throw new \InvalidArgumentException('Unknown action'),
        };

        if ($action === 'report') {
            $statement = $this->db()->prepare(
                'INSERT INTO reports (user_id, target_type, target_id, reason)
                 VALUES (:user_id, :target_type, :target_id, :reason)'
            );
            $statement->execute([
                'user_id' => $userId,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'reason' => $reason,
            ]);
            return;
        }

        $statement = $this->db()->prepare(
            "INSERT INTO {$table} (user_id, target_type, target_id)
             VALUES (:user_id, :target_type, :target_id)
             ON CONFLICT (user_id, target_type, target_id) DO NOTHING"
        );
        $statement->execute([
            'user_id' => $userId,
            'target_type' => $targetType,
            'target_id' => $targetId,
        ]);
    }
}

