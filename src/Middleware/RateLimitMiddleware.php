<?php

namespace Marcuwynu23\Narciso\Middleware;

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
			http_response_code(429);
			header('Content-Type: application/json');
			header('Retry-After: ' . ($this->windowSeconds - ($now - $entry['start'])));
			echo json_encode(['error' => 'Too Many Requests', 'retry_after' => $this->windowSeconds]);
			exit;
		}
		return $next();
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
