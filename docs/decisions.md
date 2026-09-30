# Implementation decisions

## 2026-09-30 — owner version instruction

- The owner explicitly requested Laravel 10 and the existing system PHP, superseding the original Laravel 13/portable PHP decision. Preferences are saved in `MEMORY.MD`.
- Use PHP 8.1.25 from `C:\xampp\php\php.exe`, Laravel 10.50.3, compatible Fortify/Inertia 2 and PHPUnit 10. Composer resolves against the actual runtime without ignoring platform requirements.
- Framework bootstrapping uses HTTP/console kernels, explicit providers, Blade Inertia directives and property-based model casts. Job traits use the Laravel 10 equivalents. Course/payment/progress/credential data is preserved.
- Wayfinder and the newer starter-kit installer/passkey scaffolding require newer framework/PHP APIs and are removed. Explicit auth/settings route helpers and standard Inertia 2 initialization replace them; admin TOTP remains required.
- Lockfiles are regenerated and CI/deployment documentation targets PHP 8.1. No global PHP configuration or PATH is changed.
- Laravel 10 and PHP 8.1 are past upstream security support. Composer reported four advisories affecting the framework; the local version change used a one-time `--no-security-blocking` resolution flag. Auditing remains enabled; no global ignore policy or platform bypass is configured. See `docs/test-report.md` for details.

## Unchanged product defaults

- The workspace initially contained only AGENTS.md and GEMINI.md. Preserve both.
- One organization, AED, Asia/Dubai; merchant country remains unset. No live payments until explicitly configured and approved.
- Database-backed queues, cache and sessions. MySQL is the deployment/integration target; SQLite is permitted only for fast local tests, never evidence of MySQL concurrency correctness.
- Local mail logs only. No production demo users, legal policies, biographies or courses. Development content is explicitly labelled.
- Private certificate verification defaults off. Completion is self-attested, never a claim of assessed mastery.
- Text is rendered through escaped Markdown with raw HTML and unsafe links disabled. No arbitrary embeds.
- Payment and video services fail closed if credentials are missing. No production fake adapters.

## Work sequence

1. Foundation/authentication, schema, policies and a complete free-course learning flow.
2. Course administration, publishing checks and curriculum locking.
3. Durable payment ingestion/reconciliation and financial restrictions.
4. Private video, live join/attendance and reminders.
5. Certificate jobs, sharing and revocation.
6. Deployment/recovery documentation and acceptance evidence.
