<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Normalize server variables when requests are rewritten via root .htaccess into
// backend/public (fixes subfolder deployments like /connectly where Symfony Request
// would otherwise fail to strip the subfolder prefix from the route path).
if (isset($_SERVER['SCRIPT_NAME']) && str_contains($_SERVER['SCRIPT_NAME'], '/backend/public/index.php')) {
    $_SERVER['SCRIPT_NAME'] = str_replace('/backend/public/index.php', '/index.php', $_SERVER['SCRIPT_NAME']);
}
if (isset($_SERVER['PHP_SELF']) && str_contains($_SERVER['PHP_SELF'], '/backend/public/index.php')) {
    $_SERVER['PHP_SELF'] = str_replace('/backend/public/index.php', '/index.php', $_SERVER['PHP_SELF']);
}

$scriptDir = isset($_SERVER['SCRIPT_NAME']) ? rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') : '';

// Fallback when server/proxy rewrites /connectly/* to root /index.php without /connectly in SCRIPT_NAME
if ($scriptDir === '' && isset($_SERVER['REQUEST_URI'])) {
    if ($_SERVER['REQUEST_URI'] === '/connectly' || str_starts_with($_SERVER['REQUEST_URI'], '/connectly/') || str_starts_with($_SERVER['REQUEST_URI'], '/connectly?')) {
        $scriptDir = '/connectly';
        $_SERVER['SCRIPT_NAME'] = '/connectly/index.php';
        $_SERVER['PHP_SELF'] = '/connectly/index.php';
    }
}

// Ensure exact subfolder root requests without trailing slash (e.g. /connectly) match baseUrl
if ($scriptDir !== '' && isset($_SERVER['REQUEST_URI'])) {
    if ($_SERVER['REQUEST_URI'] === $scriptDir) {
        $_SERVER['REQUEST_URI'] = $scriptDir . '/';
    } elseif (str_starts_with($_SERVER['REQUEST_URI'], $scriptDir . '?')) {
        $_SERVER['REQUEST_URI'] = $scriptDir . '/' . substr($_SERVER['REQUEST_URI'], strlen($scriptDir));
    }
}

$app->handleRequest(Request::capture());
