<?php

declare(strict_types=1);

$root = dirname(__DIR__);

require_once $root . '/app/Bootstrap/autoload.php';

use App\Config\Env;
use App\Controller\Api\AppController;
use App\Controller\Api\AuthController;
use App\Controller\Api\CategoryController;
use App\Controller\Api\CommentController;
use App\Controller\Api\PostController;
use App\Controller\Api\ProfileController;
use App\Http\Csrf;
use App\Http\Request;
use App\Http\Response;
use App\Routing\Router;

Env::load($root);

$sessionPath = Env::get('SESSION_SAVE_PATH', $root . '/storage/sessions');
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
session_save_path($sessionPath);
session_name('site_session');
session_start();

set_exception_handler(static function (Throwable $exception): void {
    Response::json([
        'ok' => false,
        'message' => Env::bool('APP_DEBUG', false)
            ? $exception->getMessage()
            : 'Internal server error.',
    ], 500);
});

$request = Request::capture(true);
if (!Csrf::verify($request)) {
    Response::json(['ok' => false, 'message' => 'CSRF token mismatch.'], 419);
}

$router = new Router();

$app = new AppController();
$auth = new AuthController();
$profile = new ProfileController();
$categories = new CategoryController();
$posts = new PostController();
$comments = new CommentController();

$router->get('/', static fn (Request $request) => $app->bootstrap($request));
$router->get('/session', static fn (Request $request) => $auth->session($request));
$router->post('/auth/register', static fn (Request $request) => $auth->register($request));
$router->post('/auth/login', static fn (Request $request) => $auth->login($request));
$router->post('/auth/logout', static fn (Request $request) => $auth->logout($request));

$router->get('/profile', static fn (Request $request) => $profile->show($request));
$router->post('/profile', static fn (Request $request) => $profile->update($request));
$router->post('/profile/photo', static fn (Request $request) => $profile->uploadPhoto($request));

$router->get('/categories', static fn (Request $request) => $categories->tree($request));
$router->get('/admin/categories', static fn (Request $request) => $categories->adminList($request));
$router->post('/admin/categories', static fn (Request $request) => $categories->create($request));
$router->put('/admin/categories/{id}', static fn (Request $request, array $params) => $categories->update($request, $params));
$router->delete('/admin/categories/{id}', static fn (Request $request, array $params) => $categories->delete($request, $params));

$router->get('/posts', static fn (Request $request) => $posts->list($request));
$router->get('/posts/{id}', static fn (Request $request, array $params) => $posts->show($request, $params));
$router->post('/posts', static fn (Request $request) => $posts->create($request));
$router->put('/posts/{id}', static fn (Request $request, array $params) => $posts->update($request, $params));
$router->delete('/posts/{id}', static fn (Request $request, array $params) => $posts->delete($request, $params));
$router->post('/posts/{id}/actions/{action}', static fn (Request $request, array $params) => $posts->action($request, $params));

$router->get('/posts/{postId}/comments', static fn (Request $request, array $params) => $comments->list($request, $params));
$router->post('/posts/{postId}/comments', static fn (Request $request, array $params) => $comments->create($request, $params));
$router->put('/comments/{id}', static fn (Request $request, array $params) => $comments->update($request, $params));
$router->delete('/comments/{id}', static fn (Request $request, array $params) => $comments->delete($request, $params));
$router->post('/comments/{id}/actions/{action}', static fn (Request $request, array $params) => $comments->action($request, $params));

$result = $router->dispatch($request);
if ($result === null) {
    Response::json(['ok' => false, 'message' => 'API route not found.'], 404);
}

