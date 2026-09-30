<?php
/**
 * Router untuk PHP built-in server (development hanya).
 * Mengarahkan semua request yang bukan file statis ke index.php CI3.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

if ($uri !== '/' && file_exists($file) && ! is_dir($file)) {
    return false; // serve file statis apa adanya
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once __DIR__ . '/index.php';
