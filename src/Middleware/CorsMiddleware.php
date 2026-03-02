<?php

namespace Marcuwynu23\Narciso\Middleware;

/**
 * Configurable CORS middleware. Set origins, methods, headers, and credentials.
 */
final class CorsMiddleware implements MiddlewareInterface
{
	private array $origins;
	private array $methods;
	private array $headers;
	private bool $credentials;
	private ?int $maxAge;

	public function __construct(
		array $origins = ['*'],
		array $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
		array $headers = ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept'],
		bool $credentials = false,
		?int $maxAge = 86400
	) {
		$this->origins = $origins;
		$this->methods = $methods;
		$this->headers = $headers;
		$this->credentials = $credentials;
		$this->maxAge = $maxAge;
	}

	public function handle(callable $next)
	{
		$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
		$allowOrigin = in_array('*', $this->origins, true)
			? '*'
			: (in_array($origin, $this->origins, true) ? $origin : ($this->origins[0] ?? '*'));

		header("Access-Control-Allow-Origin: $allowOrigin");
		header('Access-Control-Allow-Methods: ' . implode(', ', $this->methods));
		header('Access-Control-Allow-Headers: ' . implode(', ', $this->headers));
		header('Access-Control-Allow-Credentials: ' . ($this->credentials ? 'true' : 'false'));
		if ($this->maxAge !== null) {
			header('Access-Control-Max-Age: ' . $this->maxAge);
		}

		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
			http_response_code(204);
			exit;
		}

		return $next();
	}
}
