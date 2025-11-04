# Phase 3 – Repository Scaffolding Plan

## 1. Repository Tree Structure
```
app/
  Console/
  DTOs/
  Events/
  Exceptions/
  Http/
    Controllers/
    Middleware/
    Requests/
    Resources/
  Jobs/
  Models/
  Notifications/
  Policies/
  Providers/
  Services/
bootstrap/
config/
database/
  factories/
  migrations/
  seeders/
docker/
  app/
  nginx/
  php/
  queue/
  scripts/
.github/
  workflows/
public/
resources/
  js/
  sass/
  views/
routes/
storage/
tests/
  Feature/
  Unit/
```

## 2. Environment Configuration (.env.example)
```
APP_NAME=SAFE_DEFAULT=LaravelCMS        # REQUIRED
APP_ENV=SAFE_DEFAULT=local              # REQUIRED
APP_KEY=SECRET=<base64:placeholder>     # REQUIRED
APP_URL=SAFE_DEFAULT=http://localhost   # REQUIRED
LOG_CHANNEL=SAFE_DEFAULT=stack          # REQUIRED
LOG_CORRELATION_KEY=SAFE_DEFAULT=X-Request-ID  # REQUIRED
DB_CONNECTION=SAFE_DEFAULT=mysql        # REQUIRED
DB_HOST=SAFE_DEFAULT=db                 # REQUIRED
DB_PORT=SAFE_DEFAULT=3306               # REQUIRED
DB_DATABASE=SAFE_DEFAULT=cms            # REQUIRED
DB_USERNAME=SECRET=<db_username>        # REQUIRED
DB_PASSWORD=SECRET=<db_password>        # REQUIRED
REDIS_HOST=SAFE_DEFAULT=redis           # REQUIRED
REDIS_PORT=SAFE_DEFAULT=6379            # REQUIRED
QUEUE_CONNECTION=SAFE_DEFAULT=redis     # REQUIRED
MEILISEARCH_HOST=SAFE_DEFAULT=http://meilisearch:7700  # REQUIRED
MEILISEARCH_KEY=SECRET=<meilisearch_master_key>        # REQUIRED
SANCTUM_STATEFUL_DOMAINS=SAFE_DEFAULT=localhost        # REQUIRED
SESSION_DOMAIN=SAFE_DEFAULT=localhost                 # REQUIRED
FILESYSTEM_DISK=SAFE_DEFAULT=public     # REQUIRED
CACHE_DRIVER=SAFE_DEFAULT=redis         # REQUIRED
MAIL_MAILER=SAFE_DEFAULT=log            # REQUIRED
MAIL_FROM_ADDRESS=SAFE_DEFAULT=admin@example.com  # REQUIRED
MAIL_FROM_NAME=SAFE_DEFAULT=Laravel CMS          # REQUIRED
SUPERVISOR_WORKERS=SAFE_DEFAULT=4       # REQUIRED
```

## 3. Docker Compose Services
- **db**
  - Image: mysql:8 with utf8mb4
  - Healthcheck: mysqladmin ping
  - Depends on: None
  - Restart: unless-stopped
  - Resources: mem_limit 512m, cpus 0.5
- **redis**
  - Image: redis:7
  - Healthcheck: redis-cli ping
  - Depends on: db
  - Restart: unless-stopped
  - Resources: mem_limit 256m, cpus 0.25
- **meilisearch**
  - Image: getmeili/meilisearch:latest
  - Healthcheck: curl localhost:7700/health
  - Depends on: redis
  - Restart: unless-stopped
  - Resources: mem_limit 256m, cpus 0.25
- **queue**
  - Image: built from app stage with Supervisor worker config
  - Healthcheck: supervisorctl status
  - Depends on: app
  - Restart: unless-stopped
  - Resources: mem_limit 256m, cpus 0.25
- **app**
  - Image: php-fpm stage with Laravel
  - Healthcheck: php artisan route:list
  - Depends on: db, redis, meilisearch
  - Restart: unless-stopped
  - Resources: mem_limit 512m, cpus 0.5
- **nginx**
  - Image: nginx stage serving Laravel
  - Healthcheck: curl http://localhost/healthz
  - Depends on: app
  - Restart: unless-stopped
  - Resources: mem_limit 128m, cpus 0.25
- `.env.docker` overrides: DB hostnames, queue workers, Meilisearch keys

