<?php

declare(strict_types=1);

use Digit\HostingAdmin\Controllers\AuthController;

$controller = new AuthController();

$html = $controller->loginPage(
    error: null
);

assertTrueValue(
    str_contains($html, 'DiGiT Hosting Admin')
);

assertTrueValue(
    str_contains($html, 'name="username"')
);

assertTrueValue(
    str_contains($html, 'name="password"')
);

assertTrueValue(
    str_contains($html, 'เข้าสู่ระบบ')
);

$errorHtml = $controller->loginPage(
    error: 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'
);

assertTrueValue(
    str_contains(
        $errorHtml,
        'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'
    )
);

assertTrueValue(
    str_contains($html, 'auth-console-label')
);

assertTrueValue(
    str_contains($html, 'aria-labelledby="login-title"')
);

assertTrueValue(
    str_contains($html, 'aria-label="Username"')
);

assertTrueValue(
    str_contains($html, 'aria-label="Password"')
);

assertTrueValue(
    str_contains($errorHtml, 'role="alert"')
);

assertTrueValue(
    str_contains($errorHtml, 'aria-live="assertive"')
);
