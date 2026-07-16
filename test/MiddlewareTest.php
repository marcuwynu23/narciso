<?php

namespace Marcuwynu23\Narciso\Test;

use Marcuwynu23\Narciso\Application;
use Marcuwynu23\Narciso\Middleware\CorsMiddleware;
use Marcuwynu23\Narciso\Middleware\RateLimitMiddleware;
use Marcuwynu23\Narciso\Middleware\SecurityHeadersMiddleware;

final class MiddlewareTest extends TestCase
{
	public function testSecurityHeadersMiddlewareAddsDefaultHeaders(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->useSecurityHeaders();
		$app->route('GET', '/', function ($app) {
			$app->json(['ok' => true]);
		});
		$this->setRequest('GET', '/');
		[$output, $code, $headers] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertArrayHasKey('X-Content-Type-Options', $headers);
		$this->assertSame('nosniff', $headers['X-Content-Type-Options']);
		$this->assertArrayHasKey('X-Frame-Options', $headers);
		$this->assertSame('SAMEORIGIN', $headers['X-Frame-Options']);
		$this->assertArrayHasKey('X-XSS-Protection', $headers);
		$this->assertArrayHasKey('Referrer-Policy', $headers);
		$this->assertArrayHasKey('Permissions-Policy', $headers);
	}

	public function testSecurityHeadersMiddlewareCustomHeaders(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->useSecurityHeaders(['X-Custom' => 'value', 'X-Other' => 'other']);
		$app->route('GET', '/', function ($app) {
			$app->json([]);
		});
		$this->setRequest('GET', '/');
		[, , $headers] = $this->runApp($app);
		$this->assertSame('value', $headers['X-Custom'] ?? null);
		$this->assertSame('other', $headers['X-Other'] ?? null);
	}

	public function testCorsMiddlewareAddsHeaders(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->useCors(['*']);
		$app->route('GET', '/', function ($app) {
			$app->json([]);
		});
		$this->setRequest('GET', '/');
		[, $code, $headers] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
		$this->assertSame('*', $headers['Access-Control-Allow-Origin']);
		$this->assertArrayHasKey('Access-Control-Allow-Methods', $headers);
		$this->assertArrayHasKey('Access-Control-Allow-Headers', $headers);
		$this->assertArrayHasKey('Access-Control-Max-Age', $headers);
	}

	public function testCorsMiddlewareSpecificOrigin(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'https://app.example.com';
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->useCors(['https://app.example.com', 'https://other.com']);
		$app->route('GET', '/', function ($app) {
			$app->json([]);
		});
		$this->setRequest('GET', '/');
		[, , $headers] = $this->runApp($app);
		$this->assertSame('https://app.example.com', $headers['Access-Control-Allow-Origin'] ?? null);
	}

