# Hosting Monitor UI Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** ปรับ DiGiT Hosting Admin ให้เป็น multi-host monitoring console ที่สแกนสถานะ 5 VM และข้อมูลนักศึกษาได้เร็ว สม่ำเสมอ และใช้งานได้ดีบน Desktop, Tablet และ Mobile โดยไม่แตะระบบจัดการข้อมูล

**Architecture:** คง PHP server-rendered controllers และ CSS/vanilla JS เดิมไว้ เพิ่ม presentation markup ที่จำเป็นใน shared layout และหน้า monitoring แล้วรวม design tokens/responsive rules ใน `public/assets/css/app.css`. JavaScript จะดูแลเฉพาะ interaction ฝั่ง UI เช่น sidebar, filter, copy feedback และ refresh-state feedback; ข้อมูลยังไหลจาก repository/API เดิมโดยไม่มี command ใหม่

**Tech Stack:** PHP 8.3, server-rendered HTML, CSS custom properties/grid, vanilla JavaScript, existing PHP test runner, Docker Python fixture checks

**Spec:** `docs/superpowers/specs/2026-09-30-hosting-monitor-ui-redesign-design.md`

## Global Constraints

- ห้ามแก้ Database schema/records, authentication/session behavior, API provider, collector, cache contract หรือ host registry logic
- ห้ามเพิ่ม browser action หรือ command ที่ create, suspend, enable, reset password, quota, backup หรือ delete
- ใช้ existing PHP/CSS/vanilla JS stack และไม่เพิ่ม frontend framework/dependency
- Status ต้องสื่อสารด้วยข้อความหรือ icon/label ร่วมกับสี ไม่ใช้สีอย่างเดียว
- ต้องรองรับ 1440px desktop, 1024px tablet และ 390px mobile
- ต้องรักษา Thai document language และ technical English labels ที่มีอยู่
- ต้องเคารพ `prefers-reduced-motion: reduce`

## Review Focus

- Duplicate student IDs across hosts: student presentation must show host identity; test in Students rendering.
- Warning/unavailable/stale hosts: state must remain visible and text-labelled; test in Dashboard/System/Backup rendering.
- Long host/domain labels: layout must not overflow or become unreadable; test with long fixture values and CSS class hooks.
- Mobile navigation and keyboard focus: menu closes on Escape and focus styles remain visible; test JS behavior/static hooks.
- Empty/no-match data: Students and host panels must show explicit empty states; test controller output for empty collections.

---

### Task 1: Establish UI test seams and shared design tokens

**Files:**
- Modify: `public/assets/css/app.css`
- Modify: `public/assets/js/app.js`
- Test: `tests/LayoutTest.php`
- Test: `tests/UiV2Test.php`
- Test: `tests/FrontendAccessibilityTest.php` (create)

**Interfaces:**
- Consumes: current `Layout::render()` shell hooks and existing data attributes.
- Produces: stable CSS classes/data attributes for responsive shell, status labels, refresh state, focus state and reduced-motion behavior.

- [ ] **Step 1: Write failing static/UI seam tests**

  Add assertions that the rendered shell includes `data-sidebar`, `data-sidebar-toggle`, `data-sidebar-backdrop`, a read-only monitoring marker, a refresh/status marker, and visible `aria-current`/focus-capable controls. Add CSS source assertions for status text treatment and `prefers-reduced-motion`.

- [ ] **Step 2: Run the focused tests to verify they fail**

  Run: `C:\wamp64\bin\php\php8.3.14\php.exe tests/run.php`

  Expected: the new assertions fail because the refresh marker/accessibility hooks and CSS behavior are not yet present.

- [ ] **Step 3: Implement the shared tokens and interaction seams**

  Add a compact token layer for canvas, rail, surfaces, borders, text, status colors, spacing, radii and shadows. Add reduced-motion rules and visible `:focus-visible` states. Keep existing selectors compatible while introducing shared utility/status classes. Extend `app.js` only where needed for a live UI refresh marker, safe sidebar close behavior and non-color-only copy feedback.

