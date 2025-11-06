# CMS v2 Skeleton

A production-ready Laravel 11 skeleton for a content management system including Filament 3, Meilisearch-powered search, and role-based access controls.

## Requirements

- PHP 8.3+
- Node.js 20+
- Composer 2.7+
- Docker (optional, recommended for local services)

## How to Run Locally

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

> **Note:** Ensure MySQL, Redis, and Meilisearch are running (use the provided Docker stack or local equivalents).

## Docker

1. Copy `.env.example` to `.env` and set database credentials matching `docker-compose.yml`.
2. Build and start the stack:
   ```bash
   docker compose up --build
   ```
3. Access the application at `http://localhost:8080`.

The stack provisions MySQL, Redis, Meilisearch, the PHP application container, a dedicated queue worker, and Nginx.

## Testing & Quality

- Run static analysis: `vendor/bin/phpstan`
- Run formatter: `vendor/bin/pint`
- Execute tests: `vendor/bin/pest`

CI enforces Pint, PHPStan (level 8), and Pest across PHP 8.3/8.4 with MySQL & Postgres backends.

## Features

- Laravel Breeze authentication with login, registration, email verification, password resets, and remember-me support
- Laravel Scout search with Meilisearch integration
- Filament admin panel resources for Content, Media, Taxonomy, and Settings
- Filament user administration dashboard with search, role filters, and bulk activation controls
- Filament audit trail explorer with user/date filtering for authentication events
- Role and permission management via spatie/laravel-permission
- Authenticated `/api/v1/users` CRUD endpoints with audit logging for account lifecycle events
- Health check endpoint at `/health`
- Extensive migrations for CMS entities with soft deletes and foreign keys
- Demo seed data (**DO NOT USE IN PRODUCTION**)

## Security Defaults

- Configurable password policy enforcing minimum length, mixed character sets, and Have I Been Pwned compromised password checks via the `PASSWORD_*` environment flags.
- Password history tracking prevents reusing the last `PASSWORD_PREVENT_REUSE` secrets for every account.
- Session idle timeout middleware logs users out after `SESSION_IDLE_TIMEOUT` seconds of inactivity and records an `auth.session.timeout` audit trail.
- Dedicated JSON-formatted audit log channel with immutable storage of login, logout, and credential lifecycle actions.

## License

MIT
