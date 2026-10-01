<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Controllers;

use Digit\HostingAdmin\Views\Layout;

final class StudentsController
{
    public function page(
        array $user,
        array $students
    ): string {
        $rows = '';

        $enabledCount = 0;
        $suspendedCount = 0;

        foreach ($students as $student) {
            $hostCode = htmlspecialchars(
                (string) ($student['host_code'] ?? 'local'),
                ENT_QUOTES,
                'UTF-8'
            );

            $hostName = htmlspecialchars(
                (string) ($student['host_name'] ?? 'Current Host'),
                ENT_QUOTES,
                'UTF-8'
            );

            $id = htmlspecialchars(
                (string)(
                    $student['student_id']
                    ?? ''
                ),
                ENT_QUOTES,
                'UTF-8'
            );

            $domainRaw = (string) ($student['domain'] ?? '');
            $domainCell = $this->domainCell($domainRaw);

            $status = strtolower(
                (string)(
                    $student['status']
                    ?? 'unknown'
                )
            );

            $statusAttr = htmlspecialchars(
                $status,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );

            if ($status === 'enabled') {
                $enabledCount++;
            }

            if ($status === 'suspended') {
                $suspendedCount++;
            }

            $statusLabel = match ($status) {
                'enabled' => 'Enabled',
                'suspended' => 'Suspended',
                default => 'Unknown',
            };

            $statusClass = match ($status) {
                'enabled' => 'status-success',
                'suspended' => 'status-danger',
                default => 'status-warning',
            };

            $quota =
                $student['quota']
                ?? [];

            $used = (int)(
                $quota['used_mb']
                ?? 0
            );

            $soft = (int)(
                $quota['soft_mb']
                ?? 0
            );

            $hard = (int)(
                $quota['hard_mb']
                ?? 0
            );

            $php = $this->healthBadge(
                (bool)(
                    $student['php_pool']
                    ?? false
                )
            );

            $nginxHealthy =
                (bool)(
                    $student['nginx_config']
                    ?? false
                )
                &&
                (
                    $status === 'suspended'
                    ||
                    (bool)(
                        $student['nginx_enabled']
                        ?? false
                    )
                );

            $nginx =
                $this->healthBadge(
                    $nginxHealthy
                );

            $database =
                $this->healthBadge(
                    (bool)(
                        $student['database']
                        ?? false
                    )
                );

            $credential =
                $this->healthBadge(
                    (bool)(
                        $student[
                            'credential_exists'
                        ]
                        ?? false
                    )
                );

            $quotaProgress = $this->quotaProgress(
                $used,
                $soft,
                $hard
            );

            $rows .= <<<HTML
<tr
    data-student-row
    data-status="{$statusAttr}"
>

<td data-label="Host" data-host-label>
{$hostName} ({$hostCode})
</td>

<td data-label="Student ID" class="student-id">
{$id}
</td>

<td data-label="Domain">
{$domainCell}
</td>

<td data-label="Status">
<span class="status-pill {$statusClass}">
{$statusLabel}
</span>
</td>

<td data-label="Quota" class="student-quota" colspan="3">
{$quotaProgress}
</td>

<td data-label="PHP">{$php}</td>
<td data-label="Nginx">{$nginx}</td>
<td data-label="DB">{$database}</td>
<td data-label="Credential">{$credential}</td>

</tr>
HTML;
        }

        if ($rows === '') {
            $rows = <<<HTML
<tr>
<td
    colspan="11"
    class="empty"
    data-students-empty
>
ยังไม่มีข้อมูลนักศึกษา
</td>
</tr>
HTML;
        }

        $count = count(
            $students
        );

        $content = <<<HTML
<div class="page-heading">

<div>
    <h2 class="page-title">
        Student Accounts
    </h2>

    <p class="page-description">
        ตรวจสอบ Hosting Account,
        Quota และสถานะบริการของนักศึกษา
    </p>
</div>

</div>


<div class="student-summary">

<div class="surface student-summary-card">
    <small>ทั้งหมด</small>
    <strong>{$count}</strong>
</div>

<div class="surface student-summary-card">
    <small>Enabled</small>
    <strong>{$enabledCount}</strong>
</div>

<div class="surface student-summary-card">
    <small>Suspended</small>
    <strong>{$suspendedCount}</strong>
</div>

</div>


<section class="surface">

<div class="student-toolbar">

    <div class="student-search-wrap">

        <span class="search-icon">
            ⌕
        </span>

        <input
            id="student-search"
            class="student-search"
            type="search"
            placeholder="ค้นหา Student ID หรือ Domain..."
            autocomplete="off"
        >

    </div>


    <select
        id="student-status-filter"
        class="student-status-filter"
        aria-label="กรองสถานะ"
    >
        <option value="all">
            ทุกสถานะ
        </option>

        <option value="enabled">
            Enabled
        </option>

        <option value="suspended">
            Suspended
        </option>
    </select>

</div>


<div class="table-responsive">

<table class="students-table">

<thead>
<tr>
    <th>Host</th>
    <th>Student ID</th>
    <th>Domain</th>
    <th>Status</th>
    <th colspan="3">Quota</th>
    <th>PHP</th>
    <th>Nginx</th>
    <th>DB</th>
    <th>Credential</th>
</tr>
</thead>

<tbody>
{$rows}
</tbody>

</table>

<p class="student-no-match" data-student-no-match hidden>
    ไม่พบ Account ที่ตรงกับเงื่อนไขการค้นหา
</p>

</div>

</section>
HTML;

        return Layout::render(
            title: 'Students',
            active: 'students',
            content: $content,
            user: $user
        );
    }

