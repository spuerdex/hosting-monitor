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
        $summary =
            $status['summary']
            ?? [];

        $storage =
            $status['storage']
            ?? [];

        $backup =
            $status['backup']
            ?? [];

        $overall = htmlspecialchars(
            (string)(
                $status['overall_status']
                ?? 'UNAVAILABLE'
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        $students = (int)(
            $summary['students']
            ?? 0
        );

        $enabled = (int)(
            $summary['enabled']
            ?? 0
        );

        $suspended = (int)(
            $summary['suspended']
            ?? 0
        );

        $warnings = (int)(
            $summary['warnings']
            ?? 0
        );

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

        $studentDisk = max(
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

        $lastBackup =
            self::formatDateTime(
                $backup['last_backup']
                ?? null
            );

        $generated =
            self::formatDateTime(
                $status['generated_at']
                ?? null
            );

        $healthClass =
            strtoupper($overall)
                === 'HEALTHY'
            ? 'status-success'
            : 'status-danger';

        $content = <<<HTML
<div class="page-heading">

<div>
    <h2 class="page-title">
        System Overview
    </h2>

    <p class="page-description">
        ภาพรวมระบบ Student Hosting
        และสถานะการให้บริการล่าสุด
    </p>
</div>

<span class="status-pill {$healthClass}">
    ● {$overall}
</span>

</div>


<div class="metric-grid">

<div class="surface metric-card metric-card-featured">

    <div class="metric-label">
        System Health
    </div>

    <div class="metric-value metric-value-primary">
        {$overall}
    </div>

    <div class="metric-sub">
        Current hosting status
    </div>

</div>


<div class="surface metric-card">

    <div class="metric-label">
        Students
    </div>

    <div class="metric-value">
        {$students}
    </div>

    <div class="metric-sub">
        Hosting accounts
    </div>

</div>


<div class="surface metric-card">

    <div class="metric-label">
        Enabled
    </div>

    <div class="metric-value">
        {$enabled}
    </div>

    <div class="metric-sub">
        Active accounts
    </div>

</div>


<div class="surface metric-card">

    <div class="metric-label">
        Warnings
    </div>

    <div class="metric-value">
        {$warnings}
    </div>

    <div class="metric-sub">
        Suspended {$suspended}
    </div>

</div>

</div>


<div class="dashboard-grid">

<section class="surface system-health-panel">

    <h2 class="panel-title">
        Storage Usage
    </h2>

    <p class="panel-subtitle">
        พื้นที่จัดเก็บของ Hosting Server
    </p>


    <div class="disk-item">

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


    <div class="disk-item">

        <div class="disk-heading">
            <span>Student Disk</span>
            <span>{$studentDisk}%</span>
        </div>

        <div class="progress-track">
            <div
                class="progress-fill progress-fill-success"
                style="width: {$studentDisk}%"
            ></div>
        </div>

    </div>

</section>


<section class="surface recent-panel">

    <h2 class="panel-title">
        Latest Activity
    </h2>

    <p class="panel-subtitle">
        ข้อมูลล่าสุดจาก Hosting Server
    </p>

    <div class="info-stack">

        <div class="info-item">

            <span class="info-label">
                Last Backup
            </span>

            <strong class="info-value">
                {$lastBackup}
            </strong>

        </div>


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
                Portal Mode
            </span>

            <strong class="info-value">
                Read-only Monitoring
            </strong>

        </div>

    </div>

</section>

</div>
HTML;

        return Layout::render(
            title: 'Dashboard',
            active: 'dashboard',
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

            $date = $date->setTimezone(
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
