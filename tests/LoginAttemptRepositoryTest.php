<?php

declare(strict_types=1);

use Digit\HostingAdmin\Auth\LoginAttemptRepository;
use Digit\HostingAdmin\Support\Config;
use Digit\HostingAdmin\Support\Database;

$config = Config::load(
    '/etc/digit-hosting-admin/app.env'
);

$db = Database::connect($config);

$repo = new LoginAttemptRepository($db);

$ip = '198.51.100.250';

$db->beginTransaction();

try {
    assertSameValue(
        0,
        $repo->countRecentFailures(
            $ip,
            15
        )
    );

    for ($i = 0; $i < 5; $i++) {
        $repo->recordFailure(
            ipAddress: $ip,
            userAgent: 'DiGiT Login Test'
        );
    }

    assertSameValue(
        5,
        $repo->countRecentFailures(
            $ip,
            15
        )
    );

    assertSameValue(
        false,
        $repo->canAttempt(
            ipAddress: $ip,
            maxFailures: 5,
            windowMinutes: 15
        )
    );

} finally {
    $db->rollBack();
}
