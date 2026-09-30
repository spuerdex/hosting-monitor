# Multi-host API Monitoring Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** ทำให้ระบบ Monitoring ดึงข้อมูลจาก API ของหลายเว็บไซต์/VM แยกอิสระต่อ host, แสดงผลรวมใน portal เดียว และพัฒนาทดสอบบน Local ได้โดยไม่เชื่อมต่อหรือแก้ไขข้อมูล Production.

**Architecture:** ใช้ Multi-host collector เป็นชั้นกลางระหว่าง Portal กับ API ของแต่ละ VM โดยโหลด host registry, เรียก API แยกต่อ host, ตรวจและแปลง response เป็น schema กลาง แล้วเขียน cache และ metadata แยกตาม host. PHP Portal อ่าน cache ที่ผ่านการ validate แล้วเท่านั้น; Local ใช้ Mock API หลาย endpoint และ fixture ที่ไม่ใช่ข้อมูลจริง.

**Tech Stack:** PHP 8.3, Python 3, MariaDB เฉพาะ authentication/session, JSON API, file-based per-host cache, systemd timer, Nginx/PHP-FPM, PHPUnit-style PHP tests และ Python `unittest`.

**Spec:** `docs/superpowers/specs/2026-09-02-hosting-admin-ui-design.md` และข้อกำหนด Multi-host จากผู้ใช้ในบทสนทนานี้.

## Global Constraints

- Monitoring phase ต้องเป็น read-only ต่อ Hosting data และห้ามมี browser action สำหรับ create, suspend, enable, reset password, quota, backup หรือ delete.
- API ของแต่ละ host ต้องล้มเหลวแยกจากกัน; host หนึ่ง timeout หรือ response เสียต้องไม่ทำให้ host อื่นหายจากผลลัพธ์.
- ห้ามใช้ข้อมูลจาก host หนึ่งแทนข้อมูลของอีก host และต้องเก็บ cache/metadata แยกตาม `host_code`.
- Student identity ในข้อมูลรวมต้องถือว่า `(host_code, student_id)` เป็นขอบเขตอ้างอิง ไม่ใช่ `student_id` อย่างเดียว.
- Secret, API token, private key และข้อมูลจริงของนักศึกษาไม่เก็บใน Git, mock fixture หรือหน้าเว็บ.
- PHP Portal ต้องอ่าน normalized cache ไม่เรียก API ของทุก VM ใน request เดียวโดยตรง.
- Local, staging และ production ต้องเลือก provider/endpoint ผ่าน configuration ไม่ใช้โค้ด branch เฉพาะ environment.
- ต้องคง behavior ที่ข้อมูล stale/unknown ไม่ถูกนับเป็นศูนย์หรือถูกแสดงเป็น current.
- ห้ามแก้ schema หรือข้อมูล production จนกว่าจะมีคำสั่งแยกสำหรับงานนั้น.

## Review Focus

- API ของ host หนึ่ง timeout: ต้องเห็น host อื่นตามปกติ และ host ที่ล่มเป็น `UNREACHABLE` โดยคง `last_success` เดิม.
- API ตอบ JSON ผิด schema หรือเกินขนาด: ต้องไม่เขียนทับ cache ล่าสุดที่ถูกต้อง.
- Student ID ซ้ำข้าม VM: ต้องแสดงเป็นคนละ record ตาม `host_code`.
- Registry มี host ซ้ำ, code ผิดรูปแบบ, URL ผิด หรือ secret อ้างอิงไม่ได้: ต้อง reject ก่อนเริ่มเขียน cache ใด ๆ.
- Collector ถูกหยุดระหว่าง publish: ต้องไม่ทำให้ PHP อ่าน partial JSON หรือใช้ข้อมูลที่กำลัง publish เป็น current.

---

### Task 1: Define Multi-host API Contract and Environment Configuration

**Files:**
- Create: `docs/api/multi-host-status-v1.md`
- Create: `config/hosts.local.example.json`
- Modify: `config/hosts.example.json`
- Modify: `app/Support/Config.php`
- Test: `tests/ConfigTest.php`

**Interfaces:**
- Consumes: existing environment key/value loader and existing host registry shape.
- Produces: API contract schema v1, host fields `code`, `name`, `base_url`, `enabled`, `timeout_seconds`, and secret reference fields that never contain secret values.

- [ ] **Step 1: Write the API contract**

  ระบุ endpoint, HTTP method, authentication header convention, timeout expectation, response fields, timestamp format, status values และ maximum payload size. กำหนดว่า student records ใช้ `student_id` ภายใน host scope.

