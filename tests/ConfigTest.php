<?php

declare(strict_types=1);

use Digit\HostingAdmin\Support\Config;

$tmp = tempnam(sys_get_temp_dir(), 'cfg');

file_put_contents(
    $tmp,
    "APP_ENV=production\n"
    . "DB_HOST=127.0.0.1\n"
    . "DB_NAME=digit_hosting_admin\n"
);

$config = Config::load($tmp);

unlink($tmp);

assertSameValue(
    'production',
    $config['APP_ENV']
);

assertSameValue(
    '127.0.0.1',
    $config['DB_HOST']
);

assertSameValue(
    'digit_hosting_admin',
    $config['DB_NAME']
);
