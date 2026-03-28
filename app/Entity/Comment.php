<?php

declare(strict_types=1);

namespace App\Entity;

final class Comment
{
    public function __construct(
        public readonly int $id,
        public readonly int $postId,
        public readonly int $userId,
        public readonly ?int $parentId,
        public readonly string $content,
        public readonly int $depth,
        public readonly ?string $deletedAt,
        public readonly string $createdAt
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['post_id'],
            (int) $row['user_id'],
            isset($row['parent_id']) ? (int) $row['parent_id'] : null,
            (string) $row['content'],
            (int) $row['depth'],
            $row['deleted_at'] ?? null,
            (string) $row['created_at']
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'postId' => $this->postId,
            'userId' => $this->userId,
            'parentId' => $this->parentId,
            'content' => $this->content,
            'depth' => $this->depth,
            'deletedAt' => $this->deletedAt,
            'createdAt' => $this->createdAt,
        ];
    }
}
