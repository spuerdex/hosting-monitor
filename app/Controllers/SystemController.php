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
        $services =
            $status['services']
            ?? [];

        $storage =
            $status['storage']
            ?? [];

        $labels = [
            'nginx' => 'Nginx',
            'php_fpm' => 'PHP-FPM',
            'mariadb' => 'MariaDB',
            'ssh' => 'SSH',
            'ufw' => 'UFW',
        ];

        $serviceHtml = '';

        foreach (
            $labels as $key => $label
        ) {
            $running = (bool)(
                $services[$key]
                ?? false
            );

            $indicatorClass =
                $running
                ? 'service-indicator-up'
                : 'service-indicator-down';

            $state =
                $running
                ? 'Running'
                : 'Unavailable';

            $serviceHtml .= <<<HTML
<div class="surface service-card">

    <div class="service-head">

        <span class="service-name">
            {$label}
        </span>

        <span
            class="service-indicator {$indicatorClass}"
        ></span>

    </div>

    <div class="service-state">
        {$state}
    </div>

</div>
HTML;
        }

        $root = max(
            0,
            min(
                100,
                (int)(
                    $storage['root']
                        ['used_percent']
                    ?? 0
                )
            )
        );

        $student = max(
            0,
            min(
                100,
                (int)(
                    $storage['student']
                        ['used_percent']
                    ?? 0
                )
            )
        );

        $generated =
            self::formatDateTime(
                $status['generated_at']
                ?? null
            );

        $content = <<<HTML
<div class="page-heading">

<div>
    <h2 class="page-title">
        Server Services
    </h2>

    <p class="page-description">
        สถานะ Service และ Storage
        ของ Student Hosting Server
    </p>
</div>

<span class="status-pill status-success">
    ● Read-only Monitoring
</span>

</div>


<div class="service-status-grid">
{$serviceHtml}
</div>


<div class="section">

<div class="section-heading">
    <h2>Storage</h2>

    <p>
        Disk usage
    </p>
</div>

<div class="storage-grid">

<div class="surface storage-card">

    <div class="disk-heading">
        <span>Root Disk</span>
        <span>{$root}%</span>
    </div>

    <div class="progress-track">
        <div
            class="progress-fill"
            style="width: {$root}%"
        ></div>
    </div>

</div>


<div class="surface storage-card">

    <div class="disk-heading">
        <span>Student Disk</span>
        <span>{$student}%</span>
    </div>

    <div class="progress-track">
        <div
            class="progress-fill progress-fill-success"
            style="width: {$student}%"
        ></div>
    </div>

</div>

</div>

</div>


<div class="section">

<div class="surface recent-panel">

    <div class="info-item">

        <span class="info-label">
            Status Updated
        </span>

        <strong class="info-value">
            {$generated}
        </strong>

    </div>

    <div class="info-item">

        <span class="info-label">
            Monitoring Mode
        </span>

        <strong class="info-value">
            Read-only
        </strong>

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

    private static function formatDateTime(
        mixed $value
    ): string {
        if (
            !is_string($value)
            || $value === ''
        ) {
            return 'ไม่มีข้อมูล';
        }

        try {
            $date =
                new \DateTimeImmutable(
                    $value
                );

            $date =
                $date->setTimezone(
                    new \DateTimeZone(
                        'Asia/Bangkok'
                    )
                );

            return $date->format(
                'd/m/Y H:i'
            ) . ' น.';
        } catch (\Throwable) {
            return htmlspecialchars(
                $value,
                ENT_QUOTES,
                'UTF-8'
            );
        }
    }
}
