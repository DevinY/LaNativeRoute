<?php
error_reporting(E_ALL);
ini_set( 'display_errors','1');

include(__DIR__."/../vendor/autoload.php");
include(__DIR__."/../common/sys_functions.php");
if(!session_id()) {
    session_start();
}

//https://packagist.org/packages/illuminate/database
use Illuminate\Database\Capsule\Manager as DB;

//載入.env檔
//https://github.com/vlucas/phpdotenv
if (file_exists(__DIR__."/../.env")) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__."/..");
    $dotenv->load();
}

$capsule = new DB;
$capsule->setAsGlobal();
$capsule->bootEloquent();

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => env("DB_HOST"),
    'database'  => env("DB_DATABASE"),
    'username'  => env("DB_USERNAME"),
    'password'  => env("DB_PASSWORD"),
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);

//預設的目錄
$base_path = base_path();

//URL的PHP
$controller = $_SERVER['REQUEST_URI'];

if(preg_match('/.+\\?/uU', $controller)){
    $controller = explode("?",$controller)[0];
}

if($controller=="/"){
    $controller="/index";
}

$controller = route($controller);

$controller_path = $base_path.sprintf("/controllers%s.php", $controller);

//載入執行controller&model
if(file_exists($controller_path)) {
    $data = include($controller_path);
} else {
    abort("404");
}

//return 為array型態是轉JSON
if(is_array($data)){
    //強制禁止瀏覽器進行快取
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
    header("Content-Type: application/json;charset=utf-8");
    echo json_encode($data);
}