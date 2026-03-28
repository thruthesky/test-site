<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Http\Request;
use App\Http\Response;
use App\Service\SidebarService;

final class AppController extends ApiController
{
    public function __construct(private readonly SidebarService $sidebar = new SidebarService())
    {
        parent::__construct();
    }

    public function bootstrap(Request $request): never
    {
        $user = $this->currentUser($request);
        Response::json([
            'ok' => true,
            'data' => $this->sidebar->bootstrap($user?->toArray()),
        ]);
    }
}
