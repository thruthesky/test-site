<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\Env;
use App\Entity\User;
use App\Repository\UserRepository;
use RuntimeException;

final class ProfileService
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    public function update(User $user, array $input): User
    {
        $email = trim((string) ($input['email'] ?? ''));
        $username = trim((string) ($input['username'] ?? ''));
        $displayName = trim((string) ($input['display_name'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A valid email is required.');
        }
        if ($username === '' || mb_strlen($username) < 3) {
            throw new RuntimeException('Username must be at least 3 characters.');
        }
        if ($displayName === '' || mb_strlen($displayName) < 2) {
            throw new RuntimeException('Display name must be at least 2 characters.');
        }

        if ($this->users->emailExists($email, $user->id)) {
            throw new RuntimeException('Email is already taken.');
        }
        if ($this->users->usernameExists($username, $user->id)) {
            throw new RuntimeException('Username is already taken.');
        }

        $updated = $this->users->update($user->id, [
            'email' => $email,
            'username' => $username,
            'display_name' => $displayName,
            'bio' => trim((string) ($input['bio'] ?? '')) ?: null,
            'profile_photo_path' => $user->profilePhotoPath,
        ]);

        if (!$updated) {
            throw new RuntimeException('Failed to update profile.');
        }

        return $updated;
    }

    public function uploadPhoto(User $user, array $file): User
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Profile photo upload failed.');
        }

        $mime = mime_content_type((string) $file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            throw new RuntimeException('Unsupported image type.');
        }

        $uploadPath = Env::get('UPLOAD_PUBLIC_PATH', dirname(__DIR__, 2) . '/public/uploads/profiles');
        if (!is_dir($uploadPath) && !mkdir($uploadPath, 0777, true) && !is_dir($uploadPath)) {
            throw new RuntimeException('Unable to create upload directory.');
        }

        $extension = pathinfo((string) $file['name'], PATHINFO_EXTENSION) ?: 'bin';
        $filename = sprintf('profile-%d-%s.%s', $user->id, bin2hex(random_bytes(6)), $extension);
        $target = rtrim($uploadPath, '/') . '/' . $filename;

        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            throw new RuntimeException('Unable to move uploaded file.');
        }

        $urlBase = rtrim((string) Env::get('UPLOAD_PUBLIC_URL_BASE', '/uploads/profiles'), '/');
        $updated = $this->users->update($user->id, [
            'email' => $user->email,
            'username' => $user->username,
            'display_name' => $user->displayName,
            'bio' => $user->bio,
            'profile_photo_path' => $urlBase . '/' . $filename,
        ]);

        if (!$updated) {
            throw new RuntimeException('Unable to save profile photo.');
        }

        return $updated;
    }
}

