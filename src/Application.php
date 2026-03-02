<?php

namespace Marcuwynu23\Narciso;

use Marcuwynu23\Narciso\Middleware\CorsMiddleware;
use Marcuwynu23\Narciso\Middleware\MiddlewareInterface;
use Marcuwynu23\Narciso\Middleware\RateLimitMiddleware;
use Marcuwynu23\Narciso\Middleware\SecurityHeadersMiddleware;

final class Application
{
	private $viewPath;
	public $db;

	/** @var bool|string|null false = remove X-Powered-By, string = set value (e.g. "Express" or ""), null = don't touch */
	private $poweredBy = null;

	/** @var array<MiddlewareInterface|callable> */
	private array $middlewares = [];

	/** @var array<int, array{method: string, pattern: string, callback: callable}> */
	private array $routes = [];

	public function __construct()
	{
		$this->serverLog("Narciso Application.");
	}

	/**
	 * Control the X-Powered-By / technology signature (Express-style).
	 * Use this to obfuscate or silence the stack (e.g. hide that it's PHP).
	 *
	 * @param bool|string|null $value false = remove header (silent/obfuscated), "" = blank value, "Express" etc. = custom, null = leave default
	 */
	public function setPoweredBy($value): self
	{
		$this->poweredBy = $value;
		return $this;
	}

	public function serverLog($content)
	{
		error_log(print_r($content, true));
	}

	public function setViewPath(string $path): self
	{
		$this->viewPath = $path;
		return $this;
	}

	/**
	 * Add a middleware to the stack (runs in order before route handler).
	 * Accepts a MiddlewareInterface instance or a callable(request, next).
	 *
	 * @param MiddlewareInterface|callable $middleware
	 */
	public function use($middleware): self
	{
		$this->middlewares[] = $middleware;
		return $this;
	}

	/**
	 * Enable security headers (X-Content-Type-Options, X-Frame-Options, etc.).
	 * Pass custom headers array or null for defaults.
	 */
	public function useSecurityHeaders(?array $headers = null): self
	{
		$this->middlewares[] = new SecurityHeadersMiddleware($headers);
		return $this;
	}

	/**
	 * Enable configurable CORS. Chained call replaces handleCORS().
	 *
	 * @param array $origins e.g. ['*'] or ['https://app.example.com']
	 * @param array $methods e.g. ['GET','POST','PUT','DELETE','OPTIONS']
	 * @param array $headers e.g. ['Content-Type','Authorization']
	 * @param bool  $credentials Allow credentials
	 * @param int|null $maxAge Preflight cache in seconds
	 */
	public function useCors(
		array $origins = ['*'],
		array $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
		array $headers = ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept'],
		bool $credentials = false,
		?int $maxAge = 86400
	): self {
		$this->middlewares[] = new CorsMiddleware($origins, $methods, $headers, $credentials, $maxAge);
		return $this;
	}

	/**
	 * Enable rate limiting (in-memory; use Redis in production for multi-process).
	 *
	 * @param int $maxRequests Max requests per window
	 * @param int $windowSeconds Window in seconds
	 */
	public function useRateLimit(int $maxRequests = 60, int $windowSeconds = 60): self
	{
		$this->middlewares[] = new RateLimitMiddleware($maxRequests, $windowSeconds);
		return $this;
	}

	/**
	 * @deprecated Prefer useCors() for configurable CORS. This keeps backward compatibility.
	 */
	public function handleSession(string $name = "Narciso"): self
	{
		session_name($name);
		session_start();
		return $this;
	}

