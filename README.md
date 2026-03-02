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

## Contributing

Contributions are welcome. Open an issue or submit a pull request.

Happy coding!
