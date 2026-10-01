# HunarRaah

A single-organization Laravel 10 / React 19 / Inertia 2 course application, with Filament 3.3.55 administration. Public discovery uses Blade. Course access, payment reconciliation, progress and certificates are server-controlled. Tests are currently paused at the owner's request; the latest authentication and UI edits await verification. See `docs/test-report.md` and `docs/launch-checklist.md`.

## Working with coding agents

All agents must start with [RULES.md](RULES.md), [MEMORY.MD](MEMORY.MD) and [AGENTS.md](AGENTS.md). `CLAUDE.md` and `GEMINI.md` point to these shared instructions. The owner's testing pause is still active.

## GitHub repository contents

Commit application source, migrations, tests, deployment configuration, documentation, agent instructions, `.env.example`, `composer.lock` and `pnpm-lock.yaml`. Local `.env` variants, credentials/private storage, databases/SQL exports, `.tools`, test reports, `vendor`, `node_modules`, generated Filament assets and Vite builds are excluded. Do not force-add ignored local files.

The installed dependencies and built assets remain on the owner's machine so the application can keep running. A fresh clone recreates them using the setup instructions below. The existing GitHub workflow runs checks on pull requests and pushes to `main`; no GitHub push or workflow run was performed during cleanup.

## Requirements

- PHP 8.1+ with curl, fileinfo, intl, mbstring, openssl, PDO MySQL, GD, zip, DOM/XML.
- Composer 2; Node 22.12+; pnpm 11.25.0.
- MySQL 8.4 for deployment and integration verification.

Use the system `php` on PATH. On this machine it is `C:\xampp\php\php.exe` (8.1.25); run Composer with `php C:/composer/composer.phar`. The owner requires Laravel 10 and system PHP; see `MEMORY.MD`. Do not use the previous workspace-local runtime. Laravel 10/PHP 8.1 are past upstream security support; dependency audit findings remain launch blockers.

## Local setup

1. `composer install`
2. Copy `.env.example` to `.env`, configure MySQL and set local mail to `log`.
3. `php artisan key:generate` (first installation only).
4. `php artisan migrate` and `php artisan storage:link` (public course images only; certificates stay private).
5. `pnpm install --frozen-lockfile` then `pnpm run build`.
6. `php artisan db:seed --class=DevelopmentSeeder` for explicitly labelled example courses. No demo users are seeded.
7. `php artisan serve --host=127.0.0.1 --port=8000`.
8. In separate terminals: `php artisan queue:work --tries=5 --timeout=120` and `php artisan schedule:work`.

For a separate local sandbox, use `DB_CONNECTION=sqlite` with `DB_DATABASE=database/database.sqlite` and `MAIL_MAILER=log`. The owner's existing `.env` contains their MySQL and SMTP configuration; preserve it. In a log-mail sandbox, verification links are in `storage/logs/laravel.log`. Mail logs contain private links: restrict them and never use log mail in production.

For the owner's local installation, run `php artisan db:seed --class=AdministratorSeeder`. It creates `admin@mirzalearning.com` with a random password, writes credentials to private `storage/app/private/admin-bootstrap.txt` and preserves an existing account/password on reruns. This seeder has already been run against the owner's configured database. It refuses production execution.

Open `/admin` and sign in. First-time access opens `/admin/setup` to connect and confirm an authenticator. Save the recovery codes, then choose **Open administration**. Successful sign-in also confirms the password, so there is no immediate second password prompt. Change the bootstrap password and remove the private credential file after saving it securely.

In production, create an administrator interactively using `php artisan platform:admin`. There is no public administrator signup or production demo password. Bootstrap and promotion operations are audited.

## Checks

**Do not execute these checks until the owner lifts the current testing pause.** An asset build is separate from tests.

`php vendor/phpunit/phpunit/phpunit` runs deterministic feature tests. `pnpm run types:check` checks TypeScript. `pnpm run build` builds assets offline using the checked-in licensed variable font. `php vendor/bin/pint` formats PHP. `pnpm exec playwright test` runs browser checks with the local app running (see test config). MySQL tests use `phpunit.mysql.xml`; never run integration tests against a production database.

## Providers

Payments require a Stripe **test** key and webhook signing secret. Hosted Checkout creates one server-priced session per order. Returning does not grant access. Raw-body signatures are verified and minimal encrypted events are stored before acknowledgement. A database queue worker retrieves current Stripe state before fulfilling. Live payments are disabled unless merchant, policy and tax gates are configured. API version is `2025-09-30.clover`, SDK pinned in Composer lockfile. Test the pinned version against the owner account before launch.

Cloudflare Stream requires an account ID and server API token. Use owned recordings configured with signed playback and allowed origins. Token responses are private/no-store and expire in ten minutes. No service credentials means a truthful unavailable state. The scheduler polls readiness. No unsigned provider webhook is exposed.

Certificates are generated privately by a retried job. Public verification is off until the learner explicitly consents. A normal profile edit does not change existing credential snapshots. Refund/dispute state controls entitlement and credential validity separately.

Deployment, operational recovery, assumptions and actual verification evidence are under `docs/`.

See [the user guide](docs/user-guide.md) for the learner journey, course authoring, live schedules, access decisions, credentials and isolated browser-test setup.
