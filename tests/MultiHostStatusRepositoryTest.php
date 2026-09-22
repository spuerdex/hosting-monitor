<?php

declare(strict_types=1);

use Digit\HostingAdmin\Hosting\MultiHostStatusRepository;

$dir = sys_get_temp_dir()
    . '/digit-monitor-'
    . bin2hex(random_bytes(8));

mkdir($dir . '/metadata', 0700, true);

try {
    $timestamp = '2026-09-18T10:00:00+00:00';

    file_put_contents(
        $dir . '/hosts.json',
        json_encode([
            'schema_version' => 1,
            'hosts' => [
                [
                    'code' => 'cs',
                    'name' => 'Computer Science',
                    'ip' => '192.0.2.11',
                    'ssh_user' => 'hostingportal',
                    'enabled' => true,
                ],
            ],
        ], JSON_THROW_ON_ERROR)
    );

    file_put_contents(
        $dir . '/metadata/cs.json',
        json_encode([
            'host_code' => 'cs',
            'last_attempt' => $timestamp,
            'last_success' => $timestamp,
            'fetch_state' => 'SUCCESS',
            'error_category' => null,
        ], JSON_THROW_ON_ERROR)
    );

    file_put_contents(
        $dir . '/cs.json',
        json_encode([
            'schema_version' => 1,
            'generated_at' => $timestamp,
            'overall_status' => 'HEALTHY',
            'services' => [],
            'storage' => [],
            'summary' => ['students' => 1],
            'backup' => [],
            'students' => [
                [
                    'student_id' => '69001',
                    'username' => 's69001',
                ],
            ],
        ], JSON_THROW_ON_ERROR)
    );

    $repository = new MultiHostStatusRepository(
        $dir . '/hosts.json',
        $dir
    );

    $hosts = $repository->all(
        new DateTimeImmutable($timestamp)
    );

    assertSameValue(
        1,
        $hosts['cs']['account_count']
    );

    assertSameValue(
        'HEALTHY',
        $hosts['cs']['status']['overall_status']
    );


    // Duplicate host codes must invalidate the entire registry.
    $registry = json_decode(
        (string) file_get_contents($dir . '/hosts.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    $registry['hosts'][] = $registry['hosts'][0];

    file_put_contents(
        $dir . '/hosts.json',
        json_encode($registry, JSON_THROW_ON_ERROR)
    );

    $duplicateRejected = false;

    try {
        $repository->all(
            new DateTimeImmutable($timestamp)
        );
    } catch (RuntimeException $e) {
        $duplicateRejected = true;
    }

    assertSameValue(true, $duplicateRejected);

    // Invalid IPv4 addresses must invalidate the registry.
    $registry['hosts'] = [$registry['hosts'][0]];
    $registry['hosts'][0]['ip'] = 'not-an-ip';

    file_put_contents(
        $dir . '/hosts.json',
        json_encode($registry, JSON_THROW_ON_ERROR)
    );

    $invalidIpRejected = false;

    try {
        $repository->all(
            new DateTimeImmutable($timestamp)
        );
    } catch (RuntimeException $e) {
        $invalidIpRejected = true;
    }

    assertSameValue(true, $invalidIpRejected);

    // A cache missing a required v1 field must not be published.
    $registry['hosts'][0]['ip'] = '192.0.2.11';

    file_put_contents(
        $dir . '/hosts.json',
        json_encode($registry, JSON_THROW_ON_ERROR)
    );

    $status = json_decode(
        (string) file_get_contents($dir . '/cs.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    unset($status['backup']);

    file_put_contents(
        $dir . '/cs.json',
        json_encode($status, JSON_THROW_ON_ERROR)
    );

    $incompleteHosts = $repository->all(
        new DateTimeImmutable($timestamp)
    );

    assertSameValue(null, $incompleteHosts['cs']['status']);
    assertSameValue(null, $incompleteHosts['cs']['account_count']);
    assertSameValue(
        'UNAVAILABLE',
        $incompleteHosts['cs']['display_state']
    );




    // Every required v1 status field must be present.
    $completeStatus = $status;
    $completeStatus['backup'] = [];

    foreach (
        ['generated_at', 'services', 'storage', 'summary']
        as $missingRequiredField
    ) {
        $invalidStatus = $completeStatus;
        unset($invalidStatus[$missingRequiredField]);

        file_put_contents(
            $dir . '/cs.json',
            json_encode($invalidStatus, JSON_THROW_ON_ERROR)
        );

        $invalidHosts = $repository->all(
            new DateTimeImmutable($timestamp)
        );

        assertSameValue(null, $invalidHosts['cs']['status']);
        assertSameValue(null, $invalidHosts['cs']['account_count']);
        assertSameValue(
            'UNAVAILABLE',
            $invalidHosts['cs']['display_state']
        );
    }


    // Restore the complete cache after required-fields tests.
    file_put_contents(
        $dir . '/cs.json',
        json_encode($completeStatus, JSON_THROW_ON_ERROR)
    );

    // Simulate metadata changing between the two reads.
    $metadataPath = $dir . '/metadata/cs.json';

    $originalMetadata = (string) file_get_contents(
        $metadataPath
    );

    $changedMetadata = json_decode(
        $originalMetadata,
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    $changedMetadata['fetch_state'] = 'PUBLISHING';

    $changedMetadata = json_encode(
        $changedMetadata,
        JSON_THROW_ON_ERROR
    );

    $raceMetadataReads = 0;

    $raceReader = function (string $path) use (
        $metadataPath,
        $originalMetadata,
        $changedMetadata,
        &$raceMetadataReads
    ): ?string {
        if ($path === $metadataPath) {
            $raceMetadataReads++;

            return $raceMetadataReads === 1
                ? $originalMetadata
                : $changedMetadata;
        }

        $contents = file_get_contents($path);

        return is_string($contents) ? $contents : null;
    };

    $raceRepository = new MultiHostStatusRepository(
        $dir . '/hosts.json',
        $dir,
        180,
        $raceReader
    );

    $raceHosts = $raceRepository->all(
        new DateTimeImmutable($timestamp)
    );

    assertSameValue(null, $raceHosts['cs']['status']);
    assertSameValue(null, $raceHosts['cs']['account_count']);
    assertSameValue(
        'UNAVAILABLE',
        $raceHosts['cs']['display_state']
    );


    // Non-success fetch states must never expose current accounts.
    $offlineFetchStates = ['PUBLISHING', 'UNREACHABLE'];

    foreach ($offlineFetchStates as $offlineState) {
        $offlineMetadata = json_decode(
            $originalMetadata,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $offlineMetadata['fetch_state'] = $offlineState;

        file_put_contents(
            $metadataPath,
            json_encode($offlineMetadata, JSON_THROW_ON_ERROR)
        );

        $offlineHosts = $repository->all(
            new DateTimeImmutable($timestamp)
        );

        assertSameValue(
            $offlineState,
            $offlineHosts['cs']['fetch_state']
        );
        assertSameValue(
            $offlineState,
            $offlineHosts['cs']['display_state']
        );
        assertSameValue(null, $offlineHosts['cs']['status']);
        assertSameValue(null, $offlineHosts['cs']['account_count']);
        assertSameValue(
            $timestamp,
            $offlineHosts['cs']['last_success']
        );
    }

    // A successful fetch older than 180 seconds is STALE.
    file_put_contents($metadataPath, $originalMetadata);

    $staleHosts = $repository->all(
        (new DateTimeImmutable($timestamp))->modify('+181 seconds')
    );

    assertSameValue(
        'STALE',
        $staleHosts['cs']['display_state']
    );
    assertSameValue(null, $staleHosts['cs']['status']);
    assertSameValue(null, $staleHosts['cs']['account_count']);


    // Invalid generated_at must not be accepted as current status.
    $invalidTimestampStatus = $completeStatus;
    $invalidTimestampStatus['generated_at'] = 'not-a-timestamp';

    file_put_contents(
        $dir . '/cs.json',
        json_encode($invalidTimestampStatus, JSON_THROW_ON_ERROR)
    );

    $invalidGeneratedAtHosts = $repository->all(
        new DateTimeImmutable($timestamp)
    );

    assertSameValue(
        null,
        $invalidGeneratedAtHosts['cs']['status']
    );

    assertSameValue(
        null,
        $invalidGeneratedAtHosts['cs']['account_count']
    );

    assertSameValue(
        'UNAVAILABLE',
        $invalidGeneratedAtHosts['cs']['display_state']
    );


    // Invalid metadata timestamp must not expose current status.
    file_put_contents(
        $dir . '/cs.json',
        json_encode($completeStatus, JSON_THROW_ON_ERROR)
    );

    $invalidAttemptMetadata = json_decode(
        $originalMetadata,
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    $invalidAttemptMetadata['last_attempt'] = 'not-a-timestamp';

    file_put_contents(
        $metadataPath,
        json_encode($invalidAttemptMetadata, JSON_THROW_ON_ERROR)
    );

    $invalidAttemptHosts = $repository->all(
        new DateTimeImmutable($timestamp)
    );

    assertSameValue(null, $invalidAttemptHosts['cs']['status']);
    assertSameValue(null, $invalidAttemptHosts['cs']['account_count']);
    assertSameValue(
        'UNAVAILABLE',
        $invalidAttemptHosts['cs']['display_state']
    );


    // last_success must use the agreed ISO 8601 format.
    $invalidSuccessMetadata = json_decode(
        $originalMetadata,
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    $invalidSuccessMetadata['last_success'] =
        '2026-09-18 10:00:00+00:00';

    file_put_contents(
        $metadataPath,
        json_encode(
            $invalidSuccessMetadata,
            JSON_THROW_ON_ERROR
        )
    );

    $invalidSuccessHosts = $repository->all(
        new DateTimeImmutable($timestamp)
    );

    assertSameValue(null, $invalidSuccessHosts['cs']['status']);
    assertSameValue(null, $invalidSuccessHosts['cs']['account_count']);
    assertSameValue(
        'UNAVAILABLE',
        $invalidSuccessHosts['cs']['display_state']
    );


    // Reject malformed registry entries before using host identity.
    $validHost = [
        'code' => 'cs',
        'name' => 'Computer Science',
        'ip' => '192.0.2.11',
        'ssh_user' => 'hostingportal',
        'enabled' => true,
    ];

    $invalidRegistryCases = [
        'invalid_ssh_user' => array_replace(
            $validHost,
            ['ssh_user' => 'root']
        ),
        'missing_name' => array_diff_key(
            $validHost,
            ['name' => true]
        ),
        'blank_name' => array_replace(
            $validHost,
            ['name' => '']
        ),
        'extra_field' => $validHost + [
            'unexpected_field' => true,
        ],
    ];

    $acceptedInvalidRegistries = [];

    foreach ($invalidRegistryCases as $caseName => $invalidHost) {
        file_put_contents(
            $dir . '/hosts.json',
            json_encode([
                'schema_version' => 1,
                'hosts' => [$invalidHost],
            ], JSON_THROW_ON_ERROR)
        );

        try {
            $repository->all(new DateTimeImmutable($timestamp));
            $acceptedInvalidRegistries[] = $caseName;
        } catch (RuntimeException $e) {
            // Expected: reject the malformed registry.
        }
    }

    // Restore the valid registry before reporting the result.
    file_put_contents(
        $dir . '/hosts.json',
        json_encode([
            'schema_version' => 1,
            'hosts' => [$validHost],
        ], JSON_THROW_ON_ERROR)
    );

    assertSameValue([], $acceptedInvalidRegistries);

} finally {
    foreach (glob($dir . '/metadata/*') ?: [] as $fixtureFile) {
        unlink($fixtureFile);
    }

    foreach (glob($dir . '/*') ?: [] as $fixtureFile) {
        if (is_file($fixtureFile)) {
            unlink($fixtureFile);
        }
    }

    rmdir($dir . '/metadata');
    rmdir($dir);
}
