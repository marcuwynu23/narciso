<?php
/**
 * Sample: Middlewares
 *
 * Features:
 * - use() with callable: function ($app, $next) { ...; return $next(); }
 * - use() with MiddlewareInterface implementation
 * - Order of execution: first added = first to run (before request), last to run (after response)
 *
 * Try: GET / — check response headers or body for middleware trace.
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Marcuwynu23\Narciso\Application;
use Marcuwynu23\Narciso\Middleware\MiddlewareInterface;

$app = new Application();
$app->setViewPath(__DIR__ . '/views');

// Trace array to show order (in real app you wouldn't echo this)
$trace = [];

// Callable middleware — before/after
$app->use(function ($app, $next) use (&$trace) {
	$trace[] = 'callable-1-before';
	$result = $next();
	$trace[] = 'callable-1-after';
	return $result;
});

$app->use(function ($app, $next) use (&$trace) {
	$trace[] = 'callable-2-before';
	$result = $next();
	$trace[] = 'callable-2-after';
	return $result;
});

// Class implementing MiddlewareInterface
$app->use(new class($trace) implements MiddlewareInterface {
	private $trace;
	public function __construct(array &$trace) {
		$this->trace = &$trace;
	}
	public function handle(callable $next) {
		$this->trace[] = 'interface-middleware-before';
		$result = $next();
		$this->trace[] = 'interface-middleware-after';
		return $result;
	}
});

$app->route('GET', '/', function ($app) use (&$trace) {
	$trace[] = 'route-handler';
	$app->json([
		'message' => 'Middlewares ran in order',
		'order'   => $trace,
	]);
});

$app->route('GET', '/02_middlewares.php', function ($app) use (&$trace) {
	$trace[] = 'route-handler';
	$app->json(['order' => $trace]);
});

$app->run();
