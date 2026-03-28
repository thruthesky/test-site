<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\ActionRepository;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use RuntimeException;

final class PostService
{
    public function __construct(
        private readonly PostRepository $posts = new PostRepository(),
        private readonly CategoryRepository $categories = new CategoryRepository(),
        private readonly ActionRepository $actions = new ActionRepository(),
        private readonly CategoryService $categoryService = new CategoryService()
    ) {
    }

    public function list(?string $categorySlug, int $page = 1): array
    {
        $filter = $this->categoryService->resolveCategoryFilter($categorySlug);
        return [
            'category' => $filter['category'],
            'posts' => $this->posts->listByCategoryIds($filter['ids'], $page, 10),
        ];
    }

    public function show(int $id): array
    {
        $this->posts->incrementViews($id);
        $post = $this->posts->findById($id);
        if (!$post) {
            throw new RuntimeException('Post not found.');
        }
        return $post;
    }

    public function create(User $user, array $input): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $content = trim((string) ($input['content'] ?? ''));
        $categoryId = (int) ($input['category_id'] ?? 0);

        if ($title === '' || $content === '' || $categoryId <= 0) {
            throw new RuntimeException('Title, content, and category are required.');
        }

        $category = $this->categories->findById($categoryId);
        if (!$category || !$category->isEnabled) {
            throw new RuntimeException('A valid category is required.');
        }

        return $this->posts->create([
            'category_id' => $categoryId,
            'user_id' => $user->id,
            'title' => $title,
            'content' => $content,
        ])->toArray();
    }

    public function update(User $user, int $id, array $input): array
    {
        $post = $this->posts->findById($id);
        if (!$post) {
            throw new RuntimeException('Post not found.');
        }
        $this->assertOwnerOrAdmin($user, (int) $post['user_id']);

        $updated = $this->posts->update($id, [
            'category_id' => (int) ($input['category_id'] ?? $post['category_id']),
            'title' => trim((string) ($input['title'] ?? $post['title'])),
            'content' => trim((string) ($input['content'] ?? $post['content'])),
        ]);

        if (!$updated) {
            throw new RuntimeException('Unable to update post.');
        }

        return $updated->toArray();
    }

    public function delete(User $user, int $id): void
    {
        $post = $this->posts->findById($id);
        if (!$post) {
            throw new RuntimeException('Post not found.');
        }
        $this->assertOwnerOrAdmin($user, (int) $post['user_id']);
        $this->posts->softDelete($id);
    }

    public function recordAction(User $user, int $id, string $action, ?string $reason = null): void
    {
        $post = $this->posts->findById($id);
        if (!$post) {
            throw new RuntimeException('Post not found.');
        }

        $this->actions->record($action, $user->id, 'post', $id, $reason);
    }

    private function assertOwnerOrAdmin(User $user, int $ownerId): void
    {
        if ($user->role !== 'admin' && $user->id !== $ownerId) {
            throw new RuntimeException('You do not have permission for this post.');
        }
    }
}

