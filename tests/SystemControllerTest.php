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
