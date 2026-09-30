# Task 5 Report

## Status

Implemented host-scannable System and Backup presentation using the existing normalized per-host status documents.

- Added Dashboard-aligned host state classes, icons, labels, and accessible service state text.
- Added explicit Running, Unavailable, and Stale states for service/host presentation.
- Added Root Disk and Student Disk percentages with Normal, Warning, Critical, and Unavailable threshold treatment.
- Added per-host backup freshness, timestamp, size, availability/stale state, and preserved the read-only policy copy.
- Preserved selected-host validation, escaping, Layout integration, existing hooks, routes, and no backup action controls.
- Added responsive-safe host panel styling and reduced-motion compatibility through the existing shared CSS.

## TDD / Verification

- Added failing render assertions first and confirmed the expected failures before implementation.
- `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/SystemController.php` — passed.
- `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/BackupController.php` — passed.
- `git diff --check` — passed.
- `C:\wamp64\bin\php\php8.3.14\php.exe tests/run.php` — all Task 5-focused tests passed, including SystemControllerTest.php, BackupControllerTest.php, and MultiHostSystemBackupTest.php.

## Concerns

- Full-suite environment failure: AuthRepositoryTest.php and SessionRepositoryTest.php report a missing `sysadmin` fixture.
- Full-suite environment failure: MultiHostAcceptanceReviewTest.php cannot prepare its symlink fixture because this Windows environment denies symlink creation.

## Fix Round 1

- Kept System stale/unavailable host panels structurally complete with service and Root/Student storage sections.
- Added explicit `Unavailable`, `Stale`, and `—` placeholders for non-live or missing storage data; percentages and progress bars render only for numeric storage values.
- Kept Backup stale/unavailable host panels complete with per-host last backup timestamp and Backup size fields.
- Added focused stale, unavailable, and missing-storage assertions to SystemControllerTest.php, BackupControllerTest.php, and MultiHostSystemBackupTest.php.

### Fix Verification

- PHP lint for SystemController.php and BackupController.php — passed.
- `git diff --check` — passed.
- `C:\wamp64\bin\php\php8.3.14\php.exe tests/run.php` — all Task 5-focused tests passed.
- Existing environment-only failures remain unchanged: missing `sysadmin` fixture and denied Windows symlink creation.

## Fix Round 2

- Corrected SystemController stale/null service state precedence so stale host service cards say `Stale`, never `Unavailable`.
- Preserved explicit state icons/labels and existing healthy/unavailable behavior.
- Added a focused stale/null regression assertion covering host state, service state, and the absence of the unavailable label.

### Fix Verification

- SystemControllerTest.php + MultiHostSystemBackupTest.php — passed.
- PHP lint for SystemController.php — passed.
- `git diff --check` — passed.
