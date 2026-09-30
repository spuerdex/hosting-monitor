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

        $displayName = htmlspecialchars(
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
            string $label,
            string $icon
        ) use ($active): string {
            $class = $active === $key
                ? 'sidebar-link is-active'
                : 'sidebar-link';

            $current = $active === $key
                ? ' aria-current="page"'
                : '';

            return <<<HTML
<a class="{$class}" href="{$href}"{$current}>
    <span class="sidebar-icon">{$icon}</span>
    <span>{$label}</span>
</a>
HTML;
        };

        $dashboardIcon = self::iconDashboard();
        $studentsIcon = self::iconStudents();
        $systemIcon = self::iconSystem();
        $backupIcon = self::iconBackup();
        $manualIcon = self::iconManual();

        $dashboard = $nav(
            'dashboard',
            '/dashboard',
            'Dashboard',
            $dashboardIcon
        );

        $students = $nav(
            'students',
            '/students',
            'Students',
            $studentsIcon
        );

        $system = $nav(
            'system',
            '/system',
            'System',
            $systemIcon
        );

        $backup = $nav(
            'backup',
            '/backup',
            'Backup',
            $backupIcon
        );

        $manual = $nav(
            'manual',
            '/manual',
            'Manual',
            $manualIcon
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

<meta
    name="theme-color"
    content="#252525"
>

<title>{$safeTitle} - DiGiT Hosting Admin</title>

<link
    rel="stylesheet"
    href="/assets/css/app.css"
>

<script
    src="/assets/js/app.js"
    defer
></script>

</head>

<body>

<div class="app-shell">

<aside class="sidebar" data-sidebar>

    <div class="sidebar-brand">
        <a href="/dashboard" class="brand-link">
            <span class="brand-mark">D</span>

            <span class="brand-copy">
                <strong>DiGiT</strong>
                <small>Hosting Admin</small>
            </span>
        </a>
    </div>

    <div class="sidebar-section-label">
        MAIN MENU
    </div>

    <nav
        id="primary-navigation"
        class="sidebar-nav"
        aria-label="เมนูหลัก"
    >
        {$dashboard}
        {$students}
        {$system}
        {$backup}
        {$manual}
    </nav>

    <div class="sidebar-footer">

        <div
            class="sidebar-status"
            data-status-feedback
            aria-live="polite"
        >
            <span
                class="status-dot"
                aria-hidden="true"
            ></span>

            <div>
                <strong>Student Hosting</strong>
                <small data-status-copy>
                    Online · Read-only Monitoring Portal
                </small>
            </div>

            <button
                type="button"
                class="status-refresh"
                data-refresh-status
                aria-label="ตรวจสอบสถานะล่าสุด"
            >
                ↻
            </button>
        </div>

        <div class="sidebar-faculty">
            Faculty of Digital Technology
        </div>

    </div>

</aside>

<div
    class="sidebar-backdrop"
    data-sidebar-backdrop
></div>

<div class="app-main">

<header class="topbar">

    <div class="topbar-left">

        <button
            type="button"
            class="mobile-menu-button"
            data-sidebar-toggle
            aria-label="เปิดเมนู"
            aria-expanded="false"
            aria-controls="primary-navigation"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div>
            <div class="topbar-eyebrow">
                Student Hosting
            </div>

            <h1 class="topbar-title">
                {$safeTitle}
            </h1>
        </div>

    </div>

    <section
        class="monitoring-context"
        data-monitoring-context
        aria-label="บริบทการตรวจสอบ"
    >
        <span class="monitoring-context-label">
            Monitoring context
        </span>

        <span
            class="monitoring-context-item"
            data-host-count
        >
            Host inventory · All registered hosts
        </span>

        <span
            class="monitoring-context-item"
            data-monitoring-status
        >
            Status · Read-only monitoring
        </span>

        <span
            class="monitoring-context-item"
            data-last-refresh
        >
            Last refresh · On page load
        </span>
    </section>

    <div class="topbar-actions">

        <div class="admin-profile">
            <div class="admin-avatar">
                A
            </div>

            <div class="admin-copy">
                <span class="admin-label">
                    ผู้ดูแลระบบ
                </span>

                <strong>
                    {$displayName}
                </strong>
            </div>
        </div>

        <form
            method="post"
            action="/logout"
            class="logout-form"
        >
            <button
                type="submit"
                class="logout-button"
            >
                ออกจากระบบ
            </button>
        </form>

    </div>

</header>

<main class="content">
{$content}
</main>

<footer class="app-footer">
    <span>
        DiGiT Hosting Admin
    </span>

    <span>
        Faculty of Digital Technology ·
        Chiang Rai Rajabhat University
    </span>
</footer>

</div>

</div>

</body>
</html>
HTML;
    }

    private static function iconDashboard(): string
    {
        return <<<SVG
<svg viewBox="0 0 24 24" aria-hidden="true">
<rect x="3" y="3" width="7" height="7" rx="2"></rect>
<rect x="14" y="3" width="7" height="7" rx="2"></rect>
<rect x="3" y="14" width="7" height="7" rx="2"></rect>
<rect x="14" y="14" width="7" height="7" rx="2"></rect>
</svg>
SVG;
    }

    private static function iconStudents(): string
    {
        return <<<SVG
<svg viewBox="0 0 24 24" aria-hidden="true">
<circle cx="9" cy="8" r="4"></circle>
<path d="M2.5 20c.5-4 3-6 6.5-6s6 2 6.5 6"></path>
<circle cx="17.5" cy="9" r="3"></circle>
<path d="M16 15c3.5 0 5.5 1.8 5.5 5"></path>
</svg>
SVG;
    }

    private static function iconSystem(): string
    {
        return <<<SVG
<svg viewBox="0 0 24 24" aria-hidden="true">
<rect x="3" y="4" width="18" height="6" rx="2"></rect>
<rect x="3" y="14" width="18" height="6" rx="2"></rect>
<circle cx="7" cy="7" r="1"></circle>
<circle cx="7" cy="17" r="1"></circle>
<path d="M11 7h6M11 17h6"></path>
</svg>
SVG;
    }

    private static function iconBackup(): string
    {
        return <<<SVG
<svg viewBox="0 0 24 24" aria-hidden="true">
<path d="M4 7v13h16V7"></path>
<path d="M2 4h20v4H2z"></path>
<path d="M12 11v6"></path>
<path d="m9 14 3 3 3-3"></path>
</svg>
SVG;
    }

    private static function iconManual(): string
    {
        return <<<SVG
<svg viewBox="0 0 24 24" aria-hidden="true">
<path d="M4 4.5A3.5 3.5 0 0 1 7.5 1H20v18H7.5A3.5 3.5 0 0 0 4 22.5z"></path>
<path d="M4 4.5v18"></path>
<path d="M8 6h8M8 10h8"></path>
</svg>
SVG;
    }
}
