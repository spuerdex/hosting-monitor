<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Controllers;

final class AuthController
{
    public function loginPage(
        ?string $error = null
    ): string {
        $errorHtml = '';

        if (
            $error !== null
            && $error !== ''
        ) {
            $safeError = htmlspecialchars(
                $error,
                ENT_QUOTES,
                'UTF-8'
            );

            $errorHtml = <<<HTML
<div class="auth-error">
{$safeError}
</div>
HTML;
        }

        return <<<HTML
<!doctype html>
<html lang="th">

<head>
<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<meta
    name="theme-color"
    content="#252525"
>

<title>
เข้าสู่ระบบ - DiGiT Hosting Admin
</title>

<link
    rel="stylesheet"
    href="/assets/css/app.css"
>
</head>

<body class="auth-page">

<div class="auth-shell">

<section class="auth-brand-panel">

    <div class="auth-brand">

        <div class="auth-brand-mark">
            D
        </div>

        <h1>
            DiGiT<br>
            Hosting Admin
        </h1>

        <p>
            ระบบบริหารและติดตาม
            Student Hosting
            คณะเทคโนโลยีดิจิทัล
            มหาวิทยาลัยราชภัฏเชียงราย
        </p>

    </div>

    <div class="auth-features">

        <div class="auth-feature">
            <span>●</span>
            <span>
                Secure Administration Portal
            </span>
        </div>

        <div class="auth-feature">
            <span>●</span>
            <span>
                Student Hosting Monitoring
            </span>
        </div>

        <div class="auth-feature">
            <span>●</span>
            <span>
                Protected Administrative Access
            </span>
        </div>

    </div>

</section>

<section class="auth-form-panel">

    <div class="auth-form-wrap">

        <h2>เข้าสู่ระบบ</h2>

        <p class="auth-subtitle">
            กรุณาเข้าสู่ระบบด้วยบัญชีผู้ดูแล
        </p>

        {$errorHtml}

        <form
            method="post"
            action="/login"
            autocomplete="on"
        >

            <div class="form-field">

                <label for="username">
                    Username
                </label>

                <input
                    class="form-control"
                    id="username"
                    name="username"
                    type="text"
                    required
                    autofocus
                    autocomplete="username"
                >

            </div>

            <div class="form-field">

                <label for="password">
                    Password
                </label>

                <input
                    class="form-control"
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                >

            </div>

            <button
                class="login-button"
                type="submit"
            >
                เข้าสู่ระบบ
            </button>

        </form>

        <div class="auth-footer">
            Faculty of Digital Technology ·
            Chiang Rai Rajabhat University
        </div>

    </div>

</section>

</div>

</body>
</html>
HTML;
    }
}
