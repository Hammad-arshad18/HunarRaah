# Implementation decisions

## 2026-09-30

- The workspace initially contained only AGENTS.md and GEMINI.md. Preserve both.
- Use Laravel 13 with PHP 8.4, React 19, TypeScript and Inertia 3 from the official React starter kit, source revision `717b8f55aefd82d25d4119eaebdc8e3a72b8d7e5`. The specification's Laravel 10 recommendation conflicts with its maintained-release requirement. Laravel 10 is unsupported; Laravel 13 requires PHP 8.3 or newer. References: https://laravel.com/docs/13.x/releases and https://laravel.com/starter-kits.
- Resolve dependencies once and retain lockfiles. Workspace-local PHP 8.4.26 was downloaded from windows.php.net and verified against its published SHA-256. Existing system PHP is 8.1.25 and must not run this app.
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
