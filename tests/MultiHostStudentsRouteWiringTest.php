<?php

declare(strict_types=1);

$source = file_get_contents(
    dirname(__DIR__) . '/public/index.php'
);

if (!is_string($source)) {
    throw new RuntimeException('Unable to read routing source.');
}

$studentsPosition = strpos(
    $source,
    "&& \$path === '/students'"
);

$systemPosition = strpos(
    $source,
    "&& \$path === '/system'",
    $studentsPosition === false ? 0 : $studentsPosition
);

assertTrueValue(
    $studentsPosition !== false
    && $systemPosition !== false
    && $studentsPosition < $systemPosition
);

$route = substr(
    $source,
    $studentsPosition,
    $systemPosition - $studentsPosition
);

assertTrueValue(
    str_contains(
        $route,
        'new MultiHostStatusRepository('
    )
);

assertTrueValue(
    str_contains(
        $route,
        '->monitoringPage('
    )
);

assertTrueValue(
    str_contains(
        $route,
        "\$_GET['host'] ?? 'all'"
    )
);

assertTrueValue(
    !str_contains(
        $route,
        '$loadStatus()'
    )
);

assertTrueValue(
    str_contains(
        $route,
        'http_response_code(503)'
    )
);

assertTrueValue(
    str_contains(
        $route,
        'http_response_code(400)'
    )
);
