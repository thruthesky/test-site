<?php

declare(strict_types=1);

namespace App\Service;

final class SlugService
{
    public function make(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9가-힣]+/u', '-', $slug) ?? $slug;
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'item-' . bin2hex(random_bytes(3));
    }
}

