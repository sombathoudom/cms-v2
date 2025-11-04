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
- Public blog and page delivery at `/posts` and `/pages/{slug}` with archive and search support
- JSON API for posts and pages under `/api/v1`
- Extensive migrations for CMS entities with soft deletes and foreign keys
- Demo seed data (**DO NOT USE IN PRODUCTION**)

## Public Content Delivery

- Visit `http://localhost:8000/posts` to view the blog listing with search and archive navigation.
- Individual posts are available at `http://localhost:8000/posts/{slug}` and pages at `http://localhost:8000/pages/{slug}`.
- All responses emit an `X-Correlation-ID` header for tracing and audit logs record every view event.

## API Usage

- List published posts: `GET /api/v1/posts`
  - Supports JSON:API pagination with `meta` + `links` objects.
  - Filters: `q`, `category`, `tag`, `year`, `month`, `per_page` (max 50).
- Retrieve a published post: `GET /api/v1/posts/{slug}`
- Retrieve a published page: `GET /api/v1/pages/{slug}`

All endpoints emit JSON:API compliant `data` payloads and structured errors `{ "error": { "code": "...", "message": "..." } }`.
Public endpoints are rate limited (default 60 requests/minute per IP) and every call is captured in the `api_logs` table with correlation IDs for observability.

See `OPENAPI.yaml` for full request/response contracts.

## License

MIT
