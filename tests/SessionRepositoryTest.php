<?php

declare(strict_types=1);

use Digit\HostingAdmin\Auth\AuthRepository;
use Digit\HostingAdmin\Auth\SessionRepository;
use Digit\HostingAdmin\Support\Config;
use Digit\HostingAdmin\Support\Database;

$config = Config::load(
    '/etc/digit-hosting-admin/app.env'
);

$db = Database::connect($config);

$auth = new AuthRepository($db);

$user = $auth->findByUsername(
    'sysadmin'
);

assertTrueValue(
    is_array($user),
    'sysadmin must exist'
);

$db->beginTransaction();

try {
    $sessions = new SessionRepository($db);

    $token = bin2hex(
        random_bytes(32)
    );

    $sessionHash = hash(
        'sha256',
        $token
    );

    $expiresAt = new DateTimeImmutable(
        '+30 minutes'
    );

    $sessions->create(
        adminUserId: (int) $user['id'],
        sessionHash: $sessionHash,
        ipAddress: '127.0.0.1',
        userAgent: 'DiGiT Test',
        expiresAt: $expiresAt
    );

    $session = $sessions->findValidByHash(
        $sessionHash
    );

    assertTrueValue(
        is_array($session),
        'session must exist'
    );

    assertSameValue(
        (int) $user['id'],
        (int) $session['admin_user_id']
    );

    assertSameValue(
        'sysadmin',
        $session['username']
    );

    $sessions->deleteByHash(
        $sessionHash
    );

    assertSameValue(
        null,
        $sessions->findValidByHash(
            $sessionHash
        )
    );

} finally {
    $db->rollBack();
}
