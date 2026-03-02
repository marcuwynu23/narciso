<?php
/**
 * Sample: Basic routing
 *
 * Features:
 * - Exact path routes (GET /, /hello)
 * - Path parameters (/users/:id, /posts/:slug/comments/:cid)
 * - Multiple HTTP methods (GET, POST)
 * - json() response
 * - 404 when no route matches
 *
 * Try: GET /, GET /hello, GET /users/42, GET /posts/foo/comments/1, POST /echo
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Marcuwynu23\Narciso\Application;

$app = new Application();
$app->setViewPath(__DIR__ . '/views');

// Exact path — GET /
$app->route('GET', '/', function ($app) {
	$app->json([
		'message' => 'Welcome to Narciso',
		'routes'  => ['/', '/hello', '/users/:id', '/posts/:slug/comments/:cid', 'POST /echo'],
	]);
});

// Exact path — GET /hello (note: request URI in built-in server may include the script name)
$app->route('GET', '/01_basic_routing.php', function ($app) {
	$app->json(['message' => 'Same as GET / when this file is the entry point']);
});

$app->route('GET', '/hello', function ($app) {
	$app->json(['message' => 'Hello!']);
});

// Path parameters — /users/:id
$app->route('GET', '/users/:id', function ($app, $params) {
	$app->json([
		'user_id' => $params['id'],
		'note'    => 'Path param :id captured',
	]);
});

// Multiple path parameters
$app->route('GET', '/posts/:slug/comments/:cid', function ($app, $params) {
	$app->json([
		'post_slug'    => $params['slug'],
		'comment_id'   => $params['cid'],
	]);
});

// POST and request body (use requestPost() for JSON body)
$app->route('POST', '/echo', function ($app) {
	$body = $app->requestPost();
	$app->json([
		'received' => $body,
		'method'   => 'POST',
	]);
});

$app->run();
