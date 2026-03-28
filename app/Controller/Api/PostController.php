<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Http\Request;
use App\Http\Response;
use App\Service\PostService;
use RuntimeException;

final class PostController extends ApiController
{
    public function __construct(private readonly PostService $posts = new PostService())
    {
        parent::__construct();
    }

    public function list(Request $request): never
    {
        try {
            $data = $this->posts->list(
                $request->query('category_slug'),
                (int) $request->query('page', 1)
            );
            Response::json(['ok' => true, 'data' => $data]);
        } catch (RuntimeException $exception) {
            Response::json(['ok' => false, 'message' => $exception->getMessage()], 404);
        }
    }

    public function show(Request $request, array $params): never
    {
        try {
            Response::json(['ok' => true, 'data' => $this->posts->show((int) $params['id'])]);
        } catch (RuntimeException $exception) {
            Response::json(['ok' => false, 'message' => $exception->getMessage()], 404);
        }
    }

    public function create(Request $request): never
    {
        try {
            $post = $this->posts->create($this->requireUser($request), $request->all());
            Response::json(['ok' => true, 'data' => $post], 201);
        } catch (RuntimeException $exception) {
            Response::json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function update(Request $request, array $params): never
    {
        try {
            $post = $this->posts->update($this->requireUser($request), (int) $params['id'], $request->all());
            Response::json(['ok' => true, 'data' => $post]);
        } catch (RuntimeException $exception) {
            $status = str_contains($exception->getMessage(), 'permission') ? 403 : 422;
            Response::json(['ok' => false, 'message' => $exception->getMessage()], $status);
        }
    }

    public function delete(Request $request, array $params): never
    {
        try {
            $this->posts->delete($this->requireUser($request), (int) $params['id']);
            Response::json(['ok' => true]);
        } catch (RuntimeException $exception) {
            $status = str_contains($exception->getMessage(), 'permission') ? 403 : 422;
            Response::json(['ok' => false, 'message' => $exception->getMessage()], $status);
        }
    }

    public function action(Request $request, array $params): never
    {
        try {
            $this->posts->recordAction(
                $this->requireUser($request),
                (int) $params['id'],
                (string) $params['action'],
                (string) $request->input('reason', '')
            );
            Response::json(['ok' => true]);
        } catch (RuntimeException $exception) {
            Response::json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}

