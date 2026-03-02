<?php
/**
 * Sample: Views and render
 *
 * Features:
 * - setViewPath($path) — directory for view files
 * - render($view, $data) — include view with extracted variables ($view is path without .php, e.g. /home.view)
 * - redirect($url, $statusCode) — send Location and exit
 *
 * Try: GET / (or /06_views_render.php) → HTML from views/home.view.php with $message.
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Marcuwynu23\Narciso\Application;

$app = new Application();
$app->setViewPath(__DIR__ . '/views');

$app->route('GET', '/', function ($app) {
	$app->render('/home.view', [
		'message' => 'Data passed to the view',
	]);
});

$app->route('GET', '/06_views_render.php', function ($app) {
	$app->render('/home.view', ['message' => 'Sample 06']);
});

$app->route('GET', '/about', function ($app) {
	$app->render('/about/about.view');
});

// Redirect example (would exit; here we show route exists)
$app->route('GET', '/go-home', function ($app) {
	$app->redirect('/');
});

$app->run();
