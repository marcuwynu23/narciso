<?php
/**
 * Sample: Technology signature (X-Powered-By)
 *
 * Features:
 * - setPoweredBy(false) — remove X-Powered-By header (silent/obfuscated)
 * - setPoweredBy('') — send blank value
 * - setPoweredBy('Express') — send custom value (e.g. to mimic another stack)
 * - setPoweredBy(null) — leave default (don't change)
 *
 * Check response headers in dev tools: X-Powered-By is applied at run().
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Marcuwynu23\Narciso\Application;

$app = new Application();
$app->setViewPath(__DIR__ . '/views');

// Remove X-Powered-By (recommended for production to hide server tech)
$app->setPoweredBy(false);

// Or use a custom value:
// $app->setPoweredBy('Express');
// $app->setPoweredBy('');

$app->route('GET', '/', function ($app) {
	$app->json([
		'message' => 'X-Powered-By header was removed (check response headers)',
	]);
});

$app->route('GET', '/07_technology_signature.php', function ($app) {
	$app->json(['message' => 'Technology signature sample']);
});

$app->run();