- [ ] **Step 4: Run focused tests to verify they pass**

  Run: `C:\wamp64\bin\php\php8.3.14\php.exe tests/run.php`

  Expected: new shared-shell/accessibility assertions pass; report any unrelated baseline failures by name.

- [ ] **Step 5: Commit**

  ```powershell
  git add public/assets/css/app.css public/assets/js/app.js tests/LayoutTest.php tests/UiV2Test.php tests/FrontendAccessibilityTest.php
  git commit -m "refactor: establish hosting monitor UI tokens"
  ```

### Task 2: Redesign the shared shell and responsive navigation

**Files:**
- Modify: `app/Views/Layout.php`
- Modify: `public/assets/css/app.css`
- Modify: `public/assets/js/app.js`
- Test: `tests/LayoutTest.php`
- Test: `tests/FrontendAccessibilityTest.php`

**Interfaces:**
- Consumes: page title, active route, authenticated user, existing navigation links.
- Produces: shell with consistent monitoring context, active route semantics, responsive sidebar, user identity and logout controls.

- [ ] **Step 1: Add failing shell assertions**

  Assert that Layout output contains a monitoring context block, host-count/status hooks, a last-refresh label, `aria-label`/`aria-controls` for the mobile menu, and an accessible logout button.

- [ ] **Step 2: Run tests and verify the expected failure**

  Run: `C:\wamp64\bin\php\php8.3.14\php.exe tests/run.php`

- [ ] **Step 3: Update `Layout::render()` presentation markup**

  Add only presentation-level shell elements and data hooks. Preserve route URLs, active keys, logout method/action, user values and existing icon functions. Use semantic `<header>`, `<nav>`, `<main>` and labelled controls; do not add data writes or route changes.

- [ ] **Step 4: Style and verify desktop/mobile shell**

  Tune sidebar/topbar/footer spacing, active state, status context, mobile drawer/backdrop and focus states in CSS/JS. Verify no horizontal overflow at 390px and the Escape/backdrop close path leaves `body` usable.

- [ ] **Step 5: Run tests and commit**

  Run: `C:\wamp64\bin\php\php8.3.14\php.exe tests/run.php`

  ```powershell
  git add app/Views/Layout.php public/assets/css/app.css public/assets/js/app.js tests/LayoutTest.php tests/FrontendAccessibilityTest.php
  git commit -m "feat: refine responsive monitoring shell"
  ```

### Task 3: Build the multi-host Dashboard health matrix

**Files:**
- Modify: `app/Controllers/DashboardController.php`
- Modify: `public/assets/css/app.css`
- Test: `tests/DashboardControllerTest.php`
- Test: `tests/MonitoringDashboardTest.php`
- Test: `tests/MonitoringSelectionTest.php`

**Interfaces:**
- Consumes: existing normalized host records from `MultiHostStatusRepository`, including `display_state`, `account_count`, `status`, `last_success` and selected host behavior.
- Produces: aggregate metrics and host matrix markup; no changes to repository output or API payload.

- [ ] **Step 1: Add failing Dashboard render tests**

  Assert output contains total host count, healthy/warning/unavailable labels, total student count, host code/name, account count, storage signal, last-success label and text status for warning/unavailable/stale records. Add an empty-host assertion.

- [ ] **Step 2: Run focused tests and verify failure**

  Run: `C:\wamp64\bin\php\php8.3.14\php.exe tests/run.php`

- [ ] **Step 3: Implement presentation-only Dashboard sections**

  Keep existing selected-host route and summary calculations. Add a host matrix/card region that safely escapes host fields and derives aggregate display metrics from already-normalized records. Do not trust or rewrite API summary data outside the existing status document.

- [ ] **Step 4: Style dashboard density and state hierarchy**

  Add responsive host-card grid, status indicator with text, storage progress treatment, backup freshness metadata and warning emphasis. Ensure five demo hosts fit at desktop/tablet and stack cleanly on mobile.

