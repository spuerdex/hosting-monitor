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