- [ ] **Step 2: Add Local-only registry example**

  ใช้ endpoint เช่น `http://127.0.0.1:9001/api/status` และ `http://127.0.0.1:9002/api/status`; ห้ามใส่ token จริง.

- [ ] **Step 3: Define configuration validation tests**

  ทดสอบ missing endpoint, invalid URL scheme, duplicate code, invalid timeout, disabled host และ secret reference ที่เป็นค่าเปล่า.

- [ ] **Step 4: Run the configuration tests**

  Run: `php tests/ConfigTest.php`

  Expected: PASS บน PHP 8.3.

- [ ] **Step 5: Commit**

  ```bash
  git add docs/api/multi-host-status-v1.md config/hosts.local.example.json config/hosts.example.json app/Support/Config.php tests/ConfigTest.php
  git commit -m "docs: define multi-host API contract and configuration"
  ```

### Task 2: Add Provider Interfaces and Local Mock API

**Files:**
- Create: `ops/api_provider.py`
- Create: `ops/mock_api_server.py`
- Create: `fixtures/hosts/cs/status.json`
- Create: `fixtures/hosts/it/status.json`
- Test: `tests/test_api_provider.py`
- Test: `tests/test_mock_api_server.py`

**Interfaces:**
- Consumes: Task 1 contract and per-host registry entry.
- Produces: `fetch_status(host: dict) -> dict`, injectable HTTP transport, and a Local mock endpoint serving fixture data only.

- [ ] **Step 1: Write provider tests**

  ทดสอบ successful response, timeout, non-2xx response, invalid JSON, oversized body, missing token reference และ ensure request target uses the selected host only.

- [ ] **Step 2: Run tests to verify they fail**

  Run: `python -m unittest tests.test_api_provider tests.test_mock_api_server -v`

  Expected: FAIL because provider and mock server interfaces do not exist.

- [ ] **Step 3: Implement the provider**

  ใช้ HTTP client ที่กำหนด timeout และ response-size limit, ปิด redirect ไปยังปลายทางที่ไม่ได้อยู่ใน host configuration และคืน raw document ให้ขั้นตอน validation ถัดไป.

- [ ] **Step 4: Implement the mock server**

  ให้ route ตาม host fixture, รองรับ simulated delay/error ผ่าน Local configuration และห้ามอ่าน production path หรือ secret.

- [ ] **Step 5: Run tests to verify they pass**

  Run: `python -m unittest tests.test_api_provider tests.test_mock_api_server -v`

  Expected: PASS.

- [ ] **Step 6: Commit**

  ```bash
  git add ops/api_provider.py ops/mock_api_server.py fixtures/hosts tests/test_api_provider.py tests/test_mock_api_server.py
  git commit -m "feat: add multi-host API provider and local mock"
  ```

### Task 3: Normalize and Validate Per-host Status Documents

**Files:**
- Create: `ops/status_contract.py`
- Modify: `ops/status_fetcher.py`
- Modify: `ops/ssh_adapter.py`
- Test: `tests/test_status_contract.py`
- Test: `tests/StatusFetcherTest.py`

**Interfaces:**
- Consumes: API documents from Task 2 and existing legacy SSH documents.
- Produces: `validate_status(document: dict) -> dict` and `normalize_status(document: dict, host: dict) -> dict` with schema version 1.

- [ ] **Step 1: Write validation tests**

  ทดสอบ required keys, exact status values, ISO-8601 timestamps, object/list types, student row shape, quota numeric bounds, backup fields และ payload limit.

- [ ] **Step 2: Run tests to verify they fail**

  Run: `python -m unittest tests.test_status_contract -v`

  Expected: FAIL for the new contract functions.

- [ ] **Step 3: Implement normalization and validation**

  แปลง response ของ API ให้เป็น schema กลาง, เพิ่ม `host_code` ใน normalized document และไม่ trust summary count หากนับจาก list ได้โดยตรง.

- [ ] **Step 4: Preserve legacy compatibility explicitly**

  ให้ SSH adapter เรียก validator เดียวกัน แต่แยก legacy path ออกจาก API provider และบันทึกไว้ในเอกสารว่าใช้เพื่อ compatibility เท่านั้น.

- [ ] **Step 5: Run tests**

  Run: `python -m unittest tests.test_status_contract tests.StatusFetcherTest -v`

  Expected: PASS.

