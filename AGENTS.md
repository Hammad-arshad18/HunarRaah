# Agent entry point

Read [RULES.md](RULES.md) and [MEMORY.MD](MEMORY.MD) before implementing the specification below. They record subsequent owner decisions: Laravel 10 with system PHP, Filament 3 administration, the shared custom theme, and the current pause on tests and browser validation. These current instructions supersede conflicting implementation defaults and check requirements in the original specification. Preserve existing data and working features.

# Custom Course Platform — Codex Implementation Specification

Prepared: 30 September 2026 (Asia/Dubai). Research checked: 29 September 2026 UTC.

## 1. Instructions to Codex

Build a production-ready, single-organization course platform from this specification. Students discover administrator-created courses, register, buy access, study lessons and recordings, join external live meetings, and receive verifiable completion certificates. Deliver the working application, not only UI mockups.

Use the defaults below to start without repeatedly asking product questions. Record assumptions in `docs/decisions.md`. Missing production credentials must not block local implementation: provide test adapters, fixtures and setup instructions, clearly separated from production. Never simulate a successful payment or video integration in production. Do not purchase services, send real student emails, or enable live payments without the owner's production configuration.

Keep the scope narrow. Security, payment integrity, accessibility, backups and operational recovery are essential infrastructure, not optional product features. Do not add features merely because Udemy has them. Inspect any existing repository and its instructions before changing it; preserve compatible existing work.

### Deliverables

- One Laravel repository containing public pages, student application and administrator application.
- MySQL migrations, factories, development seeds and documented production bootstrap.
- Custom responsive UI, reusable components, loading/empty/error states and real backend integration.
- Authentication, policy-based authorization, paid/free enrollments, external meeting links, recordings, completion and certificates.
- Stripe test-mode integration, signed video integration, transactional email and asynchronous jobs.
- Automated tests for the business and security boundaries listed below.
- `README.md`, `.env.example`, `docs/decisions.md`, `docs/deployment.md`, `docs/operations.md`, `docs/test-report.md` and `docs/launch-checklist.md`.
- Deployment configuration for one Linux VPS, queue supervision, scheduler, backup/restore and rollback instructions.
- Clear reporting of completed work, checks actually executed, missing credentials and remaining launch blockers. Do not claim live integrations were tested when only mocked tests ran.

## 2. Product defaults and scope boundaries

| Decision | MVP default |
|---|---|
| Business model | One training company selling its own courses |
| Roles | Student and administrator only |
| Language | English; UTF-8 names supported |
| Currency | AED as a configurable single site currency; confirm merchant country before live payments |
| Purchase | One course per checkout, one-time payment |
| Access | No scheduled expiry by default; active until explicitly revoked or the published access policy applies |
| Course formats | Recorded, live, hybrid |
| Live delivery | One shared schedule per course; external meeting provider |
| Completion | Every required lesson completed; live attendance recorded by admin where required |
| Certificate | Certificate of completion, not an accredited qualification |
| Video | Private managed streaming, not application-server video hosting |
| Payments | Stripe hosted Checkout session URL generated per order |
| Refund operations | Admin performs refund in Stripe Dashboard; application synchronizes status |
| Content editing | Lock required curriculum after first enrollment; duplicate course for structural revisions |
| Scale assumption | Small launch; benchmark before promising a concurrent-user capacity |

The currency and timezone defaults are planning assumptions, not evidence that the merchant is eligible for a particular payment service. Make organization name, domain, support email, issuer name, brand assets, merchant country and business timezone configuration values.

### Included

Public course catalog and details; registration and email verification; login and reset; account details; course purchase and enrollment; student dashboard; ordered text/video/live lessons; simple progress; meeting schedule and protected join links; admin content and student management; order status; certificate PDF and verification; transactional notifications; necessary audit records.

### Explicitly excluded

Marketplace sellers, instructor accounts, commissions, subscriptions, installments, carts, bundles, coupons, affiliate systems, reviews, ratings, chat, forums, assignments, quizzes, exams, proctoring, badges, leaderboards, AI tutors, recommendations, mobile apps, multi-tenancy, multiple currencies, localization UI, SCORM/xAPI, CRM, marketing automation, full CMS, advanced analytics, built-in video conferencing and automatic meeting recording.

Do not build cohorts, seat reservations or capacity limits in MVP. A live course has one schedule shared by everyone enrolled. Close enrollment at a configured deadline. If the business needs separate batches or strict seats, flag that as a scope change before selling that format; do not silently place different batches in one classroom.

## 3. Technology and architecture

### Recommended stack

| Layer | Choice | Purpose |
|---|---|---|
| Backend | Laravel 10, compatible maintained PHP 8.3+ release | Authentication, policies, ORM, validation, jobs and email |
| Student/admin frontend | React + TypeScript + Inertia using official compatible starter kit | Rich UI within the same application and session model |
| Public marketing/catalog | Blade templates with shared design tokens | Server-rendered discoverable pages without a Node SSR server |
| Styling | Tailwind CSS and accessible headless primitives; customize any shadcn/ui components | Bespoke visual system with reliable interaction primitives |
| Database | MySQL 8.4 LTS, InnoDB, utf8mb4 | Relational integrity and familiar low-cost operations |
| Assets | Vite; build in CI | Static JS/CSS in production |
| HTTP | Nginx + PHP-FPM, HTTPS | One application deployment |
| Queue/cache/sessions | Laravel database drivers initially | Avoid a separate Redis service at launch |
| Payments | Official Stripe PHP SDK, hosted Checkout | Provider handles card entry |
| Recorded video | Cloudflare Stream private playback | Encoding and delivery outside VPS |
| Email | Laravel mail through configured transactional SMTP service | Verification, resets and essential notifications |
| Certificate PDF | Maintained PHP PDF library, such as compatible Dompdf wrapper | Generate a simple fixed certificate without headless Chrome |
| Tests | Laravel-supported PHPUnit/Pest, Playwright for critical browser flows | Verify payments, permissions and full journeys |

