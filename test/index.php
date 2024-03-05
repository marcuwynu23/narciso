<?php
require_once __DIR__ . "../../src/Application.php";

use Marcuwynu23\Narciso\Application;


$app = new Application();

$app->setViewPath(__DIR__ . '/views');
$app->handleSession();
$app->handleCors();
$app->handleDatabase([
	'type' => 'mysql',
	'host' => 'localhost',
	'database' => 'northwind',
	'username' => 'user',
	'password' => 'user',
]);



$app->route('GET', '/', function () use ($app) {
	$data = $app->db->query('SELECT * FROM customers')->fetch_all();
	$app->render('/home.view', $data);
});