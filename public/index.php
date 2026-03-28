<?php

declare(strict_types=1);

$root = dirname(__DIR__);

require_once $root . '/app/Bootstrap/autoload.php';

use App\Config\Env;
use App\Http\Csrf;
use App\Http\Request;
use App\Http\Response;
use App\View\ShellView;

Env::load($root);

$sessionPath = Env::get('SESSION_SAVE_PATH', $root . '/storage/sessions');
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
session_save_path($sessionPath);
session_name('site_session');
session_start();

$request = Request::capture(false);
$boot = [
    'appName' => Env::get('APP_NAME', 'Community Site'),
    'route' => $request->path(),
    'csrfToken' => Csrf::token(),
    'apiBase' => '/api.php',
];

$view = new ShellView();
Response::html($view->render($boot));