At implementation time, verify exact compatible versions against official documentation and commit Composer/npm lockfiles. Do not blindly use `latest` or copy old starter-kit instructions. Laravel's official React kit uses Inertia [R1]; its deployment guidance and release requirements are in [R2–R3]. The choice of monolith and public Blade pages is this specification's architecture recommendation.

Node is needed to build assets, not as a persistent production server. No separate REST API, JWT scheme, Next.js server, GraphQL, Kubernetes, Redis, WebSocket service or managed database is required for MVP. React does not make server hosting intrinsically expensive when shipped as static assets.

### Request boundaries

Public requests go to Laravel-rendered pages. Authenticated student/admin requests go to Laravel controllers and policies, then Inertia pages. All data writes pass through validated requests and dedicated business actions. MySQL is authoritative for orders, entitlements and certificates. Stripe is authoritative for payment events. Stream is authoritative for video readiness. External provider callbacks must be verified.

Use focused actions/services: `CreateCourseCheckout`, `ReconcileOrderPayment`, `GrantEnrollment`, `RevokeEnrollment`, `CompleteLesson`, `RecordAttendance`, `IssueCertificate`, `RevokeCertificate`, `CreatePlaybackToken`. Keep controllers small without creating a generic enterprise framework.

## 4. Design direction: a modern teaching studio

The interface should communicate that this company can teach through a clear learning journey, tangible outcomes, instructor expertise and well-organized content. It must feel custom and credible, not like a marketplace clone or a generic software dashboard.

### Visual concept

Use an editorial studio aesthetic: warm paper surfaces, deep ink typography, vivid cobalt actions, thin structural lines, generous space, numbered curriculum chapters and authentic course artifacts. A distinctive vertical numbered learning path connects course outcomes, syllabus and student progress. This is a navigation pattern, not a gamification feature.

Suggested starting tokens, subject to contrast checks:

```css
:root {
  --canvas: #F6F5F1;
  --surface: #FFFFFF;
  --ink: #172128;
  --muted: #52616B;
  --primary: #2448D8;
  --primary-hover: #1934A4;
  --accent-soft: #E9F2B7;
  --border: #DCE1DD;
  --success: #166534;
  --danger: #B42318;
  --radius-card: 18px;
  --radius-control: 10px;
  --content-max: 1280px;
}
```

Use one self-hosted, properly licensed variable sans-serif font with a system fallback. Typography: clear 16px body text; roughly 48–72px desktop hero, 34–42px mobile hero; comfortable 1.5–1.7 body line height. Use a restrained spacing scale: 4, 8, 12, 16, 24, 32, 48, 64, 96px. Keep reading columns around 65–75 characters.

Primary buttons are solid cobalt with clear verbs. Secondary controls use quiet borders. Accent colors highlight a next step, not large areas of decorative neon. Motion lasts approximately 150–220ms, respects reduced-motion preferences and never delays task completion.

Avoid purple gradient templates, glassmorphism everywhere, endless identical cards, fake statistics, fabricated testimonials, stock graduation imagery, autoplay hero video and decorative dashboards. Use real teaching samples and instructor details supplied by the business. Development samples must be explicitly labeled and excluded from production seeds.

### Screen requirements

| Screen | Layout and essential content |
|---|---|
| Home | Split editorial hero: specific learning promise on left, authentic curriculum/project example on right; selected courses; teaching approach; actual instructor expertise; concise FAQ; course CTA |
| Catalog | Clear title, search, format filter; intentional course covers; title, summary, level, duration, format and price; no fake ratings |
| Course detail | Outcomes first; syllabus with numbered modules; prerequisites; instructor bio; schedule/timezone for live courses; access and completion terms; certificate preview; sticky desktop purchase summary |
| Auth | Quiet focused form with visible labels, accessible validation, password toggle and clear next action |
| Student home | Large resume-learning action; enrolled courses; next live session; completed courses/certificates; useful empty state |
| Classroom | Lesson navigation at left; wide content/video stage; clear lesson title, next/previous controls, progress state; mobile navigation drawer |
| Live lesson | Session date, timezone, status, protected join action, cancellation/reschedule message and recording state |
| Payment return | Explicit confirming, confirmed, failed and pending states; order reference and safe retry/support options |
| Certificate | Elegant credential preview; download; publish verification toggle; copy credential fields; LinkedIn action |
| Admin | Compact navigation, readable tables, status filters, practical forms, preview and publish validation; share the visual identity |

On mobile, use a fixed enrollment CTA only if it does not obscure text, keyboard focus or consent controls. No hover-only functionality. Check 360px, 768px, 1280px and 1440px layouts, 200% zoom and keyboard navigation. Design target: WCAG 2.2 AA, with manual testing in addition to automated checks. Provide captions/transcripts for instructional recordings; publishing requires an admin confirmation that accessible learning content is available.

Every screen must define loading, empty, success, validation, forbidden, offline/network failure and provider-unavailable states where applicable. The classroom must explain “Recording processing” rather than display a broken player.

## 5. Public pages and discovery

Routes: `/`, `/courses`, `/courses/{slug}`, `/terms`, `/privacy`, `/refund-policy`, `/support`, `/certificates/verify/{token}`.

Catalog shows only published courses with sales visibility enabled. Basic indexed title search and format filtering are sufficient; paginate at 12–24 items. No external search service. Course detail may expose syllabus titles, outcomes and public instructor information; it must never serialize protected bodies, meeting URLs, private video IDs/tokens or student data into public HTML/JSON.

Public pages receive real server-rendered title, description, canonical URL and social metadata. Add sitemap entries for published marketing/course pages only. Exclude account, admin and certificate verification pages from indexing. Certificate social previews must be generic and avoid learner names in cached preview images.

Course CTA varies predictably: register/sign in; buy; enroll free; continue learning; enrollment closed; unavailable. A published live course can remain visible after enrollment closes. Its existing students retain access.

