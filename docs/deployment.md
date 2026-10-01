# Deployment

## Owner gate

Do not launch until `launch-checklist.md` is complete. Local checks are not proof of payment, video, mail delivery, accessibility, capacity or recoverability in production. Choose a supported Ubuntu LTS, owner-required PHP 8.1, MySQL 8.4 and Nginx. The app has one server failure domain; Stream and SMTP are external. No persistent Node server is required.

## First installation

Provision a non-root deploy user and least-privilege MySQL account; bind MySQL to localhost. Permit HTTP/HTTPS and restrict SSH. Configure TLS and owner domain. Install PHP extensions from README. Set Nginx root to the release's `public` directory using `deploy/nginx.conf`. Never serve the repository root.

Filament requires PHP `intl`. The locked Composer post-install/autoload scripts run `filament:upgrade`; ship the resulting `public/css/filament` and `public/js/filament` assets with each release. Also ship `admin-studio.css`, `admin-ui.js`, the local font and Vite build artifacts. Review the trusted Filament Blade overrides under `resources/views/vendor` when updating the package: their script nonces support the production CSP. Alpine's required evaluation permission is restricted to admin/Livewire responses; public/student responses retain the stricter policy.

Never run `AdministratorSeeder` in production or copy `storage/app/private/admin-bootstrap.txt` to deployment. Bootstrap with `php artisan platform:admin`, complete `/admin/setup` and save recovery codes securely.

Ship `public/theme.css` and `public/theme.js` with every release. All three application surfaces include them through `resources/views/shared/theme.blade.php`; semantic Tailwind and Filament colours depend on this shared palette. Public stylesheet URLs include their file modification version to refresh browser caches after a release.

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

The owner-requested Laravel 10/PHP 8.1 baseline is beyond upstream security support. Do not interpret a successful deployment or green tests as resolving the framework advisories. A maintained security patch strategy is a separate launch prerequisite.
