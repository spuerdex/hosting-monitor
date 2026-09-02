<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Views;

final class Layout
{
    public static function render(
        string $title,
        string $active,
        string $content,
        array $user
    ): string {
        $safeTitle = htmlspecialchars(
            $title,
            ENT_QUOTES,
            'UTF-8'
        );

        $name = htmlspecialchars(
            (string)(
                $user['display_name']
                ?? $user['username']
                ?? 'Admin'
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        $nav = static function (
            string $key,
            string $href,
            string $label
        ) use ($active): string {
            $class = $active === $key
                ? 'nav-link active'
                : 'nav-link';

            return sprintf(
                '<a class="%s" href="%s">%s</a>',
                $class,
                $href,
                $label
            );
        };

        $dashboard = $nav(
            'dashboard',
            '/dashboard',
            'Dashboard'
        );

        $students = $nav(
            'students',
            '/students',
            'Students'
        );

        $system = $nav(
            'system',
            '/system',
            'System'
        );

        $backup = $nav(
            'backup',
            '/backup',
            'Backup'
        );

        $manual = $nav(
            'manual',
            '/manual',
            'Manual'
        );

        return <<<HTML
<!doctype html>
<html lang="th">

<head>
<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>{$safeTitle} - DiGiT Hosting Admin</title>

<link
    rel="stylesheet"
    href="/assets/css/app.css"
>
</head>

<body>

<header class="topbar">

<a
    class="brand"
    href="/dashboard"
>
DiGiT Hosting Admin
</a>

<nav class="navigation">

{$dashboard}
{$students}
{$system}
{$backup}
{$manual}

<form
    method="post"
    action="/logout"
    class="logout-form"
>
<button
    type="submit"
    class="btn btn-danger"
>
ออกจากระบบ
</button>
</form>

</nav>

</header>

<main class="container">

<div class="user-line">
ผู้ดูแลระบบ: {$name}
</div>

{$content}

</main>

<footer class="footer">
<div>Faculty of Digital Technology</div>
<div>Chiang Rai Rajabhat University</div>
</footer>

</body>
</html>
HTML;
    }
}
