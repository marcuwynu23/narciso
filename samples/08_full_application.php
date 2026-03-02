<?php
/**
 * Sample: Full application — all features combined
 *
 * Features used:
 * - setViewPath, setPoweredBy
 * - handleSession, useSecurityHeaders, useCors, useRateLimit
 * - handleDatabase (SQLite :memory: for demo)
 * - Routing with path params, json(), sendAPI(), render(), redirect
 *
 * Run: php -S localhost:8080 -t samples
 * Then: http://localhost:8080/08_full_application.php
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Marcuwynu23\Narciso\Application;

$app = new Application();
$app->setViewPath(__DIR__ . '/views');

// Technology signature: hide PHP
$app->setPoweredBy(false);

// Session (optional)
$app->handleSession('NarcisoSample');

// Middlewares
$app->useSecurityHeaders();
$app->useCors(['*'], ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'], ['Content-Type', 'Authorization']);
$app->useRateLimit(60, 60);

// Database (SQLite in-memory for demo; use MySQL config in production)
$app->handleDatabase(['type' => 'sqlite', 'database' => ':memory:']);
$app->db->exec('CREATE TABLE IF NOT EXISTS items (id INTEGER PRIMARY KEY, name TEXT)');
$app->db->exec("INSERT OR IGNORE INTO items (id, name) VALUES (1, 'Alpha'), (2, 'Beta')");

// Routes

$app->route('GET', '/', function ($app) {
	$app->json([
		'app'    => 'Narciso full sample',
		'routes' => [
			'GET /'              => 'this',
			'GET /api'            => 'JSON or XML via sendAPI (use ?format=xml)',
			'GET /api/items'      => 'list from DB',
			'GET /api/items/:id'   => 'one item by id',
			'GET /page'           => 'HTML view',
			'GET /about'          => 'about view',
		],
	]);
});

$app->route('GET', '/08_full_application.php', function ($app) {
	$app->redirect('/');
});

$app->route('GET', '/api', function ($app) {
	$app->sendAPI([
		'message' => 'API response (JSON or XML)',
		'time'    => date('c'),
	]);
});

$app->route('GET', '/api/items', function ($app) {
	$res = $app->db->query('SELECT id, name FROM items');
	$rows = [];
	while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
		$rows[] = $row;
	}
	$app->sendAPI(['items' => $rows]);
});

$app->route('GET', '/api/items/:id', function ($app, $params) {
	$id = (int) $params['id'];
	$stmt = $app->db->prepare('SELECT id, name FROM items WHERE id = :id');
	$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
	$r = $stmt->execute();
	$row = $r->fetchArray(SQLITE3_ASSOC);
	if (!$row) {
		$app->json(['error' => 'Not found'], 404);
		return;
	}
	$app->sendAPI($row);
});

$app->route('GET', '/page', function ($app) {
	$app->render('/home.view', ['message' => 'Full app view']);
});

$app->route('GET', '/about', function ($app) {
	$app->render('/about/about.view');
});

$app->run();
