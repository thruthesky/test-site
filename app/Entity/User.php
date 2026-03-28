<?php

declare(strict_types=1);

namespace App\Entity;

final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $username,
        public readonly string $displayName,
        public readonly ?string $bio,
        public readonly ?string $profilePhotoPath,
        public readonly string $role,
        public readonly string $createdAt
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['email'],
            (string) $row['username'],
            (string) $row['display_name'],
            $row['bio'] ?? null,
            $row['profile_photo_path'] ?? null,
            (string) $row['role'],
            (string) $row['created_at']
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'username' => $this->username,
            'displayName' => $this->displayName,
            'bio' => $this->bio,
            'profilePhotoUrl' => $this->profilePhotoPath,
            'role' => $this->role,
            'createdAt' => $this->createdAt,
        ];
    }
}

