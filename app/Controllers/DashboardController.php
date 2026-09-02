<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Controllers;

use Digit\HostingAdmin\Views\Layout;

final class DashboardController
{
    public function page(
        array $user,
        array $status
    ): string {
        $summary = $status['summary'] ?? [];
        $storage = $status['storage'] ?? [];
        $backup = $status['backup'] ?? [];

        $overall = htmlspecialchars(
            (string)(
                $status['overall_status']
                ?? 'UNAVAILABLE'
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        $students = (int)(
            $summary['students'] ?? 0
        );

        $enabled = (int)(
            $summary['enabled'] ?? 0
        );

        $suspended = (int)(
            $summary['suspended'] ?? 0
        );

        $warnings = (int)(
            $summary['warnings'] ?? 0
        );

        $root = (int)(
            $storage['root']['used_percent']
            ?? 0
        );

        $studentDisk = (int)(
            $storage['student']['used_percent']
            ?? 0
        );

        $lastBackup = htmlspecialchars(
            (string)(
                $backup['last_backup']
                ?? 'ยังไม่มีข้อมูล'
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        $generated = htmlspecialchars(
            (string)(
                $status['generated_at']
                ?? 'ไม่ทราบ'
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        $content = <<<HTML
<div class="page-header">
<h1 class="page-title">Dashboard</h1>
<p class="page-description">
ภาพรวมระบบ Student Hosting
</p>
</div>

<div class="cards">

<div class="card">
<div class="card-label">สถานะระบบ</div>
<div class="card-value orange">{$overall}</div>
</div>

<div class="card">
<div class="card-label">นักศึกษาทั้งหมด</div>
<div class="card-value">{$students}</div>
</div>

<div class="card">
<div class="card-label">Enabled</div>
<div class="card-value">{$enabled}</div>
</div>

<div class="card">
<div class="card-label">Suspended</div>
<div class="card-value">{$suspended}</div>
</div>

<div class="card">
<div class="card-label">Warnings</div>
<div class="card-value">{$warnings}</div>
</div>

<div class="card">
<div class="card-label">Root Disk</div>
<div class="card-value">{$root}%</div>
</div>

<div class="card">
<div class="card-label">Student Disk</div>
<div class="card-value">{$studentDisk}%</div>
</div>

</div>

<div class="section">

<h2 class="section-title">
ข้อมูลล่าสุด
</h2>

<div class="card info-list">

<div class="info-row">
<span>Last Backup</span>
<strong>{$lastBackup}</strong>
</div>

<div class="info-row">
<span>Status Updated</span>
<strong>{$generated}</strong>
</div>

</div>

</div>
HTML;

        return Layout::render(
            title: 'Dashboard',
            active: 'dashboard',
            content: $content,
            user: $user
        );
    }
}
