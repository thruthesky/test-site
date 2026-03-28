<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\Database;
use App\Entity\User;
use PDO;

final class UserRepository
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
        return (int) $this->db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function findById(int $id): ?User
    {
        $statement = $this->db()->prepare('SELECT * FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ? User::fromRow($row) : null;
    }

    public function findForAuth(string $identity): ?array
    {
        $statement = $this->db()->prepare('SELECT * FROM users WHERE email = :identity OR username = :identity LIMIT 1');
        $statement->execute(['identity' => $identity]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    public function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE email = :email';
        $params = ['email' => $email];

        if ($ignoreId !== null) {
            $sql .= ' AND id != :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }

        $statement = $this->db()->prepare($sql);
        $statement->execute($params);
        return (int) $statement->fetchColumn() > 0;
    }

    public function usernameExists(string $username, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE username = :username';
        $params = ['username' => $username];

        if ($ignoreId !== null) {
            $sql .= ' AND id != :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }

        $statement = $this->db()->prepare($sql);
        $statement->execute($params);
        return (int) $statement->fetchColumn() > 0;
    }

    public function create(array $data): User
    {
        $role = $this->count() === 0 ? 'admin' : 'member';
        $statement = $this->db()->prepare(
            'INSERT INTO users (email, username, display_name, password_hash, bio, role)
             VALUES (:email, :username, :display_name, :password_hash, :bio, :role)
             RETURNING *'
        );
        $statement->execute([
            'email' => $data['email'],
            'username' => $data['username'],
            'display_name' => $data['display_name'],
            'password_hash' => $data['password_hash'],
            'bio' => $data['bio'] ?? null,
            'role' => $role,
        ]);

        return User::fromRow((array) $statement->fetch());
    }

    public function update(int $id, array $data): ?User
    {
        $statement = $this->db()->prepare(
            'UPDATE users
             SET email = :email,
                 username = :username,
                 display_name = :display_name,
                 bio = :bio,
                 profile_photo_path = :profile_photo_path,
                 updated_at = NOW()
             WHERE id = :id
             RETURNING *'
        );
        $statement->execute([
            'id' => $id,
            'email' => $data['email'],
            'username' => $data['username'],
            'display_name' => $data['display_name'],
            'bio' => $data['bio'] ?? null,
            'profile_photo_path' => $data['profile_photo_path'] ?? null,
        ]);

        $row = $statement->fetch();
        return $row ? User::fromRow($row) : null;
    }

    public function latestPhotos(int $limit = 6): array
    {
        $statement = $this->db()->prepare(
            'SELECT id, display_name, profile_photo_path
             FROM users
             WHERE profile_photo_path IS NOT NULL
             ORDER BY updated_at DESC, id DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }
}

