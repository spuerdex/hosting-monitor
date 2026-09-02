<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Controllers;

final class DashboardController
{
    public function page(
        array $user,
        array $status
    ): string {
        $name = htmlspecialchars(
            $user['display_name']
                ?? $user['username']
                ?? 'Admin',
            ENT_QUOTES,
            'UTF-8'
        );

        $overall = htmlspecialchars(
            $status['overall_status'] ?? 'UNKNOWN',
            ENT_QUOTES,
            'UTF-8'
        );

        $summary = $status['summary'] ?? [];
        $storage = $status['storage'] ?? [];

        $students = (int)($summary['students'] ?? 0);
        $enabled = (int)($summary['enabled'] ?? 0);
        $warnings = (int)($summary['warnings'] ?? 0);

        $rootUsed = (int)(
            $storage['root']['used_percent'] ?? 0
        );

        $studentUsed = (int)(
            $storage['student']['used_percent'] ?? 0
        );

        return <<<HTML
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard - DiGiT Hosting Admin</title>

<style>
*{box-sizing:border-box}
body{
    margin:0;
    font-family:system-ui,-apple-system,"Segoe UI",sans-serif;
    background:#f5f5f5;
    color:#333
}
header{
    background:#333;
    color:#fff;
    padding:18px 28px;
    display:flex;
    justify-content:space-between;
    align-items:center
}
.brand{font-size:20px;font-weight:700}
main{
    max-width:1100px;
    margin:auto;
    padding:28px
}
.welcome{margin-bottom:24px}
.grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:18px
}
.card{
    background:#fff;
    padding:22px;
    border-radius:15px;
    box-shadow:0 5px 20px rgba(0,0,0,.06)
}
.value{
    font-size:30px;
    font-weight:700;
    margin-top:8px
}
.status{color:#F15927}
button{
    border:0;
    background:#F15927;
    color:#fff;
    padding:9px 15px;
    border-radius:8px;
    cursor:pointer
}
</style>
</head>

<body>

<header>
<div class="brand">DiGiT Hosting Admin</div>

<form method="post" action="/logout">
<button type="submit">ออกจากระบบ</button>
</form>
</header>

<main>

<div class="welcome">
<h2>ยินดีต้อนรับ {$name}</h2>
<p>สถานะ Student Hosting ล่าสุด</p>
</div>

<div class="grid">

<div class="card">
สถานะระบบ
<div class="value status">{$overall}</div>
</div>

<div class="card">
นักศึกษาทั้งหมด
<div class="value">{$students}</div>
</div>

<div class="card">
เปิดใช้งาน
<div class="value">{$enabled}</div>
</div>

<div class="card">
คำเตือน
<div class="value">{$warnings}</div>
</div>

<div class="card">
Root Disk
<div class="value">{$rootUsed}%</div>
</div>

<div class="card">
Student Disk
<div class="value">{$studentUsed}%</div>
</div>

</div>
</main>
</body>
</html>
HTML;
    }
}