## 6. Authentication and account security

Use framework-supported session authentication and password handling. Registration fields: full name, email, password, password confirmation and terms/privacy acknowledgment. Default role is always `student`; ignore and reject privilege fields supplied by the browser.

Require verified email before checkout, free enrollment and protected learning. Support verification resend, login/logout, forgot/reset password, name update and password change. Require current-password confirmation for sensitive account changes. For MVP, email changes go through support/admin with a fresh verification flow; never silently update a paid account's email.

Require TOTP two-factor authentication for administrators before admin access; provide securely stored recovery codes using supported auth features. Students do not need MFA in MVP. Bootstrap the initial admin with an interactive CLI command; no public admin signup, hardcoded admin password or production demo user. Admin creation/promotion is a controlled CLI operation, not a student-accessible form.

Security defaults: minimum 12-character password, sensible maximum of at least 64; allow password managers and paste; use framework hashing. Secure, HttpOnly, SameSite cookies; HTTPS; CSRF protection on browser mutations; session regeneration on login and invalidation on logout. Start with a 120-minute idle session lifetime, no admin remember-me and recent authentication for sensitive admin operations. Invalidate sessions after password reset or account suspension.

Rate-limit login, password reset, registration, verification resend, checkout, playback-token requests and verification lookups. Suggested starting limits: login 5/minute per normalized email+IP with an additional IP ceiling; resend/reset 3/15 minutes per account/IP. Tune against logs. Avoid revealing account existence through reset responses. Never log passwords, reset tokens, payment secrets or meeting passcodes.

## 7. Authorization and entitlement model

Enforce Laravel policies on every protected request, not merely UI visibility. Use deny-by-default and validate resource ownership and nested relationships [R4]. An enrollment in course A must not permit a lesson, file, meeting or video in course B.

| Action | Guest | Verified student | Administrator |
|---|---|---|---|
| Public catalog/details | Yes | Yes | Yes |
| Buy/enroll | No | Own account | Can buy as own student identity if needed |
| Protected lesson/meeting/video | No | Active enrollment in that course | Audited preview access |
| Progress updates | No | Own enrollment only | Attendance/completion corrections with reason |
| Orders | No | Own orders only | View all |
| Course/student management | No | No | Yes |
| Issue/revoke certificates | No | Request eligible own certificate | Correct/revoke with reason |
| Public verification | Valid public token only | Same | Same |

Keep `enrollment.status` (`active`, `suspended`, `revoked`) separate from learning completion. A completed student can still study. Optional `access_ends_at` is null by default; if enabled later, display its meaning before purchase. Centralize entitlement evaluation: active user + verified email + active enrollment + valid access period + matching course/content. Admin preview bypass is explicit, not a generic flag passed by clients.

Account suspension blocks login/protected access but does not automatically invalidate a legitimately earned historical certificate. Refund/dispute-driven certificate rules are specified separately. Course archival removes sales exposure but preserves existing paid access. Emergency content takedown must be a separate audited block with a student-facing explanation.

## 8. Course and content administration

Course fields: title, slug, short summary, full sanitized description, cover image, learning outcomes, prerequisites, target audience, level, estimated duration, format, instructor display name/bio/photo, price in minor units, currency, publish status, sales visibility, enrollment deadline, certificate enabled and support/access wording.

Modules and lessons have explicit sort positions. Lesson types: `text`, `video`, `live`. Each has title, summary, required/optional flag, publish state and content fields appropriate to its type. A live lesson may later have a recording attached; it remains the same lesson and does not count twice.

Admin can draft, preview, publish, archive, reorder before first enrollment, attach processed recordings and correct copy. Publish validation requires required fields, at least one required lesson, a valid price/currency, legitimate instructor information, completion policy and accessible content confirmation. Published recorded lessons require a ready video. Live lessons may be published with a future session and “recording not yet available.”

Once the first enrollment exists, lock the required lesson set, required flags, lesson types and completion policy. Allow typo fixes, schedule corrections and replacing a recording for the same lesson with an audit entry. Do not delete enrolled lessons or reset historical completion. For a substantially new curriculum, duplicate into a new draft course with new lesson identities. No automatic transfer of purchases is implied.

Use restricted rich text/Markdown, sanitized server-side. No arbitrary HTML, scripts or iframe embeds. Upload images with type/size validation and re-encoding; do not permit untrusted SVG/HTML. Optional teaching PDF attachments: private storage, fixed size cap, verified MIME, safe filenames and authorized download endpoints. Do not build a generic file manager.

## 9. Enrollment and payment links

### Chosen payment-link approach

“Payment link” means a short-lived, provider-hosted Checkout URL tied to an application order. Do not default to one reusable public Stripe Payment Link per course: reliably associating a shared link's payment with the correct logged-in learner requires additional controls. Admins set prices in this platform; the server creates Checkout sessions. No custom card fields and no card data stored here.

For an admin-assisted sale, admin can copy the public course URL; the student signs in and generates their own checkout. Do not implement arbitrary third-party payment URLs as automatic enrollment triggers. A bank transfer, screenshot or emailed receipt is not automatic proof of payment. Complimentary/manual enrollment is available only to admins with a reason and audit entry, recorded separately from paid revenue.

### Checkout sequence

1. Verified student posts to the course checkout endpoint with CSRF protection.
2. Server checks course sales status/deadline, enrollment, currency and configured payment readiness. Browser-supplied prices and user IDs are ignored.
3. Create or reuse a pending order for this user/course under a transaction and lock. Snapshot title, price, tax presentation, total, currency, terms version and learner identity. Assign an opaque public reference.
4. Create a Stripe Checkout Session server-side using an idempotency key based on that order/attempt. Include the opaque order reference in server-created metadata and persist the session ID. Never rely on payment email to identify the student.
5. Redirect to the provider-hosted URL. If creation times out, recover the same attempt before making another; avoid holding a database transaction open during a network call.
6. Return page displays status from the authenticated order record. Returning from checkout never grants access by itself. Poll briefly with backoff and then show “Payment confirmation pending” with an order reference.
7. Verified payment webhook triggers durable reconciliation and atomic fulfillment. Grant one enrollment and queue one confirmation email.

