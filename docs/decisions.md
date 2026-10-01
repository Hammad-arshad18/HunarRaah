# Implementation decisions

## GitHub Actions removed — 1 October 2026

The owner requested removing GitHub Actions CI/CD for now. Remove the tests workflow and the Dependabot configuration that only updated GitHub Actions. Keep local PHPUnit tests, Composer check commands and manual deployment configuration. Update deployment instructions to build assets on a trusted build machine. Add public-discovery regression tests for unavailable courses, unpublished/protected lesson data and closed enrollment remaining publicly discoverable. The owner permitted adding tests; the existing pause on executing tests remains in force. No GitHub settings, remote workflows, commits or pushes were changed.

## 2026-09-30 — owner version instruction

- The owner explicitly requested Laravel 10 and the existing system PHP, superseding the original Laravel 13/portable PHP decision. Preferences are saved in `MEMORY.MD`.
- Use PHP 8.1.25 from `C:\xampp\php\php.exe`, Laravel 10.50.3, compatible Fortify/Inertia 2 and PHPUnit 10. Composer resolves against the actual runtime without ignoring platform requirements.
- Framework bootstrapping uses HTTP/console kernels, explicit providers, Blade Inertia directives and property-based model casts. Job traits use the Laravel 10 equivalents. Course/payment/progress/credential data is preserved.
- Wayfinder and the newer starter-kit installer/passkey scaffolding require newer framework/PHP APIs and are removed. Explicit auth/settings route helpers and standard Inertia 2 initialization replace them; admin TOTP remains required.
- Lockfiles are regenerated and CI/deployment documentation targets PHP 8.1. No global PHP configuration or PATH is changed.
- Laravel 10 and PHP 8.1 are past upstream security support. Composer reported four advisories affecting the framework; the local version change used a one-time `--no-security-blocking` resolution flag. Auditing remains enabled; no global ignore policy or platform bypass is configured. See `docs/test-report.md` for details.

## Unchanged product defaults

- The workspace initially contained only AGENTS.md and GEMINI.md. Preserve both.
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

## Studio redesign and workflow completion — 30 September 2026

At the owner's request, remove the remaining starter identity throughout the product. Use one studio mark, a paper/cobalt editorial auth layout, distinct learner/admin navigation and a structured course editor. Retain accessible, tested authentication primitives beneath the custom visual treatment.

Expose existing business actions through usable screens and add the missing record lists, live schedule, chapter/lesson controls, availability panel, audit viewer, email correction, certificate recovery, admin PDF/recording preview and completion correction. All new admin endpoints retain confirmed TOTP and recent password checks. Hiding sales preserves enrolled access; emergency takedown is a separate decision. Completion corrections preserve historical credentials pending a separate explicit revocation decision.

Browser testing uses an isolated development database on port 8001 and log-only mail. Preserve the owner's configured `.env`, MySQL database and SMTP settings. Random browser fixture administrators are refused outside the isolated SQLite/log configuration. Keep provider readiness truthful; missing keys produce useful unavailable states rather than successful demonstrations.

## Filament administration and sign-in corrections — 1 October 2026

The owner explicitly requested Filament for the entire administrator application. Pin Filament 3.3.55 and Livewire 3.8.10, compatible with Laravel 10.50.3 and the actual system PHP 8.1.25. Keep public Blade and student React/Inertia pages. Enable the system PHP `intl` extension in `C:\xampp\php\php.ini`; its previous configuration is backed up locally. This supersedes any earlier statement that global PHP configuration was unchanged.

Native Filament resources expose the existing validated and audited business actions. Financial records remain read-only apart from reconciliation; curriculum locks and certificate history remain enforced. Use the shared studio identity and Filament's compatible Tailwind assets, enhanced dropdowns/calendars and grouped row actions. Public catalog controls progressively enhance to Radix; React uses Tailwind 4. Keep these two Tailwind builds separate.

Fortify is the only sign-in flow. A successful password/TOTP sign-in stamps recent password confirmation, avoiding an immediate duplicate password prompt. Initial administrators use a focused `/admin/setup` authenticator flow; confirmed TOTP remains required by the specification. Crossing from Inertia to Filament or logging out uses full-page navigation. The admin menu posts to Fortify `/logout`, so expired admin password confirmation cannot block logout. Both Fortify and Filament logout responses return to the public home page.

At the owner's explicit request, a local-only administrator seeder created `admin@mirzalearning.com` in the configured MySQL database with a random password in private storage. It does not send mail, reset an existing account or run in production. Production administrator bootstrap remains the interactive audited CLI.

