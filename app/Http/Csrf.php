<?php

declare(strict_types=1);

namespace App\Http;

final class Csrf
{
    public static function token(): string
    {
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    public static function verify(Request $request): bool
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return true;
        }

        $provided = (string) ($request->header('X-CSRF-Token') ?? $request->input('_csrf'));
        return hash_equals((string) ($_SESSION['_csrf_token'] ?? ''), $provided);
    }
}

