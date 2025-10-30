<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$autoload = __DIR__.'/../vendor/autoload.php';
if (!file_exists($autoload)) {
    http_response_code(503);
    echo 'Composer dependencies missing. Run: composer install';
    exit(1);
}
require $autoload;

$app = require_once __DIR__.'/../bootstrap/app.php';

/** @var \Illuminate\Contracts\Http\Kernel $kernel */
$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);

