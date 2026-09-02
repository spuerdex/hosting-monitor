<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Auth;

use DateTimeImmutable;
use PDO;

final class LoginAttemptRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function recordFailure(
        string $ipAddress,
        string $userAgent
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO audit_logs
            (
                admin_user_id,
                action,
                target,
                ip_address,
                user_agent
            )
            VALUES (
                NULL,
                ?,
                NULL,
                ?,
                ?
            )'
        );

        $stmt->execute([
            'LOGIN_FAILED',
            $ipAddress,
            substr($userAgent, 0, 255),
        ]);
    }

    public function countRecentFailures(
        string $ipAddress,
        int $windowMinutes
    ): int {
        if ($windowMinutes < 1) {
            return 0;
        }

        $since = new DateTimeImmutable(
            sprintf(
                '-%d minutes',
                $windowMinutes
            )
        );

        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM audit_logs
             WHERE action = ?
               AND ip_address = ?
               AND created_at >= ?'
        );

        $stmt->execute([
            'LOGIN_FAILED',
            $ipAddress,
            $since->format(
                'Y-m-d H:i:s'
            ),
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function canAttempt(
        string $ipAddress,
        int $maxFailures,
        int $windowMinutes
    ): bool {
        return $this->countRecentFailures(
            $ipAddress,
            $windowMinutes
        ) < $maxFailures;
    }
}
