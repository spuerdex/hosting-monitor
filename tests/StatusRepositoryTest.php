<?php

declare(strict_types=1);

use Digit\HostingAdmin\Hosting\StatusRepository;

$tmp = tempnam(
    sys_get_temp_dir(),
    'status'
);

file_put_contents(
    $tmp,
    json_encode([
        'schema_version' => 1,
        'overall_status' => 'HEALTHY',
        'students' => [
            [
                'student_id' => '691000001',
                'status' => 'enabled',
            ],
        ],
    ])
);

$repo = new StatusRepository($tmp);

$status = $repo->get();

assertSameValue(
    'HEALTHY',
    $status['overall_status']
);

assertSameValue(
    '691000001',
    $status['students'][0]['student_id']
);

unlink($tmp);
