<?php

declare(strict_types=1);

use Digit\HostingAdmin\Controllers\DashboardController;

$status = [
    'overall_status' => 'HEALTHY',
    'generated_at' => '2026-09-02T10:00:00+07:00',

    'summary' => [
        'students' => 10,
        'enabled' => 9,
        'suspended' => 1,
        'warnings' => 0,
    ],

    'storage' => [
        'root' => ['used_percent' => 11],
        'student' => ['used_percent' => 1],
    ],

    'backup' => [
        'last_backup' => '2026-09-02T02:00:00+07:00',
        'size_bytes' => 1048576,
    ],
];

$html = (new DashboardController())->page(
    ['display_name' => 'admin'],
    $status
);

assertTrueValue(str_contains($html, 'HEALTHY'));
assertTrueValue(str_contains($html, '10'));
assertTrueValue(str_contains($html, 'Suspended'));
assertTrueValue(str_contains($html, 'Last Backup'));
assertTrueValue(str_contains($html, 'Read-only Monitoring'));
