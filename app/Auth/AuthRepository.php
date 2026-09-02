<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Auth;

use PDO;

final class AuthRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function findByUsername(
        string $username
    ): ?array {
        $stmt = $this->db->prepare(
            'SELECT
                id,
                username,
                password_hash,
                display_name,
                is_active,
                last_login_at,
                created_at,
                updated_at
             FROM admin_users
             WHERE username = ?
             LIMIT 1'
        );

        $stmt->execute([
            $username
        ]);

        $user = $stmt->fetch();

        return $user === false
            ? null
            : $user;
    }
}
