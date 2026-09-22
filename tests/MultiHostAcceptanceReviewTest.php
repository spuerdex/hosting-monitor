<?php

declare(strict_types=1);

use Digit\HostingAdmin\Hosting\MultiHostStatusRepository;

(static function (): void {
    $root = sys_get_temp_dir() . '/digit-task41-audit-' . bin2hex(random_bytes(8));
    if (!mkdir($root . '/metadata', 0700, true)) {
        throw new RuntimeException('Unable to prepare review fixture.');
    }

    $time = '2026-09-18T10:00:00+00:00';
    $now = new DateTimeImmutable($time);
    $hosts = [
        ['code' => 'cs', 'name' => 'Computer Science', 'ip' => '192.0.2.11',
            'ssh_user' => 'hostingportal', 'enabled' => true],
        ['code' => 'it', 'name' => 'Information Technology', 'ip' => '192.0.2.12',
            'ssh_user' => 'hostingportal', 'enabled' => true],
        ['code' => 'ai', 'name' => 'Artificial Intelligence', 'ip' => '192.0.2.13',
            'ssh_user' => 'hostingportal', 'enabled' => false],
    ];
    $metadata = static fn (string $code): array => [
        'host_code' => $code, 'last_attempt' => $time,
        'last_success' => $time, 'fetch_state' => 'SUCCESS',
        'error_category' => null,
    ];
    $status = static fn (string $username): array => [
        'schema_version' => 1, 'generated_at' => $time,
        'overall_status' => 'HEALTHY', 'services' => [], 'storage' => [],
        'summary' => ['students' => 1], 'backup' => [],
        'students' => [['student_id' => '69001', 'username' => $username]],
    ];
    $write = static function (string $path, array $data): void {
        if (file_put_contents($path, json_encode($data, JSON_THROW_ON_ERROR)) === false) {
            throw new RuntimeException('Unable to write review fixture.');
        }
    };
    $reset = static function () use ($root, $hosts, $metadata, $status, $write): void {
        $write($root . '/hosts.json', ['schema_version' => 1, 'hosts' => $hosts]);
        foreach (['cs', 'it'] as $code) {
            $write($root . '/metadata/' . $code . '.json', $metadata($code));
            $write($root . '/' . $code . '.json', $status('s69001'));
        }
    };
    $repository = new MultiHostStatusRepository($root . '/hosts.json', $root);
    $failures = [];
    $check = static function (bool $condition, string $case) use (&$failures): void {
        if (!$condition) {
            $failures[] = $case;
        }
    };
    $registryRejects = static function (array $entries) use ($root, $write, $repository, $now): bool {
        $write($root . '/hosts.json', ['schema_version' => 1, 'hosts' => $entries]);
        try {
            $repository->all($now);
            return false;
        } catch (RuntimeException $e) {
            return true;
        }
    };

    try {
        $reset();
        $snapshot = $repository->all($now);
        $check(count($snapshot) === 2 && !isset($snapshot['ai']), 'only enabled CS and IT included');
        $check(($snapshot['cs']['account_count'] ?? null) === 1, 'CS account counted');
        $check(($snapshot['it']['account_count'] ?? null) === 1, 'IT account counted separately');
        $check(($snapshot['cs']['status']['students'][0]['student_id'] ?? null) ===
            ($snapshot['it']['status']['students'][0]['student_id'] ?? null),
            'same student ID on two hosts remains separate');

        foreach (['status', 'metadata'] as $reserved) {
            $invalid = $hosts;
            $invalid[0]['code'] = $reserved;
            $check($registryRejects($invalid), "reserved code {$reserved} rejected");
        }

        $reset();
        unlink($root . '/it.json');
        $snapshot = $repository->all($now);
        $check(($snapshot['cs']['account_count'] ?? null) === 1
            && $snapshot['it']['account_count'] === null
            && $snapshot['it']['status'] === null,
            'missing IT cache isolated from live CS');

        $reset();
        $badMeta = $metadata('it');
        $badMeta['host_code'] = 'cs';
        $write($root . '/metadata/it.json', $badMeta);
        $snapshot = $repository->all($now);
        $check($snapshot['it']['account_count'] === null
            && ($snapshot['cs']['account_count'] ?? null) === 1,
            'mismatched IT metadata isolated');

        $reset();
        $badStatus = $status('s69001');
        $badStatus['students'] = ['not-a-student-object'];
        $write($root . '/it.json', $badStatus);
        $snapshot = $repository->all($now);
        $check($snapshot['it']['status'] === null
            && $snapshot['it']['account_count'] === null,
            'malformed student row must not be counted');

        $reset();
        $badMeta = $metadata('it');
        $badMeta['fetch_state'] = 'SOMETHING_UNRECOGNIZED';
        $write($root . '/metadata/it.json', $badMeta);
        $snapshot = $repository->all($now);
        $check($snapshot['it']['display_state'] === 'UNAVAILABLE'
            && $snapshot['it']['status'] === null,
            'unrecognized fetch state must not be displayed as trusted state');

        $reset();
        $badMeta = $metadata('it');
        $badMeta['fetch_state'] = 'UNREACHABLE';
        $badMeta['last_success'] = 'not-a-time';
        $write($root . '/metadata/it.json', $badMeta);
        $snapshot = $repository->all($now);
        $check($snapshot['it']['display_state'] === 'UNAVAILABLE'
            && $snapshot['it']['account_count'] === null,
            'invalid offline last_success must invalidate metadata');

        $reset();
        $write($root . '/metadata/it.json', [
            'host_code' => 'it', 'last_attempt' => $time,
            'last_success' => null, 'fetch_state' => 'PUBLISHING',
            'error_category' => null,
        ]);
        $snapshot = $repository->all($now);
        $check($snapshot['it']['display_state'] === 'PUBLISHING'
            && $snapshot['it']['account_count'] === null,
            'PUBLISHING allows null last_success, never current accounts');

        $reset();
        $future = $metadata('it');
        $future['last_success'] = '2026-09-18T10:00:01+00:00';
        $write($root . '/metadata/it.json', $future);
        $snapshot = $repository->all($now);
        $check($snapshot['it']['display_state'] === 'STALE'
            && $snapshot['it']['status'] === null,
            'future last_success never live');

        $reset();
        $badStatus = $status('s69001');
        $badStatus['summary']['students'] = 99;
        $write($root . '/it.json', $badStatus);
        $snapshot = $repository->all($now);
        $check($snapshot['it']['account_count'] === 1,
            'count entries, do not trust summary count');

        $reset();
        file_put_contents($root . '/metadata/it.json', '{broken');
        $snapshot = $repository->all($now);
        $check($snapshot['it']['status'] === null
            && $snapshot['it']['account_count'] === null,
            'malformed per-host metadata isolated');

        $reset();
        file_put_contents($root . '/it.json', '{broken');
        $snapshot = $repository->all($now);
        $check($snapshot['it']['status'] === null
            && $snapshot['it']['account_count'] === null,
            'malformed per-host cache isolated');

        $check($registryRejects([array_replace($hosts[0], ['code' => '../etc'])]),
            'path traversal host code rejected');
        $reset();
        file_put_contents($root . '/hosts.json', '{broken');
        try {
            $repository->all($now);
            $failures[] = 'malformed registry must throw';
        } catch (RuntimeException $e) {
            // Expected.
        }

        $reset();
        unlink($root . '/metadata/it.json');
        $snapshot = $repository->all($now);
        $check(($snapshot['cs']['account_count'] ?? null) === 1
            && $snapshot['it']['status'] === null
            && $snapshot['it']['account_count'] === null,
            'missing metadata isolated from other host');

        $reset();
        $emptyStatus = $status('s69001');
        $emptyStatus['students'] = [];
        $emptyStatus['summary']['students'] = 0;
        $write($root . '/it.json', $emptyStatus);
        $snapshot = $repository->all($now);
        $check($snapshot['it']['account_count'] === 0
            && is_array($snapshot['it']['status']),
            'valid zero accounts must differ from unavailable null');

        $reset();
        unlink($root . '/it.json');
        if (!symlink($root . '/cs.json', $root . '/it.json')) {
            throw new RuntimeException('Symlink fixture could not be prepared.');
        }
        $snapshot = $repository->all($now);
        $check($snapshot['it']['status'] === null
            && $snapshot['it']['account_count'] === null,
            'symlink cache rejected');
        unlink($root . '/it.json');

        $reset();
        file_put_contents($root . '/it.json', str_repeat('X', 1048577));
        $snapshot = $repository->all($now);
        $check($snapshot['it']['status'] === null
            && $snapshot['it']['account_count'] === null,
            'oversized cache rejected');

        assertSameValue([], $failures, 'Task 4.1 acceptance review');
    } finally {
        foreach (glob($root . '/metadata/*') ?: [] as $fixture) {
            if (is_file($fixture) || is_link($fixture)) {
                unlink($fixture);
            }
        }
        foreach (glob($root . '/*') ?: [] as $fixture) {
            if (is_file($fixture) || is_link($fixture)) {
                unlink($fixture);
            }
        }
        rmdir($root . '/metadata');
        rmdir($root);
    }
})();
