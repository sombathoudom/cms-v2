# Changelog

## [Unreleased]
### Added
- Authentication scaffolding with Breeze-powered login, registration, password reset, and email verification flows with audit logging.
- Public web delivery for posts and pages with archive and search filtering.
- JSON API endpoints for published posts and pages with standard error responses.
- Hardened public content API responses to JSON:API spec with pagination links, rate limiting, and API logging.
- Correlation ID middleware, JSON logging formatter, and audit logging for content views.
- Tests covering web delivery, API contracts, validation errors, and RBAC protections for published content.
- Configurable password complexity enforcement, password history tracking, and session idle timeout auditing for web auth flows.
