<?php

declare(strict_types=1);

$source = file_get_contents(
    dirname(__DIR__) . '/public/index.php'
);

if (!is_string($source)) {
    throw new RuntimeException('Unable to read routing source.');
}

$authPosition = strpos(
    $source,
    '&& !$currentUser'
);

$dashboardPosition = strpos(
    $source,
    "&& \$path === '/dashboard'"
);

$studentsPosition = strpos(
    $source,
    "&& \$path === '/students'",
    $dashboardPosition === false ? 0 : $dashboardPosition
);

assertTrueValue(
    $authPosition !== false
    && $dashboardPosition !== false
    && $studentsPosition !== false
    && $authPosition < $dashboardPosition
    && $dashboardPosition < $studentsPosition
);

$dashboardRoute = substr(
    $source,
    $dashboardPosition,
    $studentsPosition - $dashboardPosition
);

if (
    !str_contains(
        $source,
        'use Digit\HostingAdmin\Hosting\MultiHostStatusRepository;'
    )
    || !str_contains(
        $dashboardRoute,
        'new MultiHostStatusRepository('
    )
) {
    throw new RuntimeException(
        'Dashboard is not wired to MultiHostStatusRepository.'
    );
}

assertTrueValue(
    str_contains($dashboardRoute, '->monitoringPage(')
);

assertTrueValue(
    str_contains($dashboardRoute, "\$_GET['host'] ?? null")
);

assertTrueValue(
    !str_contains($dashboardRoute, '$loadStatus()')
);

assertTrueValue(
    str_contains($dashboardRoute, 'InvalidArgumentException')
);

assertTrueValue(
    str_contains($dashboardRoute, 'http_response_code(400)')
);