- [ ] **Step 5: Run focused tests and commit**

  Run: `C:\wamp64\bin\php\php8.3.14\php.exe tests/run.php`

  ```powershell
  git add app/Controllers/DashboardController.php public/assets/css/app.css tests/DashboardControllerTest.php tests/MonitoringDashboardTest.php tests/MonitoringSelectionTest.php
  git commit -m "feat: add multi-host dashboard health matrix"
  ```

### Task 4: Redesign Students with host-aware responsive presentation

**Files:**
- Modify: `app/Controllers/StudentsController.php`
- Modify: `public/assets/css/app.css`
- Modify: `public/assets/js/app.js`
- Test: `tests/StudentsControllerTest.php`
- Test: `tests/MultiHostStudentsTest.php`

**Interfaces:**
- Consumes: existing flattened/selected student arrays and current search/status filter hooks.
- Produces: host-aware rows/cards with status, domain, PHP pool and quota presentation; no new mutation controls.

- [ ] **Step 1: Add failing Students assertions**

  Assert host label/code, accessible table/card labels, quota progress hooks, explicit empty/no-match hooks and preserved search/status filter IDs. Include duplicate student IDs from two hosts and assert both host identities remain visible.

- [ ] **Step 2: Run tests to verify failure**

  Run: `C:\wamp64\bin\php\php8.3.14\php.exe tests/run.php`

- [ ] **Step 3: Update Students markup**

  Add host badge, status label, quota progress semantics and empty/no-match containers while preserving links, escaping and existing filter behavior. Keep domain links read-only and avoid introducing management actions.

- [ ] **Step 4: Implement responsive table/card behavior and filter feedback**

  Use CSS to keep desktop table density while switching to stacked row cards at narrow widths. Update JS to expose visible result count/no-match feedback without changing the filtering data source.

- [ ] **Step 5: Run tests and commit**

  Run: `C:\wamp64\bin\php\php8.3.14\php.exe tests/run.php`

  ```powershell
  git add app/Controllers/StudentsController.php public/assets/css/app.css public/assets/js/app.js tests/StudentsControllerTest.php tests/MultiHostStudentsTest.php
  git commit -m "feat: improve host-aware student monitoring UI"
  ```

### Task 5: Make System and Backup pages scanable by host

**Files:**
- Modify: `app/Controllers/SystemController.php`
- Modify: `app/Controllers/BackupController.php`
- Modify: `public/assets/css/app.css`
- Test: `tests/SystemControllerTest.php`
- Test: `tests/BackupControllerTest.php`
- Test: `tests/MultiHostSystemBackupTest.php`

**Interfaces:**
- Consumes: existing per-host status documents and selected-host routing.
- Produces: service × host presentation, storage threshold treatment, backup freshness/availability presentation; no backup action.

- [ ] **Step 1: Add failing render assertions**

  Assert System output contains host identity for each displayed host, service state text, root/student storage labels and threshold hooks. Assert Backup output contains host identity, freshness/availability text, last backup and size; include unavailable/stale cases.

- [ ] **Step 2: Run tests and verify failure**

  Run: `C:\wamp64\bin\php\php8.3.14\php.exe tests/run.php`

- [ ] **Step 3: Update System presentation**

  Add a host section/matrix around the existing service and storage values. Reuse existing normalized status data and safely escape labels. Use explicit `Running`, `Unavailable`, `Stale` text.

- [ ] **Step 4: Update Backup presentation**

  Add per-host backup cards with freshness class/text and preserve the read-only policy copy. Do not render action buttons that imply backup mutation.

- [ ] **Step 5: Style and commit**

  Add responsive grids, threshold states and long-label handling, then run the PHP suite.

  ```powershell
  git add app/Controllers/SystemController.php app/Controllers/BackupController.php public/assets/css/app.css tests/SystemControllerTest.php tests/BackupControllerTest.php tests/MultiHostSystemBackupTest.php
  git commit -m "feat: clarify system and backup host status"
  ```

