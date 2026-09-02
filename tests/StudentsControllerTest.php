<?php

declare(strict_types=1);

use Digit\HostingAdmin\Controllers\StudentsController;

$controller = new StudentsController();

$students = [
    [
        'student_id' => '691000001',
        'username' => 's691000001',
        'domain' => '691000001.cs.digit.crru.ac.th',
        'status' => 'enabled',
        'quota' => [
            'used_mb' => 20,
            'soft_mb' => 900,
            'hard_mb' => 1024,
        ],
        'php_pool' => true,
        'nginx_config' => true,
        'nginx_enabled' => true,
        'database' => true,
        'credential_exists' => true,
    ],
    [
        'student_id' => '691000002',
        'username' => 's691000002',
        'domain' => '691000002.cs.digit.crru.ac.th',
        'status' => 'suspended',
        'quota' => [
            'used_mb' => 50,
            'soft_mb' => 900,
            'hard_mb' => 1024,
        ],
        'php_pool' => true,
        'nginx_config' => true,
        'nginx_enabled' => false,
        'database' => true,
        'credential_exists' => true,
    ],
];

$html = $controller->page(
    ['display_name' => 'admin'],
    $students
);

assertTrueValue(
    str_contains(
        $html,
        '691000001'
    )
);

assertTrueValue(
    str_contains(
        $html,
        '691000002'
    )
);

assertTrueValue(
    str_contains(
        $html,
        '691000001.cs.digit.crru.ac.th'
    )
);

assertTrueValue(
    str_contains(
        $html,
        'Enabled'
    )
);

assertTrueValue(
    str_contains(
        $html,
        'Suspended'
    )
);

assertTrueValue(
    str_contains(
        $html,
        '900 MB'
    )
);

assertTrueValue(
    str_contains(
        $html,
        '1024 MB'
    )
);
