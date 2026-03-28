<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Http\Request;
use App\Http\Response;
use App\Service\CommentService;
use RuntimeException;

final class CommentController extends ApiController
{
    public function __construct(private readonly CommentService $comments = new CommentService())
    {
        parent::__construct();
    }

    public function list(Request $request, array $params): never
    {
        try {
            Response::json(['ok' => true, 'data' => $this->comments->tree((int) $params['postId'])]);
        } catch (RuntimeException $exception) {
            Response::json(['ok' => false, 'message' => $exception->getMessage()], 404);
        }
    }

    public function create(Request $request, array $params): never
    {
        try {
            $comment = $this->comments->create($this->requireUser($request), (int) $params['postId'], $request->all());
            Response::json(['ok' => true, 'data' => $comment], 201);
        } catch (RuntimeException $exception) {
            Response::json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function update(Request $request, array $params): never
    {
        try {
            $comment = $this->comments->update($this->requireUser($request), (int) $params['id'], (string) $request->input('content'));
            Response::json(['ok' => true, 'data' => $comment]);
        } catch (RuntimeException $exception) {
            $status = str_contains($exception->getMessage(), 'permission') ? 403 : 422;
            Response::json(['ok' => false, 'message' => $exception->getMessage()], $status);
        }
    }

    public function delete(Request $request, array $params): never
    {
        try {
            $this->comments->delete($this->requireUser($request), (int) $params['id']);
            Response::json(['ok' => true]);
        } catch (RuntimeException $exception) {
            $status = str_contains($exception->getMessage(), 'permission') ? 403 : 422;
            Response::json(['ok' => false, 'message' => $exception->getMessage()], $status);
        }
    }

    public function action(Request $request, array $params): never
    {
        try {
            $this->comments->recordAction(
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