Free course enrollment skips Stripe but still requires verification, server-side course checks, a transaction and the unique enrollment constraint.

### Webhook integrity

Use the official SDK to verify the signature against the raw body and endpoint secret. Exempt only this exact callback from CSRF. Store the event durably under a unique `(provider, event_id)` before acknowledging it; return a retryable error if persistence fails. Process with database jobs and record attempts/errors. Duplicate deliveries and simultaneous workers must be harmless. Stripe documents event delivery and verification requirements in [R5].

Retrieve current provider payment state as needed. Verify merchant environment, stored session/order relationship, total, currency and successful payment status. A checkout-completed event alone must not grant access for an unpaid delayed payment. Handle completed, expired, asynchronous success/failure, refunds and dispute lifecycle events supported by the pinned API version [R6]. Launch with cards/wallets supported by the merchant account; do not enable additional delayed methods without testing them.

Within a transaction, lock order/enrollment records, update financial state and grant entitlement exactly once. Use unique provider session/payment identifiers plus `UNIQUE(user_id, course_id)`. Dispatch email/certificate jobs only after commit with deduplication keys. An out-of-order late completion event must not undo a refund or dispute. Reconciliation reads current provider state instead of assuming event arrival order.

If two distinct successful payments nevertheless occur for the same user/course, keep one entitlement, record both financial facts, alert admin and resolve the extra charge through the provider. Never silently discard it or pretend the database constraint prevented a charge.

### Financial and access states

| Event/state | Enrollment behavior |
|---|---|
| Created/pending/expired/failed checkout | No new paid access |
| Confirmed successful payment | Grant active enrollment once |
| Partial refund | Record refunded amount; retain access by default |
| Full refund | Revoke paid entitlement; revoke certificate associated with that enrollment under published policy |
| Open dispute | Suspend paid entitlement and temporarily suspend public credential validity |
| Dispute won | Restore only if no refund, admin suspension or other blocking reason remains |
| Dispute lost | Revoke paid entitlement and associated credential |
| Complimentary grant | Active entitlement with admin actor/reason; no fake payment |

Track payment state, refunded amount, dispute state and access restrictions separately. Preserve explicit admin suspension reasons so payment recovery cannot override them. Review these proposed refund/certificate terms with the business before launch and disclose them before purchase.

Admin issues refunds in Stripe Dashboard for MVP. The application shows synchronized status and audit history; it must not offer a nonfunctional refund button. Run scheduled reconciliation for old pending orders and recently changed/refunded/disputed payments. Provide an admin retry/reconcile action and operational alert for mismatches. Re-purchase after revocation creates a new order and may reactivate the existing enrollment only through the same policy checks.

### Pricing and tax handling

Use integer minor units and a three-letter currency code; never floating-point money. For AED, AED 499.00 is 49,900 fils. Disable adjustable quantity, client-controlled discounts and currency conversion for MVP. Snapshot all amounts so later price edits do not alter pending or historic orders. Expire/recreate an old session when its quote is no longer valid.

Choose and document tax-inclusive or tax-exclusive pricing before live sales. Store subtotal, tax and total fields. Do not invent VAT registration, tax rates, invoice requirements or exemptions. Tax policy must come from the business/accountant for its merchant location and customer market. If automated tax is required, configure and budget the provider feature explicitly. Payment receipts and legally compliant tax invoices are not interchangeable.

## 10. Live meetings and recorded videos

### Live sessions

Admin enters provider label, HTTPS meeting URL, start/end datetime, IANA timezone and status (`scheduled`, `cancelled`, `completed`). Store instants in UTC and retain the display timezone; render both student's timezone and course timezone when different. Default business timezone is Asia/Dubai. Validate daylight-saving edge cases when another timezone is selected.

Use approved meeting domains configurable by environment/admin owner. Validate parsed hostnames, not string suffix tricks; reject credentials in URLs, unsafe schemes and malformed links. Do not fetch arbitrary user URLs on the server. Encrypt sensitive join URLs/passcodes at rest and exclude them from logs and public payloads.

An authorized POST join endpoint checks enrollment, session status and configured join window, then redirects to the stored provider URL. Default window: 15 minutes before start through 30 minutes after end. Admin can correct the schedule. Return clear too-early, cancelled and ended states. Do not embed raw links in reminder emails; link to the authenticated lesson.

The provider hosts the call. Use its waiting room/passcode controls. Students can copy a link after joining; the platform cannot guarantee it is unshareable. Clicking Join is not proof of attendance. Admin marks required attendance through a roster with timestamp and reason. If a recording is offered as the completion alternative, that rule must be frozen before enrollment.

Meeting changes/cancellations send one transactional notification per affected enrollment/change. No calendar integration or recurring meeting engine. Recording capture and rights/consent are handled by the business in the external meeting service, then uploaded to Stream.

### Recordings

Use Cloudflare Stream video IDs stored server-side. Admin can attach a video already uploaded through the provider dashboard, keeping MVP upload UI small. Validate ownership and readiness via the API; never trust a pasted “ready” flag. Model states: pending, processing, ready, failed and removed. Verify signed provider webhooks or reconcile readiness through server-side polling. Display actionable processing/failure states.

Set private videos to require signed playback and restrict allowed origins [R7]. The token endpoint checks current entitlement every time and returns a short-lived signed player URL, starting at 10 minutes with authorized refresh before expiry. Test actual long-lesson behavior with the provider player. Configure no-store on token responses; never cache them in shared HTML. Provider credentials stay server-side.

