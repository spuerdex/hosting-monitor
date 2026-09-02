<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Auth;

final class LoginManager
{
    private $findUser;
    private $canAttempt;
    private $recordFailure;
    private $createSession;

    public function __construct(
        callable $findUser,
        callable $canAttempt,
        callable $recordFailure,
        callable $createSession
    ) {
        $this->findUser = $findUser;
        $this->canAttempt = $canAttempt;
        $this->recordFailure = $recordFailure;
        $this->createSession = $createSession;
    }

    public function login(
        string $username,
        string $password,
        string $ipAddress,
        string $userAgent
    ): array {
        if (!(($this->canAttempt)($ipAddress))) {
            return [
                'success' => false,
                'reason' => 'RATE_LIMITED',
                'token' => null,
                'user' => null,
            ];
        }

        $user = ($this->findUser)(
            $username
        );

        if (
            $user === null
            || !(bool)($user['is_active'] ?? false)
            || !password_verify(
                $password,
                $user['password_hash'] ?? ''
            )
        ) {
            ($this->recordFailure)(
                $ipAddress,
                $userAgent
            );

            return [
                'success' => false,
                'reason' => 'INVALID_CREDENTIALS',
                'token' => null,
                'user' => null,
            ];
        }

        $token = ($this->createSession)(
            (int) $user['id'],
            $ipAddress,
            $userAgent
        );

        return [
            'success' => true,
            'reason' => null,
            'token' => $token,
            'user' => $user,
        ];
    }
}
