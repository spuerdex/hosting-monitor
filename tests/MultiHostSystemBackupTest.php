<?php
declare(strict_types=1);

use Digit\HostingAdmin\Controllers\BackupController;
use Digit\HostingAdmin\Controllers\SystemController;


/*
|--------------------------------------------------------------------------
| Multi-host fixtures
|--------------------------------------------------------------------------
*/

$hosts = [
    'cs' => [
        'code' => 'cs',
        'name' => 'Computer Science',
        'ip' => '192.0.2.11',
        'fetch_state' => 'SUCCESS',
        'display_state' => 'HEALTHY',
        'last_attempt' => '2026-09-25T15:00:00+07:00',
        'last_success' => '2026-09-25T15:00:00+07:00',
        'account_count' => 1,
        'status' => [
            'schema_version' => 1,
            'generated_at' => '2026-09-25T15:00:00+07:00',
            'overall_status' => 'HEALTHY',

            'services' => [
                'nginx' => true,
                'php_fpm' => true,
                'mariadb' => true,
                'ssh' => true,
                'ufw' => true,
            ],

            'storage' => [
                'root' => [
                    'used_percent' => 11,
                ],
                'student' => [
                    'used_percent' => 21,
                ],
            ],

            'summary' => [
                'students' => 1,
            ],

            'backup' => [
                'last_backup'
                    => '2026-09-25T02:00:00+07:00',
                'size_bytes'
                    => 2097152,
            ],

            'students' => [
                [
                    'student_id' => '69001',
                    'username' => 's69001',
                ],
            ],
        ],
    ],

    'it' => [
        'code' => 'it',
        'name' => 'Information Technology',
        'ip' => '192.0.2.12',
        'fetch_state' => 'SUCCESS',
        'display_state' => 'WARNING',
        'last_attempt' => '2026-09-25T15:00:00+07:00',
        'last_success' => '2026-09-25T15:00:00+07:00',
        'account_count' => 1,
        'status' => [
            'schema_version' => 1,
            'generated_at' => '2026-09-25T15:00:00+07:00',
            'overall_status' => 'WARNING',

            'services' => [
                'nginx' => false,
                'php_fpm' => true,
                'mariadb' => true,
                'ssh' => true,
                'ufw' => true,
            ],

            'storage' => [
                'root' => [
                    'used_percent' => 71,
                ],
                'student' => [
                    'used_percent' => 81,
                ],
            ],

            'summary' => [
                'students' => 1,
            ],

            'backup' => [
                'last_backup'
                    => '2026-09-24T02:00:00+07:00',
                'size_bytes'
                    => 8388608,
            ],

            'students' => [
                [
                    'student_id' => '69002',
                    'username' => 's69002',
                ],
            ],
        ],
    ],

    'ai' => [
        'code' => 'ai',
        'name' => 'Artificial Intelligence',
        'ip' => '192.0.2.13',
        'fetch_state' => 'UNREACHABLE',
        'display_state' => 'UNAVAILABLE',
        'last_attempt' => '2026-09-25T15:00:00+07:00',
        'last_success' => '2026-09-25T14:00:00+07:00',
        'account_count' => null,

        // Important:
        // non-live host must NOT expose old status
        'status' => null,
    ],
];


/*
|--------------------------------------------------------------------------
| System monitoring
|--------------------------------------------------------------------------
*/

$systemController =
    new SystemController();

assertTrueValue(
    method_exists(
        $systemController,
        'monitoringPage'
    )
);

