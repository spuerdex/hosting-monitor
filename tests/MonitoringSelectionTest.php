<?php

declare(strict_types=1);

use Digit\HostingAdmin\Controllers\DashboardController;

$controller = new DashboardController();

$user = ['display_name' => 'admin'];

$hosts = [
    'cs' => [
        'code' => 'cs',
        'name' => 'Computer Science',
        'ip' => '192.0.2.11',
        'fetch_state' => 'SUCCESS',
        'display_state' => 'HEALTHY',
        'last_success' => '2026-09-22T10:00:00+07:00',
        'account_count' => 2,
        'status' => ['overall_status' => 'HEALTHY'],
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

// No selection: show all enabled hosts.
$overview = $controller->monitoringPage($user, $hosts, null);

assertTrueValue(str_contains($overview, 'Monitoring Overview'));
assertTrueValue(str_contains($overview, 'Computer Science'));
assertTrueValue(str_contains($overview, 'Information Technology'));
assertTrueValue(str_contains($overview, 'data-host-health-matrix'));
assertTrueValue(str_contains($overview, 'href="/dashboard?host=cs"'));

// Known host: show only its detail page.
$detail = $controller->monitoringPage($user, $hosts, 'cs');

assertTrueValue(str_contains($detail, 'Host Detail'));
assertTrueValue(str_contains($detail, 'Computer Science'));
assertTrueValue(!str_contains($detail, 'Information Technology'));

// Unknown, malformed and empty selections must be rejected.
foreach (['unknown', '../etc', '', ['cs'], 123] as $invalidHost) {
    $rejected = false;

    try {
        $controller->monitoringPage($user, $hosts, $invalidHost);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
    }

    assertSameValue(true, $rejected);
}
