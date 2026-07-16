# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

We take security seriously. If you discover a security vulnerability in Narciso, please report it privately before disclosing it publicly.

**Do not** open a public GitHub issue for security vulnerabilities.

### How to Report

Send an email to **marcuwynu23@gmail.com** with the following details:

- Description of the vulnerability
- Steps to reproduce
- Affected versions
- Any potential impact
- Suggested fix (if applicable)

You should receive a response within **72 hours**. If you don't, please follow up.

### What to Expect

1. We will acknowledge receipt within 3 business days
2. We will investigate and determine impact
3. We will work on a fix and release timeline
4. We will notify you when the fix is released
5. We will credit you (if desired) in the release notes

## Security Best Practices

When using Narciso:

- Always use `useSecurityHeaders()` in production to enable OWASP-recommended headers
- Configure CORS with specific origins, not wildcard `*`, for production
- Use a reverse proxy (nginx, Apache) for TLS termination and DDoS protection
- The built-in rate limiter is in-memory and per-process; use Redis or a database-backed store for multi-process deployments
- Keep PHP and all extensions up to date
- Validate and sanitize all user input in your route handlers
