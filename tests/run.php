<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$files = glob(
    __DIR__ . '/*Test.php'
) ?: [];

$failed = 0;

foreach ($files as $file) {
    try {
        require $file;

        echo 'PASS: '
            . basename($file)
            . PHP_EOL;

    } catch (Throwable $e) {
        $failed++;

        fwrite(
            STDERR,
            'FAIL: '
            . basename($file)
            . ' - '
            . $e->getMessage()
            . PHP_EOL
        );
    }
}

exit($failed === 0 ? 0 : 1);
