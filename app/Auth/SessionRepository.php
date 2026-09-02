<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Auth;

use DateTimeImmutable;
use PDO;

final class SessionRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function create(
        int $adminUserId,
        string $sessionHash,
        string $ipAddress,
        string $userAgent,
        DateTimeImmutable $expiresAt
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO sessions
            (
                admin_user_id,
                session_hash,
                ip_address,
                user_agent,
                last_seen_at,
                expires_at
            )
            VALUES (
                ?,
                ?,
                ?,
                ?,
                NOW(),
                ?
            )'
        );

        $stmt->execute([
            $adminUserId,
            $sessionHash,
            $ipAddress,
            substr($userAgent, 0, 255),
            $expiresAt->format(
                'Y-m-d H:i:s'
            ),
        ]);
    }

    public function findValidByHash(
        string $sessionHash
    ): ?array {
        $stmt = $this->db->prepare(
            'SELECT
                s.id,
                s.admin_user_id,
                s.session_hash,
                s.ip_address,
                s.user_agent,
                s.last_seen_at,
                s.expires_at,
                s.created_at,
                u.username,
                u.display_name,
                u.is_active
             FROM sessions s
             INNER JOIN admin_users u
                ON u.id = s.admin_user_id
             WHERE s.session_hash = ?
               AND s.expires_at > NOW()
               AND u.is_active = 1
             LIMIT 1'
        );

        $stmt->execute([
            $sessionHash
        ]);

        $session = $stmt->fetch();

        return $session === false
            ? null
            : $session;
    }

    public function deleteByHash(
        string $sessionHash
    ): void {
        $stmt = $this->db->prepare(
            'DELETE FROM sessions
             WHERE session_hash = ?'
        );

        $stmt->execute([
            $sessionHash
        ]);
    }

    public function deleteExpired(): int
    {
        $stmt = $this->db->prepare(
            'DELETE FROM sessions
             WHERE expires_at <= NOW()'
        );

        $stmt->execute();

        return $stmt->rowCount();
    }
}
