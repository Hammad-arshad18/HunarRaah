# Deployment

## Owner gate

Do not launch until `launch-checklist.md` is complete. Local checks are not proof of payment, video, mail delivery, accessibility, capacity or recoverability in production. Choose a supported Ubuntu LTS, PHP 8.4, MySQL 8.4 and Nginx. The app has one server failure domain; Stream and SMTP are external. No persistent Node server is required.

## First installation

Provision a non-root deploy user and least-privilege MySQL account; bind MySQL to localhost. Permit HTTP/HTTPS and restrict SSH. Configure TLS and owner domain. Install PHP extensions from README. Set Nginx root to the release's `public` directory using `deploy/nginx.conf`. Never serve the repository root.

Store `.env` and `storage/` outside immutable releases and symlink them. Generate APP_KEY once and preserve it in encrypted recovery storage. Set APP_ENV=production, APP_DEBUG=false, APP_URL=https://owner-domain, SESSION_SECURE_COOKIE=true, SESSION_ENCRYPT=true, database cache/session/queue drivers and transactional SMTP. Configure owner organization/issuer/signatory/support/currency/timezone. Never expose secrets as VITE variables.

Run `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader` from lockfile. Build `pnpm install --frozen-lockfile && pnpm run build` in CI and ship public/build plus public/fonts and license. Configure writable storage/bootstrap cache permissions for deploy and FPM users, never world-writable.

Run `php artisan migrate --force`, `php artisan storage:link`, `php artisan platform:admin` interactively, then `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`. Start worker service from `deploy/queue.service` and cron scheduler. Do not run development seeders or copy local SQLite/users into production.

## Releases and rollback

Back up database before migrations. Upload an immutable versioned release, symlink shared environment/storage, install locked dependencies and built assets, run only backward-compatible migrations, warm caches, then atomically switch the `current` symlink. Retain prior releases/assets. Restart workers gracefully with `php artisan queue:restart`. Check /up, sign-in, catalog, authorized classroom, queue processing and provider callbacks.

Rollback by switching `current` to the previous compatible release and restarting workers/FPM. Do not automatically run down-migrations. If schema is incompatible, select a forward fix or restore using an incident plan and an explicit accepted data-loss window. Do not regenerate APP_KEY. Test this on staging before live launch.

## Provider configuration

Stripe: configure test-mode endpoint `/webhooks/stripe` with only supported snapshot event types from PaymentController, pinned API version and endpoint secret. Test Checkout, retries, signature rejection, refunds and disputes with actual test credentials. Configure card/wallet behavior; no delayed methods are enabled by default. No VAT or compliant tax invoice implementation is claimed. Live mode additionally requires approved owner tax/access/refund/certificate/legal policies and merchant eligibility.

Stream: owned private videos, required signed URLs, allowed origins matching production/staging. Upload/caption in provider dashboard and attach UID through admin. Validate unsigned playback denial, long-session token refresh and actual CSP/player behavior.

SMTP: authenticated transactional sender, SPF/DKIM/DMARC and delivery/bounce monitoring. Configure provider budgets externally. No real email or live payment was enabled during implementation.

Supply reviewed UTF-8 Markdown in `resources/content/terms.md`, `privacy.md` and `refund-policy.md`. Set a real `TERMS_VERSION` and `POLICIES_APPROVED=true` only after review. Production registration and live payments fail closed while these prerequisites are missing.
