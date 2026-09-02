<?php

declare(strict_types=1);

use Digit\HostingAdmin\Auth\AuthService;

$hash = password_hash(
    'Strong-Test-Password-123!',
    PASSWORD_ARGON2ID
);

$findUser = static function (
    string $username
) use ($hash): ?array {
    if ($username !== 'admin') {
        return null;
    }

    return [
        'id' => 1,
        'username' => 'admin',
        'password_hash' => $hash,
        'is_active' => 1,
    ];
};

$failedCount = 0;

$service = new AuthService(
    findUser: $findUser,

    failedAttempts: static function (
        string $ip
    ) use (&$failedCount): int {
        return $failedCount;
    },

    audit: static function (
        string $action
    ) use (&$failedCount): void {
        if ($action === 'LOGIN_FAILED') {
            $failedCount++;
        }
    },

    maxFailures: 5
);

assertTrueValue(
    $service->canAttempt('10.1.1.1')
);

assertTrueValue(
    $service->verifyCredentials(
        'admin',
        'Strong-Test-Password-123!'
    ) !== null
);

assertSameValue(
    null,
    $service->verifyCredentials(
        'admin',
        'wrong-password'
    )
);

for ($i = 0; $i < 5; $i++) {
    $service->recordFailure(
        '10.1.1.1'
    );
}

assertSameValue(
    false,
    $service->canAttempt(
        '10.1.1.1'
    )
);
