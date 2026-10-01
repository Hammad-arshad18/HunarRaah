# Project preferences

Updated 30 September 2026 (Asia/Dubai), at the owner's explicit request.

- Use **Laravel 10** for this repository. Do not upgrade its major version without the owner's instruction.
- Use the **system PHP** resolved by `php` on PATH. On this Windows machine it is `C:\xampp\php\php.exe`, PHP 8.1.25. Do not download or use a workspace-local PHP runtime.
- Run Composer, Artisan, tests and the local preview with system PHP. Resolve compatible dependencies against the actual runtime; never bypass PHP platform requirements.
- These preferences supersede the earlier Laravel 13 / portable PHP decision in `docs/decisions.md`.
- Keep the implemented course platform behavior and security boundaries while adapting dependencies and framework bootstrapping.

Laravel 10 and PHP 8.1 are past upstream security support. Record that operational limitation accurately; do not silently change the owner's requested versions.

- The Laravel 10.50.3 dependency audit reports four framework advisory records. Keep them visible; do not disable auditing or silently claim they are resolved.

- The product must use the custom teaching-studio identity throughout public, authentication, student, settings and administration screens. Do not restore Laravel logos, starter-kit links, documentation navigation or placeholder dashboards.
- Build real, authorized backend workflows for visible controls; preserve payment integrity, private content, required admin MFA and curriculum locks when expanding the UI.

- Use **Laravel Filament 3** for the entire administrator application, compatible with Laravel 10 and system PHP. Keep public/student pages in their existing Blade/Inertia architecture. Administration sign-in must retain Fortify TOTP and recent-password checks.

- **Tests are paused at the owner's request. Do not run tests or browser validation until the owner explicitly authorizes them again.** Building assets to deliver requested UI changes is permitted; report it separately from testing.
- Successful password sign-in must count as recent password confirmation. Do not immediately ask administrators to enter the same password again. Keep the required first-time authenticator setup clear and direct at `/admin/setup`.
- Logout must invalidate the session and navigate to the public home page on the first submission, including transitions between Inertia and Filament. Use a normal CSRF-protected form and full-page redirects where needed.
- Use enhanced Filament controls and accessible Radix/Tailwind controls instead of browser-style dropdowns. Keep spacing, control heights, typography, colors and menus consistent across public, student, account and admin screens.
- Light and dark themes must use the shared `public/theme.css` palette and synchronized appearance preference. Inputs need explicit readable text/caret/placeholder/autofill colours; active buttons use solid action colours. Never hide ordinary fields by matching their name to `code`; only the underlying segmented OTP input may be transparent.
- Keep a compact theme dropdown in the public homepage header with Light / Dark / System choices, sharing the same saved preference as account and administration screens. Avoid a bulky three-button bar.
- Keep `AGENTS.md`, `RULES.md`, `CLAUDE.md`, `GEMINI.md` and this file in Git. `RULES.md` is the common agent guidance; agent-specific files point to it. Preserve local data and dependencies during cleanup, while excluding them from GitHub.
- GitHub Actions CI/CD is removed at the owner's request. Do not recreate workflows without a new instruction. Keep local Laravel tests; adding useful test cases is authorized, but the earlier pause on running tests and checks remains active.
- The owner requested a Three.js homepage. Keep it as a homepage-only progressive enhancement with the real Blade curriculum preview, shared theme, mobile scrolling, accessible pause/rotation controls, reduced-motion support, bounded rendering and a complete static fallback. Do not load Three.js in learner/admin bundles or add remote visual assets/CDNs. Tests and browser validation remain paused.
- The owner wants a complete homepage with scroll animations, not only an animated hero. Preserve the full editorial sequence, scroll-driven 3D movement, sticky learning chapters, content reveals and closing section. Use native scrolling, visible-by-default content and a page motion switch synchronized with Three.js; respect reduced motion and disable sticky layouts on small/short screens.
