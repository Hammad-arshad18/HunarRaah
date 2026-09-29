# Teaching Studio

A single-organization Laravel 13 / React 19 / Inertia 3 course application. Public discovery uses Blade. Course access, payment reconciliation, progress and certificates are server-controlled. Local implementation and automated verification are available; this is not approved for live launch. See `docs/test-report.md` and `docs/launch-checklist.md`.

## Requirements

- PHP 8.4 with curl, fileinfo, mbstring, openssl, PDO MySQL, GD, zip, DOM/XML.
- Composer 2; Node 22.12+; pnpm 11.25.0.
- MySQL 8.4 for deployment and integration verification.

The Windows system PHP 8.1 is incompatible. This workspace has a checksum-verified portable PHP under `.tools/php`, ignored by Git. On this machine replace `php` with `.tools/php/php.exe` and run Composer through `.tools/php/php.exe C:/composer/composer.phar`.

## Local setup

1. `composer install`
2. Copy `.env.example` to `.env`, configure MySQL and set local mail to `log`.
3. `php artisan key:generate` (first installation only).
4. `php artisan migrate` and `php artisan storage:link` (public course images only; certificates stay private).
5. `pnpm install --frozen-lockfile` then `pnpm run build`.
6. `php artisan db:seed --class=DevelopmentSeeder` for explicitly labelled example courses. No demo users are seeded.
7. `php artisan serve --host=127.0.0.1 --port=8000`.
8. In separate terminals: `php artisan queue:work --tries=5 --timeout=120` and `php artisan schedule:work`.

For quick local work only, use `DB_CONNECTION=sqlite` with `DB_DATABASE=database/database.sqlite`. The current `.env` uses SQLite and log mail. It is private and not committed. Register through the UI; find verification links in `storage/logs/laravel.log`. Mail logs contain private links: restrict them and never use log mail in production.

Create an administrator interactively using `php artisan platform:admin`. Sign in, confirm the password and enable/confirm TOTP in Settings / Security. Then open `/admin/courses`. There is no public administrator signup. A CLI bootstrap is audited.

## Checks

`php vendor/phpunit/phpunit/phpunit` runs deterministic feature tests. `pnpm run types:check` checks TypeScript. `pnpm run build` builds assets offline using the checked-in licensed variable font. `php vendor/bin/pint` formats PHP. `pnpm exec playwright test` runs browser checks with the local app running (see test config). MySQL tests use `phpunit.mysql.xml`; never run integration tests against a production database.

## Providers

Payments require a Stripe **test** key and webhook signing secret. Hosted Checkout creates one server-priced session per order. Returning does not grant access. Raw-body signatures are verified and minimal encrypted events are stored before acknowledgement. A database queue worker retrieves current Stripe state before fulfilling. Live payments are disabled unless merchant, policy and tax gates are configured. API version is `2025-09-30.clover`, SDK pinned in Composer lockfile. Test the pinned version against the owner account before launch.

Cloudflare Stream requires an account ID and server API token. Use owned recordings configured with signed playback and allowed origins. Token responses are private/no-store and expire in ten minutes. No service credentials means a truthful unavailable state. The scheduler polls readiness. No unsigned provider webhook is exposed.

Certificates are generated privately by a retried job. Public verification is off until the learner explicitly consents. A normal profile edit does not change existing credential snapshots. Refund/dispute state controls entitlement and credential validity separately.

Deployment, operational recovery, assumptions and actual verification evidence are under `docs/`.
