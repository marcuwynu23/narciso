<?php
/**
 * Sample: Security headers, CORS, rate limiting
 *
 * Features:
 * - useSecurityHeaders() — X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy
 * - useCors() — configurable origins, methods, headers; try ?format=json and check Access-Control-* headers
 * - useRateLimit($maxRequests, $windowSeconds) — 10 requests per 60s per IP (for demo; use higher in production)
 *
 * Try: GET / — response includes security and CORS headers. Hit / many times to see 429 when over limit.
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Marcuwynu23\Narciso\Application;

$app = new Application();
$app->setViewPath(__DIR__ . '/views');

// Security headers on every response
$app->useSecurityHeaders();

// CORS: allow all origins for demo; restrict in production
$app->useCors(
	['*'],
	['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
	['Content-Type', 'Authorization', 'X-Requested-With', 'Accept'],
	false,
	86400
);

// Rate limit: 10 requests per 60 seconds per IP (low for demo)
$app->useRateLimit(10, 60);

$app->route('GET', '/', function ($app) {
	$app->json([
		'message'   => 'This response has security headers, CORS, and rate limiting applied',
		'headers'   => 'Check response: X-Content-Type-Options, X-Frame-Options, Access-Control-Allow-Origin',
		'rate_limit'=> 'After 10 requests in 60s you will get 429 Too Many Requests',
	]);
});

$app->route('GET', '/03_security_cors_rate_limit.php', function ($app) {
	$app->json(['message' => 'Security + CORS + rate limit applied']);
});

$app->run();
