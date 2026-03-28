<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $body,
        private readonly array $files,
        private readonly array $server,
        private readonly array $headers,
        private array $session
    ) {
    }

    public static function capture(bool $apiMode = false): self
    {
        $route = $_GET['route'] ?? null;
        $path = $apiMode
            ? (is_string($route) && $route !== '' ? $route : '/')
            : (is_string($route) && $route !== '' ? $route : (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));

        $rawHeaders = function_exists('getallheaders') ? getallheaders() : [];
        $headers = [];
        foreach ($rawHeaders as $key => $value) {
            $headers[strtolower((string) $key)] = $value;
        }
        $rawBody = file_get_contents('php://input') ?: '';
        $contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? '';
        $jsonBody = [];
        if (str_contains($contentType, 'application/json') && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $jsonBody = $decoded;
            }
        }

        $body = $_POST;
        if ($jsonBody !== []) {
            $body = $jsonBody;
        } elseif (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['PUT', 'PATCH', 'DELETE'], true)) {
            parse_str($rawBody, $parsedBody);
            if (is_array($parsedBody) && $parsedBody !== []) {
                $body = $parsedBody;
            }
        }

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            '/' . ltrim($path, '/'),
            $_GET,
            $body,
            $_FILES,
            $_SERVER,
            $headers,
            $_SESSION
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->body;
    }

    public function files(): array
    {
        return $this->files;
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        return is_array($file) ? $file : null;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        return $this->headers[strtolower($key)] ?? $default;
    }

    public function session(): array
    {
        return $this->session;
    }

    public function sessionGet(string $key, mixed $default = null): mixed
    {
        return $this->session[$key] ?? $default;
    }

    public function sessionPut(string $key, mixed $value): void
    {
        $this->session[$key] = $value;
        $_SESSION[$key] = $value;
    }

    public function sessionForget(string $key): void
    {
        unset($this->session[$key], $_SESSION[$key]);
    }
}
