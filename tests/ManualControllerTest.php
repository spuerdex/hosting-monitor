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

assertTrueValue(
    str_contains($html, 'aria-labelledby="manual-title"')
);

assertTrueValue(
    str_contains($html, 'Read-only monitoring reference')
);

assertTrueValue(
    str_contains($html, 'role="note"')
);

assertTrueValue(
    str_contains($html, 'aria-label="Manual command reference"')
);

assertTrueValue(
    str_contains($html, 'aria-label="Copy command:' )
);

assertTrueValue(
    str_contains($html, 'data-copy-feedback')
);

assertTrueValue(
    str_contains($html, 'aria-live="polite"')
);
