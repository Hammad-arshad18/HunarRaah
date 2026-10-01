# Launch gate

The application is not approved for live launch. Complete and record these checks:

- [ ] Real organization, domain, support, instructor expertise, course content and brand assets supplied.
- [ ] Reviewed privacy/terms/refund/access/certificate policy pages populated. Current policy pages are preparation notices, not approved legal documents.
- [ ] Merchant eligibility, activated account, prices/tax presentation/invoice obligations approved. Current checkout supports an inclusive amount without separately computed tax; accountant approval and any required invoice implementation remain blockers.
- [ ] Actual Stripe test-mode Checkout/webhook/refund/dispute/timeout/concurrent-worker evidence captured.
- [x] Local Laravel 10 / system PHP / MySQL 8.4.6 integration suite and eight-worker enrollment/payment/certificate races executed; see test-report. Repeat on staging with real providers.
- [ ] Owned Stream videos with captions/transcripts, private unsigned-denial, origin restrictions and long-playback behavior checked.
- [ ] Meeting domains/subscription, UTC/timezone schedules, waiting room, consent and reminders verified.
- [ ] SMTP sender and SPF/DKIM/DMARC, delivery/bounce alerts and recovery checked.
- [ ] All admin content/media/attendance/reissue workflows validated in browser; outstanding gaps listed in test-report.
- [ ] Owner authorizes resuming tests; final login/logout, authenticator onboarding, enhanced controls, mobile spacing and light/dark-theme edits are verified. Tests are currently paused.
- [ ] PDF long names, Unicode repertoire, QR and one-page layout manually verified; embedded DejaVu supports a limited repertoire and is not proof of every Unicode script.
- [ ] Responsive 360/768/1280/1440, 200% zoom, keyboard and WCAG 2.2 AA manual/automated checks completed.
- [ ] Production CSP/cookies and no demo/test data confirmed; resolve the four framework advisory records from the Laravel 10 dependency audit and establish an approved security patch strategy.
- [ ] Backup off-server encryption, initial restore/rollback drill and operational owner verified.
- [ ] Staging 20-concurrent-user benchmark and documented LCP/p95/memory/query results captured.
- [ ] Owner-controlled live payment verification authorized only after activation/configuration.

Do not remove development labels, enable live credentials or claim production readiness until these gates pass.
