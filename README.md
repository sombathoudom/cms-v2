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

- Laravel Scout search with Meilisearch integration
- Filament admin panel resources for Content, Media, Taxonomy, and Settings
- Role and permission management via spatie/laravel-permission
- Health check endpoint at `/health`
- Extensive migrations for CMS entities with soft deletes and foreign keys
- Demo seed data (**DO NOT USE IN PRODUCTION**)

## License

MIT
