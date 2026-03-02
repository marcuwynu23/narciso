<?php
/**
 * Sample: Database connection
 *
 * Features:
 * - handleDatabase() with MySQL config (host, database, user/username, password)
 * - handleDatabase() with SQLite (type => 'sqlite', database => path or :memory:)
 * - $app->db — mysqli (MySQL) or SQLite3 (SQLite)
 *
 * This sample uses SQLite :memory: so it runs without a real DB.
 * For MySQL, use config: type=>mysql, host, database, username, password.
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Marcuwynu23\Narciso\Application;

$app = new Application();
$app->setViewPath(__DIR__ . '/views');

// SQLite in-memory (no file needed)
$app->handleDatabase([
	'type'     => 'sqlite',
	'database' => ':memory:',
]);

// Create table and insert for demo
$app->db->exec('CREATE TABLE IF NOT EXISTS samples (id INTEGER PRIMARY KEY, name TEXT)');
$app->db->exec("INSERT OR IGNORE INTO samples (id, name) VALUES (1, 'First'), (2, 'Second')");

$app->route('GET', '/', function ($app) {
	$result = $app->db->query('SELECT id, name FROM samples');
	$rows = [];
	while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
		$rows[] = $row;
	}
	$app->json([
		'message' => 'Data from SQLite :memory:',
		'rows'    => $rows,
	]);
});

$app->route('GET', '/04_database.php', function ($app) {
	$r = $app->db->querySingle('SELECT COUNT(*) FROM samples');
	$app->json(['count' => (int) $r]);
});

$app->run();
