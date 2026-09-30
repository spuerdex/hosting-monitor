<?php

declare(strict_types=1);

use Digit\HostingAdmin\Controllers\DashboardController;

$monitoringHosts = [
    'cs' => [
        'code' => 'cs',
        'name' => 'Computer Science',
        'ip' => '192.0.2.11',
        'fetch_state' => 'SUCCESS',
        'display_state' => 'HEALTHY',
        'last_success' => '2026-09-22T10:00:00+07:00',
        'account_count' => 2,
        'status' => [
            'overall_status' => 'HEALTHY',
            'summary' => ['students' => 2],
            'storage' => [
                'root' => ['used_percent' => 35],
                'student' => ['used_percent' => 20],
            ],
            'backup' => [
                'last_backup' => '2026-09-22T02:00:00+07:00',
            ],
        ],
    ],
    'it' => [
        'code' => 'it',
        'name' => 'Information Technology',
        'ip' => '192.0.2.12',
        'fetch_state' => 'UNREACHABLE',
        'display_state' => 'UNREACHABLE',
        'last_success' => null,
        'account_count' => 7,
        'status' => null,
    ],
    'ee' => [
        'code' => 'ee',
        'name' => 'Electrical Engineering',
        'ip' => '192.0.2.13',
        'fetch_state' => 'SUCCESS',
        'display_state' => 'WARNING',
        'last_success' => '2026-09-22T09:00:00+07:00',
        'account_count' => 3,
        'status' => [
            'overall_status' => 'WARNING',
            'storage' => [
                'root' => ['used_percent' => 82],
            ],
            'backup' => [
                'last_backup' => null,
            ],
        ],
    ],
    'me' => [
        'code' => 'me',
        'name' => 'Mechanical Engineering',
        'ip' => '192.0.2.14',
        'fetch_state' => 'SUCCESS',
        'display_state' => 'STALE',
        'last_success' => '2026-09-20T09:00:00+07:00',
        'account_count' => 4,
        'status' => null,
    ],
];

$monitoringHtml = (new DashboardController())->overview(
    ['display_name' => 'admin'],
    $monitoringHosts
);

assertTrueValue(str_contains($monitoringHtml, 'Computer Science'));
assertTrueValue(str_contains($monitoringHtml, 'Information Technology'));
assertTrueValue(str_contains($monitoringHtml, 'UNREACHABLE'));
assertTrueValue(str_contains($monitoringHtml, 'Electrical Engineering'));
assertTrueValue(str_contains($monitoringHtml, 'WARNING'));
assertTrueValue(str_contains($monitoringHtml, 'STALE'));
assertTrueValue(str_contains($monitoringHtml, 'data-metric="total-hosts">4<'));
assertTrueValue(str_contains($monitoringHtml, 'data-metric="healthy-hosts">1<'));
assertTrueValue(str_contains($monitoringHtml, 'data-metric="warning-unavailable-hosts">3<'));
assertTrueValue(str_contains($monitoringHtml, 'data-metric="total-students">16<'));
assertTrueValue(str_contains($monitoringHtml, 'Storage'));
assertTrueValue(str_contains($monitoringHtml, 'Last successful collection'));
assertTrueValue(str_contains($monitoringHtml, 'Last backup'));
assertTrueValue(str_contains($monitoringHtml, 'data-host-health-matrix'));

// Overview must aggregate valid account_count values from stale and unavailable hosts.
assertTrueValue(str_contains($monitoringHtml, '7 accounts'));
assertTrueValue(str_contains($monitoringHtml, 'Monitored student accounts'));

// Untrusted host names must be HTML-escaped.
$xssHosts = $monitoringHosts;
$xssHosts['cs']['name'] = '<script>alert(1)</script>';

$xssHtml = (new DashboardController())->overview(
    ['display_name' => 'admin'],
    $xssHosts
);

assertTrueValue(
    str_contains($xssHtml, '&lt;script&gt;alert(1)&lt;/script&gt;')
);

assertTrueValue(
    !str_contains($xssHtml, '<script>alert(1)</script>')
);

$emptyHtml = (new DashboardController())->overview(
    ['display_name' => 'admin'],
    []
);

assertTrueValue(str_contains($emptyHtml, 'No enabled hosts available.'));
assertTrueValue(str_contains($emptyHtml, 'data-metric="total-hosts">0<'));
assertTrueValue(str_contains($emptyHtml, 'data-metric="warning-unavailable-hosts">0<'));

$malformedHtml = (new DashboardController())->overview(
    ['display_name' => 'admin'],
    [
        'valid' => $monitoringHosts['cs'],
        'malformed' => 'not-a-host-record',
    ]
);

assertTrueValue(str_contains($malformedHtml, 'data-metric="total-hosts">1<'));

// RED: A selected host must have a read-only detail page.
$hostDetailHtml = (new DashboardController())->hostDetail(
    ['display_name' => 'admin'],
    $monitoringHosts['cs']
);

assertTrueValue(str_contains($hostDetailHtml, 'Computer Science'));
assertTrueValue(str_contains($hostDetailHtml, '192.0.2.11'));
assertTrueValue(str_contains($hostDetailHtml, 'HEALTHY'));