    public function monitoringPage(
        array $user,
        array $hosts,
        mixed $selected = 'all'
    ): string {
        if (
            !is_string($selected)
            || $selected === ''
            || (
                $selected !== 'all'
                && !array_key_exists($selected, $hosts)
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid host selection.'
            );
        }

        $rows = '';
        $enabledCount = 0;
        $suspendedCount = 0;
        $accountCount = 0;
        $excludedHosts = 0;
        $excludedDetails = [];

        foreach ($hosts as $hostCode => $host) {
            if (
                $selected !== 'all'
                && $selected !== $hostCode
            ) {
                continue;
            }

            $statusPayload = $host['status'] ?? null;
            $displayState = $host['display_state'] ?? 'UNAVAILABLE';

            $isCurrent =
                is_array($statusPayload)
                && in_array(
                    $displayState,
                    ['HEALTHY', 'WARNING'],
                    true
                )
                && isset($statusPayload['students'])
                && is_array($statusPayload['students']);

            if (!$isCurrent) {
                $excludedHosts++;

                $excludedName = htmlspecialchars(
                    (string) ($host['name'] ?? $hostCode),
                    ENT_QUOTES,
                    'UTF-8'
                );

                $excludedCode = htmlspecialchars(
                    (string) $hostCode,
                    ENT_QUOTES,
                    'UTF-8'
                );

                $excludedState = htmlspecialchars(
                    (string) $displayState,
                    ENT_QUOTES,
                    'UTF-8'
                );

                $excludedDetails[] =
                    $excludedName
                    . ' ('
                    . $excludedCode
                    . '): '
                    . $excludedState;

                continue;
            }

            $hostName = htmlspecialchars(
                (string) ($host['name'] ?? $hostCode),
                ENT_QUOTES,
                'UTF-8'
            );

            $hostCodeSafe = htmlspecialchars(
                (string) $hostCode,
                ENT_QUOTES,
                'UTF-8'
            );

            foreach ($statusPayload['students'] as $student) {
                if (!is_array($student)) {
                    continue;
                }

                $accountCount++;

                $id = htmlspecialchars(
                    (string) ($student['student_id'] ?? ''),
                    ENT_QUOTES,
                    'UTF-8'
                );

                $username = htmlspecialchars(
                    (string) ($student['username'] ?? ''),
                    ENT_QUOTES,
                    'UTF-8'
                );

                $domainRaw = (string) ($student['domain'] ?? '');

                $domain = htmlspecialchars(
                    $domainRaw,
                    ENT_QUOTES,
                    'UTF-8'
                );

                $status = strtolower(
                    (string) ($student['status'] ?? 'unknown')
                );

                if ($status === 'enabled') {
                    $enabledCount++;
                }

                if ($status === 'suspended') {
                    $suspendedCount++;
                }

                $statusAttr = htmlspecialchars(
                    $status,
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                );

                $statusLabel = match ($status) {
                    'enabled' => 'Enabled',
                    'suspended' => 'Suspended',
                    default => 'Unknown',
                };

                $statusClass = match ($status) {
                    'enabled' => 'status-success',
                    'suspended' => 'status-danger',
                    default => 'status-warning',
                };

                $quota = is_array($student['quota'] ?? null)
                    ? $student['quota']
                    : [];

                $used = (int) ($quota['used_mb'] ?? 0);
                $soft = (int) ($quota['soft_mb'] ?? 0);
                $hard = (int) ($quota['hard_mb'] ?? 0);

                $php = $this->healthBadge(
                    (bool) ($student['php_pool'] ?? false)
                );

                $nginxHealthy =
                    (bool) ($student['nginx_config'] ?? false)
                    && (
                        $status === 'suspended'
                        || (bool) ($student['nginx_enabled'] ?? false)
                    );

                $nginx = $this->healthBadge($nginxHealthy);

                $database = $this->healthBadge(
                    (bool) ($student['database'] ?? false)
                );

                $credential = $this->healthBadge(
                    (bool) ($student['credential_exists'] ?? false)
                );

                $quotaProgress = $this->quotaProgress(
                    $used,
                    $soft,
                    $hard
                );
                $domainCell = $this->domainCell($domainRaw);

                $rows .= <<<HTML
<tr
    data-student-row
    data-status="{$statusAttr}"
    data-host="{$hostCodeSafe}"
>
<td data-label="Host" data-host-label>{$hostName} ({$hostCodeSafe})</td>
<td data-label="Student ID" class="student-id">{$id}</td>
<td data-label="Username">{$username}</td>
<td data-label="Domain">{$domainCell}</td>
<td data-label="Status">
<span class="status-pill {$statusClass}">
{$statusLabel}
</span>
</td>
<td data-label="Quota" class="student-quota" colspan="3">{$quotaProgress}</td>
<td data-label="PHP">{$php}</td>
<td data-label="Nginx">{$nginx}</td>
<td data-label="DB">{$database}</td>
<td data-label="Credential">{$credential}</td>
</tr>
HTML;
            }
        }

        if ($rows === '') {
            $rows = <<<HTML
<tr>
<td colspan="12" class="empty" data-students-empty>
ยังไม่มีข้อมูล Account ที่พร้อมใช้งาน
</td>
</tr>
HTML;
        }

        $hostLinks = '<a href="/students">All Hosts</a>';

        foreach ($hosts as $hostCode => $host) {
            $safeCode = htmlspecialchars(
                (string) $hostCode,
                ENT_QUOTES,
                'UTF-8'
            );

            $safeName = htmlspecialchars(
                (string) ($host['name'] ?? $hostCode),
                ENT_QUOTES,
                'UTF-8'
            );

            $href = htmlspecialchars(
                '/students?host=' . rawurlencode((string) $hostCode),
                ENT_QUOTES,
                'UTF-8'
            );

            $hostLinks .= ' | '
                . '<a href="' . $href . '">'
                . $safeName
                . ' (' . $safeCode . ')'
                . '</a>';
        }

        $excludedNotice = '';

        if ($excludedDetails !== []) {
            $excludedNotice =
                '<div class="surface metric-card">'
                . '<strong>Excluded Hosts:</strong> '
                . implode(' | ', $excludedDetails)
                . '</div>';
        }

        $content = <<<HTML
<div class="page-heading">
<div>
    <h2 class="page-title">Student Accounts</h2>
    <p class="page-description">
        Read-only Account Monitoring แยกตาม Hosting Host
    </p>
</div>
</div>

<div class="student-summary">
<div class="surface student-summary-card">
    <small>Accounts</small>
    <strong>{$accountCount}</strong>
</div>
<div class="surface student-summary-card">
    <small>Enabled</small>
    <strong>{$enabledCount}</strong>
</div>
<div class="surface student-summary-card">
    <small>Suspended</small>
    <strong>{$suspendedCount}</strong>
</div>
<div class="surface student-summary-card">
    <small>Excluded Hosts</small>
    <strong>{$excludedHosts}</strong>
</div>
</div>

{$excludedNotice}

<section class="surface">

<div class="student-toolbar">
    <div>{$hostLinks}</div>

    <div class="student-search-wrap">
        <span class="search-icon">⌕</span>
        <input
            id="student-search"
            class="student-search"
            type="search"
            placeholder="ค้นหา Student ID, Username หรือ Domain..."
            autocomplete="off"
        >
    </div>

    <select
        id="student-status-filter"
        class="student-status-filter"
        aria-label="กรองสถานะ"
    >
        <option value="all">ทุกสถานะ</option>
        <option value="enabled">Enabled</option>
        <option value="suspended">Suspended</option>
    </select>
</div>

<div class="table-responsive">
<table class="students-table">
<thead>
<tr>
    <th>Host</th>
    <th>Student ID</th>
    <th>Username</th>
    <th>Domain</th>
    <th>Status</th>
    <th colspan="3">Quota</th>
    <th>PHP</th>
    <th>Nginx</th>
    <th>DB</th>
    <th>Credential</th>
</tr>
</thead>
<tbody>
{$rows}
</tbody>
</table>

<p class="student-no-match" data-student-no-match hidden>
    ไม่พบ Account ที่ตรงกับเงื่อนไขการค้นหา
</p>
</div>

</section>
HTML;

        return Layout::render(
            title: 'Student Accounts',
            active: 'students',
            content: $content,
            user: $user
        );
    }

