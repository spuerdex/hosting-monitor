# Task 7 report

Status: Verified; no scoped application fixes required.

## Commands

- `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Views/Layout.php`
- `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/AuthController.php`
- `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/DashboardController.php`
- `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/StudentsController.php`
- `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/SystemController.php`
- `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/BackupController.php`
- `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/ManualController.php`
- `C:\wamp64\bin\php\php8.3.14\php.exe tests/run.php`
- Read-only repository verification against the local registry and existing cache.
- Browser checks with Playwright CLI at 1440x900, 1024x900, and 390x844.

## Results

- All seven PHP syntax checks passed.
- PHP suite: 26 passes; 3 known baseline/environment failures (the original report's 24 count was an undercount).
- Local Demo: 5 hosts, 80 students, 3 HEALTHY, 2 WARNING at the cache timestamp. Current time correctly reports the cache as STALE.
- UI responsive checks: Dashboard, Students, System, Backup, Manual, and Login had no horizontal overflow at all requested viewports.
- Mobile menu open, Escape close, and backdrop close passed.
- Focus visibility, long label handling, no-result hook, status text, and read-only wording were present.

## Remaining concerns

- `AuthRepositoryTest.php` and `SessionRepositoryTest.php` require the missing local `sysadmin` database fixture.
- `MultiHostAcceptanceReviewTest.php` cannot create its temporary symlink under the current Windows permission profile.
- Python fixture checks could not run because Python is not installed or on PATH.
- The live PHP route emitted a single expected `/favicon.ico` 404; this is unrelated to the approved Task 7 scope.

## Fix round 1 documentation addendum

### Exact commands and evidence

All seven lint commands exited `0`, with empty stderr and the following exact stdout:

- `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Views/Layout.php` → `No syntax errors detected in app/Views/Layout.php`
- `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/AuthController.php` → `No syntax errors detected in app/Controllers/AuthController.php`
- `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/DashboardController.php` → `No syntax errors detected in app/Controllers/DashboardController.php`
- `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/StudentsController.php` → `No syntax errors detected in app/Controllers/StudentsController.php`
- `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/SystemController.php` → `No syntax errors detected in app/Controllers/SystemController.php`
- `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/BackupController.php` → `No syntax errors detected in app/Controllers/BackupController.php`
- `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/ManualController.php` → `No syntax errors detected in app/Controllers/ManualController.php`

`C:\wamp64\bin\php\php8.3.14\php.exe tests/run.php` exited `1`. Stdout had 26 passing test-file lines: `ApiMultiHostStatusRepositoryTest.php`, `AuthControllerTest.php`, `AuthServiceTest.php`, `BackupControllerTest.php`, `ConfigTest.php`, `DashboardControllerTest.php`, `DeploymentReadinessTest.php`, `FrontendAccessibilityTest.php`, `LayoutTest.php`, `LocalWorkflowTest.php`, `LoginAttemptRepositoryTest.php`, `LoginManagerTest.php`, `ManualControllerTest.php`, `MonitoringDashboardTest.php`, `MonitoringRouteWiringTest.php`, `MonitoringSelectionTest.php`, `MultiHostStatusRepositoryTest.php`, `MultiHostStudentsRouteWiringTest.php`, `MultiHostStudentsTest.php`, `MultiHostSystemBackupRouteWiringTest.php`, `MultiHostSystemBackupTest.php`, `SessionManagerTest.php`, `StatusRepositoryTest.php`, `StudentsControllerTest.php`, `SystemControllerTest.php`, and `UiV2Test.php`. Stderr had exactly these three named failures:

The original report's `24` count was an undercount; the exact rerun emitted 26 PASS lines and 3 FAIL lines.

```text
FAIL: AuthRepositoryTest.php - sysadmin must exist: expected true, got false
FAIL: MultiHostAcceptanceReviewTest.php - Symlink fixture could not be prepared.
FAIL: SessionRepositoryTest.php - sysadmin must exist: expected true, got false
```

Stdout also contained the `symlink(): Permission denied` warning and stack trace from `MultiHostAcceptanceReviewTest.php`. The attempted Python command was `py -m unittest tests/test_demo_fixtures.py`, exit `1`, with PowerShell reporting that `py` was not recognized; `python`, `python3`, and `py` were unavailable.

### URL, route matrix, and interactions

The Local Demo URL is `http://hosting-monitor.local/...`. Without credentials, direct checks returned `302 Location: /login` for `/dashboard`, `/students`, `/system`, `/backup`, and `/manual`; `/login` returned `200`. Credentials were not used or recorded.

At the authenticated render for `http://hosting-monitor.local/dashboard`, the existing registry/cache at `2026-09-30T15:18:35+00:00` UTC (`2026-09-30 22:18:35 +07:00` Asia/Bangkok) rendered the exact dashboard summary `5` hosts and `80` students, with 3 `HEALTHY` and 2 `WARNING` host labels. The repository evidence was `repository_hosts=5 repository_students=80`; the HTML evidence was `rendered_hosts=1 rendered_students=1 healthy_labels=3 warning_labels=2`, where the first two values confirm the exact `5`/`80` summary markers.

| Route | 1440x900 | 1024x900 | 390x844 |
| --- | --- | --- | --- |
| `/dashboard` | Rendered; `overflow=false`, scroll/client `1440/1440` | Rendered; `overflow=false`, `1024/1024` | Rendered; `overflow=false`, `390/390` |
| `/students` | Rendered; `overflow=false`, scroll/client `1440/1440` | Rendered; `overflow=false`, `1024/1024` | Rendered; `overflow=false`, `390/390` |
| `/system` | Rendered; `overflow=false`, scroll/client `1440/1440` | Rendered; `overflow=false`, `1024/1024` | Rendered; `overflow=false`, `390/390` |
| `/backup` | Rendered; `overflow=false`, scroll/client `1440/1440` | Rendered; `overflow=false`, `1024/1024` | Rendered; `overflow=false`, `390/390` |
| `/manual` | Rendered; `overflow=false`, scroll/client `1440/1440` | Rendered; `overflow=false`, `1024/1024` | Rendered; `overflow=false`, `390/390` |
| `/login` | Login form; `overflow=false` | Login form; `overflow=false` | Login form; `overflow=false` |

At `/students`/`390x844`, entering `not-a-real-host` in `ค้นหา Student ID หรือ Domain...` visibly produced `ไม่พบ Account ที่ตรงกับเงื่อนไขการค้นหา`. At `/manual`/`390x844`, Tab focused the `D DiGiT Hosting Admin` anchor with `:focus-visible`. Opening the mobile menu set `sidebar is-open`, `sidebar-backdrop is-visible`, `body.sidebar-open`, and `aria-expanded="true"`; Escape and backdrop click each restored the closed classes/body and `aria-expanded="false"`.

The stale-cache observation was run at `2026-09-30T17:22:21+00:00` UTC (`2026-10-01 00:22:21 +07:00` Asia/Bangkok) and emitted `hosts=5 students=0 states={"STALE":5}` under the 180-second freshness policy. Exact favicon noise: `Failed to load resource: the server responded with a status of 404 (Not Found) @ http://127.0.0.1:8088/favicon.ico:0`; this was the only live login console error.
