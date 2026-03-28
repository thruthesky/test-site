<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Http\Request;
use App\Http\Response;
use App\Service\CategoryService;
use RuntimeException;

final class CategoryController extends ApiController
{
    public function __construct(private readonly CategoryService $categories = new CategoryService())
    {
        parent::__construct();
    }

    public function tree(Request $request): never
    {
        Response::json(['ok' => true, 'data' => $this->categories->menuTree()]);
    }

    public function adminList(Request $request): never
    {
        $this->requireAdmin($request);
        Response::json(['ok' => true, 'data' => $this->categories->adminList()]);
    }

    public function create(Request $request): never
    {
        try {
            $category = $this->categories->create($this->requireAdmin($request), $request->all());
            Response::json(['ok' => true, 'data' => $category], 201);
        } catch (RuntimeException $exception) {
            Response::json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function update(Request $request, array $params): never
    {
        try {
            $category = $this->categories->update($this->requireAdmin($request), (int) $params['id'], $request->all());
            Response::json(['ok' => true, 'data' => $category]);
        } catch (RuntimeException $exception) {
            Response::json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function delete(Request $request, array $params): never
    {
        try {
            $this->categories->delete($this->requireAdmin($request), (int) $params['id']);
            Response::json(['ok' => true]);
        } catch (RuntimeException $exception) {
            Response::json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}

