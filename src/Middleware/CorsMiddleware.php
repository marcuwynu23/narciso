<?php

namespace Marcuwynu23\Narciso\Middleware;

use Marcuwynu23\Narciso\Application;

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

		Application::setResponseHeader('Access-Control-Allow-Origin', $allowOrigin);
		Application::setResponseHeader('Access-Control-Allow-Methods', implode(', ', $this->methods));
		Application::setResponseHeader('Access-Control-Allow-Headers', implode(', ', $this->headers));
		Application::setResponseHeader('Access-Control-Allow-Credentials', $this->credentials ? 'true' : 'false');
		if ($this->maxAge !== null) {
			Application::setResponseHeader('Access-Control-Max-Age', (string) $this->maxAge);
		}

		if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
			Application::setResponseCode(204);
			exit;
		}

		return $next();
	}
}