	/**
	 * Simple CORS (reflects request Origin). For more control use useCors().
	 * @deprecated Prefer useCors() which is configurable.
	 */
	public function handleCORS(): self
	{
		if (isset($_SERVER['HTTP_ORIGIN'])) {
			header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
			header('Access-Control-Allow-Credentials: true');
			header('Access-Control-Max-Age: 86400');
		}
		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
				header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
			}
			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
				header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
			}
			exit(0);
		}
		return $this;
	}

	/**
	 * Connect to database. Supports MySQL and SQLite.
	 * Config: type, host, database, user|username, password (for MySQL); type, database (path for SQLite).
	 */
	public function handleDatabase(array $config): self
	{
		$type = $config['type'] ?? 'mysql';
		$host = $config['host'] ?? 'localhost';
		$user = $config['user'] ?? $config['username'] ?? 'user';
		$password = $config['password'] ?? '';
		$database = $config['database'] ?? 'test';

		if ($type === 'mysql') {
			$db = new \mysqli($host, $user, $password, $database);
			if ($db->connect_error) {
				throw new \RuntimeException('Database connection failed: ' . $db->connect_error);
			}
			$this->db = $db;
		} elseif ($type === 'sqlite') {
			$this->db = new \SQLite3($database);
		} else {
			throw new \InvalidArgumentException("Unsupported database type: $type");
		}
		return $this;
	}

	/**
	 * Get JSON-decoded request body (for POST/PUT/PATCH).
	 */
	public function requestPost(): ?array
	{
		$content = file_get_contents('php://input');
		$data = json_decode($content, true);
		return is_array($data) ? $data : null;
	}

	/**
	 * Get request body as raw string.
	 */
	public function requestRaw(): string
	{
		return (string) file_get_contents('php://input');
	}

	/**
	 * Register a route. Path can contain params like /users/:id or /posts/:slug.
	 * Callback receives (Application $app, array $params).
	 */
	public function route(string $method, string $path, callable $callback): self
	{
		$this->routes[] = [
			'method' => strtoupper($method),
			'pattern' => $this->pathToRegex($path),
			'path' => $path,
			'callback' => $callback,
		];
		return $this;
	}

	/** Convert /users/:id to regex and extract param names */
	private function pathToRegex(string $path): string
	{
		$pattern = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]*)/', '(?P<$1>[^/]+)', $path);
		return '#^' . $pattern . '$#';
	}

	/** Match current request URI (without query string) against stored routes and return [callback, params] or null */
	private function matchRoute(): ?array
	{
		$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

		foreach ($this->routes as $r) {
			if ($r['method'] !== $method) {
				continue;
			}
			if (preg_match($r['pattern'], $uri, $m)) {
				$params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
				return [$r['callback'], $params];
			}
		}
		return null;
	}

	/**
	 * Run the application: execute middleware stack then dispatch to matching route.
	 * Call this at the end of your entry script.
	 */
	public function run(): void
	{
		if ($this->poweredBy === false) {
			header_remove('X-Powered-By');
		} elseif (is_string($this->poweredBy)) {
			header('X-Powered-By: ' . $this->poweredBy);
		}

		$runner = function () {
			$matched = $this->matchRoute();
			if ($matched !== null) {
				[$callback, $params] = $matched;
				$callback($this, $params);
				return;
			}
			http_response_code(404);
			header('Content-Type: application/json');
			echo json_encode(['error' => 'Not Found', 'path' => $_SERVER['REQUEST_URI'] ?? '/']);
		};

		$next = $runner;
		for ($i = count($this->middlewares) - 1; $i >= 0; $i--) {
			$m = $this->middlewares[$i];
			$next = function () use ($m, $next) {
				if ($m instanceof MiddlewareInterface) {
					return $m->handle($next);
				}
				return $m($this, $next);
			};
		}
		$next();
	}

	public function render(string $view, array $data = []): void
	{
		extract($data, EXTR_SKIP);
		require $this->viewPath . $view . '.php';
	}

	public function redirect(string $url, int $statusCode = 302): void
	{
		http_response_code($statusCode);
		header("Location: $url");
		exit;
	}

	public function json($data, int $statusCode = 200): void
	{
		http_response_code($statusCode);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data);
	}

	/**
	 * Send API response as JSON or XML (legacy). Format can be forced or auto-detected from
	 * query param (?format=json|xml) or Accept header.
	 *
	 * @param array|object $data Data to send (array or object; for XML, converted to tree).
	 * @param array $options 'format' => 'json'|'xml'|null (null = auto), 'statusCode' => int, 'root' => string (XML root tag), 'xmlItemName' => string (tag for list items)
	 */
	public function sendAPI($data, array $options = []): void
	{
		$format = $options['format'] ?? $this->getPreferredApiFormat();
		$statusCode = (int) ($options['statusCode'] ?? 200);
		$root = $options['root'] ?? 'response';
		$xmlItemName = $options['xmlItemName'] ?? 'item';

		$data = is_object($data) ? (array) $data : $data;
		if (!is_array($data)) {
			$data = ['data' => $data];
		}

		http_response_code($statusCode);

		if ($format === 'xml') {
			header('Content-Type: application/xml; charset=utf-8');
			echo $this->arrayToXml($data, $root, $xmlItemName);
			return;
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data);
	}

	/**
	 * Detect preferred API format from query string (?format=json|xml) or Accept header.
	 * Returns 'json' or 'xml'; defaults to 'json'.
	 */
	public function getPreferredApiFormat(): string
	{
		$query = $_GET['format'] ?? null;
		if (is_string($query)) {
			$q = strtolower(trim($query));
			if ($q === 'xml') {
				return 'xml';
			}
			if ($q === 'json') {
				return 'json';
			}
		}
		$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
		if (stripos($accept, 'application/xml') !== false || stripos($accept, 'text/xml') !== false) {
			return 'xml';
		}
		return 'json';
	}

	/**
	 * Convert array to XML string (legacy API support). Lists use xmlItemName for each entry.
	 */
	public function arrayToXml(array $data, string $rootTag = 'response', string $itemTag = 'item'): string
	{
		$xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><' . $rootTag . '/>');
		$this->arrayToXmlRecurse($data, $xml, $itemTag);
		return $xml->asXML();
	}

	/** @param \SimpleXMLElement $xml */
	private function arrayToXmlRecurse(array $data, $xml, string $itemTag): void
	{
		$keys = array_keys($data);
		$isList = $keys === range(0, count($data) - 1);
		if ($isList && count($data) > 0) {
			foreach ($data as $item) {
				if (is_array($item)) {
					$child = $xml->addChild($itemTag);
					$this->arrayToXmlRecurse($item, $child, $itemTag);
				} else {
					$xml->addChild($itemTag, htmlspecialchars((string) $item, ENT_XML1, 'UTF-8'));
				}
			}
			return;
		}
		foreach ($data as $key => $value) {
			$name = is_int($key) ? $itemTag : $key;
			if (is_array($value)) {
				$child = $xml->addChild($name);
				$this->arrayToXmlRecurse($value, $child, $itemTag);
			} else {
				$xml->addChild($name, htmlspecialchars((string) $value, ENT_XML1, 'UTF-8'));
			}
		}
	}
}
