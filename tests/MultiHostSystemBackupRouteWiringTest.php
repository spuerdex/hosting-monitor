<?php
declare(strict_types=1);

$source = file_get_contents(
    dirname(__DIR__) . '/public/index.php'
);

assertTrueValue(
    is_string($source)
);


/*
|--------------------------------------------------------------------------
| Extract /system route
|--------------------------------------------------------------------------
*/

$systemStart = strpos(
    $source,
    "&& \$path === '/system'"
);

$backupStart = strpos(
    $source,
    "&& \$path === '/backup'"
);

assertTrueValue(
    $systemStart !== false
);

assertTrueValue(
    $backupStart !== false
);

assertTrueValue(
    $backupStart > $systemStart
);

$systemRoute = substr(
    $source,
    $systemStart,
    $backupStart - $systemStart
);


/*
|--------------------------------------------------------------------------
| /system must use multi-host monitoring
|--------------------------------------------------------------------------
*/

assertTrueValue(
    str_contains(
        $systemRoute,
        'MultiHostStatusRepository'
    )
);

assertTrueValue(
    str_contains(
        $systemRoute,
        '->monitoringPage('
    )
);

assertTrueValue(
    str_contains(
        $systemRoute,
        "\$_GET['host'] ?? null"
    )
);

assertTrueValue(
    !str_contains(
        $systemRoute,
        '$loadStatus()'
    )
);


/*
|--------------------------------------------------------------------------
| Extract /backup route
|--------------------------------------------------------------------------
*/

$manualStart = strpos(
    $source,
    "&& \$path === '/manual'"
);

assertTrueValue(
    $manualStart !== false
);

assertTrueValue(
    $manualStart > $backupStart
);

$backupRoute = substr(
    $source,
    $backupStart,
    $manualStart - $backupStart
);


/*
|--------------------------------------------------------------------------
| /backup must use multi-host monitoring
|--------------------------------------------------------------------------
*/

assertTrueValue(
    str_contains(
        $backupRoute,
        'MultiHostStatusRepository'
    )
);

assertTrueValue(
    str_contains(
        $backupRoute,
        '->monitoringPage('
    )
);

assertTrueValue(
    str_contains(
        $backupRoute,
        "\$_GET['host'] ?? null"
    )
);

assertTrueValue(
    !str_contains(
        $backupRoute,
        '$loadStatus()'
    )
);
