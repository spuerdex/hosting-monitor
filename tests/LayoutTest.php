<?php

declare(strict_types=1);

use Digit\HostingAdmin\Views\Layout;

$html = Layout::render(
    title: 'Test',
    active: 'system',
    content: '<p>Hello</p>',
    user: ['display_name' => 'admin']
);

assertTrueValue(
    str_contains($html, '/assets/css/app.css')
);

assertTrueValue(
    str_contains($html, 'href="/dashboard"')
);

assertTrueValue(
    str_contains($html, 'href="/students"')
);

assertTrueValue(
    str_contains($html, 'href="/system"')
);

assertTrueValue(
    str_contains($html, 'href="/backup"')
);

assertTrueValue(
    str_contains($html, 'href="/manual"')
);

assertTrueValue(
    str_contains($html, 'Faculty of Digital Technology')
);
