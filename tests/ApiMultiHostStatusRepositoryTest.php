<?php

declare(strict_types=1);

use Digit\HostingAdmin\Hosting\MultiHostStatusRepository;

$root = sys_get_temp_dir()
    . '/digit-api-hosts-'
    . bin2hex(random_bytes(6));

mkdir($root . '/metadata', 0700, true);

$time = '2026-09-30T12:00:00+00:00';

$write = static function (
    string $path,
    array $value
): void {
    file_put_contents(
        $path,
        json_encode(
            $value,
            JSON_THROW_ON_ERROR
        )
    );
};

try {
    $write(
        $root . '/hosts.json',
        [
            'schema_version' => 2,
            'hosts' => [
                [
                    'code' => 'cs',
                    'name' => 'Computer Science',
                    'base_url' => 'http://127.0.0.1:9001/api',
                    'enabled' => true,
                    'timeout_seconds' => 3,
                    'api_token_env' => 'CS_API_TOKEN',
                ],
            ],
        ]
    );

    $write(
        $root . '/metadata/cs.json',
        [
            'host_code' => 'cs',
            'last_attempt' => $time,
            'last_success' => $time,
            'fetch_state' => 'SUCCESS',
            'error_category' => null,
        ]
    );

    $write(
        $root . '/cs.json',
        [
            'schema_version' => 1,
            'generated_at' => $time,
            'overall_status' => 'HEALTHY',
            'services' => [],
            'storage' => [],
            'summary' => [],
            'backup' => [],
            'students' => [
                [
                    'student_id' => 'cs-001',
                    'domain' => 'cs-001.example.test',
                ],
            ],
        ]
    );

    $repository = new MultiHostStatusRepository(
        $root . '/hosts.json',
        $root
    );

    $result = $repository->all(
        new DateTimeImmutable($time)
    );

    assertSameValue(
        1,
        $result['cs']['account_count'] ?? null,
        'API host accounts must be visible'
    );

    assertSameValue(
        'cs',
        $result['cs']['code'] ?? null,
        'API host code must remain scoped'
    );
} finally {
    foreach (glob($root . '/metadata/*') ?: [] as $path) {
        if (is_file($path) || is_link($path)) {
            unlink($path);
        }
    }

    foreach (glob($root . '/*') ?: [] as $path) {
        if (is_file($path) || is_link($path)) {
            unlink($path);
        }
    }

    rmdir($root . '/metadata');
    rmdir($root);
}