Revocation blocks token refresh immediately; previously issued bearer tokens can remain useful until expiry. Document that bounded delay. Signed playback deters casual link sharing; it does not prevent screen recording. Do not promise DRM. Paid lesson downloads are disabled by default. Do not put MP4s on the VPS, use public buckets, or treat unlisted YouTube links as protected access.

## 11. Learning progress and completion

Use simple, honest completion rules. Text lessons have “Mark complete.” Recorded lessons save playback position approximately every 15 seconds and on pause/navigation; clamp values against known duration. A “Mark complete” action is available after playback begins, with confirmation that the learner completed the material. This is self-attested course completion, not verified watch time or assessed mastery. Do not claim that browser playback events prove learning.

For required live lessons, completion is an admin attendance mark OR self-attested completion of an attached recording only if the course's frozen policy permits that alternative. Optional lessons do not block a certificate. With no required lessons, publishing fails; do not treat zero divided by zero as complete.

Server calculates completed required lessons / required lessons; clients never submit a final percentage or eligibility flag. Completion writes are idempotent and restricted to enrolled published lessons. Updating position cannot erase completion. Record `completed_at`, source (`self_attested`, `attendance`, `admin_correction`) and actor where applicable.

Set enrollment completion once all required lessons are satisfied. Never retroactively revoke completion solely because descriptive content changed. Admin corrections require reasons; explicit certificate revocation is separate. “Resume learning” chooses the most recent unfinished lesson, then the next required lesson. Cross-device progress is database-backed.

## 12. Certificates and LinkedIn

Issue only for a verified learner with an eligible enrollment and all required completion conditions met. The backend performs the checks under a lock. Enforce one current credential per enrollment and idempotent generation. Do not issue on a frontend button's assertion alone.

Certificate fields: learner-name snapshot, course-title snapshot, issuer-name snapshot, issue date, opaque credential ID, stable verification URL, authorized signatory label and optional real signature asset. Use “Certificate of Completion.” No invented accreditation, university endorsement or guaranteed employment claims.

Generate a fixed landscape PDF with embedded fonts, meaningful text, generous margins and a QR code pointing to verification. Test long names/course titles and Unicode names. Restrict remote resource fetching in PDF generation; use trusted local assets. Store PDFs privately. Owner/admin downloads require authorization; use a job with retries and visible generation status. A failed PDF job must not create duplicate credentials.

Verification token: cryptographically random with at least 128 bits of entropy; never a sequential database ID. A private credential is the default. Student explicitly enables public verification before sharing; explain that their name, course and issue date become accessible to anyone with the link. Store opt-in timestamp/version. Disable indexing and do not provide a searchable learner directory.

For opted-in valid credentials, the public page shows only certificate name, learner-name snapshot, issuer, issue date, credential ID and current validity. Never expose email, progress, order or other courses. A revoked/suspended credential returns its current generic status without private reasons. When sharing is disabled, return an unavailable/private response without personal data. Revalidate status on every request and avoid stale cache after revocation. A downloaded PDF cannot be recalled; the verification page is authoritative.

Name corrections require an admin-reviewed reissue, preserving original issuance history and marking the old credential superseded. A normal profile-name edit never silently changes issued certificates. A single certificate table may hold revisions with a self-referencing superseded ID and nullable unique current-enrollment key.

### LinkedIn behavior

Provide separate “Add to LinkedIn profile,” “Copy verification link” and PDF download actions. Configure the organization's official Add to Profile link and offer copy buttons for certificate name, issuer, issue date, credential ID and URL. Opening LinkedIn is user-initiated. Include concise manual entry instructions and make this work even if the external button behavior changes.

Current LinkedIn Help says Add to Profile no longer autofills certificate fields [R8]. Older developer PDFs describe a different experience. Do not build an acceptance requirement around old prefilled URL parameters. No LinkedIn login/OAuth, automatic posting or API publishing is required. For feed sharing, provide the public verification URL and optional copyable text; the learner posts it themselves. Do not imply an official LinkedIn certification partnership.

## 13. MySQL schema contract

Use foreign keys, UTC timestamps, indexes and transactions. Internal primary keys can be bigint; publicly exposed order/certificate references must be opaque. Apply soft deletion/archival where history matters. Do not cascade-delete payments or issued credentials when deleting a course or account.

| Table | Essential fields and constraints |
|---|---|
| `users` | id, name, normalized unique email, password hash, verified_at, role, status, timezone, auth/MFA fields, timestamps |
| `courses` | id, unique slug, public content, instructor fields, format, price_minor, currency, status, sales_enabled, enrollment_closes_at, curriculum_locked_at, completion_policy, certificate_enabled, timestamps |
| `modules` | id, course_id FK, title, position; index(course_id, position) |
| `lessons` | id, module_id FK, title, type, position, required, published_at, sanitized body, video_id nullable; index(module_id, position) |
| `live_sessions` | id, unique lesson_id FK, provider, encrypted_join_url, starts_at, ends_at, timezone, status, change_version |
| `videos` | id, unique provider_video_id, provider, status, duration_seconds, captions_status, last_checked_at |
| `lesson_resources` | id, lesson_id FK, private disk/path, filename, MIME, byte_size |
| `orders` | id, unique public_reference, user_id FK, course_id FK, title/price/tax/terms snapshots, currency, subtotal_minor, tax_minor, total_minor, payment_status, refunded_minor, dispute_status, timestamps |
| `payment_attempts` | id, order_id FK, provider, unique session_id nullable, unique payment_intent_id nullable, unique idempotency_key, status, expires_at, last_reconciled_at |
| `refunds` | id, order_id FK, unique provider_refund_id, amount_minor, currency, status, processed_at |
| `webhook_events` | id, provider, event_id, event_type, restricted payload, received_at, processing_status, attempts, processed_at, error_code; unique(provider,event_id) |
| `enrollments` | id, user_id FK, course_id FK, current_order_id nullable, source, status, access_ends_at nullable, granted_at, completed_at, restriction_reason; unique(user_id,course_id) |
| `lesson_progress` | id, enrollment_id FK, lesson_id FK, position_seconds, completed_at, completion_source, actor_id nullable; unique(enrollment_id,lesson_id) |
| `certificates` | id, enrollment_id FK, nullable unique current_enrollment_id, unique credential_id, unique verification_token, snapshot fields, status, public_enabled_at, issued_at, supersedes_id nullable, revoked_at, private_pdf_path, generation_status |
| `audit_logs` | id, actor_id nullable, action, subject_type/id, sanitized before/after data, reason, request_id, created_at |
| `notification_deliveries` | id, unique dedupe_key, user_id, type, subject reference, status, sent_at, retry metadata |
| framework tables | password reset tokens, sessions, jobs, failed jobs, cache and locks as required |

