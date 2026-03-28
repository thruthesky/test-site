<?php

declare(strict_types=1);

namespace App\Repository;

final class StatsRepository
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly PostRepository $posts = new PostRepository(),
        private readonly CommentRepository $comments = new CommentRepository()
    ) {
    }

    public function siteStats(): array
    {
        return [
            'members' => $this->users->count(),
            'posts' => $this->posts->count(),
            'comments' => $this->comments->count(),
        ];
    }
}

