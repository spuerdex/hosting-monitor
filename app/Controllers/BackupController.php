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
        $backup =
            $status['backup']
            ?? [];

        $last =
            $backup['last_backup']
            ?? null;

        $bytes =
            $backup['size_bytes']
            ?? null;

        $available =
            $last !== null;

        $lastLabel =
            self::formatDateTime(
                $last
            );

        $size =
            $bytes !== null
            ? number_format(
                ((int)$bytes)
                / 1048576,
                2
            ) . ' MB'
            : 'ไม่ทราบ';

        $statusText =
            $available
            ? 'Available'
            : 'Unavailable';

        $content = <<<HTML
<div class="page-heading">

<div>
    <h2 class="page-title">
        Backup Overview
    </h2>

    <p class="page-description">
        ตรวจสอบ Backup ล่าสุด
        และนโยบายการสำรองข้อมูล
    </p>
</div>

</div>


<section class="surface backup-hero">

<div>

    <div class="backup-hero-label">
        Backup Status
    </div>

    <h2 class="backup-hero-title">
        {$statusText}
    </h2>

    <p class="backup-hero-sub">
        Last Backup:
        {$lastLabel}
    </p>

</div>


<div class="backup-size">

    <strong>
        {$size}
    </strong>

    <span>
        Latest backup size
    </span>

</div>

</section>


<div class="backup-policy-grid">

<div class="surface policy-card">

    <div class="policy-icon">
        ⏱
    </div>

    <div class="policy-label">
        Automatic Backup
    </div>

    <div class="policy-value">
        ทุกวัน เวลา 02:00 น.
    </div>

</div>


<div class="surface policy-card">

    <div class="policy-icon">
        14
    </div>

    <div class="policy-label">
        Retention
    </div>

    <div class="policy-value">
        14 วัน
    </div>

</div>


<div class="surface policy-card">

    <div class="policy-icon">
        ↻
    </div>

    <div class="policy-label">
        Old Backup Cleanup
    </div>

    <div class="policy-value">
        ทุกวัน เวลา 02:30 น.
    </div>

</div>

</div>


<div class="section">