- [ ] **Step 6: Commit**

  ```bash
  git add ops/status_contract.py ops/status_fetcher.py ops/ssh_adapter.py tests/test_status_contract.py tests/StatusFetcherTest.py
  git commit -m "feat: normalize and validate host status documents"
  ```

### Task 4: Implement Multi-host Collection and Atomic Publication

**Files:**
- Modify: `ops/multi_host_fetcher.py`
- Modify: `ops/multi_host_entrypoint.py`
- Modify: `ops/multi_host_cli.py`
- Test: `tests/test_multi_host_fetcher.py`
- Test: `tests/test_multi_host_entrypoint.py`
- Test: `tests/test_partial_write_publication.py`

**Interfaces:**
- Consumes: `fetch_status` and normalization from Tasks 2–3.
- Produces: `fetch_all(registry_path: Path, cache_dir: Path, fetch: Callable, now: Callable) -> dict[str, str]` with per-host result states.

- [ ] **Step 1: Add failing multi-host tests**

  ทดสอบหลาย host สำเร็จ, host หนึ่งล้มเหลว, timeout isolation, malformed payload, oversized payload, empty enabled registry และ invalid registry ที่ต้องไม่เขียน cache.

- [ ] **Step 2: Run focused tests**

  Run: `python -m unittest tests.test_multi_host_fetcher tests.test_multi_host_entrypoint tests.test_partial_write_publication -v`

  Expected: FAIL เฉพาะ behavior ที่เปลี่ยนจาก SSH-only เป็น API provider หรือ schema ใหม่.

- [ ] **Step 3: Implement per-host orchestration**

  ดึงข้อมูลแยก host, catch failure ต่อ host, เขียน `cache/<code>.json` และ `cache/metadata/<code>.json`, preserve last successful timestamp และไม่เขียน cache ใหม่เมื่อ validation ล้มเหลว.

- [ ] **Step 4: Keep publication atomic**

  เขียน temporary file ใน directory เดียวกัน, flush/fsync, replace atomically และใช้ metadata state `PUBLISHING` ก่อน cache กับ `SUCCESS` หลัง cache สำเร็จ.

- [ ] **Step 5: Run focused and full Python tests**

  Run: `python -m unittest discover -s tests -p "test*.py" -v`

  Expected: PASS.

- [ ] **Step 6: Commit**

  ```bash
  git add ops/multi_host_fetcher.py ops/multi_host_entrypoint.py ops/multi_host_cli.py tests/test_multi_host_fetcher.py tests/test_multi_host_entrypoint.py tests/test_partial_write_publication.py
  git commit -m "feat: collect and publish status independently per host"
  ```

### Task 5: Wire PHP Portal to Multi-host Normalized Cache

**Files:**
- Modify: `app/Hosting/MultiHostStatusRepository.php`
- Modify: `public/index.php`
- Modify: `app/Controllers/DashboardController.php`
- Modify: `app/Controllers/StudentsController.php`
- Modify: `app/Controllers/SystemController.php`
- Modify: `app/Controllers/BackupController.php`
- Test: `tests/MultiHostStatusRepositoryTest.php`
- Test: `tests/MonitoringDashboardTest.php`
- Test: `tests/MultiHostStudentsTest.php`
- Test: `tests/MultiHostSystemBackupTest.php`

**Interfaces:**
- Consumes: per-host registry/cache contract from Tasks 1 and 4.
- Produces: read-only PHP view model keyed by `host_code`; controllers do not call remote API or write Hosting data.

- [ ] **Step 1: Add view-model tests**

  ทดสอบ host list, selected host, duplicate student IDs across hosts, stale/unreachable state, zero students versus unavailable และ host isolation.

- [ ] **Step 2: Run tests to establish baseline**

  Run: `php tests/run.php`

  Expected: baseline result บน PHP 8.3; ห้ามใช้ผล PHP 7.4 ตัดสินความถูกต้องของ source นี้.

- [ ] **Step 3: Update repository contract only where needed**

  อ่าน registry แบบ exact schema, reject symlink/path traversal/oversized JSON และ expose unavailable host without borrowing another host's cache.

- [ ] **Step 4: Keep controllers read-only**

  ใช้ normalized view model, escape output, scope links/filter by `host_code`, และไม่เพิ่ม POST management action.

- [ ] **Step 5: Run PHP tests and syntax checks**

  Run: `php -l public/index.php` และ `php tests/run.php`

  Expected: no syntax errors and all tests PASS บน PHP 8.3.

