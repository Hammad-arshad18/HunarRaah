# Project preferences

Updated 30 September 2026 (Asia/Dubai), at the owner's explicit request.

- Use **Laravel 10** for this repository. Do not upgrade its major version without the owner's instruction.
- Use the **system PHP** resolved by `php` on PATH. On this Windows machine it is `C:\xampp\php\php.exe`, PHP 8.1.25. Do not download or use a workspace-local PHP runtime.
- Run Composer, Artisan, tests and the local preview with system PHP. Resolve compatible dependencies against the actual runtime; never bypass PHP platform requirements.
- These preferences supersede the earlier Laravel 13 / portable PHP decision in `docs/decisions.md`.
- Keep the implemented course platform behavior and security boundaries while adapting dependencies and framework bootstrapping.

Laravel 10 and PHP 8.1 are past upstream security support. Record that operational limitation accurately; do not silently change the owner's requested versions.

- The Laravel 10.50.3 dependency audit reports four framework advisory records. Keep them visible; do not disable auditing or silently claim they are resolved.
