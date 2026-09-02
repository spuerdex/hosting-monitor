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
            $id = htmlspecialchars(
                (string)(
                    $student['student_id']
                    ?? ''
                ),
                ENT_QUOTES,
                'UTF-8'
            );

            $domain = htmlspecialchars(
                (string)(
                    $student['domain']
                    ?? ''
                ),
                ENT_QUOTES,
                'UTF-8'
            );

            $status = strtolower(
                (string)(
                    $student['status']
                    ?? 'unknown'
                )
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

            $rows .= <<<HTML
<tr
    data-student-row
    data-status="{$status}"
>

<td class="student-id">
{$id}
</td>

<td>
<a
    class="student-domain"
    href="https://{$domain}"
    target="_blank"
    rel="noopener noreferrer"
>
{$domain}
</a>
</td>

<td>
<span class="status-pill {$statusClass}">
{$statusLabel}
</span>
</td>

<td>{$used} MB</td>
<td>{$soft} MB</td>
<td>{$hard} MB</td>

<td>{$php}</td>
<td>{$nginx}</td>
<td>{$database}</td>
<td>{$credential}</td>

</tr>
HTML;
        }

        if ($rows === '') {
            $rows = <<<HTML
<tr>
<td
    colspan="10"
    class="empty"
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
    <th>Student ID</th>
    <th>Domain</th>
    <th>Status</th>
    <th>Used</th>
    <th>Soft</th>
    <th>Hard</th>
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
