<div align="center">

# Narciso

<a href="https://packagist.org/packages/marcuwynu23/narciso"><img src="https://img.shields.io/packagist/v/marcuwynu23/narciso" alt="Packagist"></a>
<a href="LICENSE"><img src="https://img.shields.io/github/license/marcuwynu23/narciso" alt="License"></a>
<a href="https://github.com/marcuwynu23/narciso/actions/workflows/ci.yml"><img src="https://github.com/marcuwynu23/narciso/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
<a href="https://github.com/marcuwynu23/narciso/stargazers"><img src="https://img.shields.io/github/stars/marcuwynu23/narciso" alt="Stars"></a>
<img src="https://img.shields.io/packagist/php-version/marcuwynu23/narciso" alt="PHP version">

<strong>Lightweight PHP web library.</strong> Routing, middleware, CORS, rate limiting, security headers, and database — no framework required.

➡️ **[Read the full user guide →](USER-GUIDE.md)**

</div>

---

## Table of Contents

- [What Is Narciso?](#what-is-narciso)
- [Use Cases](#use-cases)
- [Benefits](#benefits-for-developers)
- [Advantages Over Other Tools](#advantages-over-other-tools)
- [User Guide](USER-GUIDE.md)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [API Reference](#api-reference)
- [Configuration](#configuration)
- [Example Output](#example-output)
- [CI/CD Integration](#cicd-integration)
- [Development](#development)
- [Architecture](#architecture)
- [Contributing](CONTRIBUTING.md)
- [License](#license)

---

## What Is Narciso?

**Narciso** is a lightweight PHP web library built on top of native PHP, inspired by **FastAPI** and **Flask**. It gives you a simple, expressive API for routing, middlewares, CORS, rate limiting, security headers, and database access — so you can build APIs and web apps quickly without a heavy framework.

### What It Does

- **Route** — Map HTTP methods and URL patterns (including path parameters like `/users/:id`) to handler callbacks
- **Protect** — Add security headers, CORS, and rate limiting with one-line method calls
- **Connect** — Configure MySQL or SQLite in a single config array; `$app->db` is ready in every route
- **Respond** — Return JSON, XML (auto-detected from query/header), or render PHP views
- **Obfuscate** — Hide or fake the `X-Powered-By` header to keep the server technology private
- **Extend** — Add custom middleware via a callable or the `MiddlewareInterface`
- **Session** — Start PHP sessions with `handleSession()`

### Why Use It?

| Problem | How Narciso Solves It |
|---|---|
| Heavy frameworks require boilerplate | Narciso is ~500 lines of PHP; drop it in and start coding |
| CORS and security headers are tedious | One call: `$app->useCors([...])`, `$app->useSecurityHeaders()` |
| Rate limiting needs middleware setup | `$app->useRateLimit(100, 60)` — 1 line, in-memory, per-IP |
| Database config varies between MySQL/SQLite | Single `handleDatabase([...])` with `type: mysql` or `type: sqlite` |
| API format negotiation is boilerplate | `$app->sendAPI($data)` auto-detects JSON vs XML from `Accept` header or `?format=` |
| Server signature leaks tech stack | `$app->setPoweredBy(false)` removes `X-Powered-By` entirely |

### The Philosophy

1. **Minimal setup, maximum value.** A working API server in 10 lines of code.
2. **Your process stays yours.** No forced directory structure, no service container, no DI. Just PHP.
3. **Native-first.** Built on `mysqli`, `SQLite3`, and native PHP sessions — no runtime dependencies.

---

## Use Cases

| Scenario | How Narciso Helps |
|---|---|
| **JSON API backend** | Define routes with `$app->route()`, return JSON with `$app->json()` or `$app->sendAPI()`. CORS and rate limiting built in. |
| **Rapid prototype** | Install via Composer, write a single PHP file, run with `php -S`. Zero config. |
| **Microservice** | Lightweight enough to deploy as a standalone service. Add security headers and rate limiting in two lines. |
| **Simple web app with database** | Connect MySQL or SQLite with `handleDatabase()`, render views with `render()`. |
| **API gateway / proxy** | Use the middleware pipeline to add logging, auth, and rate limiting before proxying requests. |
| **Learning tool** | Read the ~500-line source to understand how routing, middleware, and request handling work in PHP. |

---

## Benefits for Developers

- **~10 second setup** — `composer require marcuwynu23/narciso`
- **No runtime dependencies** — Zero Composer dependencies at runtime
- **Familiar API** — Inspired by Flask and FastAPI; route handlers receive `($app, $params)`
- **Path parameters** — `/users/:id` syntax like Express.js
- **Middleware onion** — Add as many middlewares as needed; they wrap in order
- **JSON/XML auto-detection** — `sendAPI()` reads `Accept` header or `?format=` query param
- **Built-in security** — Security headers, CORS, rate limiting ship with the library
- **Technology obfuscation** — Remove or fake `X-Powered-By` with `setPoweredBy()`
- **PHP 7.4+ compatible** — Works on legacy and modern PHP
- **Fully tested** — PHPUnit suite with 36+ tests covering routing, middleware, database, and API

---

## Advantages Over Other Tools

| Aspect | Narciso | Laravel | Slim | Symfony | Handwritten |
|---|---|---|---|---|---|
| **Setup time** | ~10 seconds | Minutes | ~30 seconds | Minutes | Ongoing effort |
| **Runtime dependencies** | 0 | 50+ | 5 | 80+ | 0 |
| **Learning curve** | Low | High | Medium | High | N/A |
| **File size (source)** | ~500 lines | 10,000s | ~2,000 | 100,000s | Varies |
| **Database abstraction** | mysqli / SQLite3 | Eloquent | PDO | Doctrine | Custom |
| **CORS middleware** | Built-in | Package | Package | Bundle | Custom |
| **Rate limiting** | Built-in | Package | Package | Bundle | Custom |
| **Security headers** | Built-in | Middleware | Package | Bundle | Custom |
| **JSON/XML auto-detect** | Built-in | Manual | Manual | Manual | Custom |
| **Path parameters** | `:param` | Route params | `{param}` | `{param}` | Custom |
| **Middleware interface** | Yes | Yes | Yes | Yes | Custom |
| **Template engine** | PHP includes | Blade | Twig/Plates | Twig | Custom |
| **CLI tooling** | No | Artisan | No | Maker | N/A |
| **ORM** | No | Eloquent | Optional | Doctrine | Custom |
| **License** | Apache 2.0 | MIT | MIT | MIT | Your choice |

---

## Installation

```bash
composer require marcuwynu23/narciso
```

Requires PHP 7.4+ and the `json` extension. For MySQL use, install `php-mysql`. For SQLite, install `php-sqlite3`.

**Verify:**

```bash
php -r "require 'vendor/autoload.php'; echo class_exists(Marcuwynu23\\\Narciso\\\Application::class) ? 'OK' : 'FAIL';"
```

---

## Quick Start

Create `index.php`:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Marcuwynu23\Narciso\Application;

$app = new Application();

$app->route('GET', '/', function ($app) {
    $app->json(['message' => 'Hello World']);
});

$app->route('GET', '/users/:id', function ($app, $params) {
    $app->json(['user_id' => $params['id']]);
});

$app->run();
```

Run it:

```bash
php -S localhost:8080 index.php
```

Test it:

```bash
curl http://localhost:8080/
curl http://localhost:8080/users/42
```

---

## API Reference

### `route(string $method, string $path, callable $handler)`

Register a route handler.

| Parameter | Default | Description |
|---|---|---|
| `$method` | — | HTTP method (GET, POST, PUT, DELETE, PATCH, etc.) |
| `$path` | — | URL pattern with optional `:param` segments |
| `$handler` | — | `function($app, $params)` callback |

### `use($middleware)`

Add a middleware to the pipeline.

| Parameter | Default | Description |
|---|---|---|
| `$middleware` | — | Callable `($app, $next)` or object implementing `MiddlewareInterface` |

### `useSecurityHeaders(?array $headers = null)`

| Flag | Default | Description |
|---|---|---|
| `$headers` | `null` | Custom headers array or `null` for defaults |

### `useCors(array $origins, array $methods, array $headers, bool $credentials, int $maxAge)`

| Flag | Default | Description |
|---|---|---|
| `$origins` | `['*']` | Allowed origins |
| `$methods` | `['GET','POST','PUT','PATCH','DELETE','OPTIONS']` | Allowed HTTP methods |
| `$headers` | `['Content-Type','Authorization']` | Allowed request headers |
| `$credentials` | `false` | Allow credentials |
| `$maxAge` | `86400` | Preflight cache in seconds |

### `useRateLimit(int $maxRequests, int $windowSeconds)`

| Flag | Default | Description |
|---|---|---|
| `$maxRequests` | — | Max requests per window |
| `$windowSeconds` | — | Window duration in seconds |

### `sendAPI($data, array $options)`

| Option | Default | Description |
|---|---|---|
| `format` | `'json'` | `'json'` or `'xml'` |
| `root` | `'response'` | XML root tag |
| `xmlItemName` | `'item'` | XML list item tag |
| `statusCode` | `200` | HTTP status code |

### `setPoweredBy($value)`

| Flag | Default | Description |
|---|---|---|
| `$value` | — | `false` to remove, `''` for blank, `string` for custom, `null` to leave default |

### `handleDatabase(array $config)`

| Key | Default | Description |
|---|---|---|
| `type` | — | `'mysql'` or `'sqlite'` |
| `host` | `'localhost'` | MySQL host |
| `database` | — | Database name (MySQL) or file path (SQLite) |
| `username` | — | MySQL username |
| `password` | — | MySQL password |

---

## Configuration

Narciso uses method calls, not config files. Configuration precedence:

1. Method arguments (highest)
2. Built-in defaults (lowest)

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

## Example Output

### JSON Response

```json
HTTP/1.1 200 OK
Content-Type: application/json

{"message":"Hello World"}
```

### XML Response

```xml
HTTP/1.1 200 OK
Content-Type: application/xml

<?xml version="1.0"?>
<response>
  <message>Hello World</message>
</response>
```

### Rate Limited Response

```http
HTTP/1.1 429 Too Many Requests
Retry-After: 43
Content-Type: application/json

{"error":"Rate limit exceeded. Try again in 43 seconds."}
```

---

## CI/CD Integration

### GitHub Actions

```yaml
name: CI

on: [push, pull_request]

jobs:
  test:
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

---

## Development

| Prerequisite | Version | Purpose |
|---|---|---|
| PHP | 7.4+ | Runtime |
| Composer | 2.x | Dependency management |

```bash
git clone https://github.com/marcuwynu23/narciso.git
cd narciso
composer install
composer test
```

### Project Structure

```
narciso/
├── src/Application.php          # Main class (~500 lines)
├── src/Middleware/
│   ├── MiddlewareInterface.php  # Middleware contract
│   ├── CorsMiddleware.php      # CORS handler
│   ├── RateLimitMiddleware.php # Per-IP rate limiter
│   └── SecurityHeadersMiddleware.php # Security headers
├── test/                       # PHPUnit test suite
│   ├── TestCase.php
│   ├── ApplicationTest.php     # 26 tests
│   └── MiddlewareTest.php      # 10 tests
├── samples/                    # Example applications
│   ├── 01_basic_routing.php
│   └── ...
└── docs/                       # Documentation website
```

---

## Architecture

1. **Application** is the central class — it holds routes, middleware config, database config, and session state
2. **Routes** are registered as `(method, pattern, handler)` tuples. Path parameters (`:param`) are converted to regex with named groups
3. **Middleware pipeline** is an onion-wrapped array. Each middleware calls `$next()` to pass control inward
4. **Request dispatch** happens in `run()`: middleware stack executes first, then route matching, then the matched handler
5. **Database** connections are lazy — created on first access via `$app->db` magic getter
6. **API format** detection checks `?format=` query param first, then `Accept` header, then defaults to JSON

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for full details — coding standards, commit conventions, PR process, and more.

---

## License

Narciso is open source under the [Apache License, Version 2.0](LICENSE).
