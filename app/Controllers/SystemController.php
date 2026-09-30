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
            aria-label="{$label}: {$state}"
            role="img"
        ></span>

    </div>

    <div class="service-state">
        {$state}
    </div>

</div>
HTML;
        }

        $rootValue = $storage['root']['used_percent'] ?? null;
        $root = max(
            0,
            min(
                100,
                is_numeric($rootValue) ? (int) $rootValue : 0
            )
        );

        $studentValue = $storage['student']['used_percent'] ?? null;
        $student = max(
            0,
            min(
                100,
                is_numeric($studentValue) ? (int) $studentValue : 0
            )
        );

        $rootThreshold = self::storageThreshold(
            is_numeric($rootValue) ? (int) $rootValue : null
        );
        $studentThreshold = self::storageThreshold(
            is_numeric($studentValue) ? (int) $studentValue : null
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
            class="progress-fill {$rootThreshold['fill_class']}"
            style="width: {$root}%"
        ></div>
    </div>

    <span class="storage-threshold" data-storage-threshold="{$rootThreshold['key']}">
        {$rootThreshold['label']}
    </span>

</div>


<div class="surface storage-card">

    <div class="disk-heading">
        <span>Student Disk</span>
        <span>{$student}%</span>
    </div>

    <div class="progress-track">
        <div
            class="progress-fill {$studentThreshold['fill_class']}"
            style="width: {$student}%"
        ></div>
    </div>

    <span class="storage-threshold" data-storage-threshold="{$studentThreshold['key']}">
        {$studentThreshold['label']}
    </span>

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

        $labels = [
            'nginx' => 'Nginx',
            'php_fpm' => 'PHP-FPM',
            'mariadb' => 'MariaDB',
            'ssh' => 'SSH',
            'ufw' => 'UFW',
        ];

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

            $rawState = strtoupper((string) (
                $host['display_state'] ?? 'UNAVAILABLE'
            ));
            $stateClass = self::hostStateClass($rawState);
            $stateIcon = self::hostStateIcon($rawState);

            $status =
                $host['status']
                ?? null;

            if (!is_array($status)) {
                $unavailableLabel = $rawState === 'STALE'
                    ? 'Stale'
                    : 'Unavailable';

                $hostsHtml .= <<<HTML
<section class="surface monitoring-host-panel {$stateClass}" data-host-state="{$rawState}">

<div class="section-heading">
    <div>
        <h2>{$name}</h2>

        <p>
            Host: {$code}
            · {$ip}
        </p>
    </div>

    <span class="dashboard-host-status" aria-label="Status: {$displayState}">
        <span class="dashboard-host-status-icon" aria-hidden="true">{$stateIcon}</span>
        <span>{$displayState}</span>
    </span>
</div>

<div class="surface recent-panel">

    <div class="info-item">
        <span class="info-label">
            Current Monitoring Data
        </span>

        <strong class="info-value">
            {$unavailableLabel}
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

            $services =
                is_array(
                    $status['services']
                    ?? null
                )
                ? $status['services']
                : [];

            $storage =
                is_array(
                    $status['storage']
                    ?? null
                )
                ? $status['storage']
                : [];

            $serviceHtml = '';
            $isStale = $rawState === 'STALE';

            foreach (
                $labels as $key => $label
            ) {
                $running = (bool)(
                    $services[$key]
                    ?? false
                );

                $state = $isStale
                    ? 'Stale'
                    : ($running ? 'Running' : 'Unavailable');

                $indicatorClass = $running && !$isStale
                    ? 'service-indicator-up'
                    : 'service-indicator-down';

                $serviceHtml .= <<<HTML
<div class="surface service-card" data-service-state="{$state}">

    <div class="service-head">

        <span class="service-name">
            {$label}
        </span>

        <span
            class="service-indicator {$indicatorClass}"
            aria-label="{$label}: {$state}"
            role="img"
        ></span>

    </div>

    <div class="service-state">
        {$state}
    </div>

</div>
HTML;
            }

            $rootValue = $storage['root']['used_percent'] ?? null;
            $root = max(
                0,
                min(
                    100,
                    is_numeric($rootValue) ? (int) $rootValue : 0
                )
            );

            $studentValue = $storage['student']['used_percent'] ?? null;
            $student = max(
                0,
                min(
                    100,
                    is_numeric($studentValue) ? (int) $studentValue : 0
                )
            );

            $rootThreshold = self::storageThreshold(
                is_numeric($rootValue) ? (int) $rootValue : null
            );
            $studentThreshold = self::storageThreshold(
                is_numeric($studentValue) ? (int) $studentValue : null
            );

            $generated =
                self::formatDateTime(
                    $status['generated_at']
                    ?? null
                );

            $hostsHtml .= <<<HTML
<section class="monitoring-host-panel {$stateClass}" data-host-state="{$rawState}">

<div class="section-heading">

    <div>
        <h2>{$name}</h2>

        <p>
            Host: {$code}
            · {$ip}
        </p>
    </div>

    <span class="dashboard-host-status" aria-label="Status: {$displayState}">
        <span class="dashboard-host-status-icon" aria-hidden="true">{$stateIcon}</span>
        <span>{$displayState}</span>
    </span>

</div>

<div class="service-status-grid">
{$serviceHtml}
</div>

<div class="section">

    <div class="section-heading">
        <h2>Storage</h2>

        <p>
            Disk usage for {$name}
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
                class="progress-fill {$rootThreshold['fill_class']}"
                    style="width: {$root}%"
                ></div>
            </div>

        <span class="storage-threshold" data-storage-threshold="{$rootThreshold['key']}">
            {$rootThreshold['label']}
        </span>

        </div>

        <div class="surface storage-card">

            <div class="disk-heading">
                <span>Student Disk</span>
                <span>{$student}%</span>
            </div>

            <div class="progress-track">
            <div
                class="progress-fill {$studentThreshold['fill_class']}"
                    style="width: {$student}%"
                ></div>
            </div>

        <span class="storage-threshold" data-storage-threshold="{$studentThreshold['key']}">
            {$studentThreshold['label']}
        </span>

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
                Host State
            </span>

            <strong class="info-value">
                {$displayState}
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
        Server Services
    </h2>

    <p class="page-description">
        สถานะ Service และ Storage
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
            title: 'System',
            active: 'system',
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

    private static function hostStateClass(string $state): string
    {
        return match ($state) {
            'HEALTHY' => 'dashboard-host-healthy',
            'WARNING' => 'dashboard-host-warning',
            'STALE' => 'dashboard-host-stale',
            default => 'dashboard-host-unavailable',
        };
    }

    private static function hostStateIcon(string $state): string
    {
        return match ($state) {
            'HEALTHY' => '✓',
            'WARNING', 'STALE' => '!',
            default => '×',
        };
    }

    private static function storageThreshold(?int $percentage): array
    {
        if ($percentage === null) {
            return [
                'key' => 'unavailable',
                'label' => 'Unavailable',
                'fill_class' => 'progress-fill-muted',
            ];
        }

        if ($percentage >= 85) {
            return [
                'key' => 'critical',
                'label' => 'Critical',
                'fill_class' => 'progress-fill-danger',
            ];
        }

        if ($percentage >= 70) {
            return [
                'key' => 'warning',
                'label' => 'Warning',
                'fill_class' => 'progress-fill-warning',
            ];
        }

        return [
            'key' => 'healthy',
            'label' => 'Normal',
            'fill_class' => 'progress-fill-success',
        ];
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
