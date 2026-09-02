<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Controllers;

use Digit\HostingAdmin\Views\Layout;

final class BackupController
{
    public function page(
        array $user,
        array $status
    ): string {
        $backup = $status['backup'] ?? [];

        $last = $backup['last_backup']
            ?? null;

        $bytes = $backup['size_bytes']
            ?? null;

        $available = $last !== null;

        $lastLabel = htmlspecialchars(
            (string)(
                $last
                ?? 'ยังไม่มีข้อมูล'
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        $size = $bytes !== null
            ? number_format(
                ((int)$bytes) / 1048576,
                2
            ) . ' MB'
            : 'ไม่ทราบ';

        $statusBadge = $available
            ? '<span class="badge badge-ok">Available</span>'
            : '<span class="badge badge-bad">Unavailable</span>';

        $content = <<<HTML
<div class="page-header">
<h1 class="page-title">Backup</h1>

<p class="page-description">
สถานะ Backup ของ Student Hosting
</p>
</div>

<div class="cards">

<div class="card">
<div class="card-label">Backup Status</div>
<div class="card-value">
{$statusBadge}
</div>
</div>

<div class="card">
<div class="card-label">Backup Size</div>
<div class="card-value">{$size}</div>
</div>

</div>

<div class="section">

<div class="card info-list">

<div class="info-row">
<span>Last Backup</span>
<strong>{$lastLabel}</strong>
</div>

<div class="info-row">
<span>Automatic Backup</span>
<strong>ทุกวัน เวลา 02:00 น.</strong>
</div>

<div class="info-row">
<span>Retention</span>
<strong>14 วัน</strong>
</div>

<div class="info-row">
<span>Old Backup Cleanup</span>
<strong>ทุกวัน เวลา 02:30 น.</strong>
</div>

<div class="info-row">
<span>Portal Mode</span>
<strong>Read-only</strong>
</div>

</div>

</div>
HTML;

        return Layout::render(
            title: 'Backup',
            active: 'backup',
            content: $content,
            user: $user
        );
    }
}
