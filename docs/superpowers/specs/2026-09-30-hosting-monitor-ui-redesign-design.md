# Hosting Monitor UI Redesign Design

## Purpose

ปรับหน้าจอ Local และ Production-ready UI ของ DiGiT Hosting Admin ให้เป็น Monitoring Console สำหรับผู้ดูแลระบบที่ดูภาพรวมหลาย VM ได้เร็ว อ่านสถานะผิดปกติได้ทันที และใช้งานได้สม่ำเสมอทั้ง Desktop, Tablet และ Mobile โดยไม่เปลี่ยนแปลงระบบจัดการข้อมูลหรือ API contract

## Design direction

ใช้ visual direction แบบ **Operations Control Room**:

- พื้นฐานเป็น warm light canvas เพื่อให้ข้อมูลจำนวนมากอ่านง่าย
- ใช้ charcoal rail/top-level surfaces เป็น anchor ของระบบ
- ใช้ orange เป็น brand accent และ action accent เท่าที่จำเป็น
- ใช้ semantic status colors อย่างสม่ำเสมอ: green = healthy, amber = warning, red = unavailable/error, slate = stale/unknown
- ใช้ typography hierarchy ที่ชัดเจนระหว่าง page title, metric, host name, label และ metadata
- ใช้ density ระดับ admin console: compact แต่มี whitespace พอให้ scan ได้เร็ว
- Signature moment คือ host health matrix ที่ทำให้เห็นสถานะของหลาย VM ในภาพเดียว

## Scope

### In scope

- Shared application shell: sidebar, topbar, footer, active state, user profile, mobile navigation
- Dashboard overview และ multi-host health presentation
- Students list, filters, host labels, status and quota presentation
- System service and storage presentation
- Backup status presentation
- Manual/read-only command cards
- Login screen visual consistency
- CSS tokens, responsive breakpoints, focus states, hover states and reduced-motion behavior
- Small presentation-only JavaScript improvements such as filter feedback, menu state and copy feedback

### Out of scope

- Database schema or records
- Authentication/session behavior
- Host registry validation
- API provider, collector, cache or status contract
- Student create/suspend/reset/quota/backup/delete actions
- Production host configuration
- New external UI dependencies

## Page requirements

### Shared shell

- Sidebar must identify the active route with both visual treatment and `aria-current`.
- Topbar must retain page title, authenticated admin identity and logout action.
- Shell must expose a consistent monitoring context such as host count and read-only monitoring label where available.
- Mobile navigation must be keyboard reachable, closable with Escape, and not leave the page scroll-locked after closing.
- All interactive controls need visible focus styling.

### Dashboard

- Lead with aggregate metrics: total hosts, healthy hosts, warning/unavailable hosts, and total students.
- Provide a host health matrix/card region showing every enabled host, its display state, account count, storage signal and last successful collection.
- Warning and unavailable states must be visually stronger than healthy states without relying on color alone.
- Keep storage and backup summaries visible without requiring navigation.
- Support selected-host detail without changing the underlying status data.

### Students

- Preserve search and status filtering.
- Add host identity to every student row/card so duplicate student IDs remain understandable across hosts.
- Keep student status, domain, PHP pool health and quota readable at desktop width.
- At narrow widths, convert the row presentation to an accessible card or stacked layout instead of forcing unreadable horizontal content.
- Show explicit empty and no-match states.

### System

- Present service health by host so a failing service can be located quickly.
- Present root and student storage with percentage, label and threshold treatment.
- Use the same host naming and status language as Dashboard.

### Backup

- Present backup freshness and availability per host.
- Show last backup timestamp and size with a clear unavailable/stale treatment.
- Retain read-only policy information and avoid implying that a backup action is available.

### Manual and Login

- Keep Manual explicitly read-only and security-scoped.
- Make command cards easy to scan and copy, with non-color-only copy feedback.
- Make Login visually part of the same product family, while keeping the authentication flow unchanged.

## Component and styling decisions

- Consolidate colors, spacing, radii, shadows, typography sizes and status tokens in `:root` CSS variables.
- Prefer existing server-rendered PHP structure and CSS over introducing a frontend framework.
- Add semantic wrappers/classes only where the current markup cannot express host context or responsive layout cleanly.
- Use CSS grid for overview/matrix layouts and CSS container behavior for dense content.
- Use `clamp()` only where it improves fluid type/spacing without hurting predictable admin layouts.
- Use motion sparingly for page entrance, sidebar transitions and copy feedback; respect `prefers-reduced-motion: reduce`.

## Accessibility and resilience

- Maintain Thai document language and readable English technical labels where the existing product uses them.
- Ensure text and status indicators meet usable contrast; status must include text or icon/label, not color alone.
- Preserve focus order and keyboard operation for menu, filters, links, buttons and copy controls.
- Avoid layout breakage with long domain names, host names and translated labels.
- Treat unavailable or stale monitoring data as a first-class UI state rather than an empty success state.

## Verification criteria

- All existing PHP tests remain passing except documented pre-existing environment-only failures.
- New/updated UI tests assert shared shell hooks, host matrix markup, host identity in student presentation, and status labels.
- Visual checks cover at least 1440px desktop, 1024px tablet and 390px mobile widths.
- Dashboard shows the Local Demo data as 5 hosts and 80 students without data-layer changes.
- Students, System, Backup and Manual pages remain reachable and readable after the redesign.
- No UI action sends a management command or mutates hosting/student data.