### Task 6: Refine Manual and Login presentation

**Files:**
- Modify: `app/Controllers/ManualController.php`
- Modify: `app/Controllers/AuthController.php`
- Modify: `public/assets/css/app.css`
- Modify: `public/assets/js/app.js` (only if copy/focus feedback needs shared behavior)
- Test: `tests/ManualControllerTest.php`
- Test: `tests/AuthControllerTest.php`

**Interfaces:**
- Consumes: existing read-only manual commands and login form flow.
- Produces: consistent command cards, safe copy feedback and product-family-consistent login screen without auth behavior changes.

- [ ] **Step 1: Add failing presentation assertions**

  Assert Manual includes read-only/security notice, labelled command blocks and copy feedback hooks. Assert Login includes labelled username/password controls, error region semantics and consistent brand/status hooks.

- [ ] **Step 2: Run tests and verify failure**

  Run: `C:\wamp64\bin\php\php8.3.14\php.exe tests/run.php`

- [ ] **Step 3: Update presentation markup only**

  Preserve form method/action/name fields, command text and existing security copy. Add semantic labels/regions and UI hooks; do not change authentication validation or command content.

- [ ] **Step 4: Style and commit**

  Tune command-card density, login hierarchy, error/focus states and mobile layout. Respect reduced motion.

  ```powershell
  git add app/Controllers/ManualController.php app/Controllers/AuthController.php public/assets/css/app.css public/assets/js/app.js tests/ManualControllerTest.php tests/AuthControllerTest.php
  git commit -m "feat: polish manual and login surfaces"
  ```

### Task 7: Responsive visual QA and regression verification

**Files:**
- Modify: `public/assets/css/app.css` (only fixes found during QA)
- Modify: `public/assets/js/app.js` (only fixes found during QA)
- Test: `tests/UiV2Test.php`
- Test: `tests/FrontendAccessibilityTest.php`
- Create: `docs/superpowers/verification/2026-09-30-hosting-monitor-ui-redesign.md`

**Interfaces:**
- Consumes: completed page markup and Local Demo data (`5 hosts / 80 students`).
- Produces: verified visual/responsive checklist and documented exceptions.

- [ ] **Step 1: Run syntax and application tests**

  Run:

  ```powershell
  C:\wamp64\bin\php\php8.3.14\php.exe -l app/Views/Layout.php
  C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/DashboardController.php
  C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/StudentsController.php
  C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/SystemController.php
  C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/BackupController.php
  C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/ManualController.php
  C:\wamp64\bin\php\php8.3.14\php.exe -l app/Controllers/AuthController.php
  C:\wamp64\bin\php\php8.3.14\php.exe tests/run.php
  ```

- [ ] **Step 2: Verify Local Demo data path**

  Confirm the repository returns `5 hosts`, `80 students`, and visible `HEALTHY`/`WARNING` states from `config/hosts.local.example.json` and `.local-cache/`.

- [ ] **Step 3: Capture visual checks**

  Use the local WAMP site at `/dashboard`, `/students`, `/system`, `/backup`, `/manual`, and `/login` at 1440px, 1024px and 390px. Check overflow, focus, menu close, long domain/host labels, no-result state and status text.

- [ ] **Step 4: Record verification results**

  Write the checked pages, viewport sizes, test commands, pass/fail results and any baseline environment failures in `docs/superpowers/verification/2026-09-30-hosting-monitor-ui-redesign.md`.

- [ ] **Step 5: Final commit**

  ```powershell
  git add public/assets/css/app.css public/assets/js/app.js tests/UiV2Test.php tests/FrontendAccessibilityTest.php docs/superpowers/verification/2026-09-30-hosting-monitor-ui-redesign.md
  git commit -m "test: verify hosting monitor UI redesign"
  ```
