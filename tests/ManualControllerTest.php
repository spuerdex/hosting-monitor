<?php

declare(strict_types=1);

use Digit\HostingAdmin\Controllers\ManualController;

$html = (new ManualController())->page(
    ['display_name' => 'admin']
);

assertTrueValue(
    str_contains($html, 'digit-student-create')
);

assertTrueValue(
    str_contains($html, 'digit-student-reset-password')
);

assertTrueValue(
    str_contains($html, 'digit-hosting-health')
);

assertTrueValue(
    str_contains($html, 'digit-hosting-backup')
);
