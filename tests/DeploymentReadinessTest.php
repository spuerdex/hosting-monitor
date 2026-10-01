<?php

declare(strict_types=1);

$service = file_get_contents(
    dirname(__DIR__)
    . '/ops/systemd/digit-hosting-admin-fetch.service'
);

$timer = file_get_contents(
    dirname(__DIR__)
    . '/ops/systemd/digit-hosting-admin-fetch.timer'
);

assertTrueValue(
    is_string($service),
    'fetch service must be readable'
);

assertTrueValue(
    is_string($timer),
    'fetch timer must be readable'
);

assertTrueValue(
    str_contains($service, 'NoNewPrivileges=yes')
);

assertTrueValue(
    str_contains($service, 'ProtectSystem=strict')
);

assertTrueValue(
    str_contains($service, 'ReadWritePaths=/var/lib/digit-hosting-admin/status')
);

assertTrueValue(
    str_contains($service, 'TimeoutStartSec=30s'),
    'collector must have a bounded startup time'
);

assertTrueValue(
    str_contains($timer, 'OnUnitActiveSec=60s')
);
