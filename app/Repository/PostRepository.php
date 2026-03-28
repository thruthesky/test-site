<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\Database;
use App\Entity\Post;
use PDO;

final class PostRepository
{
    public function __construct(private readonly ?PDO $connection = null)
    {
    }

    private function db(): PDO
    {
        return $this->connection ?? Database::connection();
    }

    public function count(): int
    {
        return (int) $this->db()->query('SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL')->fetchColumn();
    }

    public function create(array $data): Post
    {
        $statement = $this->db()->prepare(
            'INSERT INTO posts (category_id, user_id, title, content)
             VALUES (:category_id, :user_id, :title, :content)
             RETURNING *'
        );
        $statement->execute([
            'category_id' => $data['category_id'],
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'content' => $data['content'],
        ]);

        return Post::fromRow((array) $statement->fetch());
    }

    public function update(int $id, array $data): ?Post
    {
        $statement = $this->db()->prepare(
            'UPDATE posts
             SET category_id = :category_id,
                 title = :title,
                 content = :content,
                 updated_at = NOW()
             WHERE id = :id AND deleted_at IS NULL
             RETURNING *'
        );
        $statement->execute([
            'id' => $id,
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'content' => $data['content'],
        ]);

        $row = $statement->fetch();
        return $row ? Post::fromRow($row) : null;
    }

    public function softDelete(int $id): void
    {
        $statement = $this->db()->prepare('UPDATE posts SET deleted_at = NOW() WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function incrementViews(int $id): void
    {
        $statement = $this->db()->prepare('UPDATE posts SET view_count = view_count + 1 WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db()->prepare(
            'SELECT p.*, 
                    u.display_name AS author_name,
                    u.profile_photo_path AS author_photo,
                    c.name AS category_name,
                    c.slug AS category_slug,
                    (
                        SELECT COUNT(*) FROM comments cm
                        WHERE cm.post_id = p.id
                    ) AS comment_count
             FROM posts p
             INNER JOIN users u ON u.id = p.user_id
             INNER JOIN categories c ON c.id = p.category_id
             WHERE p.id = :id AND p.deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    public function listByCategoryIds(array $categoryIds, int $page = 1, int $perPage = 10): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $params = [];
        $placeholders = [];

        foreach ($categoryIds as $index => $categoryId) {
            $key = 'category_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $categoryId;
        }

        $where = $placeholders === [] ? '' : 'AND p.category_id IN (' . implode(', ', $placeholders) . ')';

        $countSql = 'SELECT COUNT(*) FROM posts p WHERE p.deleted_at IS NULL ' . $where;
        $countStatement = $this->db()->prepare($countSql);
        $countStatement->execute($params);
        $total = (int) $countStatement->fetchColumn();

        $sql = 'SELECT p.id, p.title, p.view_count, p.created_at,
                       u.display_name AS author_name,
                       c.name AS category_name,
                       c.slug AS category_slug,
                       (
                           SELECT COUNT(*) FROM comments cm
                           WHERE cm.post_id = p.id
                       ) AS comment_count
                FROM posts p
                INNER JOIN users u ON u.id = p.user_id
                INNER JOIN categories c ON c.id = p.category_id
                WHERE p.deleted_at IS NULL ' . $where . '
                ORDER BY p.created_at DESC, p.id DESC
                LIMIT :limit OFFSET :offset';

        $statement = $this->db()->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value, PDO::PARAM_INT);
        }
        $statement->bindValue('limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => $statement->fetchAll(),
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'pages' => (int) max(1, ceil($total / $perPage)),
            ],
        ];
    }

    public function recent(int $limit = 6): array
    {
        $statement = $this->db()->prepare(
            'SELECT p.id, p.title, p.created_at, c.slug AS category_slug
             FROM posts p
             INNER JOIN categories c ON c.id = p.category_id
             WHERE p.deleted_at IS NULL
             ORDER BY p.created_at DESC, p.id DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }
}

