<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Support;

use PDO;

final class Database
{
    public static function connect(
        array $config
    ): PDO {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['DB_HOST'],
            $config['DB_NAME']
        );

        return new PDO(
            $dsn,
            $config['DB_USER'],
            $config['DB_PASS'],
            [
                PDO::ATTR_ERRMODE
                    => PDO::ERRMODE_EXCEPTION,

                PDO::ATTR_DEFAULT_FETCH_MODE
                    => PDO::FETCH_ASSOC,

                PDO::ATTR_EMULATE_PREPARES
                    => false,
            ]
        );
    }
}
