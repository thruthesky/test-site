<?php

declare(strict_types=1);

namespace App\Entity;

final class Post
{
    public function __construct(
        public readonly int $id,
        public readonly int $categoryId,
        public readonly int $userId,
        public readonly string $title,
        public readonly string $content,
        public readonly int $viewCount,
        public readonly string $createdAt,
        public readonly ?string $updatedAt
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['category_id'],
            (int) $row['user_id'],
            (string) $row['title'],
            (string) $row['content'],
            (int) $row['view_count'],
            (string) $row['created_at'],
            $row['updated_at'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'categoryId' => $this->categoryId,
            'userId' => $this->userId,
            'title' => $this->title,
            'content' => $this->content,
            'viewCount' => $this->viewCount,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}

