# Rules for every coding agent

Read this file, `MEMORY.MD`, `AGENTS.md`, `README.md` and relevant `docs/` before editing.
The owner's latest explicit instructions take precedence. `MEMORY.MD` records current preferences; the original specification in `AGENTS.md` must be read with these updates.

## Current working constraints
- **Tests are paused. Do not run test suites, browser validation, lint or type-check commands until the owner explicitly authorizes resuming checks.** Asset builds are allowed when needed to deliver frontend changes. Report builds separately from tests.
- Use **Laravel 10**, **Filament 3** for administration, and the **system PHP** on PATH. On the owner's Windows machine PHP is `C:\xampp\php\php.exe`; never download a separate runtime or bypass Composer platform checks.
- Keep Composer and pnpm lockfiles. Install from them; do not upgrade major versions, regenerate locks or disable security auditing as routine cleanup.
- Preserve existing work. Inspect the working tree and references before deleting or replacing files. Do not reset, clean, force-push, commit or publish without the relevant user authorization.
- Do not create extra agents or delegate work unless the owner explicitly requests it.

## Architecture and data
- Public pages are Blade; learner/account pages are React + TypeScript + Inertia; admin pages are native Filament resources. Keep them in this Laravel monolith.
- Use existing actions, controllers, policies and `app/Filament/Support/AdminWorkflows.php`. All mutations require server validation and authorization; hiding a button is insufficient.
- Preserve the owner's `.env`, MySQL data, SMTP settings, uploads, private certificates and credentials. Never print secrets or commit local databases, logs, credentials, dependency folders or generated assets.
- Do not run destructive migrations, `migrate:fresh`, resets, truncate operations or broad seeders against the owner's database. Schema changes must preserve existing data.
- Do not regenerate an existing `APP_KEY`; it protects encrypted meeting links and authenticator secrets.
- Local `AdministratorSeeder` is an explicit development bootstrap with a random private password. Never hardcode a password, silently reset existing credentials or use this seeder in production.

## Boundaries that must survive changes
- Keep confirmed administrator TOTP, active-account checks, verified email, CSRF, session invalidation and recent authentication for sensitive operations.
- A successful sign-in counts as password confirmation. Do not prompt again immediately. Keep `/admin/setup` usable and logout as a CSRF-protected full-page POST to `/logout`.
- Use full-page navigation between Inertia and Filament/Blade responses; do not return plain HTML to an Inertia visit and cause a popup.
- Enforce ownership and matching course/lesson/enrollment relationships. Never expose private lesson content, meeting URLs, playback tokens or student details publicly.
- Payment returns never grant access. Keep verified, durable, idempotent provider reconciliation, integer money, unique enrollment constraints and separate refund/dispute/admin restrictions.
- Lock required curriculum after enrollment. Preserve historical completion and certificate snapshots. Credentials remain private until explicit opt-in; issuance and revisions stay authorized and idempotent.
- Preserve signed video, protected join windows, private PDF storage, audited admin actions and retryable jobs. Never simulate successful providers in production or send real mail/payments without owner configuration and authorization.

## UI and maintenance
- Use the custom studio identity, shared `public/theme.css` palette and synchronized `public/theme.js` preference. Preserve the compact public theme menu, keyboard operation and visible field text/caret/placeholders/autofill in both themes.
- Keep Filament's assets separate from the React Tailwind build. Preserve CSP nonces in trusted vendor-view overrides when updating Filament.
- Keep useful loading, empty, validation, forbidden and provider-unavailable states. Do not replace working controls with placeholders or restore starter-kit branding.
- Keep the MVP scope in `AGENTS.md`; do not add marketplace, chat, quizzes, subscriptions or other excluded features.
- When checks are authorized, use isolated test databases and log/array mail. Never target the owner's MySQL database with tests or test fixtures.
- Update relevant documentation and `MEMORY.MD` when the owner changes a lasting preference. Clearly report modified files, executed commands, unverified behavior and launch blockers; a build is not a passing test suite.
- Known Laravel/PHP support limitations and recorded framework advisories remain documented. Do not claim production readiness or remove launch gates without evidence.
