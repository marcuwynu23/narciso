<?php
require_once __DIR__ . "../../src/Application.php";

use Marcuwynu23\Narciso\Application;


// Create a new application
$app = new Application();
// View path
$app->setViewPath(__DIR__ . '/views');
// Session
$app->handleSession();
// CORS
$app->handleCors();
// Database connection
$app->handleDatabase([
	'type' => 'mysql',
	'host' => 'localhost',
	'database' => 'northwind',
	'username' => 'user',
	'password' => 'user',
]);
// Routes
$app->route('GET', '/', function () use ($app) {
	$data = $app->db->query('SELECT * FROM customers')->fetch_all();
	$app->render('/home.view', $data);
});

$app->route('GET', '/about', function () use ($app) {
	$app->render('/about/about.view');
});






