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

    public static function loadHostRegistry(
        string $path
    ): array {
        if (!is_file($path)) {
            throw new RuntimeException(
                'Host registry file missing.'
            );
        }

        $raw = file_get_contents($path);

        if ($raw === false) {
            throw new RuntimeException(
                'Unable to read host registry.'
            );
        }

        try {
            $document = json_decode(
                $raw,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $exception) {
            throw new RuntimeException(
                'Malformed host registry.',
                previous: $exception
            );
        }

        if (
            !is_array($document)
            || ($document['schema_version'] ?? null) !== 2
            || !isset($document['hosts'])
            || !is_array($document['hosts'])
            || !array_is_list($document['hosts'])
        ) {
            throw new RuntimeException(
                'Invalid host registry.'
            );
        }

        $hosts = [];
        $seenCodes = [];

        foreach ($document['hosts'] as $host) {
            if (!is_array($host)) {
                throw new RuntimeException(
                    'Invalid host registry.'
                );
            }

            $expectedFields = [
                'api_token_env',
                'base_url',
                'code',
                'enabled',
                'name',
                'timeout_seconds',
            ];

            $actualFields = array_keys($host);
            sort($actualFields);

            if ($actualFields !== $expectedFields) {
                throw new RuntimeException(
                    'Invalid host registry fields.'
                );
            }

            $code = $host['code'];

            if (
                !is_string($code)
                || preg_match(
                    '/^[a-z][a-z0-9_-]{0,31}$/D',
                    $code
                ) !== 1
                || in_array(
                    $code,
                    ['status', 'metadata'],
                    true
                )
                || isset($seenCodes[$code])
            ) {
                throw new RuntimeException(
                    'Invalid or duplicate host code.'
                );
            }

            if (
                !is_string($host['name'])
                || trim($host['name']) === ''
                || !is_string($host['base_url'])
                || filter_var(
                    $host['base_url'],
                    FILTER_VALIDATE_URL
                ) === false
                || !in_array(
                    strtolower(
                        (string) parse_url(
                            $host['base_url'],
                            PHP_URL_SCHEME
                        )
                    ),
                    ['http', 'https'],
                    true
                )
                || parse_url(
                    $host['base_url'],
                    PHP_URL_QUERY
                ) !== null
                || parse_url(
                    $host['base_url'],
                    PHP_URL_FRAGMENT
                ) !== null
                || !is_bool($host['enabled'])
                || !is_int($host['timeout_seconds'])
                || $host['timeout_seconds'] < 1
                || $host['timeout_seconds'] > 60
                || !is_string($host['api_token_env'])
                || preg_match(
                    '/^[A-Z][A-Z0-9_]*$/D',
                    $host['api_token_env']
                ) !== 1
            ) {
                throw new RuntimeException(
                    'Invalid host registry values.'
                );
            }

            $seenCodes[$code] = true;
            $hosts[] = $host;
        }

        return $hosts;
    }
}