The owner paused tests during the final authentication/control/spacing corrections. Do not run tests or browser validation until explicitly authorized again. Asset compilation is required to deliver the UI changes and is reported separately.

## Shared light/dark palette — 1 October 2026

Replace competing page-local palettes with `public/theme.css`. Public Blade, React/Tailwind and Filament load the same tokens; `theme.js` synchronizes the existing appearance cookie/local-storage preference and Filament's theme preference before rendering. Use separate action and link colours so solid buttons keep readable white labels while small dark-mode links remain bright enough to read.

Fields explicitly define foreground, caret, placeholder, autofill and focus colours. Disabled controls remain visibly disabled without fading their contents. Ordinary authentication-code fields remain visible; transparent input styling is limited to the underlying segmented OTP input. Preserve intentional paper credential previews and static course artwork independently of the surrounding theme. These changes await owner-authorized testing.

## Repository cleanup and agent guidance — 1 October 2026

At the owner's request, remove unused starter layouts/navigation and one-off local scripts, screenshots, generated browser reports, archived downloads and the obsolete portable PHP/starter copies. Preserve the running application's dependencies and generated assets, local databases, private credentials, PHP configuration backup and historical text reports; these remain ignored. Reusable UI primitives remain available for future work.

Track `AGENTS.md`, `RULES.md`, `CLAUDE.md`, `GEMINI.md` and `MEMORY.MD`. Keep common constraints in `RULES.md` and small entry points for Claude/Gemini so rules do not diverge. Current owner decisions override the original specification's conflicting defaults. Strengthen exclusions for environment variants and database exports. No tests, commit, GitHub push or remote workflow were run during cleanup.

## Three.js homepage — 1 October 2026

At the owner's request, add a procedural learning sculpture to the homepage hero using Three.js 0.186.1 and matching `@types/three` 0.186.0. Preserve the actual course/lesson preview in Blade. Use a separate small Vite entry and dynamic scene import after viewport proximity/idle time; learner, catalog and admin entries do not import Three.js. Geometry and canvas textures are generated locally with no third-party asset requests or CSP changes. See the official [WebGLRenderer documentation](https://threejs.org/docs/pages/WebGLRenderer.html) for WebGL 2 support and renderer lifecycle.

Shared theme tokens drive the scene and HTML controls. Rotation is available by pointer drag and native labelled buttons; touch keeps vertical scrolling. Provide play/pause, start paused under reduced motion, cap animation at 30 frames/second and DPR at 1.25 on narrow screens or 1.5 otherwise. Suspend rendering offscreen, in hidden tabs and during back/forward caching. Release renderer, geometries, materials, textures, observers and listeners on final navigation or context loss. A responsive SVG remains available before initialization, without JavaScript, with data saving, on failed downloads or without WebGL 2. The fallback has no dead controls.

The deferred renderer adds approximately 135 KB gzip; Vite reports its minified chunk exceeds 500 KB. Keep the size warning visible rather than raising the threshold. No external models, postprocessing, real-time shadows or render loop outside the homepage are introduced. Low-end-device performance, touch interaction, theme contrast and browser recovery still require owner-authorized validation; an asset build does not establish seamless operation on every device.

### Full-page scroll experience

The owner requested a more impressive complete page after the initial hero. Replace the old uniform sections with an oversized opening, a moving statement, a sticky three-chapter learning path, the actual course grid, teaching approach, actual featured instructor, native FAQ and a closing course CTA. Keep all product content in Blade; no fake metrics, testimonials or student progress are added. The instructor monogram is decorative, not a fabricated portrait.

Use native passive scroll events coalesced into requestAnimationFrame, IntersectionObserver and Web Animations; no additional animation dependency or scroll interception. Separate the scroll module from Three.js, passing hero progress and motion state through local events. Content remains visible without JS and after animation failure. Keyboard focus cancels a reveal. A tab-scoped motion preference stops decorative movement, including Three.js; reduced-motion takes precedence. Stop work in hidden/cached pages and clean up on final navigation. Desktop sticky chapters become normal flow on narrow screens and short viewports. Browser validation remains paused.

At the owner's request for more detail, add a decorative scroll-driven word ribbon, a marker following the SVG learning path, native chapter jump links and subtle mouse-only course-art tilt/spotlights. Use the existing event-driven animation frame and motion preference; no new dependency or permanent animation loop. Pointer effects clear on exit, blur, keyboard focus, hidden pages and motion-off. Chapter navigation remains usable with no JavaScript. Raise the short-viewport sticky cutoff to accommodate the new chapter navigation.
