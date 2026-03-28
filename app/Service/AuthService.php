<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Validation\Validator;
use RuntimeException;

final class AuthService
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    public function register(array $input): User
    {
        $errors = Validator::validate($input, [
            'email' => ['required', 'email'],
            'username' => ['required', 'min:3', 'max:50'],
            'display_name' => ['required', 'min:2', 'max:100'],
            'password' => ['required', 'min:6', 'max:255'],
        ]);

        if ($this->users->emailExists((string) ($input['email'] ?? ''))) {
            $errors['email'][] = 'Email is already taken.';
        }
        if ($this->users->usernameExists((string) ($input['username'] ?? ''))) {
            $errors['username'][] = 'Username is already taken.';
        }

        if ($errors !== []) {
            throw new RuntimeException(json_encode($errors, JSON_UNESCAPED_UNICODE) ?: 'Validation failed');
        }

        return $this->users->create([
            'email' => trim((string) $input['email']),
            'username' => trim((string) $input['username']),
            'display_name' => trim((string) $input['display_name']),
            'password_hash' => password_hash((string) $input['password'], PASSWORD_DEFAULT),
            'bio' => trim((string) ($input['bio'] ?? '')) ?: null,
        ]);
    }

    public function login(string $identity, string $password): ?User
    {
        $row = $this->users->findForAuth($identity);
        if (!$row || !password_verify($password, (string) $row['password_hash'])) {
            return null;
        }

        return User::fromRow($row);
    }
}

