<?php

declare(strict_types=1);

use Digit\HostingAdmin\Support\Config;

$tmp = tempnam(sys_get_temp_dir(), 'cfg');

file_put_contents(
    $tmp,
    "APP_ENV=production\n"
    . "DB_HOST=127.0.0.1\n"
    . "DB_NAME=digit_hosting_admin\n"
);

$config = Config::load($tmp);

unlink($tmp);

assertSameValue(
    'production',
    $config['APP_ENV']
);

assertSameValue(
    '127.0.0.1',
    $config['DB_HOST']
);

assertSameValue(
    'digit_hosting_admin',
    $config['DB_NAME']
);

$registryPath = tempnam(
    sys_get_temp_dir(),
    'hosts'
);

file_put_contents(
    $registryPath,
    json_encode([
        'schema_version' => 2,
        'hosts' => [
            [
                'code' => 'cs',
                'name' => 'Computer Science',
                'base_url' => 'http://127.0.0.1:9001/api',
                'enabled' => true,
                'timeout_seconds' => 10,
                'api_token_env' => 'LOCAL_CS_API_TOKEN',
            ],
        ],
    ], JSON_THROW_ON_ERROR)
);

$hosts = Config::loadHostRegistry($registryPath);

unlink($registryPath);

assertSameValue(
    'cs',
    $hosts[0]['code']
);

assertSameValue(
    10,
    $hosts[0]['timeout_seconds']
);

$invalidRegistryPath = tempnam(
    sys_get_temp_dir(),
    'hosts-invalid'
);

file_put_contents(
    $invalidRegistryPath,
    json_encode([
        'schema_version' => 2,
        'hosts' => [
            [
                'code' => 'cs',
                'name' => 'Computer Science',
                'base_url' => 'file:///etc/passwd',
                'enabled' => true,
                'timeout_seconds' => 10,
                'api_token_env' => 'LOCAL_CS_API_TOKEN',
            ],
        ],
    ], JSON_THROW_ON_ERROR)
);

$rejected = false;

try {
    Config::loadHostRegistry($invalidRegistryPath);
} catch (RuntimeException) {
    $rejected = true;
} finally {
    unlink($invalidRegistryPath);
}

assertTrueValue(
    $rejected,
    'non-http host API URL must be rejected'
);