Validate cross-table course ownership in services; foreign keys alone do not guarantee a lesson belongs to an enrollment's course. Constrain nonnegative money/positions, end after start and recognized status values. Store provider IDs as strings. Add indexes for pending-order reconciliation, enrollment lookup, session schedule, queued retries and public verification. Never persist an entire serialized Eloquent model into public page props.

An order has one course; a user may have multiple orders for the same course but only one enrollment. Preserve old order history on re-purchase. A course has modules; modules have lessons; a live lesson has at most one session in this MVP. Keeping these constraints explicit avoids accidentally building a marketplace or cohort system.

## 14. Route and response contracts

| Method/path | Purpose and guard |
|---|---|
| Standard auth routes | Framework auth, CSRF, throttling |
| GET `/dashboard` | Verified active user; own enrollments |
| GET `/learn/{course}/{lesson?}` | Entitlement + matching nested lesson |
| POST `/courses/{course}/checkout` | Verified user; trusted price; idempotent pending-order reuse |
| POST `/courses/{course}/enroll-free` | Verified user; server confirms zero price |
| GET `/orders/{reference}` | Owner/admin only |
| GET `/checkout/return` | Authenticated owned order lookup; status only |
| POST `/webhooks/stripe` | Signature validation; durable event ingestion |
| POST `/webhooks/stream` | Provider signature validation if enabled |
| POST `/lessons/{lesson}/playback-token` | Entitlement; no-store; rate-limited |
| POST `/lessons/{lesson}/progress` | Own matching enrollment; validated position |
| POST `/lessons/{lesson}/complete` | Own matching enrollment; permitted completion method |
| POST `/live-sessions/{session}/join` | Entitlement, time window and status |
| GET `/resources/{resource}/download` | Entitlement and safe content disposition |
| POST `/enrollments/{enrollment}/certificate` | Own eligible enrollment; idempotent |
| GET `/certificates/{credential}/download` | Owner/admin; private file |
| PATCH `/certificates/{credential}/sharing` | Owner; consent and CSRF |
| GET `/certificates/verify/{token}` | Minimal public status; no-index; throttled |
| `/admin/*` | Admin role + enforced MFA + policies |

Admin routes cover courses/modules/lessons, sessions/attendance, video references, student suspension/manual grants, orders/reconciliation and certificate correction/revocation. Use Form Requests for validation and explicit response resources for data exposure. Missing/unauthorized private identifiers should not reveal another student's records. Use 422 for form errors, 409 for state conflicts, 429 for throttling and safe 5xx pages with request IDs.

## 15. Notifications, privacy and operational security

Transactional email only: verification/reset, enrollment confirmation, payment unresolved/failure when useful, live-session change/cancellation, one reminder approximately 24 hours before a future session, certificate ready and relevant access changes. Link to protected application pages. For sessions created less than 24 hours ahead, skip the already-past reminder. Deduplicate per recipient/session/change and make scheduler retries safe.

Configure SPF, DKIM and DMARC with the chosen email provider. Retry transient failures, record permanent failure/bounce status where supported and alert admin for important undelivered messages. Enrollment must remain valid when its confirmation email fails. No SMS, WhatsApp or marketing notification center.

Use least-privilege application database credentials, private network/localhost MySQL, encrypted secrets, HTTPS, restricted administrative server access, dependency audits and security updates. Set production debug off. Apply a restrictive CSP with only the required Stream frame/media domains, safe referrer policy and no cross-origin permission wildcard. Test headers against the actual player and checkout redirects.

Keep private documents outside the public web root. Restrict upload types/limits, sanitize rich text and escape output. Never execute uploaded content. Minimize webhook payload retention and redact secrets/PII from logs. Audit financial reconciliation, manual enrollment, attendance changes, role changes, suspension, publication and certificate changes.

Provide privacy, terms, access and refund policy pages populated by the owner; sample legal text must not be published as reviewed policy. Record the accepted policy version at enrollment/purchase. Provide a support contact for account deletion/data requests and an administrator runbook for identity verification, export and deletion/anonymization. Retain financial records only according to the business's applicable obligations. Do not hardcode a universal legal retention period. Public certificate consent and retained credential identity must be addressed explicitly during deletion requests.

## 16. Deployment and cost plan

### Initial topology

One Ubuntu LTS VPS runs Nginx, PHP-FPM, MySQL, one database queue worker and the scheduler. Media playback is delivered by Stream. Use external transactional email and encrypted off-server backups. Build frontend assets in CI to avoid consuming production memory. A 2GB server is a lean starting candidate; 4GB offers more headroom. Benchmark actual application/database memory and resize if necessary; this is not a capacity guarantee.

The single server has a shared failure domain. That is the accepted low-cost launch tradeoff, not high availability. Shared hosting is not the preferred baseline because worker supervision, scheduled tasks and operational access may be constrained. Managed database/high availability can be added when real demand justifies it.

### Cost basis, USD unless stated

