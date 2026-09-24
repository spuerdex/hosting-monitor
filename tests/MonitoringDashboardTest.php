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
        ],
    ],
    'it' => [
        'code' => 'it',
        'name' => 'Information Technology',
        'ip' => '192.0.2.12',
        'fetch_state' => 'UNREACHABLE',
        'display_state' => 'UNREACHABLE',
        'last_success' => null,
        'account_count' => null,
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

// Overview must count only current accounts.
assertTrueValue(str_contains($monitoringHtml, 'Current Accounts: 2'));
assertTrueValue(str_contains($monitoringHtml, 'Excluded hosts: 1'));
assertTrueValue(str_contains($monitoringHtml, 'Unknown (not current)'));

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

// RED: A selected host must have a read-only detail page.
$hostDetailHtml = (new DashboardController())->hostDetail(
    ['display_name' => 'admin'],
    $monitoringHosts['cs']
);

assertTrueValue(str_contains($hostDetailHtml, 'Computer Science'));
assertTrueValue(str_contains($hostDetailHtml, '192.0.2.11'));
assertTrueValue(str_contains($hostDetailHtml, 'HEALTHY'));