## 4. Dockerfile Plan (Multi-stage)
1. **Composer stage**
   - Base: composer:2-php8.2
   - Install PHP extensions, run composer install with --no-dev, cache vendor
2. **Node stage**
   - Base: node:20
   - Install dependencies, build assets, cache node_modules and build output
3. **PHP-FPM stage**
   - Base: php:8.2-fpm-alpine
   - Copy vendor and build artifacts, add PHP extensions, configure non-root user `www`.
   - Copy application code excluding docs/tests via .dockerignore
4. **Nginx + Supervisor stage**
   - Base: nginx:alpine
   - Copy compiled assets, nginx conf, supervisor config for queue workers
   - Copy from PHP-FPM stage for php-fpm socket communication via separate container
   - Ensure final image minimal, remove build tools.

## 5. Data Model Overview
| Entity | Fields (name:type:index?) | Relationships | Constraints | Source Feature(s) |
| --- | --- | --- | --- | --- |
| User | id:uuid:pk, name:string, email:string:unique, password:string, role:string:index, two_factor_secret:string:nullable, remember_token:string:nullable, last_login_at:datetime:nullable, session_expires_at:datetime:nullable, created_at, updated_at, deleted_at (SoftDeletes) | hasMany(Content), hasMany(ActivityLog) | Email unique, password policy enforced, role in enum | Authentication & User Management; Publishing Workflow |
| ActivityLog | id:bigint:pk, user_id:uuid:index, action:string:index, subject_type:string, subject_id:uuid:index, metadata:json, created_at | belongsTo(User) | Foreign key user_id references users | Authentication & User Management; Security & Compliance |
| Content | id:uuid:pk, title:string, slug:string:unique, excerpt:text:nullable, body:longtext, featured_image_id:uuid:nullable, status:string:index, author_id:uuid:index, publish_at:datetime:nullable, published_at:datetime:nullable, type:string:index, meta_title:string:nullable, meta_description:text:nullable, canonical_url:string:nullable, schedule_token:string:nullable, created_at, updated_at, deleted_at (SoftDeletes), audit_created_by:uuid, audit_updated_by:uuid | belongsTo(User, as Author), hasMany(ContentRevision), morphMany(MediaAttachment), hasMany(ContentWorkflow), belongsTo(Category), belongsToMany(Tag) | Slug unique, publish_at >= now for schedules | Content Management; Publishing Workflow; Taxonomy & Metadata; Security & Compliance |
| ContentRevision | id:bigint:pk, content_id:uuid:index, revision_number:int:index, data:json, created_by:uuid:index, created_at | belongsTo(Content), belongsTo(User, as Creator) | Unique (content_id, revision_number) | Content Management |
| ContentWorkflow | id:bigint:pk, content_id:uuid:index, state:string:index, assigned_to:uuid:nullable:index, notes:text:nullable, transition_at:datetime, created_at | belongsTo(Content), belongsTo(User, as Assignee) | Workflow state enum order | Publishing Workflow |
| Media | id:uuid:pk, file_name:string, disk:string, path:string:unique, mime_type:string:index, size:integer, width:integer:nullable, height:integer:nullable, uploaded_by:uuid:index, checksum:string:unique, created_at, updated_at, deleted_at (SoftDeletes) | morphMany(MediaAttachment) | checksum unique, size limit | Media Library; Security & Compliance |
| MediaAttachment | id:bigint:pk, media_id:uuid:index, attachable_type:string:index, attachable_id:uuid:index, field:string:nullable, created_at | belongsTo(Media), morphTo(Attachable) | Foreign key media_id references media | Media Library; Content Management |
| Category | id:uuid:pk, name:string, slug:string:unique, parent_id:uuid:nullable:index, description:text:nullable, created_at, updated_at, deleted_at (SoftDeletes) | hasMany(Content), belongsTo(Category, as Parent), hasMany(Category, as Children) | parent_id references categories | Taxonomy & Metadata |
| Tag | id:uuid:pk, name:string, slug:string:unique, created_at, updated_at | belongsToMany(Content) | slug unique | Taxonomy & Metadata |
| ContentTag | content_id:uuid:index, tag_id:uuid:index | belongsTo(Content), belongsTo(Tag) | Primary key (content_id, tag_id) | Taxonomy & Metadata |
| Menu | id:uuid:pk, name:string, location:string:index, created_at, updated_at | hasMany(MenuItem) | location unique per site | Site Settings & Configuration |
| MenuItem | id:uuid:pk, menu_id:uuid:index, parent_id:uuid:nullable:index, title:string, url:string, order:int:index, target:string:nullable, created_at, updated_at | belongsTo(Menu), hasMany(MenuItem, as Children) | parent_id references menu_items | Site Settings & Configuration |
| Setting | key:string:pk, value:json, type:string:index, updated_by:uuid:nullable, updated_at | belongsTo(User, as Updater) | key unique | Site Settings & Configuration |
| Announcement | id:uuid:pk, message:text, starts_at:datetime, ends_at:datetime:nullable, is_active:boolean:index, created_at, updated_at | belongsTo(User, as Creator) | Active range validated | Site Settings & Configuration |
| ApiToken | id:uuid:pk, user_id:uuid:index, name:string, token:string:unique, abilities:json, last_used_at:datetime:nullable, expires_at:datetime:nullable, created_at, updated_at | belongsTo(User) | token unique | API & Headless Mode |
| Webhook | id:uuid:pk, name:string, url:string, secret:string, events:json, is_active:boolean:index, created_at, updated_at | hasMany(WebhookDelivery) | URL validated, secret required | API & Headless Mode |
| WebhookDelivery | id:bigint:pk, webhook_id:uuid:index, content_id:uuid:nullable:index, payload:json, status:string:index, response_code:int:nullable, response_body:text:nullable, sent_at:datetime, created_at | belongsTo(Webhook), belongsTo(Content) | Retry policy required | API & Headless Mode; Publishing Workflow |
| AuditTrail | id:bigint:pk, user_id:uuid:nullable:index, entity_type:string:index, entity_id:uuid:index, action:string:index, changes:json, ip_address:string:nullable, user_agent:string:nullable, occurred_at:datetime:index, created_at | belongsTo(User) | Records all mutations | Security & Compliance |
| Sitemap | id:uuid:pk, path:string:unique, generated_at:datetime, created_at | — | path unique | Taxonomy & Metadata |
| CacheInvalidation | id:bigint:pk, content_id:uuid:index, tags:json, triggered_by:uuid:index, triggered_at:datetime | belongsTo(Content), belongsTo(User) | Maintains cache tags | System Architecture Foundations |
| Backup | id:uuid:pk, file_name:string, disk:string, checksum:string:unique, created_by:uuid:index, created_at | belongsTo(User) | ensures integrity | Site Settings & Configuration; DevOps |
| QueueJob | id:bigint:pk, job_name:string:index, payload:json, attempts:int, available_at:datetime, reserved_at:datetime:nullable, created_at | — | Mirrors queue table for horizon-like monitoring | DevOps, Workflow |

