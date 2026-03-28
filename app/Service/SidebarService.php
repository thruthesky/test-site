<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\StatsRepository;
use App\Repository\UserRepository;

final class SidebarService
{
    public function __construct(
        private readonly StatsRepository $stats = new StatsRepository(),
        private readonly PostRepository $posts = new PostRepository(),
        private readonly CommentRepository $comments = new CommentRepository(),
        private readonly UserRepository $users = new UserRepository(),
        private readonly CategoryService $categories = new CategoryService()
    ) {
    }

    public function bootstrap(?array $currentUser = null): array
    {
        return [
            'currentUser' => $currentUser,
            'menu' => $this->categories->menuTree(),
            'stats' => $this->stats->siteStats(),
            'sidebar' => [
                'recentPosts' => $this->posts->recent(),
                'recentComments' => $this->comments->recent(),
                'recentPhotos' => $this->users->latestPhotos(),
            ],
        ];
    }
}

