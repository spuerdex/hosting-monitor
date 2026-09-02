<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Auth;

final class AuthService
{
    private $findUser;
    private $failedAttempts;
    private $audit;

    public function __construct(
        callable $findUser,
        callable $failedAttempts,
        callable $audit,
        private int $maxFailures = 5
    ) {
        $this->findUser = $findUser;
        $this->failedAttempts = $failedAttempts;
        $this->audit = $audit;
    }

    public function canAttempt(
        string $ip
    ): bool {
        $failed = ($this->failedAttempts)(
            $ip
        );

        return $failed < $this->maxFailures;
    }

    public function verifyCredentials(
        string $username,
        string $password
    ): ?array {
        $user = ($this->findUser)(
            $username
        );

        if ($user === null) {
            return null;
        }

        if (
            !isset($user['is_active'])
            || !(bool)$user['is_active']
        ) {
            return null;
        }

        if (
            !password_verify(
                $password,
                $user['password_hash']
            )
        ) {
            return null;
        }

        return $user;
    }

    public function recordFailure(
        string $ip
    ): void {
        ($this->audit)(
            'LOGIN_FAILED',
            null,
            $ip
        );
    }
}