Audit trail fields appear on tables requiring compliance.

## 6. API Endpoints
| Method | Path | Auth | Purpose | Sample Request | Sample Response | Error Response |
| --- | --- | --- | --- | --- | --- | --- |
| POST | /api/v1/auth/login | Public | Authenticate user and issue token | `{ "email": "user@example.com", "password": "secret" }` | `{ "token": "...", "user": { "id": "...", "name": "..." } }` | `{ "error": { "code": "AUTH_INVALID", "message": "Invalid credentials." } }` |
| POST | /api/v1/auth/logout | Sanctum Token | Revoke current token | `{}` | `{ "status": "logged_out" }` | `{ "error": { "code": "AUTH_UNAUTHORIZED", "message": "Token invalid." } }` |
| GET | /api/v1/users | Sanctum Token + RBAC | List users with filters | `?role=Editor` | `{ "data": [ { "id": "...", "email": "..." } ] }` | `{ "error": { "code": "AUTH_FORBIDDEN", "message": "Insufficient permissions." } }` |
| POST | /api/v1/users | Sanctum Token + RBAC | Create user | `{ "name": "Editor", "email": "editor@example.com", "role": "Editor" }` | `{ "data": { "id": "..." } }` | `{ "error": { "code": "VALIDATION_FAILED", "message": "Email already taken." } }` |
| GET | /api/v1/content | Public | List published content with filters | `?type=post&category=tech` | `{ "data": [ { "id": "...", "title": "..." } ], "meta": { "pagination": {"page":1} } }` | `{ "error": { "code": "CONTENT_NOT_FOUND", "message": "No content available." } }` |
| POST | /api/v1/content | Sanctum Token + RBAC | Create new content draft | `{ "title": "Sample", "type": "post" }` | `{ "data": { "id": "...", "status": "draft" } }` | `{ "error": { "code": "VALIDATION_FAILED", "message": "Title required." } }` |
| PATCH | /api/v1/content/{id} | Sanctum Token + RBAC | Update content | `{ "status": "review" }` | `{ "data": { "id": "...", "status": "review" } }` | `{ "error": { "code": "AUTH_FORBIDDEN", "message": "Cannot change status." } }` |
| POST | /api/v1/content/{id}/publish | Sanctum Token + RBAC | Approve and publish | `{ "publish_at": "2024-05-01T10:00:00Z" }` | `{ "data": { "id": "...", "status": "scheduled" } }` | `{ "error": { "code": "WORKFLOW_INVALID", "message": "Invalid transition." } }` |
| GET | /api/v1/media | Sanctum Token + RBAC | Browse media | `?type=image` | `{ "data": [ { "id": "...", "file_name": "hero.jpg" } ] }` | `{ "error": { "code": "AUTH_FORBIDDEN", "message": "Access denied." } }` |
| POST | /api/v1/media | Sanctum Token + RBAC | Upload media | multipart form-data | `{ "data": { "id": "...", "url": "..." } }` | `{ "error": { "code": "VALIDATION_FAILED", "message": "Unsupported type." } }` |
| GET | /api/v1/taxonomies/categories | Public | List categories | `?parent=null` | `{ "data": [ { "id": "...", "name": "Tech" } ] }` | `{ "error": { "code": "TAXONOMY_NOT_FOUND", "message": "Category missing." } }` |
| GET | /api/v1/taxonomies/tags | Public | List tags | `?q=laravel` | `{ "data": [ { "id": "...", "name": "Laravel" } ] }` | `{ "error": { "code": "TAXONOMY_NOT_FOUND", "message": "Tag missing." } }` |
| GET | /api/v1/settings | Sanctum Token + RBAC | Retrieve site settings | `{}` | `{ "data": { "site_name": "..." } }` | `{ "error": { "code": "AUTH_FORBIDDEN", "message": "Insufficient permissions." } }` |
| PATCH | /api/v1/settings | Sanctum Token + RBAC | Update settings | `{ "site_name": "CMS" }` | `{ "data": { "site_name": "CMS" } }` | `{ "error": { "code": "VALIDATION_FAILED", "message": "Invalid value." } }` |
| GET | /api/v1/menus | Public | Fetch menus | `?location=main` | `{ "data": [ { "id": "...", "items": [ ... ] } ] }` | `{ "error": { "code": "MENU_NOT_FOUND", "message": "Menu missing." } }` |
| POST | /api/v1/menus | Sanctum Token + RBAC | Manage menus | `{ "name": "Footer", "items": [] }` | `{ "data": { "id": "..." } }` | `{ "error": { "code": "VALIDATION_FAILED", "message": "Name required." } }` |
| GET | /api/v1/webhooks | Sanctum Token + RBAC | List webhooks | `{}` | `{ "data": [ { "id": "...", "url": "..." } ] }` | `{ "error": { "code": "AUTH_FORBIDDEN", "message": "Access denied." } }` |
| POST | /api/v1/webhooks | Sanctum Token + RBAC | Create webhook | `{ "name": "PublishHook", "url": "https://..." }` | `{ "data": { "id": "..." } }` | `{ "error": { "code": "VALIDATION_FAILED", "message": "URL invalid." } }` |
| POST | /api/v1/cache/invalidate | Sanctum Token + RBAC | Trigger cache busting | `{ "tags": ["content:123"] }` | `{ "status": "queued" }` | `{ "error": { "code": "CACHE_INVALID", "message": "Tag missing." } }` |
| POST | /api/v1/backups | Sanctum Token + RBAC | Trigger backup export | `{}` | `{ "status": "started", "id": "..." }` | `{ "error": { "code": "BACKUP_FAILED", "message": "Unable to start." } }` |
| GET | /api/v1/audit-logs | Sanctum Token + RBAC | Fetch audit logs | `?entity=content` | `{ "data": [ { "id": "...", "action": "update" } ] }` | `{ "error": { "code": "AUTH_FORBIDDEN", "message": "Access denied." } }` |

