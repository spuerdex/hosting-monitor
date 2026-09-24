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

    public function overview(array $user, array $hosts): string
    {
        $cards = '';
        $currentAccounts = 0;
        $excludedHosts = 0;

        foreach ($hosts as $code => $host) {
            $safeName = htmlspecialchars(
                (string) ($host['name'] ?? $code),
                ENT_QUOTES,
                'UTF-8'
            );

            $safeCode = htmlspecialchars(
                (string) $code,
                ENT_QUOTES,
                'UTF-8'
            );

            $safeIp = htmlspecialchars(
                (string) ($host['ip'] ?? '-'),
                ENT_QUOTES,
                'UTF-8'
            );

            $state = htmlspecialchars(
                (string) ($host['display_state'] ?? 'UNAVAILABLE'),
                ENT_QUOTES,
                'UTF-8'
            );

            $lastSuccess = self::formatDateTime(
                $host['last_success'] ?? null
            );

            $href = htmlspecialchars(
                '/dashboard?host=' . rawurlencode((string) $code),
                ENT_QUOTES,
                'UTF-8'
            );

            $isCurrent = is_array($host['status'] ?? null)
                && in_array(
                    $host['display_state'] ?? null,
                    ['HEALTHY', 'WARNING'],
                    true
                )
                && is_int($host['account_count'] ?? null)
                && $host['account_count'] >= 0;

            if ($isCurrent) {
                $currentAccounts += $host['account_count'];

                $accountLabel =
                    $host['account_count'] . ' accounts';
            } else {
                $excludedHosts++;
                $accountLabel = 'Unknown (not current)';
            }

            $cards .= <<<HTML
<article class="surface metric-card">
    <h3><a href="{$href}">{$safeName}</a></h3>
    <p>Host: {$safeCode} | IP: {$safeIp}</p>
    <p>State: {$state}</p>
    <p>Accounts: {$accountLabel}</p>
    <p>Last successful fetch: {$lastSuccess}</p>
</article>
HTML;
        }

        if ($cards === '') {
            $cards = '<p>No enabled hosts available.</p>';
        }

        $content = <<<HTML
<div class="page-heading">
    <div>
        <h2 class="page-title">Monitoring Overview</h2>
        <p class="page-description">
            Read-only status by hosting program
        </p>
    </div>
</div>

<div class="surface metric-card">
    <h3>Current Accounts: {$currentAccounts}</h3>
    <p>Excluded hosts: {$excludedHosts}</p>
    <p>Totals count current host data only; unknown is not zero.</p>
</div>

<div class="metric-grid">
    {$cards}
</div>
HTML;

        return Layout::render(
            title: 'Monitoring Overview',
            active: 'dashboard',
            content: $content,
            user: $user
        );
    }

    public function hostDetail(array $user, array $host): string
    {
        $escape = static fn (mixed $value): string => htmlspecialchars(
            is_scalar($value) ? (string) $value : '-',
            ENT_QUOTES,
            'UTF-8'
        );

        $name = $escape($host['name'] ?? '-');
        $code = $escape($host['code'] ?? '-');
        $ip = $escape($host['ip'] ?? '-');
        $state = $escape($host['display_state'] ?? 'UNAVAILABLE');
        $fetchState = $escape($host['fetch_state'] ?? 'UNKNOWN');

        $lastSuccess = $escape(
            self::formatDateTime($host['last_success'] ?? null)
        );

        $isCurrent = is_array($host['status'] ?? null)
            && in_array(
                $host['display_state'] ?? null,
                ['HEALTHY', 'WARNING'],
                true
            )
            && is_int($host['account_count'] ?? null)
            && $host['account_count'] >= 0;

        $accounts = $isCurrent
            ? $escape($host['account_count'])
            : 'Unknown (not current)';

        $content = <<<HTML
<div class="page-heading">
    <div>
        <h2 class="page-title">Host Detail: {$name}</h2>
        <p class="page-description">
            Read-only hosting monitoring
        </p>
    </div>
</div>

<div class="surface metric-card">
    <p><a href="/dashboard">Back to Monitoring Overview</a></p>
    <h3>{$name}</h3>
    <p>Program: {$code}</p>
    <p>IP Address: {$ip}</p>
    <p>System State: {$state}</p>
    <p>Fetch State: {$fetchState}</p>
    <p>Student Accounts: {$accounts}</p>
    <p>Last successful fetch: {$lastSuccess}</p>
</div>
HTML;

        return Layout::render(
            title: 'Host Detail',
            active: 'dashboard',
            content: $content,
            user: $user
        );
    }

    public function monitoringPage(
        array $user,
        array $hosts,
        mixed $selected = null
    ): string {
        if ($selected === null) {
            return $this->overview($user, $hosts);
        }

        if (
            !is_string($selected)
            || $selected === ''
            || !array_key_exists($selected, $hosts)
        ) {
            throw new \InvalidArgumentException(
                'Invalid host selection.'
            );
        }

        return $this->hostDetail(
            $user,
            $hosts[$selected]
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
