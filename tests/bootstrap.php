<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';


function assertSameValue(
    mixed $expected,
    mixed $actual,
    string $message = ''
): void {
    if ($expected !== $actual) {
        throw new RuntimeException(
            ($message !== '' ? $message . ': ' : '')
            . 'expected '
            . var_export($expected, true)
            . ', got '
            . var_export($actual, true)
        );
    }
}

function assertTrueValue(
    bool $value,
    string $message = ''
): void {
    assertSameValue(
        true,
        $value,
        $message
    );
}