<div class="surface recent-panel">

    <div class="info-item">

        <span class="info-label">
            Last Backup
        </span>

        <strong class="info-value">
            {$lastLabel}
        </strong>

    </div>

    <div class="info-item">

        <span class="info-label">
            Portal Mode
        </span>

        <strong class="info-value">
            Read-only
        </strong>

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

    public function monitoringPage(
        array $user,
        array $hosts,
        mixed $selectedHost = null
    ): string {
        if ($selectedHost !== null) {
            if (
                !is_string($selectedHost)
                || $selectedHost === ''
                || !array_key_exists(
                    $selectedHost,
                    $hosts
                )
            ) {
                throw new \InvalidArgumentException(
                    'Unknown host selection.'
                );
            }

            $hosts = [
                $selectedHost
                    => $hosts[$selectedHost],
            ];
        }

        $hostsHtml = '';

        foreach ($hosts as $host) {
            if (!is_array($host)) {
                continue;
            }

            $name = self::escapeHtml(
                $host['name']
                ?? $host['code']
                ?? 'Unknown Host'
            );

            $code = self::escapeHtml(
                $host['code']
                ?? ''
            );

            $ip = self::escapeHtml(
                $host['ip']
                ?? ''
            );

            $displayState = self::escapeHtml(
                $host['display_state']
                ?? 'UNAVAILABLE'
            );

            $status =
                $host['status']
                ?? null;

            /*
            |--------------------------------------------------------------------------
            | Non-live host
            |--------------------------------------------------------------------------
            |
            | MultiHostStatusRepository deliberately exposes status => null
            | for stale, unreachable, publishing, or unavailable hosts.
            | Never fall back to another host or legacy status.json here.
            |
            */

            if (!is_array($status)) {
                $hostsHtml .= <<<HTML
<section class="surface monitoring-host-panel backup-host-panel">

<div class="section-heading">

    <div>
        <h2>{$name}</h2>

        <p>
            Host: {$code}
            · {$ip}
        </p>
    </div>

    <span class="status-pill">
        {$displayState}
    </span>

</div>

<div class="surface recent-panel">

    <div class="info-item">

        <span class="info-label">
            Current Backup Data
        </span>

        <strong class="info-value">
            Unavailable
        </strong>

    </div>

    <div class="info-item">

        <span class="info-label">
            Host State
        </span>

        <strong class="info-value">
            {$displayState}
        </strong>

    </div>

</div>

</section>
HTML;

                continue;
            }

            $backup =
                is_array(
                    $status['backup']
                    ?? null
                )
                ? $status['backup']
                : [];

            $last =
                $backup['last_backup']
                ?? null;

            $bytes =
                $backup['size_bytes']
                ?? null;

            $available =
                is_string($last)
                && $last !== '';

            $lastLabel =
                self::formatDateTime(
                    $last
                );

            $size =
                $bytes !== null
                ? number_format(
                    ((int)$bytes)
                    / 1048576,
                    2
                ) . ' MB'
                : 'ไม่ทราบ';

            $statusText =
                $available
                ? 'Available'
                : 'Unavailable';

            $hostsHtml .= <<<HTML
<section class="monitoring-host-panel backup-host-panel">

<div class="section-heading">

    <div>
        <h2>{$name}</h2>

        <p>
            Host: {$code}
            · {$ip}
        </p>
    </div>

    <span class="status-pill">
        {$displayState}
    </span>

</div>


<section class="surface backup-hero">

<div>

    <div class="backup-hero-label">
        Backup Status
    </div>

    <h2 class="backup-hero-title">
        {$statusText}
    </h2>

    <p class="backup-hero-sub">
        Last Backup:
        {$lastLabel}
    </p>

</div>


<div class="backup-size">

    <strong>
        {$size}
    </strong>

    <span>
        Latest backup size
    </span>

</div>

</section>


<div class="backup-policy-grid">

<div class="surface policy-card">

    <div class="policy-icon">
        ⏱
    </div>

    <div class="policy-label">
        Automatic Backup
    </div>

    <div class="policy-value">
        ทุกวัน เวลา 02:00 น.
    </div>

</div>


<div class="surface policy-card">

    <div class="policy-icon">
        14
    </div>

    <div class="policy-label">
        Retention
    </div>

    <div class="policy-value">
        14 วัน
    </div>

</div>


<div class="surface policy-card">

    <div class="policy-icon">
        ↻
    </div>

    <div class="policy-label">
        Old Backup Cleanup
    </div>

    <div class="policy-value">
        ทุกวัน เวลา 02:30 น.
    </div>

</div>

</div>


<div class="section">

<div class="surface recent-panel">

    <div class="info-item">

        <span class="info-label">
            Last Backup
        </span>

        <strong class="info-value">
            {$lastLabel}
        </strong>

    </div>

    <div class="info-item">

        <span class="info-label">
            Host State
        </span>

        <strong class="info-value">
            {$displayState}
        </strong>

    </div>

    <div class="info-item">

        <span class="info-label">
            Portal Mode
        </span>

        <strong class="info-value">
            Read-only
        </strong>

    </div>

</div>

</div>

</section>
HTML;
        }

        if ($hostsHtml === '') {
            $hostsHtml = <<<HTML
<div class="surface recent-panel">
    <strong>
        No monitoring hosts available.
    </strong>
</div>
HTML;
        }

        $content = <<<HTML
<div class="page-heading">

<div>
    <h2 class="page-title">
        Backup Overview
    </h2>

    <p class="page-description">
        ตรวจสอบ Backup ล่าสุด
        แยกตาม Student Hosting Server
    </p>
</div>

<span class="status-pill status-success">
    ● Read-only Monitoring
</span>

</div>

{$hostsHtml}
HTML;

        return Layout::render(
            title: 'Backup',
            active: 'backup',
            content: $content,
            user: $user
        );
    }

    private static function escapeHtml(
        mixed $value
    ): string {
        return htmlspecialchars(
            (string)$value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }

    private static function formatDateTime(
        mixed $value
    ): string {
        if (
            !is_string($value)
            || $value === ''
        ) {
            return 'ยังไม่มีข้อมูล';
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
