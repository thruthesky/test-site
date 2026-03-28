<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Http\Request;
use App\Http\Response;
use App\Repository\UserRepository;

abstract class ApiController
{
    protected UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    protected function currentUser(Request $request): ?User
    {
        $userId = (int) $request->sessionGet('user_id', 0);
        return $userId > 0 ? $this->users->findById($userId) : null;
    }

    protected function requireUser(Request $request): User
    {
        $user = $this->currentUser($request);
        if (!$user) {
            Response::json(['ok' => false, 'message' => 'Authentication required.'], 401);
        }

        return $user;
    }

    protected function requireAdmin(Request $request): User
    {
        $user = $this->requireUser($request);
        if ($user->role !== 'admin') {
            Response::json(['ok' => false, 'message' => 'Admin access required.'], 403);
        }

        return $user;
    }
}

