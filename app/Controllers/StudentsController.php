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

        foreach ($students as $student) {
            $id = htmlspecialchars(
                (string)($student['student_id'] ?? ''),
                ENT_QUOTES,
                'UTF-8'
            );

            $domain = htmlspecialchars(
                (string)($student['domain'] ?? ''),
                ENT_QUOTES,
                'UTF-8'
            );

            $status = strtolower(
                (string)(
                    $student['status']
                    ?? 'unknown'
                )
            );

            $statusLabel = match ($status) {
                'enabled' => 'Enabled',
                'suspended' => 'Suspended',
                default => 'Unknown',
            };

            $statusClass = match ($status) {
                'enabled' => 'badge-enabled',
                'suspended' => 'badge-suspended',
                default => 'badge-neutral',
            };

            $quota = $student['quota'] ?? [];

            $used = (int)(
                $quota['used_mb'] ?? 0
            );

            $soft = (int)(
                $quota['soft_mb'] ?? 0
            );

            $hard = (int)(
                $quota['hard_mb'] ?? 0
            );

            $php = $this->healthBadge(
                (bool)(
                    $student['php_pool']
                    ?? false
                )
            );

            $nginx = $this->healthBadge(
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
                )
            );

            $database = $this->healthBadge(
                (bool)(
                    $student['database']
                    ?? false
                )
            );

            $credential = $this->healthBadge(
                (bool)(
                    $student['credential_exists']
                    ?? false
                )
            );

            $rows .= <<<HTML
<tr data-student-row>

<td><strong>{$id}</strong></td>

<td>
<a
    href="https://{$domain}"
    target="_blank"
    rel="noopener noreferrer"
>
{$domain}
</a>
</td>

<td>
<span class="badge {$statusClass}">
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

        $count = count($students);

        $content = <<<HTML
<div class="page-header-row">

<div>
<h1 class="page-title">นักศึกษา</h1>

<p class="page-description">
{$count} Hosting Accounts
</p>
</div>

<input
    id="student-search"
    class="search-box"
    type="search"
    placeholder="ค้นหา Student ID หรือ Domain..."
    autocomplete="off"
>

</div>

<div class="table-card">
<div class="table-wrap">

<table>

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
</div>

<script>
const search =
    document.getElementById(
        'student-search'
    );

if (search) {
    search.addEventListener(
        'input',
        function () {
            const query =
                this.value
                    .trim()
                    .toLowerCase();

            document
                .querySelectorAll(
                    '[data-student-row]'
                )
                .forEach(function (row) {
                    row.hidden =
                        query !== ''
                        &&
                        !row.textContent
                            .toLowerCase()
                            .includes(query);
                });
        }
    );
}
</script>
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
        return $healthy
            ? '<span class="health-ok">✓</span>'
            : '<span class="health-bad">✕</span>';
    }
}
