# Narciso samples

Detailed examples for every feature. Run the PHP built-in server from the project root and open the sample you want (each file is a standalone entry point).

## How to run

From project root:

```sh
composer install
php -S localhost:8080 -t samples
```

Then open in browser:

- http://localhost:8080/ — index with links to all samples
- http://localhost:8080/01_basic_routing.php
- http://localhost:8080/02_middlewares.php
- … etc.

Or run a single sample:

```sh
php -S localhost:8080 -t samples
# Visit http://localhost:8080/08_full_application.php
```

## Sample index

| File | Features covered |
|------|------------------|
| **01_basic_routing.php** | Routing (GET/POST), path parameters (`/users/:id`), exact paths, `json()`, 404 |
| **02_middlewares.php** | `use()` with callable, custom `MiddlewareInterface`, order of execution |
| **03_security_cors_rate_limit.php** | `useSecurityHeaders()`, `useCors()`, `useRateLimit()` |
| **04_database.php** | `handleDatabase()` MySQL and SQLite, `$app->db`, query examples |
| **05_api_json_xml.php** | `sendAPI()`, JSON/XML format, `?format=xml`, `getPreferredApiFormat()`, `arrayToXml()` |
| **06_views_render.php** | `setViewPath()`, `render()` with data, redirect |
| **07_technology_signature.php** | `setPoweredBy()` — remove, blank, or custom (e.g. "Express") |
| **08_full_application.php** | Combined: routing, middlewares, DB, API, views, session, CORS |

## Views

- `views/home.view.php` — used by view/render samples.
- `views/about/about.view.php` — nested view path example.
