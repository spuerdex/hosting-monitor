<?php

declare(strict_types=1);

use Digit\HostingAdmin\Auth\SessionManager;

$stored = null;
$deleted = null;

$manager = new SessionManager(
    storeSession: static function (
        int $userId,
        string $hash,
        string $ip,
        string $userAgent,
        DateTimeImmutable $expiresAt
    ) use (&$stored): void {
        $stored = [
            'user_id' => $userId,
            'hash' => $hash,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'expires_at' => $expiresAt,
        ];
    },

    findSession: static function (
        string $hash
    ) use (&$stored): ?array {
        if (
            $stored !== null
            && hash_equals(
                $stored['hash'],
                $hash
            )
        ) {
            return [
                'admin_user_id'
                    => $stored['user_id'],
                'username'
                    => 'sysadmin',
                'display_name'
                    => 'admin',
            ];
        }

        return null;
    },

    deleteSession: static function (
        string $hash
    ) use (&$deleted): void {
        $deleted = $hash;
    },

    lifetimeSeconds: 1800
);

$token = $manager->create(
    userId: 1,
    ipAddress: '10.1.1.1',
    userAgent: 'Test Browser'
);

assertTrueValue(
    strlen($token) >= 64
);

assertTrueValue(
    is_array($stored)
);

assertSameValue(
    hash('sha256', $token),
    $stored['hash']
);

$session = $manager->resolve(
    $token
);

assertSameValue(
    'sysadmin',
    $session['username']
);

$manager->destroy(
    $token
);

assertSameValue(
    hash('sha256', $token),
    $deleted
);