| Item | Planning amount | Basis |
|---|---:|---|
| VPS | $12/month for 2GB or $24/month for 4GB | Example DigitalOcean Basic pricing checked during research [R9] |
| Framework/database licenses | $0 | Open-source chosen components; review third-party package licenses |
| Off-server backup storage | Budget $2–5/month initially | Allowance, not a vendor quote; size/retention may increase it |
| Transactional email | Budget $0–15/month at low volume | Allowance; select provider and verify limits before launch |
| Domain | Budget $10–25/year | Allowance; extension/renewal pricing varies |
| TLS | $0 certificate cost using an automated certificate service | Server operations still require maintenance |
| Video storage | $5/month per purchased 1,000 stored minutes | Stream published pricing [R10] |
| Video delivery | $1 per 1,000 delivered minutes | Stream published pricing [R10] |
| Payments | UAE example: 2.9% + AED 1 per domestic-card success | Stripe UAE published standard pricing; other fees can apply [R11] |
| Meeting platform | Separate business subscription if needed | Existing plan/meeting limits must be checked |

Baseline without video, payments or meeting subscription: approximately $15–46/month including a domain allowance. This excludes taxes, developer/server-management labor and optional paid monitoring. These are planning numbers, not a fixed hosting quote.

Illustrative video arithmetic: 20 hours of recordings = 1,200 stored minutes, requiring 2,000 minutes of purchased capacity = $10/month. If 100 learners each receive 5 hours that month, delivery is approximately 30,000 minutes = $30. Video is then approximately $40, making the example total about $55–86/month before payments/meetings/taxes. Buffering, repeats and provider billing measurement can alter delivery totals. At 1,000 learners with the same viewing, delivery alone is roughly $300. Video use can outweigh the app-server cost.

An AED 499 domestic-card payment at the stated base fee costs approximately AED 15.47 before any applicable additional charges. International cards, conversion, disputes and optional services can change the amount. Recheck prices and merchant eligibility before committing. Do not enable paid provider add-ons by default.

### Deployment runbook requirements

1. Provision a supported OS/PHP/MySQL combination, non-root deploy user, firewall and SSH keys; expose only necessary HTTP/HTTPS and restricted SSH.
2. Configure domain/TLS, correct proxy trust, Nginx document root at Laravel `public`, PHP-FPM limits and writable storage/cache directories.
3. Install from lockfiles, build assets in CI, deploy an immutable versioned release and retain previous assets/releases for rollback.
4. Set production environment secrets securely; generate and preserve `APP_KEY` once. Do not regenerate it during routine deploys.
5. Back up database, execute backward-compatible migrations, cache configuration/routes/views where supported and atomically switch release.
6. Restart long-running queue workers gracefully. Run scheduler every minute and supervise worker restarts with systemd or Supervisor. Keep resource/time limits explicit.
7. Check app health, database, queue heartbeat, email, payment callback reachability and one authorized playback flow.
8. Roll back application release if needed; avoid destructive automatic down-migrations. Document forward-fix/database recovery decisions.

`.env.example` must document application URL/key, database, database cache/session/queue drivers, mail, organization/issuer/contact/currency/timezone, Stripe keys/webhook secret/test mode, Stream account/token/signing configuration and private storage/backup configuration. Use backend-only environment variables for secrets; never put them in `VITE_*`.

### Backups and monitoring

Daily encrypted off-server MySQL backup plus private file and configuration/key recovery plan; proposed retention: 7 daily and 4 weekly copies, adjustable to approved policy. Keep original teaching recordings in a separate owner-controlled archive: a streaming service is not the only backup. Restrict backup access and alert on failure. Perform an initial restore drill and repeat monthly. Initial targets: recovery point up to 24 hours; recovery within 4 hours, subject to a measured drill. These targets are not a contractual SLA.

Monitor uptime, 5xx rate, CPU/RAM/disk, database connections, slow requests, oldest queue job, failed jobs, webhook processing age, provider spend and backup age. Use simple logs and alerts rather than building an analytics product. Never let log files fill the disk. Consider Redis or a separate database only when measured contention warrants it.

## 17. Implementation phases

### Phase 1 — foundation and design

Inspect repository; record assumptions; install compatible stack; configure MySQL and auth; create migrations/policies; establish design tokens and representative home/course/classroom/admin screens. Implement real registration/verification and secure admin bootstrap. Exit: real account and policy tests pass; responsive screen direction is coherent.

### Phase 2 — catalog and course management

Build public Blade pages and admin course/module/lesson editing, previews, sanitization, publishing rules and curriculum locking. Exit: admin creates/publishes a course, guests see only public fields, and students cannot reach admin mutations.

### Phase 3 — purchase and entitlement

Implement free enrollment, orders, hosted checkout, webhook persistence, reconciliation, refunds/disputes and student order UI. Exit: test-mode paid purchase grants exactly one entitlement; forged return/webhooks, duplicate delivery and cross-account access tests pass.

### Phase 4 — classroom, meetings and video

Implement protected content, video readiness/signing, resume position, completion, session join window, attendance and transactional reminders. Exit: unenrolled users cannot fetch private content; authorized students can study and join within the rules.

### Phase 5 — certificates and polish

Implement eligibility, PDF jobs, public opt-in verification, revocation/reissue and LinkedIn copy/manual flow. Finish responsive, accessible and failure-state polish. Exit: the complete learning-to-certificate journey works without manual database edits.

### Phase 6 — operational readiness

Complete deployment scripts/runbooks, production-like load smoke test, external-provider test evidence, backup restore drill and launch checklist. Deploy only with configured credentials and owner-approved business content/policies. Do not claim a local/mock build is launch-ready.

## 18. Required acceptance tests

