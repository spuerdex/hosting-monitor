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

        $host =
            $status['host']
            ?? [];

        $program = htmlspecialchars(
            (string)(
                $host['program_code']
                ?? '-'
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        $hostname = htmlspecialchars(
            (string)(
                $host['hostname']
                ?? '-'
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        $hostIp = htmlspecialchars(
            (string)(
                $host['ip']
                ?? '-'
            ),
            ENT_QUOTES,
            'UTF-8'
        );

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
        Current hosting status<br>
        Program: {$program}<br>
        Host: {$hostname}<br>
        IP Address: {$hostIp}
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
        $totalStudents = 0;
        $healthyHosts = 0;
        $warningHosts = 0;
        $validHostCount = 0;

        foreach ($hosts as $code => $host) {
            if (!is_array($host)) {
                continue;
            }

            $validHostCount++;

            $state = strtoupper((string) (
                $host['display_state'] ?? 'UNAVAILABLE'
            ));

            if ($state === 'HEALTHY') {
                $healthyHosts++;
            } else {
                $warningHosts++;
            }

            $isCurrent = is_array($host['status'] ?? null)
                && in_array(
                    $host['display_state'] ?? null,
                    ['HEALTHY', 'WARNING'],
                    true
                )
                && is_int($host['account_count'] ?? null)
                && $host['account_count'] >= 0;

            if ($isCurrent) {
                $totalStudents += $host['account_count'];
            }

            $cards .= self::renderHostHealthCard(
                (string) $code,
                $host,
                $isCurrent
            );
        }

        if ($cards === '') {
            $cards = <<<HTML
<div class="dashboard-empty">
    <span class="dashboard-empty-icon" aria-hidden="true">!</span>
    <strong>No enabled hosts available.</strong>
    <span>Host health will appear here when an enabled host is configured.</span>
</div>
HTML;
        }

        $totalHosts = $validHostCount;

        $content = <<<HTML
<div class="page-heading">
    <div>
        <h2 class="page-title">Monitoring Overview</h2>
        <p class="page-description">
            Read-only status by hosting program
        </p>
    </div>
</div>

<div class="dashboard-summary" aria-label="Dashboard summary">
    <article class="surface dashboard-summary-card">
        <span class="metric-label">Total Hosts</span>
        <strong class="dashboard-summary-value" data-metric="total-hosts">{$totalHosts}</strong>
        <span class="metric-sub">Enabled hosting programs</span>
    </article>

    <article class="surface dashboard-summary-card dashboard-summary-card-success">
        <span class="metric-label">Healthy Hosts</span>
        <strong class="dashboard-summary-value" data-metric="healthy-hosts">{$healthyHosts}</strong>
        <span class="metric-sub">Reporting normally</span>
    </article>

    <article class="surface dashboard-summary-card dashboard-summary-card-warning">
        <span class="metric-label">Warning / Unavailable</span>
        <strong class="dashboard-summary-value" data-metric="warning-unavailable-hosts">{$warningHosts}</strong>
        <span class="metric-sub">Needs attention</span>
    </article>

    <article class="surface dashboard-summary-card">
        <span class="metric-label">Total Students</span>
        <strong class="dashboard-summary-value" data-metric="total-students">{$totalStudents}</strong>
        <span class="metric-sub">Monitored student accounts</span>
    </article>
</div>

<section class="surface dashboard-host-panel" data-host-health-matrix>
    <div class="dashboard-section-heading">
        <div>
            <h2 class="panel-title">Host Health Matrix</h2>
            <p class="panel-subtitle">Storage, backup, and collection health for every enabled host</p>
        </div>
        <span class="dashboard-section-count">{$totalHosts} hosts</span>
    </div>

    <div class="dashboard-host-grid">
    {$cards}
    </div>
</section>
HTML;

        return Layout::render(
            title: 'Monitoring Overview',
            active: 'dashboard',
            content: $content,
            user: $user
        );
    }

    private static function renderHostHealthCard(
        string $code,
        array $host,
        bool $isCurrent
    ): string {
        $escape = static fn (mixed $value): string => htmlspecialchars(
            is_scalar($value) ? (string) $value : '-',
            ENT_QUOTES,
            'UTF-8'
        );

        $state = strtoupper((string) (
            $host['display_state'] ?? 'UNAVAILABLE'
        ));

        $stateClass = match ($state) {
            'HEALTHY' => 'dashboard-host-healthy',
            'WARNING' => 'dashboard-host-warning',
            'STALE' => 'dashboard-host-stale',
            default => 'dashboard-host-unavailable',
        };

        $stateIcon = match ($state) {
            'HEALTHY' => '✓',
            'WARNING', 'STALE' => '!',
            default => '×',
        };

        $safeCode = $escape($host['code'] ?? $code);
        $safeName = $escape($host['name'] ?? $code);
        $safeIp = $escape($host['ip'] ?? '-');
        $safeState = $escape($state);
        $safeLastSuccess = $escape(self::formatDateTime(
            $host['last_success'] ?? null
        ));
        $safeHref = $escape(
            '/dashboard?host=' . rawurlencode($code)
        );

        $accountLabel = $isCurrent
            ? $escape($host['account_count'] . ' accounts')
            : 'Unknown (not current)';

        $storage = is_array($host['status'] ?? null)
            && is_array($host['status']['storage'] ?? null)
            ? $host['status']['storage']
            : [];

        $rootPercent = self::percentage(
            $storage['root']['used_percent'] ?? null
        );
        $studentPercent = self::percentage(
            $storage['student']['used_percent'] ?? null
        );

        $backup = is_array($host['status'] ?? null)
            && is_array($host['status']['backup'] ?? null)
            ? self::formatDateTime(
                $host['status']['backup']['last_backup'] ?? null
            )
            : 'ไม่มีข้อมูล';

        return <<<HTML
<article class="dashboard-host-card {$stateClass}">
    <div class="dashboard-host-card-heading">
        <div>
            <span class="dashboard-host-code">{$safeCode}</span>
            <h3><a href="{$safeHref}">{$safeName}</a></h3>
            <span class="dashboard-host-ip">{$safeIp}</span>
        </div>
        <span class="dashboard-host-status" aria-label="Status: {$safeState}">
            <span class="dashboard-host-status-icon" aria-hidden="true">{$stateIcon}</span>
            <span>{$safeState}</span>
        </span>
    </div>

    <dl class="dashboard-host-facts">
        <div>
            <dt>Accounts</dt>
            <dd>{$accountLabel}</dd>
        </div>
        <div>
            <dt>Last successful collection</dt>
            <dd>{$safeLastSuccess}</dd>
        </div>
        <div>
            <dt>Storage</dt>
            <dd class="dashboard-storage-signals">
                <span>Root {$rootPercent}</span>
                <span>Student {$studentPercent}</span>
            </dd>
        </div>
        <div>
            <dt>Last backup</dt>
            <dd>{$escape($backup)}</dd>
        </div>
    </dl>
</article>
HTML;
    }

    private static function percentage(mixed $value): string
    {
        if (!is_int($value) && !is_float($value) && !is_numeric($value)) {
            return '—';
        }

        $percentage = max(0, min(100, (float) $value));

        return floor($percentage) === $percentage
            ? (string) (int) $percentage . '%'
            : number_format($percentage, 1, '.', '') . '%';
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
