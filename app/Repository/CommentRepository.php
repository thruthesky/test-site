<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\Database;
use App\Entity\Comment;
use PDO;

final class CommentRepository
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
        return (int) $this->db()->query('SELECT COUNT(*) FROM comments')->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db()->prepare('SELECT * FROM comments WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    public function create(array $data): Comment
    {
        $statement = $this->db()->prepare(
            'INSERT INTO comments (post_id, user_id, parent_id, content, depth)
             VALUES (:post_id, :user_id, :parent_id, :content, :depth)
             RETURNING *'
        );
        $statement->execute([
            'post_id' => $data['post_id'],
            'user_id' => $data['user_id'],
            'parent_id' => $data['parent_id'] ?? null,
            'content' => $data['content'],
            'depth' => $data['depth'] ?? 0,
        ]);

        return Comment::fromRow((array) $statement->fetch());
    }

    public function update(int $id, string $content): ?Comment
    {
        $statement = $this->db()->prepare(
            'UPDATE comments
             SET content = :content,
                 updated_at = NOW()
             WHERE id = :id
             RETURNING *'
        );
        $statement->execute(['id' => $id, 'content' => $content]);
        $row = $statement->fetch();

        return $row ? Comment::fromRow($row) : null;
    }

    public function softDelete(int $id): void
    {
        $statement = $this->db()->prepare(
            "UPDATE comments
             SET content = '[deleted]',
                 deleted_at = NOW(),
                 updated_at = NOW()
             WHERE id = :id"
        );
        $statement->execute(['id' => $id]);
    }

    public function listByPostId(int $postId): array
    {
        $statement = $this->db()->prepare(
            'SELECT c.*, u.display_name AS author_name, u.profile_photo_path AS author_photo
             FROM comments c
             INNER JOIN users u ON u.id = c.user_id
             WHERE c.post_id = :post_id
             ORDER BY c.created_at ASC, c.id ASC'
        );
        $statement->execute(['post_id' => $postId]);
        return $statement->fetchAll();
    }

    public function recent(int $limit = 6): array
    {
        $statement = $this->db()->prepare(
            'SELECT c.id, c.post_id, c.content, c.created_at, p.title AS post_title
             FROM comments c
             INNER JOIN posts p ON p.id = c.post_id
             ORDER BY c.created_at DESC, c.id DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }
}

