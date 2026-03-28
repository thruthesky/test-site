<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\ActionRepository;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use RuntimeException;

final class CommentService
{
    public function __construct(
        private readonly CommentRepository $comments = new CommentRepository(),
        private readonly PostRepository $posts = new PostRepository(),
        private readonly ActionRepository $actions = new ActionRepository()
    ) {
    }

    public function tree(int $postId): array
    {
        $post = $this->posts->findById($postId);
        if (!$post) {
            throw new RuntimeException('Post not found.');
        }

        $rows = $this->comments->listByPostId($postId);
        $nodes = [];

        foreach ($rows as $row) {
            $row['children'] = [];
            $nodes[(int) $row['id']] = $row;
        }

        $tree = [];
        foreach ($nodes as $id => $node) {
            $parentId = $node['parent_id'] ? (int) $node['parent_id'] : null;
            if ($parentId && isset($nodes[$parentId])) {
                $nodes[$parentId]['children'][] = &$nodes[$id];
            } else {
                $tree[] = &$nodes[$id];
            }
        }

        return $tree;
    }

    public function create(User $user, int $postId, array $input): array
    {
        $content = trim((string) ($input['content'] ?? ''));
        if ($content === '') {
            throw new RuntimeException('Comment content is required.');
        }

        $post = $this->posts->findById($postId);
        if (!$post) {
            throw new RuntimeException('Post not found.');
        }

        $parentId = isset($input['parent_id']) && $input['parent_id'] !== '' ? (int) $input['parent_id'] : null;
        $depth = 0;

        if ($parentId) {
            $parent = $this->comments->findById($parentId);
            if (!$parent || (int) $parent['post_id'] !== $postId) {
                throw new RuntimeException('Parent comment not found.');
            }
            $depth = min(((int) $parent['depth']) + 1, 10);
        }

        return $this->comments->create([
            'post_id' => $postId,
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'content' => $content,
            'depth' => $depth,
        ])->toArray();
    }

    public function update(User $user, int $commentId, string $content): array
    {
        $comment = $this->comments->findById($commentId);
        if (!$comment) {
            throw new RuntimeException('Comment not found.');
        }
        $this->assertOwnerOrAdmin($user, (int) $comment['user_id']);

        $updated = $this->comments->update($commentId, trim($content));
        if (!$updated) {
            throw new RuntimeException('Unable to update comment.');
        }

        return $updated->toArray();
    }

    public function delete(User $user, int $commentId): void
    {
        $comment = $this->comments->findById($commentId);
        if (!$comment) {
            throw new RuntimeException('Comment not found.');
        }
        $this->assertOwnerOrAdmin($user, (int) $comment['user_id']);
        $this->comments->softDelete($commentId);
    }

    public function recordAction(User $user, int $commentId, string $action, ?string $reason = null): void
    {
        $comment = $this->comments->findById($commentId);
        if (!$comment) {
            throw new RuntimeException('Comment not found.');
        }

        $this->actions->record($action, $user->id, 'comment', $commentId, $reason);
    }

    private function assertOwnerOrAdmin(User $user, int $ownerId): void
    {
        if ($user->role !== 'admin' && $user->id !== $ownerId) {
            throw new RuntimeException('You do not have permission for this comment.');
        }
    }
}

