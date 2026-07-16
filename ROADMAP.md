# Roadmap

## Vision

Narciso aims to be the go-to lightweight PHP web library for developers who want the expressiveness of Flask/FastAPI without the weight of a full framework. Zero dependencies, minimal boilerplate, maximum value.

## Current Status (v1.0.x)

Stable core with routing, middleware, CORS, rate limiting, security headers, database, and API format negotiation.

## Short-term (v1.1 — v1.2)

- [ ] **PSR-7 / PSR-15 support** — Implement PSR-7 HTTP messages and PSR-15 middleware interfaces for ecosystem compatibility
- [ ] **PSR-3 logger integration** — Optional PSR-3 logger injection
- [ ] **Static analysis** — PHPStan at max level in CI
- [ ] **Group routes** — Prefix-based route grouping (`$app->group('/api', function($app) { ... })`)
- [ ] **Route name / URL generation** — Named routes with `$app->url('route-name', params)`
- [ ] **Before/after hooks** — Per-route before/after filters

## Medium-term (v1.3 — v2.0)

- [ ] **PSR-11 container integration** — Optional dependency injection container support
- [ ] **PSR-3 logger integration** — Optional PSR-3 logger injection
- [ ] **Middleware for static files** — Built-in static file serving
- [ ] **Input validation helpers** — Simple request validation utilities
- [ ] **CLI command** — `narciso serve` to start the built-in server
- [ ] **Configuration file support** — Optional PHP config file for app settings
- [ ] **OpenAPI/Swagger integration** — Auto-generate API docs from routes

## Long-term (v2.0+)

- [ ] **PSR-11 container integration** — Optional dependency injection container
- [ ] **WebSocket support** — Basic WebSocket route handling
- [ ] **Plugin system** — Composer-based plugin architecture
- [ ] **Template engine abstraction** — Pluggable template engines (Twig, Blade, etc.)
- [ ] **CLI tool** — `narciso` command for project scaffolding, route list, etc.
- [ ] **Performance benchmarks** — Published benchmarks vs Slim, Laravel, raw PHP

## Non-goals

Narciso will never include:
- A full ORM (use Doctrine or Eloquent separately)
- A built-in admin panel
- A frontend asset pipeline
- A queue/worker system
- Its own template language (PHP includes are sufficient for the scope)

## How to influence the roadmap

Open a [discussion](https://github.com/marcuwynu23/narciso/discussions) or vote on existing ones. Popular requests get prioritized.
