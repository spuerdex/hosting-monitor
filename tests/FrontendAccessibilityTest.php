<?php

declare(strict_types=1);

$cssFile = dirname(__DIR__)
    . '/public/assets/css/app.css';

$jsFile = dirname(__DIR__)
    . '/public/assets/js/app.js';

$css = file_get_contents($cssFile);
$js = file_get_contents($jsFile);

foreach ([
    '--canvas',
    '--rail',
    '--surface',
    '--surface-raised',
    '--border',
    '--text',
    '--status-success',
    '--status-warning',
    '--status-danger',
    '--space-1',
    '--radius-sm',
    '--shadow-sm',
] as $token) {
    assertTrueValue(
        str_contains($css, $token),
        'missing design token: ' . $token
    );
}

assertTrueValue(
    str_contains($css, ':focus-visible')
);

assertTrueValue(
    str_contains($css, 'prefers-reduced-motion: reduce')
);

assertTrueValue(
    str_contains($css, 'status-success')
);

assertTrueValue(
    str_contains($css, 'status-warning')
);

assertTrueValue(
    str_contains($css, 'status-danger')
);

assertTrueValue(
    str_contains($js, 'data-refresh-status')
);

assertTrueValue(
    str_contains($js, 'data-status-feedback')
);

assertTrueValue(
    str_contains($js, 'aria-live')
);
