<?php

declare(strict_types=1);

namespace App\Entity;

final class Category
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $parentId,
        public readonly string $name,
        public readonly string $slug,
        public readonly int $depth,
        public readonly int $sortOrder,
        public readonly bool $isEnabled
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            isset($row['parent_id']) ? (int) $row['parent_id'] : null,
            (string) $row['name'],
            (string) $row['slug'],
            (int) $row['depth'],
            (int) $row['sort_order'],
            (bool) $row['is_enabled']
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'parentId' => $this->parentId,
            'name' => $this->name,
            'slug' => $this->slug,
            'depth' => $this->depth,
            'sortOrder' => $this->sortOrder,
            'isEnabled' => $this->isEnabled,
        ];
    }
}

