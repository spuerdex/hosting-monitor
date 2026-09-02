<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Controllers;

use Digit\HostingAdmin\Views\Layout;

final class SystemController
{
    public function page(
        array $user,
        array $status
    ): string {
        $services = $status['services'] ?? [];
        $storage = $status['storage'] ?? [];

        $labels = [
            'nginx' => 'Nginx',
            'php_fpm' => 'PHP-FPM',
            'mariadb' => 'MariaDB',
            'ssh' => 'SSH',
            'ufw' => 'UFW',
        ];

        $serviceHtml = '';

        foreach ($labels as $key => $label) {
            $running = (bool)(
                $services[$key] ?? false
            );

            $badge = $running
                ? '<span class="badge badge-ok">Running</span>'
                : '<span class="badge badge-bad">Unavailable</span>';

            $serviceHtml .= <<<HTML
<div class="card service">
<span class="service-name">{$label}</span>
{$badge}
</div>
HTML;
        }

        $root = (int)(
            $storage['root']['used_percent']
            ?? 0
        );

        $student = (int)(
            $storage['student']['used_percent']
            ?? 0
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
<h1 class="page-title">System</h1>

<p class="page-description">
สถานะบริการของ Student Hosting Server
</p>
</div>

<div class="service-grid">
{$serviceHtml}
</div>

<div class="section">

<h2 class="section-title">
Storage
</h2>

<div class="cards">

<div class="card">
<div class="card-label">Root Disk</div>
<div class="card-value">{$root}%</div>
</div>

<div class="card">
<div class="card-label">Student Disk</div>
<div class="card-value">{$student}%</div>
</div>

</div>

</div>

<div class="section">

<div class="card info-list">

<div class="info-row">
<span>Status Updated</span>
<strong>{$generated}</strong>
</div>

<div class="info-row">
<span>Mode</span>
<strong>Read-only Monitoring</strong>
</div>

</div>

</div>
HTML;

        return Layout::render(
            title: 'System',
            active: 'system',
            content: $content,
            user: $user
        );
    }
}
