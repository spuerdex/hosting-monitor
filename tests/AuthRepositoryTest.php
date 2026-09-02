<?php

declare(strict_types=1);

use Digit\HostingAdmin\Auth\AuthRepository;
use Digit\HostingAdmin\Support\Config;
use Digit\HostingAdmin\Support\Database;

$config = Config::load(
    '/etc/digit-hosting-admin/app.env'
);

$db = Database::connect($config);

$repository = new AuthRepository($db);

$user = $repository->findByUsername(
    'sysadmin'
);

assertTrueValue(
    is_array($user),
    'sysadmin must exist'
);

assertSameValue(
    'sysadmin',
    $user['username']
);

assertSameValue(
    1,
    (int) $user['is_active']
);
