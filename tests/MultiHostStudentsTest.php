<?php

declare(strict_types=1);

use Digit\HostingAdmin\Controllers\StudentsController;

$student = [
    'student_id' => '69001',
    'username' => 's69001',
    'domain' => '69001.example.test',
    'status' => 'enabled',
    'quota' => [
        'used_mb' => 2,
        'soft_mb' => 10,
        'hard_mb' => 20,
    ],
    'php_pool' => true,
    'nginx_config' => true,
    'nginx_enabled' => true,
    'database' => true,
    'credential_exists' => true,
];

$hosts = [
    'cs' => [
        'code' => 'cs',
        'name' => 'Computer Science',
        'display_state' => 'HEALTHY',
        'status' => [
            'students' => [$student],
        ],
    ],
    'it' => [
        'code' => 'it',
        'name' => 'Information Technology',
        'display_state' => 'HEALTHY',
        'status' => [
            'students' => [$student],
        ],
    ],
];

$controller = new StudentsController();

$html = $controller->monitoringPage(
    ['display_name' => 'admin'],
    $hosts,
    'all'
);

// Same student on two hosts = two accounts.
assertSameValue(
    2,
    substr_count($html, 'data-student-row')
);

// Required monitoring columns.
assertTrueValue(str_contains($html, 'Host'));
assertTrueValue(str_contains($html, 'Username'));
assertTrueValue(str_contains($html, 'Computer Science'));
assertTrueValue(str_contains($html, 'Information Technology'));
assertTrueValue(str_contains($html, 's69001'));

// Filtering by CS must exclude IT accounts.
$csHtml = $controller->monitoringPage(
    ['display_name' => 'admin'],
    $hosts,
    'cs'
);

assertSameValue(
    1,
    substr_count($csHtml, 'data-student-row')
);

assertTrueValue(str_contains($csHtml, 'Computer Science'));
assertTrueValue(
    !str_contains($csHtml, 'data-host="it"')
);

// Offline host must not expose stale account rows.
$offlineHosts = $hosts;
$offlineHosts['it']['display_state'] = 'UNREACHABLE';
$offlineHosts['it']['status'] = null;

$offlineHtml = $controller->monitoringPage(
    ['display_name' => 'admin'],
    $offlineHosts,
    'all'
);

assertSameValue(
    1,
    substr_count($offlineHtml, 'data-student-row')
);

assertTrueValue(
    str_contains($offlineHtml, 'UNREACHABLE')
);

// Malformed host selector must be rejected, not cause TypeError.
foreach ([['cs'], 123, ''] as $invalidSelection) {
    $rejected = false;

    try {
        $controller->monitoringPage(
            ['display_name' => 'admin'],
            $hosts,
            $invalidSelection
        );
    } catch (InvalidArgumentException $e) {
        $rejected = true;
    }

    assertSameValue(true, $rejected);
}

// Remote account status must never inject HTML attributes.
$xssStudent = $student;
$xssStudent['status'] =
    'enabled" onmouseover="alert(1)';

$xssHosts = [
    'cs' => [
        'code' => 'cs',
        'name' => 'Computer Science',
        'display_state' => 'HEALTHY',
        'status' => [
            'students' => [$xssStudent],
        ],
    ],
];

$xssHtml = $controller->monitoringPage(
    ['display_name' => 'admin'],
    $xssHosts,
    'cs'
);

assertTrueValue(
    !str_contains(
        $xssHtml,
        'data-status="enabled" onmouseover="alert(1)"'
    )
);

assertTrueValue(
    str_contains(
        $xssHtml,
        'enabled&quot; onmouseover=&quot;alert(1)'
    )
);
