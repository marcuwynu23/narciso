<?php

namespace Marcuwynu23\Narciso\Middleware;

use Marcuwynu23\Narciso\Application;

/**
 * Adds security-related HTTP headers to every response.
 */
final class SecurityHeadersMiddleware implements MiddlewareInterface
{
	private array $headers;

	public function __construct(?array $headers = null)
	{
		$this->headers = $headers ?? [
			'X-Content-Type-Options' => 'nosniff',
			'X-Frame-Options' => 'SAMEORIGIN',
			'X-XSS-Protection' => '1; mode=block',
			'Referrer-Policy' => 'strict-origin-when-cross-origin',
			'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
		];
	}

	public function handle(callable $next)
	{
		foreach ($this->headers as $name => $value) {
			Application::setResponseHeader($name, $value);
		}
		return $next();
	}
}
