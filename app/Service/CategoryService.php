<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\CategoryRepository;
use RuntimeException;

final class CategoryService
{
    public function __construct(
        private readonly CategoryRepository $categories = new CategoryRepository(),
        private readonly SlugService $slugger = new SlugService()
    ) {
    }

    public function menuTree(): array
    {
        return $this->categories->tree(true);
    }

    public function adminList(): array
    {
        return $this->categories->adminList();
    }

    public function create(User $user, array $input): array
    {
        $this->assertAdmin($user);
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Category name is required.');
        }

        $hasParent = array_key_exists('parent_id', $input) && $input['parent_id'] !== '' && $input['parent_id'] !== null;
        $parentId = $hasParent ? (int) $input['parent_id'] : null;
        $depth = $parentId ? 2 : 1;
        if ($parentId) {
            $parent = $this->categories->findById($parentId);
            if (!$parent || $parent->depth !== 1) {
                throw new RuntimeException('Second-level category requires a valid first-level parent.');
            }
        }

        $slug = trim((string) ($input['slug'] ?? ''));
        $slug = $slug !== '' ? $this->slugger->make($slug) : $this->slugger->make($name);

        if ($this->categories->slugExists($slug)) {
            throw new RuntimeException('Category slug already exists.');
        }

        return $this->categories->create([
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'depth' => $depth,
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'is_enabled' => filter_var($input['is_enabled'] ?? true, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
        ])->toArray();
    }

    public function update(User $user, int $id, array $input): array
    {
        $this->assertAdmin($user);
        $existing = $this->categories->findById($id);
        if (!$existing) {
            throw new RuntimeException('Category not found.');
        }

        $name = trim((string) ($input['name'] ?? $existing->name));
        $hasParent = array_key_exists('parent_id', $input) && $input['parent_id'] !== '' && $input['parent_id'] !== null;
        $parentId = $hasParent ? (int) $input['parent_id'] : null;
        $depth = $parentId ? 2 : 1;
        $slug = trim((string) ($input['slug'] ?? $existing->slug));
        $slug = $this->slugger->make($slug !== '' ? $slug : $name);

        if ($this->categories->slugExists($slug, $id)) {
            throw new RuntimeException('Category slug already exists.');
        }

        $updated = $this->categories->update($id, [
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'depth' => $depth,
            'sort_order' => (int) ($input['sort_order'] ?? $existing->sortOrder),
            'is_enabled' => filter_var($input['is_enabled'] ?? $existing->isEnabled, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $existing->isEnabled,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
        ]);

        if (!$updated) {
            throw new RuntimeException('Unable to update category.');
        }

        return $updated->toArray();
    }

    public function delete(User $user, int $id): void
    {
        $this->assertAdmin($user);
        $this->categories->delete($id);
    }

    public function resolveCategoryFilter(?string $slug): array
    {
        if (!$slug) {
            return ['category' => null, 'ids' => []];
        }

        $category = $this->categories->findBySlug($slug, true);
        if (!$category) {
            throw new RuntimeException('Category not found.');
        }

        return [
            'category' => $category->toArray(),
            'ids' => $this->categories->descendantIds($category->id),
        ];
    }

    private function assertAdmin(User $user): void
    {
        if ($user->role !== 'admin') {
            throw new RuntimeException('Admin access is required.');
        }
    }
}