## 7. Makefile Targets
- **up**: `docker compose up -d`
- **down**: `docker compose down`
- **seed**: `docker compose exec app php artisan db:seed`
- **test**: `docker compose exec app php artisan test`
- **lint**: `docker compose exec app ./vendor/bin/phpcs`
- **pint**: `docker compose exec app ./vendor/bin/pint`
- **stan**: `docker compose exec app ./vendor/bin/phpstan analyse`
- **reindex**: `docker compose exec app php artisan scout:reindex`
- **migrate**: `docker compose exec app php artisan migrate`
- **logs**: `docker compose logs -f`
- **ci-check**: run lint, test, stan sequentially
- **reset**: drop database, run migrate --fresh, seed, reindex

## 8. CI/CD (GitHub Actions)
- Workflow triggers: pull_request to main, push to main, push tags `v*`
- Jobs:
  - **build**: matrix {php: [8.2,8.3], db: [mysql8, mariadb10.6]} builds app, caches composer/npm
  - **lint**: runs Pint, PHPStan, ESLint, formatting checks
  - **test**: executes Pest/PHPUnit, integration with sqlite in-memory, uses services for mysql/redis
  - **deploy-preview**: runs on pull_request, deploys to staging environment, auto-teardown when PR closed/merged
- Each job uses cache for composer (`vendor`), npm (`node_modules`), phpunit coverage artifact upload.
- Secrets: use GitHub environment for staging deployment tokens.

