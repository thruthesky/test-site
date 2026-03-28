<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Http\Request;
use App\Http\Response;
use App\Service\AuthService;
use RuntimeException;

final class AuthController extends ApiController
{
    public function __construct(private readonly AuthService $auth = new AuthService())
    {
        parent::__construct();
    }

    public function session(Request $request): never
    {
        $user = $this->currentUser($request);
        Response::json(['ok' => true, 'data' => $user?->toArray()]);
    }

    public function register(Request $request): never
    {
        try {
            $user = $this->auth->register($request->all());
            $request->sessionPut('user_id', $user->id);
            Response::json(['ok' => true, 'data' => $user->toArray()], 201);
        } catch (RuntimeException $exception) {
            Response::json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function login(Request $request): never
    {
        $identity = trim((string) $request->input('identity'));
        $password = (string) $request->input('password');
        $user = $this->auth->login($identity, $password);

        if (!$user) {
            Response::json(['ok' => false, 'message' => 'Invalid credentials.'], 401);
        }

        $request->sessionPut('user_id', $user->id);
        Response::json(['ok' => true, 'data' => $user->toArray()]);
    }

    public function logout(Request $request): never
    {
        $request->sessionForget('user_id');
        Response::json(['ok' => true]);
    }
}