if (
    method_exists(
        $systemController,
        'monitoringPage'
    )
) {
    $systemHtml =
        $systemController->monitoringPage(
            ['display_name' => 'admin'],
            $hosts
        );

    // Host identity must remain visible.
    assertTrueValue(
        str_contains(
            $systemHtml,
            'Computer Science'
        )
    );

    assertTrueValue(
        str_contains(
            $systemHtml,
            'Information Technology'
        )
    );

    assertTrueValue(
        str_contains(
            $systemHtml,
            'Artificial Intelligence'
        )
    );

    // Storage must remain host-specific.
    assertTrueValue(
        str_contains(
            $systemHtml,
            '11%'
        )
    );

    assertTrueValue(
        str_contains(
            $systemHtml,
            '21%'
        )
    );

    assertTrueValue(
        str_contains(
            $systemHtml,
            '71%'
        )
    );

    assertTrueValue(
        str_contains(
            $systemHtml,
            '81%'
        )
    );

    // Non-live host must be identified as unavailable.
    assertTrueValue(
        str_contains(
            $systemHtml,
            'UNAVAILABLE'
        )
        || str_contains(
            $systemHtml,
            'Unavailable'
        )
    );

    // Existing UI contract should still be used.
    assertTrueValue(
        str_contains(
            $systemHtml,
            'service-status-grid'
        )
    );

    assertTrueValue(
        str_contains(
            $systemHtml,
            'progress-track'
        )
    );

    assertTrueValue(
        str_contains(
            $systemHtml,
            'dashboard-host-warning'
        )
    );

    assertTrueValue(
        str_contains(
            $systemHtml,
            'dashboard-host-unavailable'
        )
    );

    assertTrueValue(
        str_contains(
            $systemHtml,
            'aria-label="Nginx: Unavailable"'
        )
    );

    assertTrueValue(
        str_contains(
            $systemHtml,
            'data-storage-threshold="warning"'
        )
    );
}


/*
|--------------------------------------------------------------------------
| Backup monitoring
|--------------------------------------------------------------------------
*/

$backupController =
    new BackupController();

assertTrueValue(
    method_exists(
        $backupController,
        'monitoringPage'
    )
);

if (
    method_exists(
        $backupController,
        'monitoringPage'
    )
) {
    $backupHtml =
        $backupController->monitoringPage(
            ['display_name' => 'admin'],
            $hosts
        );

    // Host identity must remain attached to backup data.
    assertTrueValue(
        str_contains(
            $backupHtml,
            'Computer Science'
        )
    );

    assertTrueValue(
        str_contains(
            $backupHtml,
            'Information Technology'
        )
    );

    assertTrueValue(
        str_contains(
            $backupHtml,
            'Artificial Intelligence'
        )
    );

    // CS backup = 2 MB.
    assertTrueValue(
        str_contains(
            $backupHtml,
            '2.00 MB'
        )
    );

    // IT backup = 8 MB.
    assertTrueValue(
        str_contains(
            $backupHtml,
            '8.00 MB'
        )
    );

    // Non-live host must not receive another host's backup.
    assertTrueValue(
        str_contains(
            $backupHtml,
            'UNAVAILABLE'
        )
        || str_contains(
            $backupHtml,
            'Unavailable'
        )
    );

    // Existing visual language should remain available.
    assertTrueValue(
        str_contains(
            $backupHtml,
            'backup-hero'
        )
        || str_contains(
            $backupHtml,
            'backup-policy-grid'
        )
    );

    assertTrueValue(
        str_contains(
            $backupHtml,
            'Last backup timestamp'
        )
    );

    assertTrueValue(
        str_contains(
            $backupHtml,
            'data-backup-status="unavailable"'
        )
    );

    assertTrueValue(
        !str_contains(
            $backupHtml,
            'backup-action'
        )
    );
}
/*
|--------------------------------------------------------------------------
| Host data isolation
|--------------------------------------------------------------------------
*/

