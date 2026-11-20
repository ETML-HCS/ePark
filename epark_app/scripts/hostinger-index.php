<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));
$appRoot = dirname(__DIR__, 2).'/epark-releases/__EPARK_RELEASE__';
if (file_exists($maintenance = $appRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}
require $appRoot.'/vendor/autoload.php';
/** @var Application $app */
$app = require_once $appRoot.'/bootstrap/app.php';
$app->handleRequest(Request::capture());