- [ ] **Step 6: Commit**

  ```bash
  git add app/Hosting/MultiHostStatusRepository.php public/index.php app/Controllers tests
  git commit -m "feat: display normalized multi-host monitoring data"
  ```

### Task 6: Add Local Developer Workflow and Safe Fixtures

**Files:**
- Create: `docs/local-development.md`
- Create: `scripts/start-local-mock-api.ps1`
- Create: `scripts/run-local-collector.ps1`
- Create: `fixtures/README.md`
- Modify: `.gitignore`
- Test: `tests/LocalWorkflowTest.php`

**Interfaces:**
- Consumes: Local registry, mock API and collector from Tasks 1–4.
- Produces: reproducible Local workflow with no production credentials or paths.

- [ ] **Step 1: Write workflow checks**

  ตรวจว่า Local config ชี้เฉพาะ `127.0.0.1`, cache อยู่ใน local temporary directory และ fixture ไม่มี secret-like fields.

- [ ] **Step 2: Implement PowerShell helpers**

  ให้ start mock API หลาย port, run collector แบบ explicit cache path และ stop ได้โดยไม่แตะ `/etc`, production cache หรือ database จริง.

- [ ] **Step 3: Document commands and failure simulation**

  ระบุวิธีเปิด/ปิด host, จำลอง timeout, malformed response และดู per-host cache.

- [ ] **Step 4: Run Local workflow test**

  Run: `php tests/LocalWorkflowTest.php` และ manual smoke test ตาม `docs/local-development.md`.

  Expected: dashboard แสดงอย่างน้อยสอง host และ host ที่จำลองล้มเหลวไม่กระทบ host อื่น.

- [ ] **Step 5: Commit**

  ```bash
  git add docs/local-development.md scripts fixtures .gitignore tests/LocalWorkflowTest.php
  git commit -m "docs: add safe local multi-host development workflow"
  ```

### Task 7: Staging and Production Readiness

**Files:**
- Create: `docs/deployment/staging-checklist.md`
- Create: `docs/deployment/production-checklist.md`
- Modify: `ops/systemd/digit-hosting-admin-fetch.service`
- Modify: `ops/systemd/digit-hosting-admin-fetch.timer`
- Modify: `ops/nginx/hosting-admin-php.conf`
- Modify: `ops/php-fpm/hosting-admin.conf`
- Test: `tests/MultiHostAcceptanceReviewTest.php`

**Interfaces:**
- Consumes: stable collector/cache/view contracts from Tasks 1–6.
- Produces: deployment checklist and acceptance evidence for staging before production.

- [ ] **Step 1: Define staging acceptance cases**

  ครอบคลุม all-host success, partial outage, stale cache, bad registry, API auth failure, collector restart, timer overlap และ permissions.

- [ ] **Step 2: Add service hardening checks**

  ยืนยัน least privilege, read-only SSH config, write-only intended cache path, no production secret in logs และ no overlapping collectors.

- [ ] **Step 3: Run acceptance tests**

  Run: `php tests/MultiHostAcceptanceReviewTest.php` และ Python full suite บน runtime ที่ production รองรับ.

  Expected: PASS และมีหลักฐานว่า host ที่ล้มเหลวไม่ทำให้ cache ของ host อื่นเสียหาย.

- [ ] **Step 4: Perform staging smoke test**

  ใช้ staging VM/API เท่านั้น, ตรวจทุกหน้า dashboard/students/system/backup และตรวจ timestamps/status ต่อ host.

- [ ] **Step 5: Commit**

  ```bash
  git add docs/deployment ops tests/MultiHostAcceptanceReviewTest.php
  git commit -m "chore: document multi-host staging and production readiness"
  ```

## Self-review

- API contract, provider, normalization, collector, cache publication, PHP display, Local workflow และ deployment readiness มี task รองรับครบ.
- ไม่มี task ใดเพิ่ม Management API หรือแก้ Hosting data; management ถูกกันไว้เป็น phase ถัดไป.
- Failure modes ทั้งห้าข้อใน Review Focus ถูกผูกกับ test ใน Task 2–7.
- ทุก task มีผลลัพธ์ที่ทดสอบแยกได้ และลำดับ interface สอดคล้องกันตั้งแต่ registry → provider → normalized document → cache → PHP view.
- งานที่เปลี่ยนหลาย subsystem ถูกแบ่งเป็นงานย่อยตาม boundary เพื่อให้ review และ rollback ได้ง่าย.