if (
    isset($systemHtml)
    && is_string($systemHtml)
) {
    $csStart = strpos(
        $systemHtml,
        'Computer Science'
    );

    $itStart = strpos(
        $systemHtml,
        'Information Technology'
    );

    $aiStart = strpos(
        $systemHtml,
        'Artificial Intelligence'
    );

    assertTrueValue(
        $csStart !== false
        && $itStart !== false
        && $aiStart !== false
    );

    assertTrueValue(
        $csStart < $itStart
        && $itStart < $aiStart
    );

    $csBlock = substr(
        $systemHtml,
        $csStart,
        $itStart - $csStart
    );

    $itBlock = substr(
        $systemHtml,
        $itStart,
        $aiStart - $itStart
    );

    $aiBlock = substr(
        $systemHtml,
        $aiStart
    );

    /*
     * CS storage must remain CS-only.
     */
    assertTrueValue(
        str_contains(
            $csBlock,
            '11%'
        )
    );

    assertTrueValue(
        str_contains(
            $csBlock,
            '21%'
        )
    );

    assertTrueValue(
        !str_contains(
            $csBlock,
            '71%'
        )
    );

    assertTrueValue(
        !str_contains(
            $csBlock,
            '81%'
        )
    );

    /*
     * IT storage must remain IT-only.
     */
    assertTrueValue(
        str_contains(
            $itBlock,
            '71%'
        )
    );

    assertTrueValue(
        str_contains(
            $itBlock,
            '81%'
        )
    );

    assertTrueValue(
        !str_contains(
            $itBlock,
            '11%'
        )
    );

    assertTrueValue(
        !str_contains(
            $itBlock,
            '21%'
        )
    );

    /*
     * Non-live AI host must not inherit
     * another host's current storage values.
     */
    assertTrueValue(
        !str_contains(
            $aiBlock,
            '11%'
        )
        && !str_contains(
            $aiBlock,
            '21%'
        )
        && !str_contains(
            $aiBlock,
            '71%'
        )
        && !str_contains(
            $aiBlock,
            '81%'
        )
    );

    assertTrueValue(str_contains($aiBlock, 'service-status-grid'));
    assertTrueValue(str_contains($aiBlock, 'storage-grid'));
    assertTrueValue(str_contains($aiBlock, 'Root Disk'));
    assertTrueValue(str_contains($aiBlock, 'Student Disk'));
    assertTrueValue(str_contains($aiBlock, 'Unavailable'));
    assertTrueValue(str_contains($aiBlock, '—'));
    assertTrueValue(!str_contains($aiBlock, '0%'));
}


if (
    isset($backupHtml)
    && is_string($backupHtml)
) {
    $csStart = strpos(
        $backupHtml,
        'Computer Science'
    );

    $itStart = strpos(
        $backupHtml,
        'Information Technology'
    );

    $aiStart = strpos(
        $backupHtml,
        'Artificial Intelligence'
    );

    assertTrueValue(
        $csStart !== false
        && $itStart !== false
        && $aiStart !== false
    );

    $csBlock = substr(
        $backupHtml,
        $csStart,
        $itStart - $csStart
    );

    $itBlock = substr(
        $backupHtml,
        $itStart,
        $aiStart - $itStart
    );

    $aiBlock = substr(
        $backupHtml,
        $aiStart
    );

    /*
     * CS must show only the CS fixture backup size.
     */
    assertTrueValue(
        str_contains(
            $csBlock,
            '2.00 MB'
        )
    );

    assertTrueValue(
        !str_contains(
            $csBlock,
            '8.00 MB'
        )
    );

    /*
     * IT must show only the IT fixture backup size.
     */
    assertTrueValue(
        str_contains(
            $itBlock,
            '8.00 MB'
        )
    );

    assertTrueValue(
        !str_contains(
            $itBlock,
            '2.00 MB'
        )
    );

    /*
     * Non-live AI must not inherit a backup.
     */
    assertTrueValue(
        !str_contains(
            $aiBlock,
            '2.00 MB'
        )
        && !str_contains(
            $aiBlock,
            '8.00 MB'
        )
    );

    assertTrueValue(str_contains($aiBlock, 'Last backup timestamp'));
    assertTrueValue(str_contains($aiBlock, 'Backup size'));
    assertTrueValue(str_contains($aiBlock, '—'));
}
/*
|--------------------------------------------------------------------------
| Malformed host selectors
|--------------------------------------------------------------------------
*/

foreach ([['cs'], 123, ''] as $invalidSelection) {
    $systemRejected = false;

    try {
        $systemController->monitoringPage(
            ['display_name' => 'admin'],
            $hosts,
            $invalidSelection
        );
    } catch (\InvalidArgumentException $e) {
        $systemRejected = true;
    }

    assertSameValue(
        true,
        $systemRejected
    );


    $backupRejected = false;

    try {
        $backupController->monitoringPage(
            ['display_name' => 'admin'],
            $hosts,
            $invalidSelection
        );
    } catch (\InvalidArgumentException $e) {
        $backupRejected = true;
    }

    assertSameValue(
        true,
        $backupRejected
    );
}
