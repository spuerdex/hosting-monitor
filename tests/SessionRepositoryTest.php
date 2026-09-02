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


$config = Config::load(
    '/etc/digit-hosting-admin/app.env'
);

$db = Database::connect($config);

$auth = new AuthRepository($db);
$user = $auth->findByUsername('sysadmin');

$db->beginTransaction();

try {
    $sessions = new SessionRepository($db);

    $hash = hash(
        'sha256',
        bin2hex(random_bytes(32))
    );

    $sessions->create(
        adminUserId: (int) $user['id'],
        sessionHash: $hash,
        ipAddress: '127.0.0.1',
        userAgent: 'Session Lifetime Test',
        expiresAt: new DateTimeImmutable('+30 minutes')
    );

    $stmt = $db->prepare(
        'SELECT TIMESTAMPDIFF(
            SECOND,
            NOW(),
            expires_at
         )
         FROM sessions
         WHERE session_hash = ?'
    );

    $stmt->execute([$hash]);

    $remaining = (int) $stmt->fetchColumn();

    assertTrueValue(
        $remaining >= 1750
        && $remaining <= 1850,
        'session expiry must be about 30 minutes'
    );

} finally {
    $db->rollBack();
}
