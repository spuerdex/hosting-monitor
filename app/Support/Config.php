<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Support;

use RuntimeException;

final class Config
{
    public static function load(
        string $path
    ): array {
        if (!is_file($path)) {
            throw new RuntimeException(
                'Configuration file missing.'
            );
        }

        $result = [];

        $lines = file(
            $path,
            FILE_IGNORE_NEW_LINES
            | FILE_SKIP_EMPTY_LINES
        ) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (
                $line === ''
                || str_starts_with($line, '#')
            ) {
                continue;
            }

            [$key, $value] = array_pad(
                explode('=', $line, 2),
                2,
                ''
            );

            $result[trim($key)] = trim($value);
        }

        return $result;
    }
}
