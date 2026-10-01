# Using the teaching studio

The platform uses one custom visual identity across the catalog, authentication, classroom, account and teaching desk. Student and admin links are separated by role. The mobile menu supports keyboard focus, Escape and focus restoration.

Choose Light, Dark or System in the homepage/public-page header, under Account / Appearance, or in the administration user menu. The preference is shared across public pages, account screens and administration. System follows the device's appearance automatically.

## Learner journey

Create an account, accept the terms and verify the email address. Choose a course from the public catalog. Free enrollment opens the classroom immediately; paid checkout opens Stripe and awaits verified payment reconciliation before granting access.

My learning shows actual required-lesson progress and resumes the most recently updated unfinished lesson. The classroom exposes only published lessons belonging to the enrolled course. Text completion is self-attested. Recording progress requires private playback. Required live completion uses recorded attendance unless the course permits a recording alternative. Join controls respect the configured window, cancellation and ending states.

Live schedule lists enrolled sessions in course and local time. Purchases lists owned orders, refunds and disputes. Certificates lists issued credentials and completed eligible courses. Request a certificate, download its generated PDF and explicitly opt in before sharing public verification. Failed PDF generation has a retry action. Issued names remain snapshots.

## Administrator journey

Open `/admin` and sign in. Successful sign-in counts as password confirmation; it does not ask for the same password again immediately. A new administrator is guided through `/admin/setup` to connect an authenticator, confirm its code and save recovery codes before opening administration. Later sign-ins use the authenticator challenge. Sensitive operations retain recent-authentication protection.

The owner-requested local `AdministratorSeeder` has created `admin@mirzalearning.com`; its random initial password is in private `storage/app/private/admin-bootstrap.txt`. Rerunning the seeder preserves the existing password and refuses production. Production bootstrap remains `php artisan platform:admin` with the system PHP. There is no public admin signup or production demo password.

The teaching desk provides course search/status filters and a dedicated Create a course page. Complete the outcome, instructor, pricing and access/completion sections. Upload a real course cover or instructor image. Add chapters and text/video/live lessons. Chapter and lesson positions are explicit and editable before enrollment. Empty chapters and unenrolled lessons can be removed with a reason. After enrollment, structural changes are locked; titles and copy may be corrected. Duplicate the course for a new curriculum.

For a recording, supply an owned Cloudflare Stream UID and an attachment reason. The server checks privacy and provider readiness. Ready recordings have an audited admin preview. For a live lesson, save the shared UTC schedule, display timezone, approved HTTPS join URL and change reason. Existing schedule values are prefilled. Publishing validates content and required lessons.

Archive a course to close sales while retaining enrolled access. The availability panel can hide sales independently or set an emergency takedown message that blocks enrolled access. Every access decision must be intentional and audited.

Filament navigation groups Courses, Chapters and Lessons under Curriculum; Students, Enrollments, Orders and Certificates under Student records; and Activity log and Failed jobs under Operations. Tables offer search and enhanced status filters. A row's **Manage** menu groups its available actions.

Administrators can suspend/restore accounts, correct an email with fresh verification, grant complimentary access, adjust enrollment restrictions with reasons, review order totals/refunds/disputes and request reconciliation. Refunds take place in Stripe Dashboard. Certificates support audited PDF download, explicit revocation, failed-generation retry and name-correction reissue preserving history. Enrollment progress lists per-lesson completion; attendance and corrections require reasons. They do not silently revoke historical credentials. Activity log displays the actor, action, record and recorded reason. Failed jobs can be retried with an audit reason; private payloads are hidden.

## Local browser checks

Browser checks and all tests are paused until the owner authorizes them again. The instructions below are for a future authorized run.

Use a separate SQLite file named `browser.sqlite`, `MAIL_MAILER=log`, local/testing environment, database sessions/cache/queues and a localhost URL. Set `BROWSER_BASE_URL` to that server. Migrate it and explicitly seed `DevelopmentSeeder`. The Playwright fixture refuses to create an admin unless the isolated SQLite file and log mailer are configured. It generates random credentials and exercises real TOTP. Do not run browser fixture tests against production or the owner's application database.

The current review preview is at port 8001 with isolated development content. The owner's `.env`, database and SMTP configuration are preserved. Stripe, Stream and SMTP production configuration and actual provider smoke tests remain launch requirements; fixtures are not evidence of live success.
