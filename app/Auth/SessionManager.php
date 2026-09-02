<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Auth;

use DateTimeImmutable;

final class SessionManager
{
    private $storeSession;
    private $findSession;
    private $deleteSession;

    public function __construct(
        callable $storeSession,
        callable $findSession,
        callable $deleteSession,
        private int $lifetimeSeconds = 1800
    ) {
        $this->storeSession = $storeSession;
        $this->findSession = $findSession;
        $this->deleteSession = $deleteSession;
    }

    public function create(
        int $userId,
        string $ipAddress,
        string $userAgent
    ): string {
        $token = bin2hex(
            random_bytes(32)
        );

        $hash = hash(
            'sha256',
            $token
        );

        $expiresAt = new DateTimeImmutable(
            sprintf(
                '+%d seconds',
                $this->lifetimeSeconds
            )
        );

        ($this->storeSession)(
            $userId,
            $hash,
            $ipAddress,
            $userAgent,
            $expiresAt
        );

        return $token;
    }

    public function resolve(
        string $token
    ): ?array {
        if ($token === '') {
            return null;
        }

        $hash = hash(
            'sha256',
            $token
        );

        return ($this->findSession)(
            $hash
        );
    }

    public function destroy(
        string $token
    ): void {
        if ($token === '') {
            return;
        }

        ($this->deleteSession)(
            hash(
                'sha256',
                $token
            )
        );
    }
}
