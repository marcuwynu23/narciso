# Contributing to Narciso

First off, thank you for considering contributing to Narciso. Your time and effort make this project better for everyone.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Prerequisites](#prerequisites)
- [Project Structure](#project-structure)
- [Makefile / Scripts Reference](#makefile--scripts-reference)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Commit Conventions](#commit-conventions)
- [Pull Request Process](#pull-request-process)
- [Release Process](#release-process)
- [Questions](#questions)

---

## Code of Conduct

This project adheres to the [Contributor Covenant](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code. Please report unacceptable behavior to marcuwynu23@gmail.com.

---

## Prerequisites

| Tool   | Version        | Purpose                         |
|--------|----------------|---------------------------------|
| PHP    | 7.4+           | Runtime                         |
| Composer | 2.x          | Dependency management           |

---

## Project Structure

```
narciso/
├── src/                      # Library source code
│   ├── Application.php       # Main Application class
│   └── Middleware/
│       ├── MiddlewareInterface.php
│       ├── CorsMiddleware.php
│       ├── RateLimitMiddleware.php
│       └── SecurityHeadersMiddleware.php
├── test/                     # PHPUnit test suite
│   ├── TestCase.php          # Base test case
│   ├── ApplicationTest.php   # Application tests
│   ├── MiddlewareTest.php    # Middleware tests
│   └── run_tests.php         # Alternative test runner
├── samples/                  # Example entry points
│   ├── index.php
│   ├── 01_basic_routing.php
│   ├── 02_middlewares.php
│   └── ...
├── docs/                     # Documentation website (Cloudflare Pages)
│   ├── index.html
│   ├── documentation.html
│   ├── css/style.css
│   └── ...
├── .github/workflows/        # CI configuration
├── composer.json
├── phpunit.xml
└── README.md
```

---

## Scripts Reference

| Command                  | Description                    |
|--------------------------|--------------------------------|
| `composer test`          | Run the PHPUnit test suite     |
| `composer test-coverage` | Run tests with HTML coverage   |
| `vendor/bin/phpunit`     | Run tests directly             |
| `php test/run_tests.php` | Run tests via alternative entry |

---

## Development Workflow

1. **Fork** the repository on GitHub
2. **Create a feature branch** from `main`:
   ```bash
   git checkout -b feat/my-feature
   ```
3. **Write your code** following the [coding standards](#coding-standards)
4. **Write or update tests** for your changes
5. **Run the tests** to make sure everything passes:
   ```bash
   composer test
   ```
6. **Commit** using [conventional commits](#commit-conventions)
7. **Push** and open a pull request

---

## Coding Standards

- **Naming:** camelCase for methods/variables, PascalCase for classes/interfaces
- **Imports:** One `use` statement per line, grouped by namespace
- **Errors:** Use exceptions for error handling; avoid `die()` or `exit()` in library code
- **Formatting:** Follow PSR-12. Run `php -l` on every file to check syntax
- **Documentation:** Document public methods with PHPDoc blocks where behavior isn't obvious
- **Type hints:** Use scalar type hints and return types where possible (PHP 7.4+ compatible)

---

## Testing

We aim for high test coverage. All new features should include tests.

### Coverage Target

- Core routing: 100% (exact match, path params, 404, multiple methods)
- Middleware: 100% (all three built-in middleware classes + custom interface)
- API responses: JSON, XML, auto-detect
- Edge cases: empty params, invalid methods, boundary values

### Writing Tests

Tests extend `Marcuwynu23\Narciso\Test\TestCase` which provides:

- `setRequest(string $method, string $uri, array $server = [])` — set up the incoming request
- `runApp(Application $app)` — capture the response

```php
public function test_route_matches_path_parameter(): void
{
    $app = $this->createApp();
    $app->route('GET', '/users/:id', function ($app, $params) {
        $app->json(['id' => $params['id']]);
    });
    $this->setRequest('GET', '/users/42');
    $this->runApp($app);
    $this->expectOutputString(json_encode(['id' => '42']));
}
```

---

## Commit Conventions

We use [Conventional Commits](https://www.conventionalcommits.org/) to generate changelogs automatically.

```
<type>(<scope>): <description>
```

| Type       | Usage                                  |
|------------|----------------------------------------|
| `feat`     | A new feature                          |
| `fix`      | A bug fix                              |
| `docs`     | Documentation only changes             |
| `style`    | Formatting, missing semicolons, etc.   |
| `refactor` | Code change that neither fixes nor adds |
| `test`     | Adding or fixing tests                 |
| `chore`    | Build process or tooling changes       |
| `perf`     | Performance improvement                |
| `ci`       | CI configuration changes               |

### Scope

The scope should be the package or area affected: `core`, `middleware`, `db`, `docs`, `ci`, etc.

### Examples

```
feat(core): add support for PATCH method
fix(middleware): handle empty origin in CorsMiddleware
docs(readme): update quick-start example
test(api): add coverage for XML response format
```

---

## Pull Request Process

1. Ensure your branch is up to date with `main`
2. Run `composer test` and confirm all tests pass
3. Fill in the pull request template completely
4. Maintainers will review your PR within a few days

### PR Checklist

- [ ] I have read the Contributing Guide
- [ ] My code follows the project's coding standards
- [ ] I have added or updated tests
- [ ] All tests pass (`composer test`)
- [ ] I have updated documentation if necessary
- [ ] No new warnings or errors introduced

### What Gets Merged

- Bug fixes with passing tests
- New features with test coverage
- Documentation improvements
- Refactoring that doesn't break existing tests

### What Doesn't

- PRs without tests for new functionality
- Changes that break existing tests without justification
- Large, unfocused changes (keep PRs small and scoped)

---

## Release Process

1. A maintainer creates a tag:
   ```bash
   git tag v1.0.2
   git push origin v1.0.2
   ```
2. CI runs tests and creates a GitHub Release
3. Packagist auto-updates from the tag

---

## Questions

- Open a [Discussion](https://github.com/marcuwynu23/narciso/discussions) for questions
- Open an [Issue](https://github.com/marcuwynu23/narciso/issues) for bugs or feature requests
- Check the [Documentation](https://narciso.marcuwynu.space/) for usage guides
