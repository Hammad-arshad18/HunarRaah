# Verification report — updated 1 October 2026

The application runs locally. It is **not approved for production launch**. Tests are currently paused at the owner's explicit request. Final sign-in/logout, administrator onboarding, enhanced controls, spacing and shared light/dark-theme edits have not been tested. This report distinguishes historical executed checks from fixtures and untested services.

## Filament migration evidence before the testing pause

- Filament 3.3.55 / Livewire 3.8.10 were installed using the system PHP and compatible locked dependencies. The system `intl` extension was enabled.
- An 11-case native Filament administration run passed with 140 assertions. Four additional cases were subsequently added; initial failures in certificate setup and a CSP expectation were corrected and passed in focused reruns. The complete final set has not been rerun together.
- Desktop browser journeys passed for real Fortify sign-in/TOTP, native course/chapter/text-lesson creation and publishing, record navigation, complimentary enrollment and account suspension/restoration.
- The most recent full browser run before the pause had **10 passes and 2 mobile failures**, caused by an open sidebar overlay intercepting controls. A mobile sidebar initialization correction was implemented afterward and remains unverified.
- PHPStan level 7, PHP formatting, TypeScript/lint checks and an asset build passed earlier in the migration. These results predate the final authentication and UI edits and are not validation of those edits.
- The requested local administrator seeder was executed against the owner's configured MySQL database. Its generated credentials reached the authenticator setup screen before the testing pause. The owner's authenticator has not been configured on their behalf.

No test suite, browser journey or browser validation has been executed after the owner's pause. The final asset rebuild is compilation only. Future authorized validation must cover first-submission logout from both applications, no duplicate password prompt after login, initial authenticator setup, enhanced controls, grouped actions, mobile navigation and light/dark field/autofill/button contrast.

The final `pnpm run build` completed successfully on 1 October 2026, compiling 2,467 modules including the new public select and administrator setup page. No PHP tests, frontend checks or browser checks accompanied this build.

## Earlier executed baseline checks

- Owner-requested runtime: system PHP 8.1.25 (`C:\xampp\php\php.exe`), Laravel 10.50.3, Inertia 2 and MySQL 8.4.6. Composer/pnpm lockfiles regenerated; Composer platform requirements pass on the actual runtime.
- SQLite suite: **58 tests passed, 210 assertions**.
- MySQL feature suite: **57 tests passed, 209 assertions** against isolated `studio_test`. SQLite's one unit test is excluded from that configuration.
- Laravel 10 / system PHP MySQL races: eight simultaneous processes each for free enrollment, confirmed payment fulfillment and certificate issuance. Every worker exited successfully; exactly one enrollment/current credential remained. Payment state was a deterministic fixture, not a real Stripe charge.
- PHPStan level 7: no errors. PHP formatting, TypeScript checks, frontend formatting/lint and production asset build executed.
- Playwright Chromium: **six tests passed** across 1280px desktop and 360px mobile. Public navigation/search/filter, visible labels/keyboard skip link, registration, logged verification link, free enrollment, both lessons, certificate queue generation, private/public opt-in and download states were exercised.
- Generated certificate rendered with Poppler and visually inspected: one landscape A4 page with embedded text, QR code, credential fields and generous margins. Business timezone is snapshotted for its issue date.

The regression suite covers privilege injection, required consent, unverified/suspended accounts, cross-course access, protected-content leaks, uniqueness, completion idempotency, optional lessons, archived access, admin role/MFA, owned orders, forged payment returns, unpaid completion, amount mismatches, refunds/disputes/admin restrictions, invalid and signed duplicate webhook delivery, private certificates, unauthorized PDF access, PDF retries, live joining windows/cancellation, curriculum locks and unsafe image rejection/re-encoding.

## Dependency audit after the owner-requested downgrade

`composer audit --format=json` reports **four advisories affecting Laravel 10.50.3**, including two records for the same email-rule issue:

- `PKSA-d5tc-s1qs-h781`: [debug-page information XSS](https://github.com/advisories/GHSA-jh5r-qr3c-85q8).
- `PKSA-m5cs-t1y6-qpcs`: [temporary signed URL path confusion](https://github.com/advisories/GHSA-crmm-hgp2-wgrp).
- `PKSA-3r5d-mb8f-1qw9` and `PKSA-mdq4-51ck-6kdq`: [default email-rule CRLF injection](https://github.com/laravel/framework/security/advisories/GHSA-5vg9-5847-vvmq).

The requested local downgrade used one `--no-security-blocking` Composer update. Global security auditing/blocking and PHP platform checks remain enabled. A browser-input control-character guard and regression tests cover registration/reset input; this is not a claim that the upstream advisory has been patched. Production debug remains disabled by deployment instructions. Laravel 10/PHP 8.1 are beyond upstream security support. Resolve these risks through an approved maintenance/patch strategy before launch.

## Provider evidence and limitations

Stripe reconciliation tests use controlled provider-state fixtures and signature verification uses the real SDK. No actual hosted test Checkout, provider webhook forwarding, refund, dispute, merchant environment or provider timeout smoke test was performed. Missing Stripe configuration fails closed.

Stream API inspection and signed token creation are implemented. Actual owned videos, unsigned-denial/origin checks, captions, player token refresh during long lessons and revocation expiry were not tested without owner credentials. No fake successful video integration is used.

Email was written to local logs only. SMTP delivery, DNS authentication, bounces and operational alert delivery remain unverified. No real student email or live payment was sent.

## Remaining launch work

Supply real organization/course/instructor material, reviewed Markdown policies, merchant/tax decisions and provider credentials. Complete actual provider smoke tests; full admin browser journeys including MFA/media/attendance/reissue; long-name and full required Unicode PDF checks; 768/1440px and 200% zoom testing; manual WCAG 2.2 AA and automated accessibility audit; dependency advisory checks with fresh network data; staging performance benchmark; Linux deployment, encrypted off-server backup restore and rollback drills.

CI configuration is supplied but has not run on a remote CI host. Local MySQL concurrency is evidence of transaction behavior, not a capacity guarantee or real payment-provider race test. The encrypted backup script has not run against a configured remote. A single rendered PDF does not establish support for every Unicode script.

Use `launch-checklist.md` as the owner launch gate. Historical text/XML output remains in ignored `.tools/`; generated browser reports and screenshots were removed during the owner-requested repository cleanup. Repeatable tests live under `tests/`. Cleanup did not rerun tests.
