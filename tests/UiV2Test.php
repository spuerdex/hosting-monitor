<?php

declare(strict_types=1);

use Digit\HostingAdmin\Views\Layout;
use Digit\HostingAdmin\Controllers\AuthController;
use Digit\HostingAdmin\Controllers\DashboardController;
use Digit\HostingAdmin\Controllers\StudentsController;
use Digit\HostingAdmin\Controllers\SystemController;
use Digit\HostingAdmin\Controllers\BackupController;
use Digit\HostingAdmin\Controllers\ManualController;


/*
|--------------------------------------------------------------------------
| Shared Hybrid Layout
|--------------------------------------------------------------------------
*/

$layout = Layout::render(
    title: 'Dashboard',
    active: 'dashboard',
    content: '<div>Content</div>',
    user: ['display_name' => 'admin']
);

assertTrueValue(
    str_contains($layout, 'class="app-shell"')
);

assertTrueValue(
    str_contains($layout, 'class="sidebar"')
);

assertTrueValue(
    str_contains($layout, 'data-sidebar-toggle')
);

assertTrueValue(
    str_contains($layout, 'data-sidebar-backdrop')
);

assertTrueValue(
    str_contains($layout, '/assets/js/app.js')
);


/*
|--------------------------------------------------------------------------
| Login UI v2
|--------------------------------------------------------------------------
*/

$login = (new AuthController())
    ->loginPage();

assertTrueValue(
    str_contains($login, 'auth-shell')
);

assertTrueValue(
    str_contains($login, 'auth-brand-panel')
);

assertTrueValue(
    str_contains($login, 'auth-form-panel')
);


/*
|--------------------------------------------------------------------------
| Dashboard UI v2
|--------------------------------------------------------------------------
*/

$dashboardStatus = [
    'overall_status' => 'HEALTHY',
    'generated_at' => '2026-09-02T12:00:00+07:00',

    'summary' => [
        'students' => 10,
        'enabled' => 10,
        'suspended' => 0,
        'warnings' => 0,
    ],

    'storage' => [
        'root' => [
            'used_percent' => 11,
        ],

        'student' => [
            'used_percent' => 1,
        ],
    ],

    'backup' => [
        'last_backup'
            => '2026-09-02T02:00:00+07:00',

        'size_bytes'
            => 1048576,
    ],
];

$dashboard = (new DashboardController())
    ->page(
        ['display_name' => 'admin'],
        $dashboardStatus
    );

assertTrueValue(
    str_contains($dashboard, 'metric-grid')
);

assertTrueValue(
    str_contains($dashboard, 'progress-track')
);

assertTrueValue(
    str_contains($dashboard, 'system-health-panel')
);


/*
|--------------------------------------------------------------------------
| Students UI v2
|--------------------------------------------------------------------------
*/

$students = [
    [
        'student_id' => '691000001',
        'domain'
            => '691000001.cs.digit.crru.ac.th',

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
];

$studentsPage = (new StudentsController())
    ->page(
        ['display_name' => 'admin'],
        $students
    );

assertTrueValue(
    str_contains(
        $studentsPage,
        'student-status-filter'
    )
);

assertTrueValue(
    str_contains(
        $studentsPage,
        'student-search'
    )
);

assertTrueValue(
    str_contains(
        $studentsPage,
        'table-responsive'
    )
);


/*
|--------------------------------------------------------------------------
| System UI v2
|--------------------------------------------------------------------------
*/

$systemStatus = [
    'generated_at'
        => '2026-09-02T12:00:00+07:00',

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
            'used_percent' => 1,
        ],
    ],
];

$system = (new SystemController())
    ->page(
        ['display_name' => 'admin'],
        $systemStatus
    );

assertTrueValue(
    str_contains(
        $system,
        'service-status-grid'
    )
);

assertTrueValue(
    str_contains(
        $system,
        'progress-track'
    )
);


/*
|--------------------------------------------------------------------------
| Backup UI v2
|--------------------------------------------------------------------------
*/

$backup = (new BackupController())
    ->page(
        ['display_name' => 'admin'],
        [
            'backup' => [
                'last_backup'
                    => '2026-09-02T02:00:00+07:00',

                'size_bytes'
                    => 2097152,
            ],
        ]
    );

assertTrueValue(
    str_contains(
        $backup,
        'backup-hero'
    )
);

assertTrueValue(
    str_contains(
        $backup,
        'backup-policy-grid'
    )
);


/*
|--------------------------------------------------------------------------
| Manual UI v2
|--------------------------------------------------------------------------
*/

$manual = (new ManualController())
    ->page(
        ['display_name' => 'admin']
    );

assertTrueValue(
    str_contains(
        $manual,
        'command-copy'
    )
);

assertTrueValue(
    str_contains(
        $manual,
        'data-copy-command'
    )
);


/*
|--------------------------------------------------------------------------
| Responsive Assets
|--------------------------------------------------------------------------
*/

$cssFile = dirname(__DIR__)
    . '/public/assets/css/app.css';

$jsFile = dirname(__DIR__)
    . '/public/assets/js/app.js';

assertTrueValue(
    is_file($cssFile)
);

assertTrueValue(
    is_file($jsFile)
);

$css = file_get_contents($cssFile);
$js = file_get_contents($jsFile);

assertTrueValue(
    str_contains(
        $css,
        '@media (max-width: 768px)'
    )
);

assertTrueValue(
    str_contains(
        $css,
        '.sidebar.is-open'
    )
);

assertTrueValue(
    str_contains(
        $js,
        'data-sidebar-toggle'
    )
);

assertTrueValue(
    str_contains(
        $js,
        'data-copy-command'
    )
);

assertTrueValue(
    str_contains(
        $js,
        'data-refresh-status'
    )
);

assertTrueValue(
    str_contains(
        $js,
        'data-status-feedback'
    )
);
