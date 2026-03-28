<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Http\Request;
use App\Http\Response;
use App\Service\ProfileService;
use RuntimeException;

final class ProfileController extends ApiController
{
    public function __construct(private readonly ProfileService $profiles = new ProfileService())
    {
        parent::__construct();
    }

    public function show(Request $request): never
    {
        $user = $this->requireUser($request);
        Response::json(['ok' => true, 'data' => $user->toArray()]);
    }

    public function update(Request $request): never
    {
        $user = $this->requireUser($request);

        try {
            $updated = $this->profiles->update($user, $request->all());
            Response::json(['ok' => true, 'data' => $updated->toArray()]);
        } catch (RuntimeException $exception) {
            Response::json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function uploadPhoto(Request $request): never
    {
        $user = $this->requireUser($request);
        $file = $request->file('photo');

        if (!$file) {
            Response::json(['ok' => false, 'message' => 'Photo file is required.'], 422);
        }

        try {
            $updated = $this->profiles->uploadPhoto($user, $file);
            Response::json(['ok' => true, 'data' => $updated->toArray()]);
        } catch (RuntimeException $exception) {
            Response::json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}

