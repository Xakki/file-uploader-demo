<?php

declare(strict_types=1);

use Demo\Config;
use Demo\Controller\DownloadController;
use Demo\Controller\PageController;
use Demo\Controller\UploadController;
use Demo\Factory;
use Demo\Http\Response;
use Demo\I18n;
use Demo\Router;
use Demo\Session;
use Demo\View;

require __DIR__.'/../vendor/autoload.php';

$config = new Config;
$response = new Response;

// Resolve the UI language (?lang → cookie → Accept-Language → en). A valid
// explicit ?lang is persisted to the cookie before any output so the choice
// survives navigation between / and /widget. The cookie is only written on an
// explicit switch, so the upload/download/API routes never emit Set-Cookie.
$lang = I18n::resolve(
    $_GET['lang'] ?? null,
    $_COOKIE['fu-lang'] ?? null,
    $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null,
);
if (isset($_GET['lang']) && I18n::supports($_GET['lang'])) {
    setcookie('fu-lang', $lang, [
        'expires' => time() + 60 * 60 * 24 * 365,
        'path' => '/',
        'samesite' => 'Lax',
    ]);
}

$view = new View(new I18n($lang));
// Issue/read the guest cookie before any output so Set-Cookie lands in the headers.
$session = new Session;
$factory = new Factory($config, $session);

$page = new PageController($config, $view, $response);
$upload = new UploadController($factory, $response);
$download = new DownloadController($factory, $response);

$prefix = Config::ENDPOINT_PREFIX;

$router = new Router($response);

// Pages (side-effect-free: no core/manager construction).
$router->get('/', $page->home(...));
$router->get('/widget', $page->widget(...));

// Uniform download endpoint (filesystem or S3).
$router->get('/dl', $download->download(...));

// Upload Protocol v1 API.
$router->post("$prefix/chunks", $upload->chunks(...));
$router->get("$prefix/files", $upload->list(...));
$router->delete("$prefix/trash/cleanup", $upload->cleanup(...));
$router->post("#^$prefix/files/([^/]+)/restore$#", $upload->restore(...), regex: true);
$router->delete("#^$prefix/files/([^/]+)$#", $upload->delete(...), regex: true);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$router->dispatch($method, $path);
