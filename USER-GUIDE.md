<div align="center">

# User Guide

</div>

**Narciso** is a lightweight PHP web library built on top of native PHP, inspired by FastAPI and Flask. This guide covers every feature, command, and configuration option.

---

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Command Reference](#command-reference)
- [Configuration](#configuration)
- [Concepts](#concepts)
- [CI/CD Integration](#cicd-integration)
- [Workflows](#workflows)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)

---

## Installation

### Prerequisites

| Requirement | Minimum                                  |
| ----------- | ---------------------------------------- |
| PHP         | 7.4+                                     |
| Composer    | 2.x                                      |
| Extensions  | json, mysqli (MySQL) or sqlite3 (SQLite) |

### Install via Composer

```bash
composer require marcuwynu23/narciso
```

### Verify Installation

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = new Marcuwynu23\Narciso\Application();
echo "Narciso installed";
```

---

## Quick Start

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Marcuwynu23\Narciso\Application;

$app = new Application();
$app->setViewPath(__DIR__ . '/views');

$app->useSecurityHeaders();
$app->useCors(['*']);

$app->route('GET', '/', function ($app) {
    $app->json(['message' => 'Hello World']);
});

$app->route('GET', '/users/:id', function ($app, $params) {
    $app->json(['user_id' => $params['id']]);
});

$app->run();
```

Run with the PHP built-in server:

```bash
php -S localhost:8080 -t .
```

---

## Command Reference

### `route(string $method, string $path, callable $handler)`

Register a route with an HTTP method and URL pattern.

```bash
GET /users/42
```

| Parameter  | Type       | Default | Description                                 |
| ---------- | ---------- | ------- | ------------------------------------------- |
| `$method`  | `string`   | —       | HTTP method (GET, POST, PUT, DELETE, etc.)  |
| `$path`    | `string`   | —       | URL pattern with optional `:param` segments |
| `$handler` | `callable` | —       | `function($app, $params)` callback          |

**Path parameters** are extracted into the `$params` array:

```php
$app->route('GET', '/posts/:slug', function ($app, $params) {
    $app->json(['slug' => $params['slug']]);
});
```

#### Examples by Use Case

**Basic JSON API:**

```php
$app->route('GET', '/api/users', function ($app) {
    $app->json(['users' => [['id' => 1, 'name' => 'Alice']]]);
});
```

**Form submission (POST):**

```php
$app->route('POST', '/contact', function ($app) {
    $data = $app->requestPost();
    // save $data ...
    $app->json(['status' => 'ok']);
});
```

---

### `use($middleware)`

Add custom middleware to the pipeline. Middleware runs in the order added.

**Callable middleware:**

```php
$app->use(function ($app, $next) {
    $start = microtime(true);
    $result = $next();
    $elapsed = (microtime(true) - $start) * 1000;
    header('X-Response-Time: ' . round($elapsed, 2) . 'ms');
    return $result;
});
```

**Interface middleware (recommended):**

```php
use Marcuwynu23\Narciso\Middleware\MiddlewareInterface;

class LoggerMiddleware implements MiddlewareInterface
{
    public function handle(callable $next)
    {
        error_log('Request started');
        $result = $next();
        error_log('Request finished');
        return $result;
    }
}

$app->use(new LoggerMiddleware());
```

---

### `useSecurityHeaders(?array $headers = null)`

Add security headers to every response.

| Header                 | Default Value                              |
| ---------------------- | ------------------------------------------ |
| X-Content-Type-Options | `nosniff`                                  |
| X-Frame-Options        | `SAMEORIGIN`                               |
| X-XSS-Protection       | `1; mode=block`                            |
| Referrer-Policy        | `strict-origin-when-cross-origin`          |
| Permissions-Policy     | `geolocation=(), microphone=(), camera=()` |

**Custom headers:**

```php
$app->useSecurityHeaders([
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options'        => 'DENY',
    'X-Custom'               => 'value',
]);
```

---

### `useCors(array $origins = ['*'], array $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], array $headers = ['Content-Type', 'Authorization'], bool $credentials = false, int $maxAge = 86400)`

Configure Cross-Origin Resource Sharing.

| Parameter      | Default                                           | Description                               |
| -------------- | ------------------------------------------------- | ----------------------------------------- |
| `$origins`     | `['*']`                                           | Allowed origins                           |
| `$methods`     | `['GET','POST','PUT','PATCH','DELETE','OPTIONS']` | Allowed HTTP methods                      |
| `$headers`     | `['Content-Type','Authorization']`                | Allowed request headers                   |
| `$credentials` | `false`                                           | Allow credentials (cookies, auth headers) |
| `$maxAge`      | `86400`                                           | Preflight cache duration (seconds)        |

```php
$app->useCors(
    ['https://app.example.com'],
    ['GET', 'POST'],
    ['Content-Type'],
    true,
    3600
);
```

---

### `useRateLimit(int $maxRequests, int $windowSeconds)`

Limit requests per IP address.

| Parameter        | Default | Description                    |
| ---------------- | ------- | ------------------------------ |
| `$maxRequests`   | —       | Maximum requests in the window |
| `$windowSeconds` | —       | Time window in seconds         |

```php
// 100 requests per 60 seconds per IP
$app->useRateLimit(100, 60);
```

When the limit is exceeded, the client receives:

- **Status:** `429 Too Many Requests`
- **Header:** `Retry-After: <seconds>`

> **Note:** Rate limiting is in-memory. For multi-process production deployments, replace with a Redis-backed middleware.

---

### `sendAPI($data, array $options = [])`

Respond with JSON or XML based on format detection or explicit option.

| Option        | Default      | Description            |
| ------------- | ------------ | ---------------------- |
| `format`      | `'json'`     | `'json'` or `'xml'`    |
| `root`        | `'response'` | XML root tag name      |
| `xmlItemName` | `'item'`     | XML list item tag name |
| `statusCode`  | `200`        | HTTP status code       |

```php
$app->sendAPI(['users' => [...]], ['format' => 'xml', 'root' => 'data', 'xmlItemName' => 'user']);
```

Format auto-detection priority:

1. `?format=json` or `?format=xml` query parameter
2. `Accept: application/json` or `Accept: application/xml` header
3. Default: JSON

---

### `setPoweredBy($value)`

Control the `X-Powered-By` response header.

| `$value` | Behavior                                         |
| -------- | ------------------------------------------------ |
| `false`  | Remove the header entirely (silent / obfuscated) |
| `''`     | Set the header to a blank value                  |
| `string` | Set to a custom value (e.g. `'Express'`)         |
| `null`   | Leave PHP's default behavior unchanged           |

---

### `handleDatabase(array $config)`

Configure and connect to a database. Available through `$app->db`.

**MySQL:**

```php
$app->handleDatabase([
    'type'     => 'mysql',
    'host'     => 'localhost',
    'database' => 'mydb',
    'username' => 'user',
    'password' => 'secret',
    'port'     => 3306,   // optional, default 3306
]);
// $app->db is a mysqli instance
```

**SQLite:**

```php
$app->handleDatabase([
    'type'     => 'sqlite',
    'database' => __DIR__ . '/data/app.db',
]);
// $app->db is a SQLite3 instance
```

---

### `handleSession()`

Start a PHP session. After calling this, `$_SESSION` is available in routes.

```php
$app->handleSession();

$app->route('GET', '/login', function ($app) {
    $_SESSION['user_id'] = 42;
    $app->json(['status' => 'logged in']);
});
```

---

### `render(string $view)`

Render a PHP view file. Views are resolved relative to the path set by `setViewPath()`.

```php
$app->setViewPath(__DIR__ . '/views');
$app->route('GET', '/', function ($app) {
    $app->render('home/index');   // resolves to __DIR__/views/home/index.view.php
});
```

### `redirect(string $url, int $statusCode = 302)`

Redirect the client to another URL.

```php
$app->route('GET', '/old-page', function ($app) {
    $app->redirect('/new-page', 301);
});
```

---

## Configuration

### Application Configuration

Narciso has no config file — everything is configured through method calls on the `Application` instance.

### Configuration Precedence

1. Method call parameters (highest priority)
2. Built-in defaults (lowest priority)

### Full Example

```php
$app = new Application();
$app->setViewPath(__DIR__ . '/views');
$app->setPoweredBy(false);
$app->handleSession();
$app->useSecurityHeaders();
$app->useCors(['https://myapp.com']);
$app->useRateLimit(60, 60);
$app->handleDatabase([
    'type'     => 'sqlite',
    'database' => __DIR__ . '/data/app.db',
]);
```

---

## Concepts

### Conventional Commits

Narciso uses [Conventional Commits](https://www.conventionalcommits.org/) for its own changelog generation. The format is:

```
<type>(<scope>): <description>
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`, `perf`, `ci`.

### Middleware Pipeline

Middleware runs in the order they are added. Each middleware wraps the next, forming an onion-like pipeline:

```
Request → Middleware1 → Middleware2 → Route → Middleware2 → Middleware1 → Response
```

### Path Parameter Syntax

Route paths use `:param` syntax (like FastAPI/Flask):

```
/users/:id           → matches /users/42, /users/abc
/posts/:slug/comments → matches /posts/hello-world/comments
```

---

## CI/CD Integration

### GitHub Actions

```yaml
name: CI

on: [push, pull_request]

jobs:
  build:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: [8.1, 8.2, 8.3, 8.4]

    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          tools: composer:v2
      - run: composer validate
      - run: composer install --prefer-dist --no-progress
      - run: vendor/bin/phpunit
```

### GitLab CI

```yaml
image: php:8.2

before_script:
  - apt-get update && apt-get install -y git unzip
  - curl -sS https://getcomposer.org/installer | php
  - php composer.phar install

test:
  script:
    - vendor/bin/phpunit
```

---

## Workflows

### Monorepo / Multi-App Setup

Multiple applications can share the same Narciso installation:

```php
// admin.php
$admin = new Application();
$admin->route('GET', '/', fn($app) => $app->render('admin/dashboard'));
$admin->run();

// api.php
$api = new Application();
$api->route('GET', '/status', fn($app) => $app->json(['status' => 'ok']));
$api->run();
```

### JSON API with XML Fallback

```php
$app->route('GET', '/api/users', function ($app) {
    $users = [['id' => 1, 'name' => 'Alice'], ['id' => 2, 'name' => 'Bob']];
    $app->sendAPI($users, [
        'root'        => 'users',
        'xmlItemName' => 'user',
    ]);
});
// ?format=xml or Accept: application/xml returns XML
// Default returns JSON
```

---

## Troubleshooting

| Problem                    | Cause                                      | Fix                                                                                                        |
| -------------------------- | ------------------------------------------ | ---------------------------------------------------------------------------------------------------------- |
| Route returns 404          | Path doesn't match any registered route    | Check route path for typos; remember `:param` syntax                                                       |
| CORS preflight fails       | OPTIONS request not handled                | `useCors()` handles OPTIONS automatically; ensure it's called before `run()`                               |
| Rate limit always exceeded | X-Forwarded-For not set behind proxy       | Configure your reverse proxy to set `X-Forwarded-For`; rate limit reads `$_SERVER['HTTP_X_FORWARDED_FOR']` |
| Database connection fails  | Missing PHP extension                      | Install `php-mysql` or `php-sqlite3`                                                                       |
| `$app->db` is null         | `handleDatabase()` not called              | Call `$app->handleDatabase(...)` before routes                                                             |
| XML output shows `?>` tags | Format auto-detection picks XML            | Force JSON with `sendAPI($data, ['format' => 'json'])`                                                     |
| Session data lost          | `handleSession()` not called before routes | Call `$app->handleSession()` before `$app->run()`                                                          |

---

## FAQ

**Q: Does Narciso work with PHP 8.4?**

A: Yes. Narciso is tested against PHP 8.1 through 8.4 in CI. PHP 7.4+ is supported.

**Q: Can I use Narciso with a framework like Laravel or Symfony?**

A: Narciso is designed as a standalone library. It's not intended to run inside another framework, though you can use individual components with care.

**Q: Does Narciso support dependency injection?**

A: Not natively. Narciso follows a simple, explicit pattern — you create an `Application` instance and configure it directly.

**Q: How do I handle file uploads?**

A: Access uploaded files via `$_FILES` inside your route callback. Narciso doesn't abstract file handling.

**Q: Can I use Narciso with an ORM like Eloquent or Doctrine?**

A: Yes. You can `require` Composer packages alongside Narciso and use them in your route callbacks.

**Q: Is Narciso production-ready?**

A: Yes, but review your security, CORS, and rate limit configuration for your specific deployment.

**Q: How do I debug a route that isn't matching?**

A: Check the route path and method. Paths are case-sensitive. Use `$app->route('GET', ...)` for exact method matching.

**Q: Does Narciso support WebSockets?**

A: No. Narciso handles HTTP request-response cycles. For WebSockets, use Ratchet or a dedicated WebSocket library.

**Q: Can I use Narciso with nginx or Apache?**

A: Yes. Point your document root to the directory containing your entry script (e.g., `index.php`). Configure URL rewriting to pass non-file requests to your script.

**Q: How do I contribute?**

A: See [CONTRIBUTING.md](CONTRIBUTING.md) for full details. Open an issue first for significant changes.

**Q: Is there a way to group routes with a prefix?**

A: Not built-in, but you can organize routes by creating separate entry points or using a routing function:

```php
function api_routes(Application $app) {
    $app->route('GET', '/api/v1/users', ...);
    $app->route('GET', '/api/v1/posts', ...);
}
api_routes($app);
```

**Q: How do I serve static files?**

A: Narciso doesn't include a static file server. Use the PHP built-in server (`php -S`) or configure nginx/Apache to serve static files directly.

**Q: What's the difference between `json()` and `sendAPI()`?**

A: `json()` always returns JSON. `sendAPI()` auto-detects JSON or XML based on request parameters or headers.

**Q: Can I use middleware with specific routes only?**

A: Currently middleware applies globally. You can conditionally skip logic inside the middleware by inspecting the request URI.

**Q: Is there a way to register error handlers?**

A: Not yet. Consider wrapping your route callback in a try-catch for custom error handling.
