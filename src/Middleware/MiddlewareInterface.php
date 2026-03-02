<?php

namespace Marcuwynu23\Narciso\Middleware;

/**
 * Middleware interface. Implement this to create custom middlewares
 * that run before your route handler (like Flask @app.before_request or FastAPI dependencies).
 */
interface MiddlewareInterface
{
	/**
	 * Handle the request. Call $next() to continue to the next middleware or route.
	 * Return a response (or exit) to stop the pipeline.
	 *
	 * @param callable $next Next middleware or final handler
	 * @return mixed
	 */
	public function handle(callable $next);
}
