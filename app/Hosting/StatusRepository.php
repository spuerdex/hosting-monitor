<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Hosting;

use RuntimeException;

final class StatusRepository
{
    public function __construct(
        private string $path
    ) {
    }

    public function get(): array
    {
        if (!is_file($this->path)) {
            throw new RuntimeException(
                'Hosting status unavailable.'
            );
        }

        $raw = file_get_contents(
            $this->path
        );

        if ($raw === false) {
            throw new RuntimeException(
                'Unable to read hosting status.'
            );
        }

        $status = json_decode(
            $raw,
            true
        );

        if (!is_array($status)) {
            throw new RuntimeException(
                'Invalid hosting status.'
            );
        }

        return $status;
    }
}
