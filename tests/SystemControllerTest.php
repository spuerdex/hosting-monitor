<?php

declare(strict_types=1);

use Digit\HostingAdmin\Controllers\SystemController;

$status = [
    'generated_at' => '2026-09-02T10:00:00+07:00',

    'services' => [
        'nginx' => true,
        'php_fpm' => true,
        'mariadb' => true,
        'ssh' => true,
        'ufw' => true,
    ],

    'storage' => [
        'root' => ['used_percent' => 11],
        'student' => ['used_percent' => 1],
    ],
];

$html = (new SystemController())->page(
    ['display_name' => 'admin'],
    $status
);

assertTrueValue(str_contains($html, 'Nginx'));
assertTrueValue(str_contains($html, 'PHP-FPM'));
assertTrueValue(str_contains($html, 'MariaDB'));
assertTrueValue(str_contains($html, 'UFW'));
assertTrueValue(str_contains($html, '11%'));

assertTrueValue(str_contains($html, 'Root Disk'));
assertTrueValue(str_contains($html, 'Student Disk'));
assertTrueValue(str_contains($html, 'data-storage-threshold="healthy"'));
assertTrueValue(str_contains($html, 'aria-label="Nginx: Running"'));
assertTrueValue(str_contains($html, 'status-success'));

$missingStorageHtml = (new SystemController())->page(
    ['display_name' => 'admin'],
    [
        'services' => [],
        'storage' => [],
    ]
);

assertTrueValue(str_contains($missingStorageHtml, 'service-status-grid'));
assertTrueValue(str_contains($missingStorageHtml, 'storage-grid'));
assertTrueValue(str_contains($missingStorageHtml, 'data-storage-threshold="unavailable"'));
assertTrueValue(str_contains($missingStorageHtml, 'Unavailable'));
assertTrueValue(str_contains($missingStorageHtml, '—'));
assertTrueValue(!str_contains($missingStorageHtml, '0%'));

$staleHtml = (new SystemController())->monitoringPage(
    ['display_name' => 'admin'],
    [
        'cs' => [
            'code' => 'cs',
            'name' => 'Computer Science',
            'ip' => '192.0.2.11',
            'display_state' => 'STALE',
            'status' => [
                'services' => ['nginx' => true],
                'storage' => [
                    'root' => ['used_percent' => 91],
                    'student' => ['used_percent' => 92],
                ],
            ],
        ],
    ]
);

assertTrueValue(str_contains($staleHtml, 'Stale'));
assertTrueValue(str_contains($staleHtml, 'aria-label="Nginx: Stale"'));
assertTrueValue(str_contains($staleHtml, 'data-storage-threshold="unavailable"'));
assertTrueValue(!str_contains($staleHtml, '91%'));

$staleNullHtml = (new SystemController())->monitoringPage(
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

assertTrueValue(str_contains($staleNullHtml, 'data-host-state="STALE"'));
assertTrueValue(str_contains($staleNullHtml, 'aria-label="Nginx: Stale"'));
assertTrueValue(str_contains($staleNullHtml, 'data-service-state="Stale"'));
assertTrueValue(!str_contains($staleNullHtml, 'aria-label="Nginx: Unavailable"'));
