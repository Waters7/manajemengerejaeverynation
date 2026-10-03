<?php

/*
|--------------------------------------------------------------------------
| public_html/index.php for cPanel shared hosting (Biznet Gio NEO Web Hosting)
|--------------------------------------------------------------------------
|
| The Laravel application lives OUTSIDE public_html (e.g. /home/USER/everynation)
| so .env, storage and vendor are never reachable from the web. Only the files
| of the `public` folder are placed in public_html, together with this index.php.
|
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Folder of the Laravel application, relative to public_html. Change if you used another name.
$appPath = __DIR__.'/../everynation';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $appPath.'/vendor/autoload.php';

// Bootstrap Laravel, serve assets (build/, images/) from public_html, and handle the request...
/** @var Application $app */
$app = require_once $appPath.'/bootstrap/app.php';
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
