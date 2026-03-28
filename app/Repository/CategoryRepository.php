<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\Database;
use App\Entity\Category;
use PDO;

final class CategoryRepository
{
    public function __construct(private readonly ?PDO $connection = null)
    {
    }

    private function db(): PDO
    {
        return $this->connection ?? Database::connection();
    }

    public function findById(int $id): ?Category
    {
        $statement = $this->db()->prepare('SELECT * FROM categories WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ? Category::fromRow($row) : null;
    }

    public function findBySlug(string $slug, bool $enabledOnly = true): ?Category
    {
        $sql = 'SELECT * FROM categories WHERE slug = :slug';
        if ($enabledOnly) {
            $sql .= ' AND is_enabled = TRUE';
        }
        $statement = $this->db()->prepare($sql . ' LIMIT 1');
        $statement->execute(['slug' => $slug]);
        $row = $statement->fetch();

        return $row ? Category::fromRow($row) : null;
    }

    public function tree(bool $enabledOnly = true): array
    {
        $sql = 'SELECT * FROM categories';
        if ($enabledOnly) {
            $sql .= ' WHERE is_enabled = TRUE';
        }
        $sql .= ' ORDER BY depth ASC, sort_order ASC, id ASC';

        $rows = $this->db()->query($sql)->fetchAll();
        $top = [];
        $children = [];

        foreach ($rows as $row) {
            $category = Category::fromRow($row)->toArray();
            $category['children'] = [];

            if ($category['parentId'] === null) {
                $top[$category['id']] = $category;
            } else {
                $children[$category['parentId']][] = $category;
            }
        }

        foreach ($children as $parentId => $items) {
            if (isset($top[$parentId])) {
                $top[$parentId]['children'] = $items;
            }
        }

        return array_values($top);
    }

    public function adminList(): array
    {
        return $this->tree(false);
    }

    public function create(array $data): Category
    {
        $statement = $this->db()->prepare(
            'INSERT INTO categories (parent_id, name, slug, depth, sort_order, is_enabled, description)
             VALUES (:parent_id, :name, :slug, :depth, :sort_order, :is_enabled, :description)
             RETURNING *'
        );
        $statement->execute([
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'depth' => $data['depth'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_enabled' => $data['is_enabled'] ?? true,
            'description' => $data['description'] ?? null,
        ]);

        return Category::fromRow((array) $statement->fetch());
    }

    public function update(int $id, array $data): ?Category
    {
        $statement = $this->db()->prepare(
            'UPDATE categories
             SET parent_id = :parent_id,
                 name = :name,
                 slug = :slug,
                 depth = :depth,
                 sort_order = :sort_order,
                 is_enabled = :is_enabled,
                 description = :description,
                 updated_at = NOW()
             WHERE id = :id
             RETURNING *'
        );
        $statement->execute([
            'id' => $id,
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'depth' => $data['depth'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_enabled' => $data['is_enabled'] ?? true,
            'description' => $data['description'] ?? null,
        ]);

        $row = $statement->fetch();
        return $row ? Category::fromRow($row) : null;
    }

    public function delete(int $id): void
    {
        $statement = $this->db()->prepare('DELETE FROM categories WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM categories WHERE slug = :slug';
        $params = ['slug' => $slug];

        if ($ignoreId !== null) {
            $sql .= ' AND id != :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }

        $statement = $this->db()->prepare($sql);
        $statement->execute($params);
        return (int) $statement->fetchColumn() > 0;
    }

    public function descendantIds(int $categoryId): array
    {
        $statement = $this->db()->prepare(
            'WITH RECURSIVE descendants AS (
                SELECT id, parent_id FROM categories WHERE id = :id
                UNION ALL
                SELECT c.id, c.parent_id
                FROM categories c
                INNER JOIN descendants d ON c.parent_id = d.id
             )
             SELECT id FROM descendants'
        );
        $statement->execute(['id' => $categoryId]);
        return array_map('intval', array_column($statement->fetchAll(), 'id'));
    }
}

