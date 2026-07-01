<?php

namespace Marcuwynu23\Narciso\Middleware;

use Marcuwynu23\Narciso\Application;

/**
 * Rate limit requests per client (by IP). In-memory store; use Redis in production for multi-process.
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
	/** @var int Max requests per window */
	private int $maxRequests;

	/** @var int Window length in seconds */
	private int $windowSeconds;

	/** @var array<string, array{count: int, start: int}> */
	private static array $store = [];

	public function __construct(int $maxRequests = 60, int $windowSeconds = 60)
	{
		$this->maxRequests = $maxRequests;
		$this->windowSeconds = $windowSeconds;
	}

	public function handle(callable $next)
	{
		$key = $this->clientKey();
		$now = time();
		if (!isset(self::$store[$key])) {
			self::$store[$key] = ['count' => 0, 'start' => $now];
		}
		$entry = &self::$store[$key];
		if ($now - $entry['start'] >= $this->windowSeconds) {
			$entry = ['count' => 0, 'start' => $now];
		}
		$entry['count']++;
		if ($entry['count'] > $this->maxRequests) {
			Application::setResponseCode(429);
			Application::setResponseHeader('Content-Type', 'application/json');
			Application::setResponseHeader('Retry-After', (string) ($this->windowSeconds - ($now - $entry['start'])));
			echo json_encode(['error' => 'Too Many Requests', 'retry_after' => $this->windowSeconds]);
			return;
		}
		return $next();
	}

	public static function resetStore(): void
	{
		self::$store = [];
	}

	private function clientKey(): string
	{
		$forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
		if ($forwarded) {
			return trim(explode(',', $forwarded)[0]);
		}
		return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	}
}
