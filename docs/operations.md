# Operations and recovery

## Daily checks

Check uptime /up, 5xx logs, disk/CPU/RAM, database connections, oldest queue job, failed_jobs, webhook age/errors, financial_exception orders, notification_deliveries failures and backup age. Set operational alerts outside the app; no alert delivery is claimed until configured. Run `php artisan platform:maintain` to recover pending/failed webhook processing, refresh recent payment state, poll video readiness and schedule reminders. Its heartbeat is in database cache. Run `php artisan queue:failed`; retry reviewed transient failures with `queue:retry`.

Keep queue retry_after longer than worker timeout. A provider outage must leave orders pending, not grant access. A checkout creation unresolved beyond 23 hours fails closed for operator review to avoid retrying an expired Stripe idempotency key. Check provider metadata/session history before resolving. Financial exceptions require Dashboard review/refund of an actual duplicate charge. Refunds are done in Stripe Dashboard; use admin reconcile after changes. Do not mark an order paid manually.

Reconciliation jobs share a database cache lock so provider retrieval is serialized. Database transactions lock course/order/enrollment and enforce unique enrollments. This must be verified under MySQL concurrent workers before launch. Keep webhook payloads encrypted and minimal (IDs/references only), never full provider PII. Retention periods need business approval.

Transactional mail has an outbox/dedupe key and retried jobs. SMTP delivery is at-least-once: a crash after SMTP acceptance before database acknowledgement can resend. A provider with idempotent send keys is needed for strict exactly-once email delivery. Failed email does not undo enrollment. Monitor permanent bounces externally.

## Backups

Use `deploy/backup.sh` with a restricted MySQL option file, age recipient and configured off-server target. Archive the SQL dump, public course images, private storage and protected APP_KEY recovery material. Never upload plaintext backups. Proposed retention: seven daily/four weekly copies, subject to business approval. Keep original teaching recordings separately under owner control.

Restore into an isolated staging environment: decrypt backup, create empty MySQL database, import SQL, restore private files and original APP_KEY/environment, install the matching release, migrate forward only if required, verify encrypted meeting URLs, owned lesson access, private PDF download and queue execution. Compare record/file counts and sample checksums. Measure recovery duration; initial targets are RPO 24 hours/RTO 4 hours until a drill demonstrates them. Restore drill is not yet executed. Repeat monthly and after schema/key/storage changes.

## Account requests

The initial local administrator was created with the owner-requested `AdministratorSeeder`. Credentials are in private `storage/app/private/admin-bootstrap.txt`; reruns preserve the password. This is a local-only exception. Production administrators are created/promoted through the controlled CLI. Connect and confirm an authenticator at `/admin/setup` and keep recovery codes securely. A fresh successful sign-in counts as password confirmation. Recover lost authenticators through recovery codes and owner identity review; do not bypass MFA through a public endpoint.

Verify identity via established support channels before email changes, exports or deletion. Paid/learned accounts use support review because financial/credential retention needs a legal/business decision. Preserve required financial facts and audit history; anonymize only approved data, remove sessions, disable public certificate sharing and handle retained credential identity under owner policy. Do not silently change certificate snapshots on profile updates. Email changes must clear verification, invalidate sessions and send fresh verification. Admin role creation stays CLI-controlled.

## Access incident

Suspend account or enrollment with an audited reason; payment recovery must not erase the admin restriction. Archival hides sales but preserves existing study access. An emergency content takedown is separate from archival and requires explanation. Revocation stops new Stream token issuance immediately; issued bearer tokens can remain usable for up to ten minutes. Downloaded PDFs cannot be recalled; current verification is authoritative.

## Logs/secrets

Never log credentials, raw webhook bodies, reset links in production, Stream tokens or meeting passcodes. Configure log rotation/disk alerts. APP_KEY loss destroys decryption of protected fields. Rotate provider secrets deliberately, validate callback signatures and update encrypted key recovery material. Dependency audits and supported runtime updates are routine operational work.
