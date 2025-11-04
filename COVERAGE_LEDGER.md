# Coverage Ledger

| Feature | Models | Endpoints / Resources | Tests |
| --- | --- | --- | --- |
| Authentication & User Management | `User`, `Role`, `Permission`, `AuditLog`, session tables | `/login`, `/register`, `/forgot-password`, `/reset-password`, `/verify-email`, Filament admin auth | `tests/Feature/AuthTest.php`, `tests/Feature/RbacPolicyTest.php`, `tests/Feature/AuthenticationScaffoldingTest.php` |
| Content Management (Pages & Posts) | `Content`, `ContentRevision`, `ContentSlugHistory`, `SeoMeta` | Filament `ContentResource` | `tests/Feature/ContentCrudTest.php`, `tests/Feature/FactoryTest.php` |
| Media Library | `Media`, `MediaUsage` | Filament `MediaResource` | `tests/Feature/MediaCrudTest.php`, `tests/Feature/FactoryTest.php` |
| Taxonomy & Metadata | `Category`, `Tag`, `SitemapEntry`, `SeoMeta` | Filament `CategoryResource`, `TagResource` | `tests/Feature/TaxonomyCrudTest.php`, `tests/Feature/FactoryTest.php` |
| Publishing Workflow | `Workflow`, `WorkflowStep`, `WorkflowInstance`, `WorkflowAction`, `PublishQueue` | Queue worker, Filament Content scheduling fields | `tests/Feature/WorkflowModelTest.php` |
| Public Delivery & Frontend | `Content`, `Category`, `Tag`, `SeoMeta` | `/posts`, `/pages/{slug}`, `/api/v1/posts`, `/api/v1/pages/{slug}` | `tests/Feature/PublicContentDeliveryTest.php`, `tests/Feature/PublicContentApiTest.php`, `tests/Feature/HealthcheckTest.php` |
| Site Settings & Configuration | `Setting`, `Menu`, `MenuItem`, `PageLayout`, `Theme`, `Announcement`, `CacheEntry`, `Backup` | Filament `SettingResource` | `tests/Feature/SettingsCrudTest.php` |
| API & Headless Mode | `ApiToken`, `Webhook`, `ApiLog`, `Content` | `/api/v1/posts`, `/api/v1/posts/{slug}`, `/api/v1/pages/{slug}`, `/api/health` | `tests/Feature/PublicContentApiTest.php`, `tests/Feature/HealthcheckTest.php` |
| Security & Compliance | `AuditLog`, `ApiLog`, `Policies`, `DatabaseSeeder` | Gate `before` super-admin bypass, Docker security defaults | `tests/Feature/AuthTest.php`, `tests/Feature/RbacPolicyTest.php` |
| DevOps, Testing & QA | Dockerfile, `docker-compose.yml`, `.github/workflows/ci.yml`, `pint.json`, `phpstan.neon` | CI pipeline targets | `tests/Feature/FactoryTest.php`, GitHub Actions CI (lint/phpstan/pest) |
| System Architecture Foundations | `AppServiceProvider`, `config/scout.php`, modular models | Scout/Meilisearch integration, Filament panel provider | `tests/Feature/FactoryTest.php`, `tests/Feature/WorkflowModelTest.php` |
