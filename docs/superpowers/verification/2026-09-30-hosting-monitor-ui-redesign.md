# Hosting Monitor UI redesign verification

Date: 2026-10-01

## Scope

Final Task 7 verification for the UI redesign. No application source or test changes were required because the checks found no concrete scoped defect.

## Syntax and automated checks

Ran the requested PHP syntax checks with `C:\wamp64\bin\php\php8.3.14\php.exe -l` for `app/Views/Layout.php` and all six controllers (`AuthController.php`, `DashboardController.php`, `StudentsController.php`, `SystemController.php`, `BackupController.php`, and `ManualController.php`). All seven reported `No syntax errors detected`.

Ran `C:\wamp64\bin\php\php8.3.14\php.exe tests/run.php`:

- 26 test files passed, including `FrontendAccessibilityTest.php`, `UiV2Test.php`, all page/controller tests, and `LocalWorkflowTest.php`.
- `AuthRepositoryTest.php` failed: `sysadmin must exist: expected true, got false`.
- `SessionRepositoryTest.php` failed with the same missing `sysadmin` fixture.
- `MultiHostAcceptanceReviewTest.php` failed because Windows denied the test's temporary symlink creation (`Symlink fixture could not be prepared`).

These three failures are environment/database or Windows filesystem baseline failures; none is caused by the UI changes.

The Python demo-fixture test was attempted but could not run because no `python`, `python3`, or `py` executable is available in this environment.

## Local Demo verification

Using `config/hosts.local.example.json` and the existing local cache, the repository returned five hosts, 80 students, and these states at the cache's recorded verification time (`2026-09-30T15:18:35+00:00`): three `HEALTHY` hosts (`cs-01`, `cs-02`, `eng-01`) and two `WARNING` hosts (`it-01`, `sci-01`). At the current verification time, the same cache correctly becomes `STALE` under the repository's 180-second freshness policy.

## Browser and responsive verification

The local PHP server served the current worktree at `http://127.0.0.1:8088`. The login page and generated authenticated page renders for Dashboard, Students, System, Backup, and Manual were inspected at 1440px, 1024px, and 390px widths.

- No horizontal overflow occurred at any requested viewport.
- Long student/domain labels remained visible without creating overflow.
- Students exposed the no-match hook and read-only monitoring wording.
- Keyboard focus produced a visible `:focus-visible` target.
- The mobile drawer opened with `aria-expanded=true`, `sidebar is-open`, visible backdrop, and `body.sidebar-open`.
- Escape and backdrop click both closed the drawer and restored `aria-expanded=false`, removed the open classes, and restored body scrolling.
- Login rendered cleanly at all three widths.
- Console was clean for the authenticated generated page renders. The live login route had only the expected missing `/favicon.ico` 404.

## Changes

No changes were made to `app/`, `public/assets/`, or `tests/`. This verification document is the only tracked change.

## Fix round 1 evidence addendum

### Exact PHP command evidence

Each lint below exited `0`; stderr was empty in each case.

| Command | Exit | Stdout |
| --- | ---: | --- |
| `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Views/Layout.php` | 0 | `No syntax errors detected in app/Views/Layout.php` |
| `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/AuthController.php` | 0 | `No syntax errors detected in app/Controllers/AuthController.php` |
| `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/DashboardController.php` | 0 | `No syntax errors detected in app/Controllers/DashboardController.php` |
| `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/StudentsController.php` | 0 | `No syntax errors detected in app/Controllers/StudentsController.php` |
| `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/SystemController.php` | 0 | `No syntax errors detected in app/Controllers/SystemController.php` |
| `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/BackupController.php` | 0 | `No syntax errors detected in app/Controllers/BackupController.php` |
| `C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/ManualController.php` | 0 | `No syntax errors detected in app/Controllers/ManualController.php` |

`C:\wamp64\bin\php\php8.3.14\php.exe tests/run.php` exited `1`. Its stdout contained 26 `PASS:` lines: `ApiMultiHostStatusRepositoryTest.php`, `AuthControllerTest.php`, `AuthServiceTest.php`, `BackupControllerTest.php`, `ConfigTest.php`, `DashboardControllerTest.php`, `DeploymentReadinessTest.php`, `FrontendAccessibilityTest.php`, `LayoutTest.php`, `LocalWorkflowTest.php`, `LoginAttemptRepositoryTest.php`, `LoginManagerTest.php`, `ManualControllerTest.php`, `MonitoringDashboardTest.php`, `MonitoringRouteWiringTest.php`, `MonitoringSelectionTest.php`, `MultiHostStatusRepositoryTest.php`, `MultiHostStudentsRouteWiringTest.php`, `MultiHostStudentsTest.php`, `MultiHostSystemBackupRouteWiringTest.php`, `MultiHostSystemBackupTest.php`, `SessionManagerTest.php`, `StatusRepositoryTest.php`, `StudentsControllerTest.php`, `SystemControllerTest.php`, and `UiV2Test.php`. The same stdout also contained the PHP warning/stack trace for the denied symlink in `tests/MultiHostAcceptanceReviewTest.php`. Stderr contained exactly:

