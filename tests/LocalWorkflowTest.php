<?php

declare(strict_types=1);

use Digit\HostingAdmin\Support\Config;

$path = dirname(__DIR__)
    . '/config/hosts.local.example.json';

$hosts = Config::loadHostRegistry($path);

assertSameValue(
    2,
    count($hosts),
    'local workflow must include multiple hosts'
);

foreach ($hosts as $host) {
    assertSameValue(
        '127.0.0.1',
        parse_url($host['base_url'], PHP_URL_HOST),
        'local host must point to loopback'
    );

    assertTrueValue(
        !str_contains(
            strtolower(json_encode($host, JSON_THROW_ON_ERROR)),
            'password'
        ),
        'local registry must not contain passwords'
    );
}

$fixtureRoot = dirname(__DIR__) . '/fixtures/hosts';

foreach (['cs', 'it'] as $code) {
    assertTrueValue(
        is_file($fixtureRoot . '/' . $code . '/status.json'),
        'fixture must exist for each local host'
    );
}

assertTrueValue(
    is_file(dirname(__DIR__) . '/scripts/start-local-mock-api.ps1'),
    'local mock start script must exist'
);

assertTrueValue(
    is_file(dirname(__DIR__) . '/scripts/run-local-collector.ps1'),
    'local collector script must exist'
);