## 9. Observability Strategy
- Configure Monolog to emit JSON with correlation ID header `X-Request-ID`.
- Add middleware to ensure correlation IDs exist per request.
- Placeholder integration for Sentry (error capture) and Elastic APM (metrics/log shipping).
- Expose Prometheus-ready metrics endpoint via `/metrics` guarded by token; StatsD counters for jobs and cache events.
- Prepare OpenTelemetry exporter stubs for tracing across queue workers and HTTP requests.

## 10. Migration & Import Plan
- **Users**: migrate existing users with hashed passwords; invalid emails logged and quarantined.
- **Content**: map posts/pages into `contents` with type field; missing slugs auto-generated; invalid statuses quarantined.
- **Media**: migrate asset metadata and files; missing files logged; duplicates resolved via checksum.
- **Taxonomy**: import categories and tags ensuring parent relationships; invalid references stored in quarantine table.
- **Workflow states**: set default to draft if missing; schedule tasks enqueued.
- **Audit records**: import to `audit_trails`; if data corrupt, log and quarantine.
- **Error reconciliation**: maintain import log table, tag records needing manual review; rerun import after fixes.
- Unsupported legacy fields flagged as TBD for manual assessment.

## 11. Coverage Ledger
| Feature | Models/Endpoints |
| --- | --- |
| Authentication & User Management | Models: User, ApiToken; Endpoints: /api/v1/auth/login, /api/v1/auth/logout, /api/v1/users (GET, POST) |
| Content Management (Pages & Posts) | Models: Content, ContentRevision, MediaAttachment; Endpoints: /api/v1/content (GET, POST, PATCH), /api/v1/content/{id}/publish |
| Media Library | Models: Media, MediaAttachment; Endpoints: /api/v1/media (GET, POST) |
| Taxonomy & Metadata | Models: Category, Tag, ContentTag, Sitemap; Endpoints: /api/v1/taxonomies/categories, /api/v1/taxonomies/tags |
| Publishing Workflow | Models: ContentWorkflow, ActivityLog, WebhookDelivery; Endpoints: /api/v1/content/{id}/publish |
| Public Delivery & Frontend | Models: Content, Menu, MenuItem, Sitemap; Endpoints: /api/v1/content (GET), /api/v1/menus (GET) |
| Site Settings & Configuration | Models: Setting, Announcement, Menu, MenuItem, Backup; Endpoints: /api/v1/settings (GET, PATCH), /api/v1/menus (POST), /api/v1/backups |
| API & Headless Mode | Models: ApiToken, Webhook, WebhookDelivery; Endpoints: /api/v1/webhooks (GET, POST) |
| Security & Compliance | Models: ActivityLog, AuditTrail, ApiToken; Endpoints: /api/v1/audit-logs |
| DevOps, Testing & QA | Models: Backup, QueueJob; Endpoints: /api/v1/backups |
| System Architecture Foundations | Models: CacheInvalidation, Setting; Endpoints: /api/v1/cache/invalidate |
