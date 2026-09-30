<?php

declare(strict_types=1);

use Digit\HostingAdmin\Controllers\BackupController;

$status = [
    'backup' => [
        'last_backup' => '2026-09-02T02:00:00+07:00',
        'size_bytes' => 2097152,
    ],
];

$html = (new BackupController())->page(
    ['display_name' => 'admin'],
    $status
);

assertTrueValue(
    str_contains($html, 'Last Backup')
);

assertTrueValue(
    str_contains($html, '2.00 MB')
);

assertTrueValue(
    str_contains($html, '14 วัน')
);

assertTrueValue(str_contains($html, 'data-backup-status="available"'));
assertTrueValue(str_contains($html, 'Last backup timestamp'));
assertTrueValue(str_contains($html, 'backup-size-label'));
assertTrueValue(!str_contains($html, 'backup-action'));

$unavailableHtml = (new BackupController())->monitoringPage(
    ['display_name' => 'admin'],
    [
        'ai' => [
            'code' => 'ai',
            'name' => 'Artificial Intelligence',
            'ip' => '192.0.2.13',
            'display_state' => 'UNAVAILABLE',
            'status' => null,
        ],
    ]
);

assertTrueValue(str_contains($unavailableHtml, 'Last backup timestamp'));
assertTrueValue(str_contains($unavailableHtml, 'Backup size'));
assertTrueValue(str_contains($unavailableHtml, 'data-backup-status="unavailable"'));
assertTrueValue(str_contains($unavailableHtml, '—'));

$staleHtml = (new BackupController())->monitoringPage(
    ['display_name' => 'admin'],
    [
        'cs' => [
            'code' => 'cs',
            'name' => 'Computer Science',
            'ip' => '192.0.2.11',
            'display_state' => 'STALE',
            'status' => null,
        ],
    ]
);

assertTrueValue(str_contains($staleHtml, 'Stale'));
assertTrueValue(str_contains($staleHtml, 'Last backup timestamp'));
assertTrueValue(str_contains($staleHtml, 'Backup size'));
assertTrueValue(str_contains($staleHtml, '—'));