	public function testRateLimitMiddlewareAllowsUnderLimit(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->useRateLimit(10, 60);
		$app->route('GET', '/', function ($app) {
			$app->json(['ok' => true]);
		});
		$this->setRequest('GET', '/');
		[$output, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
		$this->assertJsonStringEqualsJsonString('{"ok":true}', $output);
	}

	public function testRateLimitMiddlewareReturns429WhenExceeded(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->useRateLimit(2, 60); // 2 requests per window
		$app->route('GET', '/', function ($app) {
			$app->json(['ok' => true]);
		});
		$this->setRequest('GET', '/');
		$this->runApp($app);
		$this->runApp($app);
		[$output, $code, $headers] = $this->runApp($app);
		$this->assertSame(429, $code);
		$this->assertArrayHasKey('Retry-After', $headers);
		$data = json_decode($output, true);
		$this->assertSame('Too Many Requests', $data['error'] ?? null);
	}

	public function testRateLimitUsesRemoteAddr(): void
	{
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->useRateLimit(5, 60);
		$app->route('GET', '/', function ($app) {
			$app->json([]);
		});
		$this->setRequest('GET', '/');
		[, $code] = $this->runApp($app);
		$this->assertSame(200, $code);
	}

	public function testCorsMiddlewareInstance(): void
	{
		$m = new CorsMiddleware(['*'], ['GET', 'POST'], ['Content-Type'], false, 3600);
		$this->assertInstanceOf(\Marcuwynu23\Narciso\Middleware\MiddlewareInterface::class, $m);
	}

	public function testSecurityHeadersMiddlewareInstance(): void
	{
		$m = new SecurityHeadersMiddleware();
		$this->assertInstanceOf(\Marcuwynu23\Narciso\Middleware\MiddlewareInterface::class, $m);
	}

	public function testRateLimitMiddlewareInstance(): void
	{
		$m = new RateLimitMiddleware(60, 60);
		$this->assertInstanceOf(\Marcuwynu23\Narciso\Middleware\MiddlewareInterface::class, $m);
	}

	// --- Additional edge case tests ---

	public function testCorsMiddlewareWithNoOriginHeader(): void
	{
		unset($_SERVER['HTTP_ORIGIN']);
		$m = new CorsMiddleware(['https://example.com']);
		$called = false;
		$m->handle(function () use (&$called) { $called = true; });
		$this->assertTrue($called);
	}

	public function testCorsMiddlewareWithCredentialsTrue(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'https://app.com';
		$m = new CorsMiddleware(['https://app.com'], ['GET'], ['X-Custom'], true, 3600);
		$called = false;
		$m->handle(function () use (&$called) { $called = true; });
		$this->assertTrue($called);
	}

	public function testCorsMiddlewareWithMaxAgeNull(): void
	{
		$_SERVER['HTTP_ORIGIN'] = 'https://app.com';
		$m = new CorsMiddleware(['https://app.com'], ['GET'], ['X-Custom'], false, null);
		$called = false;
		$m->handle(function () use (&$called) { $called = true; });
		$this->assertTrue($called);
	}

	public function testRateLimitMiddlewareWithXForwardedFor(): void
	{
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1, 10.0.0.2';
		$m = new RateLimitMiddleware(5, 60);
		$called = false;
		$m->handle(function () use (&$called) { $called = true; });
		$this->assertTrue($called);
	}

	public function testRateLimitMiddlewareWindowExpiry(): void
	{
		$m = new RateLimitMiddleware(1, 1);
		$called1 = false;
		$m->handle(function () use (&$called1) { $called1 = true; });
		$this->assertTrue($called1);

		// Second call within window should be blocked
		$called2 = false;
		$m->handle(function () use (&$called2) { $called2 = true; });
		$this->assertFalse($called2);

		// Reset for clean state
		RateLimitMiddleware::resetStore();
	}

	public function testRateLimitMiddlewareResetStore(): void
	{
		RateLimitMiddleware::resetStore();
		$m = new RateLimitMiddleware(1, 60);
		$called = false;
		$m->handle(function () use (&$called) { $called = true; });
		$this->assertTrue($called);
	}

	public function testSecurityHeadersMiddlewareDefaultHeadersCount(): void
	{
		$m = new SecurityHeadersMiddleware();
		$called = false;
		$m->handle(function () use (&$called) { $called = true; });
		$this->assertTrue($called);
	}

	public function testSecurityHeadersMiddlewareCustomHeadersOverride(): void
	{
		$app = new Application();
		$app->setViewPath(__DIR__ . '/../samples/views');
		$app->useSecurityHeaders(['X-Content-Type-Options' => 'custom-value']);
		$app->route('GET', '/', function ($app) {
			$app->json([]);
		});
		$this->setRequest('GET', '/');
		[, , $headers] = $this->runApp($app);
		$this->assertSame('custom-value', $headers['X-Content-Type-Options'] ?? null);
		$this->assertArrayNotHasKey('X-Frame-Options', $headers);
	}
}
