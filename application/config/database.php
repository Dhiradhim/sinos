<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
|--------------------------------------------------------------------------
| Konfigurasi ini mengambil nilai dari file .env bila tersedia, sehingga
| kredensial tidak lagi hardcoded. Salin file .env.example menjadi .env
| lalu sesuaikan nilainya.
*/

$env = array();
$env_file = FCPATH . '.env';
if (file_exists($env_file)) {
	$env = parse_ini_file($env_file, false, INI_SCANNER_RAW);
}

$hostname = isset($env['DB_HOSTNAME']) ? $env['DB_HOSTNAME'] : 'localhost';
$username = isset($env['DB_USERNAME']) ? $env['DB_USERNAME'] : 'root';
$password = isset($env['DB_PASSWORD']) ? $env['DB_PASSWORD'] : '';
$database = isset($env['DB_DATABASE']) ? $env['DB_DATABASE'] : 'sinos';

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
	'dsn'	=> '',
	'hostname' => $hostname,
	'username' => $username,
	'password' => $password,
	'database' => $database,
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'production'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8mb4',
	'dbcollat' => 'utf8mb4_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);