| Area | Acceptance condition |
|---|---|
| Registration | Role injection rejected; duplicate email handled; verification required |
| Sessions | Logout/reset/suspension invalidates relevant access; CSRF blocks forged mutations |
| Admin | Student cannot access direct admin endpoints; admin MFA enforced |
| Ownership | Student A cannot read student B's orders, progress or private PDFs |
| Course isolation | Enrollment in A never unlocks lesson/video/session/resource B |
| Checkout | Altered browser amount ignored; archived/closed sales blocked; pending retry reuses attempt |
| Payment return | Forged success URL grants nothing; pending UI remains truthful |
| Webhooks | Invalid signature rejected; durable ingestion failure retries; duplicate/concurrent events grant once |
| Payment ordering | Delayed unpaid completion grants nothing; late success cannot reverse refund/dispute |
| Financial exceptions | Partial/full refund and dispute transitions apply stated access/certificate rules; duplicate real charges flagged |
| Reconciliation | Missed webhook recovered; unknown/mismatched order/amount fails closed and alerts |
| Free enrollment | No payment needed; unique enrollment maintained; paid course cannot use free endpoint |
| Content | No protected fields in public HTML/Inertia props; HTML payload sanitized |
| Video | Unauthorized token refused; direct unsigned playback fails; expiry refresh and revocation behave as documented |
| Live | Timezone display accurate; join window/cancellation enforced; join click never marks attendance |
| Progress | Position bounded; repeated completion idempotent; optional lessons excluded; zero-required course cannot publish |
| Curriculum | Required set locked after enrollment; archival preserves bought access |
| Certificates | Ineligible issuance denied; concurrent requests issue one; PDF retries safe; long/Unicode names render |
| Privacy | Private certificate shows no learner details; public opt-in works; revocation not served stale |
| LinkedIn | User can copy every required credential field and open the configured external flow without an API account |
| Operations | Mail failure does not undo enrollment; queue restart resumes work; restored backup is usable |
| UI | Mobile/desktop and keyboard flows work; focus/error states visible; no fabricated social proof |

Use MySQL in integration tests for locking/constraint behavior; SQLite-only tests do not validate MySQL concurrency. Mock external services for deterministic tests, then run separate sandbox/provider smoke tests with actual configured credentials. Never include live credentials in test fixtures.

Performance targets to measure, not claim in advance: public LCP within 2.5 seconds under a documented mobile test profile; representative non-provider app requests p95 below 500ms under a documented small-launch workload. Run a starting smoke load of 20 simultaneous application users against a production-like server, excluding video bytes, and record CPU/RAM/query results. Tune N+1 queries, eager loading and pagination before buying larger infrastructure. Do not turn these targets into an invented user-capacity guarantee.

## 19. Launch inputs and final gate

Implementation can proceed with sensible placeholders. Live launch needs: actual organization/brand/domain; instructor and course content; company LinkedIn page/Add to Profile URL; approved issuer/signatory; merchant country and activated payment account; price/tax/invoice policy; refund/access/certificate rules; meeting subscription and URLs; streaming credentials and owned recordings; captions/transcripts; transactional sender; support contact; reviewed privacy/terms; server/backups; and a named person to monitor payments and restore service.

Do not publish fake biographies, success claims, placeholder legal policies or test course purchases. Disable test seed accounts and sandbox banners only when production configuration is validated. Perform a controlled authorized live-payment verification after activation, including webhook delivery and reconciliation, according to the owner's operational plan.

Definition of done: an administrator can publish a course, a new learner can verify an account and pay, only that learner receives access, lessons and meetings work, completion yields a downloadable and optionally public verifiable certificate, refunds/revocations are handled, and the application can be deployed and recovered using its documentation. Every included feature must work end to end; excluded features must remain excluded.

## 20. Research references and interpretation

These are primary sources checked during preparation. Prices and integrations can change; Codex must recheck pinned-version details at implementation. Product defaults, schema, UX and business policies above are recommendations for this project, not requirements imposed by the vendors.

- **[R1] Laravel starter kits:** https://laravel.com/framework/docs/starter-kits — React/Inertia foundation.
- **[R2] Laravel deployment:** https://laravel.com/framework/docs/deployment — production application configuration.
- **[R3] Laravel 10 release requirements:** https://laravel.com/docs/13.x/releases — PHP compatibility.
- **[R4] OWASP authorization guidance:** https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html — server-side access checks.
- **[R5] Stripe webhooks:** https://docs.stripe.com/webhooks — callback verification and delivery behavior.
- **[R6] Stripe event reference:** https://docs.stripe.com/api/events/types — payment/refund/dispute event selection; also consult https://docs.stripe.com/checkout/fulfillment when implementing.
- **[R7] Cloudflare Stream protection:** https://developers.cloudflare.com/stream/viewing-videos/securing-your-stream/ — signed playback; provider webhook authenticity: https://developers.cloudflare.com/stream/manage-video-library/using-webhooks/.
- **[R8] LinkedIn current Add to Profile FAQ:** https://www.linkedin.com/help/recruiter/answer/a528030/linkedin-add-to-profile-feature-faqs?lang=en — current manual-entry behavior takes precedence over older PDFs describing autofill.
- **[R9] DigitalOcean Droplet pricing:** https://www.digitalocean.com/pricing/droplets — reference VPS cost, not a mandatory hosting vendor.
- **[R10] Cloudflare Stream pricing:** https://developers.cloudflare.com/stream/pricing/ — storage capacity and delivered-minute pricing.
- **[R11] Stripe UAE pricing:** https://stripe.com/ae/pricing — domestic-card base fee example; eligibility and other fees must be verified for the business.
- **[R12] Stripe Payment Links:** https://stripe.com/ae/payments/payment-links — reusable links exist; per-order Checkout is the deliberate choice here for account association and control.

## 21. Copy/paste kickoff prompt

> Read this entire specification and the repository instructions. Implement this custom course platform in the stated phases using Laravel, React/Inertia and MySQL. Keep the MVP scope exact. Start by inspecting the repository, recording assumptions and creating a concise implementation plan, then implement working vertical slices. Use the specified custom visual direction and real server-enforced authentication, authorization, payments, video access and certificate rules. Use test integrations when credentials are unavailable and clearly list production blockers. Test the security and payment boundaries, document deployment and recovery, and report what actually passed. Do not add marketplace, subscription, chat, quiz or AI features. Do not stop after generating static pages.