The original report's `24` count was an undercount; the exact rerun emitted 26 PASS lines and 3 FAIL lines.

```text
FAIL: AuthRepositoryTest.php - sysadmin must exist: expected true, got false
FAIL: MultiHostAcceptanceReviewTest.php - Symlink fixture could not be prepared.
FAIL: SessionRepositoryTest.php - sysadmin must exist: expected true, got false
```

The Python attempt was `py -m unittest tests/test_demo_fixtures.py`; it exited `1` because PowerShell reported `py: The term 'py' is not recognized...`. No `python`, `python3`, or `py` executable was available.

### Local Demo URL and rendered data

The Local Demo URL is `http://hosting-monitor.local/...`. Unauthenticated HTTP checks returned `302 Location: /login` for `/dashboard`, `/students`, `/system`, `/backup`, and `/manual`; `/login` returned `200`. No credentials were recorded or exposed.

At the authenticated dashboard render for `http://hosting-monitor.local/dashboard`, using the existing registry/cache at `2026-09-30T15:18:35+00:00` UTC (`2026-09-30 22:18:35 +07:00` Asia/Bangkok), the rendered dashboard contained `Total Hosts = 5`, `Total Students = 80`, three `HEALTHY` labels, and two `WARNING` labels. The repository check emitted `repository_hosts=5 repository_students=80`; the rendered check emitted `rendered_hosts=1 rendered_students=1 healthy_labels=3 warning_labels=2` where the boolean `1` means the exact `5`/`80` summary markers were present. This was an authenticated controller render with the existing data; the public browser session correctly remained at `/login` without credentials.

The stale observation was run at `2026-09-30T17:22:21+00:00` UTC (`2026-10-01 00:22:21 +07:00` Asia/Bangkok). It emitted `hosts=5 students=0 states={"STALE":5}`, which is expected after the repository's 180-second freshness limit.

### Route × viewport matrix

The authenticated route renders were checked at the requested viewport sizes. Every cell reported `overflow=false` with `scrollWidth=clientWidth` equal to the viewport width.

| Route | 1440x900 | 1024x900 | 390x844 |
| --- | --- | --- | --- |
| `/dashboard` | Rendered; `overflow=false`, 1440=1440 | Rendered; `overflow=false`, 1024=1024 | Rendered; `overflow=false`, 390=390 |
| `/students` | Rendered; `overflow=false`, 1440=1440 | Rendered; `overflow=false`, 1024=1024 | Rendered; `overflow=false`, 390=390 |
| `/system` | Rendered; `overflow=false`, 1440=1440 | Rendered; `overflow=false`, 1024=1024 | Rendered; `overflow=false`, 390=390 |
| `/backup` | Rendered; `overflow=false`, 1440=1440 | Rendered; `overflow=false`, 1024=1024 | Rendered; `overflow=false`, 390=390 |
| `/manual` | Rendered; `overflow=false`, 1440=1440 | Rendered; `overflow=false`, 1024=1024 | Rendered; `overflow=false`, 390=390 |
| `/login` | Rendered login form; `overflow=false` | Rendered login form; `overflow=false` | Rendered login form; `overflow=false` |

### Interaction evidence

- At `/students`, viewport `390x844`, filling `ค้นหา Student ID หรือ Domain...` with `not-a-real-host` left the row hidden and visibly added `ไม่พบ Account ที่ตรงกับเงื่อนไขการค้นหา`.
- At `/manual`, viewport `390x844`, pressing Tab focused the `D DiGiT Hosting Admin` anchor with `:focus-visible` present.
- At `/manual`, viewport `390x844`, opening the menu produced `sidebar is-open`, `sidebar-backdrop is-visible`, `body.sidebar-open`, and `aria-expanded="true"`. Escape restored `sidebar`, removed the backdrop/body classes, and set `aria-expanded="false"`. A separate backdrop click produced the same closed state.
- The exact browser console noise was one `Failed to load resource: the server responded with a status of 404 (Not Found) @ http://127.0.0.1:8088/favicon.ico:0` on the live login route. Authenticated generated page renders had no console messages.
