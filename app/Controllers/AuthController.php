<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Controllers;

final class AuthController
{
    public function loginPage(
        ?string $error = null
    ): string {
        $errorHtml = '';

        if ($error !== null) {
            $errorHtml = sprintf(
                '<div class="alert">%s</div>',
                htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                )
            );
        }

        return <<<HTML
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DiGiT Hosting Admin</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    background: #f5f5f5;
    color: #333;
}

.page {
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 24px;
}

.card {
    width: 100%;
    max-width: 420px;
    background: #fff;
    border-radius: 18px;
    padding: 32px;
    box-shadow: 0 12px 40px rgba(0,0,0,.08);
}

.brand {
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 4px;
}

.subtitle {
    color: #777;
    margin-bottom: 28px;
}

label {
    display: block;
    font-weight: 600;
    margin-bottom: 7px;
}

input {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #ddd;
    border-radius: 10px;
    font-size: 16px;
    margin-bottom: 18px;
}

button {
    width: 100%;
    border: 0;
    border-radius: 10px;
    padding: 13px 16px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    background: #F15927;
    color: #fff;
}

.alert {
    padding: 12px;
    border-radius: 9px;
    margin-bottom: 18px;
    background: #fff1ed;
    color: #b53b16;
}

.footer {
    margin-top: 22px;
    text-align: center;
    color: #999;
    font-size: 13px;
}
</style>
</head>

<body>
<div class="page">

<div class="card">

<div class="brand">
DiGiT Hosting Admin
</div>

<div class="subtitle">
Student Hosting Management
</div>

{$errorHtml}

<form method="post" action="/login">

<label for="username">
ชื่อผู้ใช้
</label>

<input
    id="username"
    name="username"
    type="text"
    autocomplete="username"
    required
    autofocus
>

<label for="password">
รหัสผ่าน
</label>

<input
    id="password"
    name="password"
    type="password"
    autocomplete="current-password"
    required
>

<button type="submit">
เข้าสู่ระบบ
</button>

</form>

<div class="footer">
Faculty of Digital Technology
</div>

</div>
</div>
</body>
</html>
HTML;
    }
}
