<div align="center">

# Narciso

[![GitHub license](https://img.shields.io/github/license/marcuwynu23/narciso)](https://github.com/marcuwynu23/narciso/blob/main/LICENSE)
[![GitHub stars](https://img.shields.io/github/stars/marcuwynu23/narciso)](https://github.com/marcuwynu23/narciso/stargazers)
[![GitHub issues](https://img.shields.io/github/issues/marcuwynu23/narciso)](https://github.com/marcuwynu23/narciso/issues)

</div>

Narciso is a lightweight web library built on top of native PHP, inspired by **FastAPI** and **Flask**. It gives you a simple, expressive API for routing, middlewares, CORS, rate limiting, security headers, and database access—so you can build APIs and web apps quickly without a heavy framework.

**Packagist:** https://packagist.org/packages/marcuwynu23/narciso

---

## Features

- **Routing** — Define routes with path parameters (e.g. `/users/:id`), multiple HTTP methods.
- **Middlewares** — Add middlewares in order (security, CORS, rate limit, or your own). User-friendly `use()` API.
- **Easy database integration** — One config for MySQL or SQLite; `$app->db` ready to use.
- **Cross-Origin (CORS)** — Configurable origins, methods, and headers; no more guessing.
- **Rate limiting** — Built-in per-IP rate limit middleware; plug in Redis later for production.
- **Security** — Optional security headers (X-Frame-Options, X-Content-Type-Options, etc.) and secure defaults.
- **Technology signature** — Make the stack silent or changeable: remove `X-Powered-By`, set it blank, or fake it (e.g. "Express") so the server type is obfuscated.
- **HTTP** — JSON requests/responses, redirects, views, session handling.

---

## Installation

```bash
composer require marcuwynu23/narciso
```

---

## Quick start (Flask/FastAPI style)

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Marcuwynu23\Narciso\Application;

$app = new Application();
$app->setViewPath(__DIR__ . '/views');

// Optional: hide or change technology signature (X-Powered-By)
$app->setPoweredBy(false);   // silent, or setPoweredBy('Express') etc.

// Optional: session
$app->handleSession();

// Middlewares (order matters: first added = first run)
$app->useSecurityHeaders();                    // Security headers on every response
$app->useCors(['*']);                          // CORS: allow all origins (or list specific origins)
$app->useRateLimit(60, 60);                    // 60 requests per 60 seconds per IP

// Database: one call, then use $app->db everywhere
$app->handleDatabase([
    'type'     => 'mysql',
    'host'     => 'localhost',
    'database' => 'mydb',
    'username' => 'user',   // or 'user'
    'password' => 'secret',
]);

// Routes (path params like FastAPI/Flask)
$app->route('GET', '/', function ($app) {
    $app->render('/home/index.view');
});

$app->route('GET', '/json', function ($app) {
    $app->json(['message' => 'Hello World']);
});

$app->route('GET', '/users/:id', function ($app, $params) {
    $id = $params['id'] ?? null;
    $app->json(['user_id' => $id]);
});

// Run the app (dispatches request through middlewares and routes)
$app->run();
```

---

## Middlewares (user-friendly)

Add middlewares with `$app->use(...)` or the built-in helpers. They run in the order you add them.

### Built-in middlewares

| Method | Description |
|--------|-------------|
| `useSecurityHeaders(?array $headers)` | Sends security headers (X-Content-Type-Options, X-Frame-Options, etc.). Pass `null` for defaults or your own array. |
| `useCors($origins, $methods, $headers, $credentials, $maxAge)` | Configurable CORS. See [CORS](#cross-origin-cors) below. |
| `useRateLimit($maxRequests, $windowSeconds)` | Rate limit per client IP (in-memory; use Redis for multi-process). |

### Custom middleware

**Option 1 — Implement interface (recommended):**

```php
use Marcuwynu23\Narciso\Middleware\MiddlewareInterface;

class MyMiddleware implements MiddlewareInterface {
    public function handle(callable $next) {
        // Before request
        $result = $next();
        // After request (if $next returns)
        return $result;
    }
}

$app->use(new MyMiddleware());
```

**Option 2 — Callable (Flask-like):**

```php
$app->use(function ($app, $next) {
    // Before
    $result = $next();
    // After
    return $result;
});
```

---

## Database

One-time setup; then use `$app->db` in your routes.

**MySQL:**

```php
$app->handleDatabase([
    'type'     => 'mysql',
    'host'     => 'localhost',
    'database' => 'mydb',
    'username' => 'user',   // or 'user'
    'password' => 'pass',
]);
// $app->db is mysqli
```

**SQLite:**

```php
$app->handleDatabase([
    'type'     => 'sqlite',
    'database' => __DIR__ . '/data/app.db',
]);
// $app->db is SQLite3
```

---

## Cross-Origin (CORS)

Use `useCors()` for full control (recommended over the legacy `handleCORS()`):

```php
// Allow all origins (default)
$app->useCors();

// Restrict to your frontend (PHP 8+ named args; or pass by position)
$app->useCors(
    ['https://app.example.com', 'http://localhost:3000'],
    ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    ['Content-Type', 'Authorization'],
    true,   // credentials
    86400   // maxAge
);
```

---

## Rate limiting

Built-in per-IP rate limit (in-memory). For production with multiple processes, replace with a Redis-backed middleware.

```php
$app->useRateLimit(100, 60);   // 100 requests per 60 seconds per IP
```

When exceeded, responses are `429 Too Many Requests` with a `Retry-After` header.

---

## Security

- **Security headers** — `$app->useSecurityHeaders()` sends headers like:
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: SAMEORIGIN`
  - `X-XSS-Protection: 1; mode=block`
  - `Referrer-Policy`, `Permissions-Policy`
- **Custom headers:** `$app->useSecurityHeaders(['X-Custom' => 'value']);`
- **CORS** — Prefer `useCors()` with explicit origins instead of `*` when using credentials.
- **Database** — Use parameterized queries with `$app->db`; never concatenate user input into SQL.
- **Technology signature (X-Powered-By)** — Like Express’s `app.disable('x-powered-by')`: call `$app->setPoweredBy(false)` to remove the header (silent/obfuscated), `$app->setPoweredBy('')` for a blank value, or `$app->setPoweredBy('Express')` to send a custom value so the server type is not revealed.

---

## Technology signature (X-Powered-By)

Control or hide the `X-Powered-By` header so the server technology (e.g. PHP) is **silent** or **changeable** (Express-style):

```php
// Remove the header (obfuscated / silent)
$app->setPoweredBy(false);

// Send a blank value
$app->setPoweredBy('');

// Send a custom value (e.g. pretend another stack)
$app->setPoweredBy('Express');

// Leave default (don't touch; default behavior)
$app->setPoweredBy(null);  // or omit
```

Applied automatically at the start of `run()`.

---

## Routing and path parameters

Routes support **path parameters** (e.g. `/users/:id`). Your callback receives `($app, $params)`.

```php
$app->route('GET', '/posts/:slug', function ($app, $params) {
    $slug = $params['slug'];
    $app->json(['slug' => $slug]);
});
```

---

## API response (JSON or XML)

Use `$app->sendAPI()` to respond as **JSON** or **XML** (legacy). Format can be forced or **auto-detected** from query param (`?format=json` or `?format=xml`) or `Accept` header.

```php
// Auto-detect from ?format=xml or Accept: application/xml
$app->route('GET', '/api/users', function ($app) {
    $app->sendAPI(['users' => [['id' => 1, 'name' => 'Alice']]]);
});

// Force JSON
$app->sendAPI($data, ['format' => 'json']);

// Force XML (legacy)
$app->sendAPI($data, ['format' => 'xml']);

// Options: root tag, list item tag, status code
$app->sendAPI($data, [
    'format'       => 'xml',
    'root'         => 'data',
    'xmlItemName'  => 'user',
    'statusCode'   => 200,
]);
```

- **Default format** is JSON; use `?format=xml` or `Accept: application/xml` for XML.
- **arrayToXml** is public for custom XML: `$app->arrayToXml($array, 'rootTag', 'itemTag')`.
- **getPreferredApiFormat()** returns `'json'` or `'xml'` from the current request.

---

## How to run

**PHP built-in server:**

```sh
php -S localhost:8080 -t <entry_point_directory>
```

**With .autofile script:**

```sh
+ php -S localhost:8080 -t <entry_point_directory>
```

Then run:

```sh
auto
```

---

## Inspiration

Narciso is inspired by the myth of Narcissus and by the simplicity of **FastAPI** and **Flask**. It focuses on clarity and minimal setup: middlewares, database, CORS, rate limit, and security are a few method calls away, so you can spend time on your app instead of framework configuration.

---

## Testing

Tests are test-driven and cover all main functionality: routing (exact and path params), middlewares (security, CORS, rate limit), `setPoweredBy`, JSON/render responses, 404, database (SQLite), and custom middlewares.

**Run the test suite:**

```sh
composer install
vendor/bin/phpunit
```

Or from project root:

```sh
php test/run_tests.php
```

---

## Contributing

Contributions are welcome. Open an issue or submit a pull request.

Happy coding!
