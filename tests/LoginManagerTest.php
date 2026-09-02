<?php

declare(strict_types=1);

use Digit\HostingAdmin\Auth\LoginManager;

$users = [
    'sysadmin' => [
        'id' => 1,
        'username' => 'sysadmin',
        'display_name' => 'admin',
        'is_active' => 1,
        'password_hash' => password_hash(
            'Test-Password-123456!',
            PASSWORD_ARGON2ID
        ),
    ],
];

$failures = 0;
$sessionCreated = false;

$manager = new LoginManager(
    findUser: static function (string $username) use ($users): ?array {
        return $users[$username] ?? null;
    },

    canAttempt: static function (string $ip) use (&$failures): bool {
        return $failures < 5;
    },

    recordFailure: static function () use (&$failures): void {
        $failures++;
    },

    createSession: static function (
        int $userId,
        string $ip,
        string $userAgent
    ) use (&$sessionCreated): string {
        $sessionCreated = true;
        return 'test-session-token';
    }
);

$result = $manager->login(
    username: 'sysadmin',
    password: 'Test-Password-123456!',
    ipAddress: '10.1.1.1',
    userAgent: 'Test Browser'
);

assertSameValue(
    true,
    $result['success']
);

assertSameValue(
    'test-session-token',
    $result['token']
);

assertTrueValue(
    $sessionCreated
);

$bad = $manager->login(
    username: 'sysadmin',
    password: 'wrong-password',
    ipAddress: '10.1.1.1',
    userAgent: 'Test Browser'
);

assertSameValue(
    false,
    $bad['success']
);

assertSameValue(
    1,
    $failures
);