    private function domainCell(string $domainRaw): string
    {
        $domain = htmlspecialchars(
            $domainRaw,
            ENT_QUOTES,
            'UTF-8'
        );

        if (
            $domainRaw === ''
            || preg_match('/^[A-Za-z0-9.-]+$/D', $domainRaw) !== 1
            || str_contains($domainRaw, '..')
        ) {
            return $domain;
        }

        $href = htmlspecialchars(
            'https://' . $domainRaw,
            ENT_QUOTES,
            'UTF-8'
        );

        return <<<HTML
<a
    class="student-domain"
    href="{$href}"
    target="_blank"
    rel="noopener noreferrer"
>{$domain}</a>
HTML;
    }

    private function quotaProgress(
        int $used,
        int $soft,
        int $hard
    ): string {
        $maximum = max(0, $hard);
        $usedLabel = max(0, $used);
        $value = min($usedLabel, $maximum);
        $softLabel = max(0, $soft);
        $hardLabel = max(0, $hard);
        $percent = $maximum > 0
            ? (int) round(($value / $maximum) * 100)
            : 0;

        return <<<HTML
<div
    class="quota-progress"
    data-quota-progress
    data-quota-used="{$usedLabel}"
    data-quota-soft="{$softLabel}"
    data-quota-hard="{$hardLabel}"
    role="progressbar"
    aria-valuemin="0"
    aria-valuemax="{$hardLabel}"
    aria-valuenow="{$value}"
    aria-label="Quota used {$usedLabel} MB of {$hardLabel} MB"
>
    <span class="quota-progress-label">
        {$usedLabel} MB used · {$softLabel} MB soft · {$hardLabel} MB hard
    </span>
    <span class="quota-progress-track" aria-hidden="true">
        <span
            class="quota-progress-fill"
            style="width: {$percent}%"
        ></span>
    </span>
</div>
HTML;
    }

    private function healthBadge(
        bool $healthy
    ): string {
        if ($healthy) {
            return <<<HTML
<span
    class="health-check health-check-ok"
    title="OK"
>
✓
</span>
HTML;
        }

        return <<<HTML
<span
    class="health-check health-check-bad"
    title="Unavailable"
>
×
</span>
HTML;
    }
}
