<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Controllers;

final class StudentsController
{
    public function page(
        array $user,
        array $students
    ): string {
        $name = htmlspecialchars(
            $user['display_name']
                ?? $user['username']
                ?? 'Admin',
            ENT_QUOTES,
            'UTF-8'
        );

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
                (string)($student['status'] ?? 'unknown')
            );

            $statusLabel = match ($status) {
                'enabled' => 'Enabled',
                'suspended' => 'Suspended',
                default => 'Unknown',
            };

            $statusClass = match ($status) {
                'enabled' => 'enabled',
                'suspended' => 'suspended',
                default => 'unknown',
            };

            $quota = $student['quota'] ?? [];

            $used = (int)($quota['used_mb'] ?? 0);
            $soft = (int)($quota['soft_mb'] ?? 0);
            $hard = (int)($quota['hard_mb'] ?? 0);

            $php = $this->healthBadge(
                (bool)($student['php_pool'] ?? false)
            );

            $nginx = $this->healthBadge(
                (bool)($student['nginx_config'] ?? false)
                && (
                    $status === 'suspended'
                    || (bool)($student['nginx_enabled'] ?? false)
                )
            );

            $database = $this->healthBadge(
                (bool)($student['database'] ?? false)
            );

            $credential = $this->healthBadge(
                (bool)($student['credential_exists'] ?? false)
            );

            $rows .= <<<HTML
<tr data-student-row>
    <td>
        <strong>{$id}</strong>
    </td>

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

    <td>
        {$used} MB
    </td>

    <td>
        {$soft} MB
    </td>

    <td>
        {$hard} MB
    </td>

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
    <td colspan="10" class="empty">
        ยังไม่มีข้อมูลนักศึกษา
    </td>
</tr>
HTML;
        }

        $count = count($students);

        return <<<HTML
<!doctype html>
<html lang="th">

<head>
<meta charset="utf-8">
<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>
Students - DiGiT Hosting Admin
</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family:
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;

    background: #f5f5f5;
    color: #333;
}

header {
    background: #333;
    color: #fff;

    padding: 16px 28px;

    display: flex;
    align-items: center;
    justify-content: space-between;
}

.brand {
    font-size: 20px;
    font-weight: 700;
}

nav {
    display: flex;
    align-items: center;
    gap: 18px;
}

nav a {
    color: #ddd;
    text-decoration: none;
}

nav a.active {
    color: #fff;
    font-weight: 700;
}

.logout {
    border: 0;
    background: #F15927;
    color: #fff;

    padding: 9px 14px;
    border-radius: 8px;

    cursor: pointer;
}

main {
    max-width: 1500px;
    margin: auto;
    padding: 28px;
}

.page-head {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: end;

    margin-bottom: 22px;
}

h1 {
    margin: 0 0 5px;
    font-size: 27px;
}

.muted {
    color: #777;
}

.search {
    width: min(360px, 100%);
}

.search input {
    width: 100%;

    padding: 11px 14px;

    border: 1px solid #ddd;
    border-radius: 10px;

    font-size: 15px;
    background: #fff;
}

.table-card {
    background: #fff;
    border-radius: 15px;
    overflow: hidden;

    box-shadow:
        0 5px 20px rgba(0,0,0,.06);
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    white-space: nowrap;
}

th,
td {
    padding: 14px 16px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

th {
    background: #fafafa;
    color: #666;
    font-size: 13px;
}

td {
    font-size: 14px;
}

tbody tr:hover {
    background: #fffaf8;
}

a {
    color: #d94d1f;
}

.badge {
    display: inline-flex;
    align-items: center;

    padding: 5px 9px;

    border-radius: 999px;

    font-size: 12px;
    font-weight: 700;
}

.badge.enabled {
    color: #167542;
    background: #eaf8f0;
}

.badge.suspended {
    color: #a73920;
    background: #fff0ec;
}

.badge.unknown {
    color: #666;
    background: #eee;
}

.health-ok {
    font-weight: 700;
    color: #168347;
}

.health-bad {
    font-weight: 700;
    color: #c33d26;
}

.empty {
    text-align: center;
    color: #999;
    padding: 40px;
}

@media (max-width: 760px) {
    header {
        align-items: flex-start;
        gap: 15px;
        flex-direction: column;
    }

    .page-head {
        align-items: stretch;
        flex-direction: column;
    }

    .search {
        width: 100%;
    }
}

</style>
</head>

<body>

<header>

<div class="brand">
DiGiT Hosting Admin
</div>

<nav>

<a href="/dashboard">
Dashboard
</a>

<a
    href="/students"
    class="active"
>
Students
</a>

<form
    method="post"
    action="/logout"
>
<button
    type="submit"
    class="logout"
>
ออกจากระบบ
</button>
</form>

</nav>

</header>

<main>

<div class="page-head">

<div>
<h1>นักศึกษา</h1>

<div class="muted">
{$count} Hosting Accounts · ผู้ดูแล {$name}
</div>
</div>

<div class="search">
<input
    id="student-search"
    type="search"
    placeholder="ค้นหา Student ID หรือ Domain..."
    autocomplete="off"
>
</div>

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

<tbody id="student-table-body">
{$rows}
</tbody>

</table>

</div>
</div>

</main>

<script>
const search = document.getElementById(
    'student-search'
);

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
                    && !row.textContent
                        .toLowerCase()
                        .includes(query);
            });
    }
);
</script>

</body>
</html>
HTML;
    }


    private function healthBadge(
        bool $healthy
    ): string {
        return $healthy
            ? '<span class="health-ok">✓</span>'
            : '<span class="health-bad">✕</span>';
    }
}
