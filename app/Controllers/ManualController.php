<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Controllers;

use Digit\HostingAdmin\Views\Layout;

final class ManualController
{
    public function page(
        array $user
    ): string {
        $items = [
            [
                'สร้าง Student Hosting',
                'สร้าง Linux/SFTP user, Web, DB, PHP pool, quota และ credential',
                'sudo digit-student-create <student_id>',
            ],
            [
                'Reset Password',
                'Reset SFTP, Database หรือทั้งสองส่วน',
                'sudo digit-student-reset-password <student_id> sftp|db|all',
            ],
            [
                'Quota',
                'ตรวจสอบหรือปรับพื้นที่ของนักศึกษา',
                'sudo digit-student-quota',
            ],
            [
                'Suspend / Enable',
                'ระงับหรือเปิด Hosting Account โดยเก็บข้อมูลเดิมไว้',
                'sudo digit-student-suspend',
            ],
            [
                'CSV Import',
                'สร้าง Account จำนวนมากจาก CSV',
                'sudo digit-student-import --dry-run <file.csv>',
            ],
            [
                'Credential Export',
                'Export credential สำหรับผู้ดูแลระบบ',
                'sudo digit-student-export',
            ],
            [
                'Audit Accounts',
                'ตรวจ user, quota, PHP, Nginx, DB และ credential',
                'sudo digit-student-audit',
            ],
            [
                'Legacy Repair',
                'ซ่อม Account รุ่นเก่าให้เป็นมาตรฐานปัจจุบัน',
                'sudo digit-student-repair-legacy',
            ],
            [
                'Hosting Health',
                'ตรวจ Service, Disk, DB และ Account consistency',
                'sudo digit-hosting-health',
            ],
            [
                'Configuration Backup',
                'สำรอง Hosting configuration และ MariaDB',
                'sudo digit-hosting-backup',
            ],
            [
                'Status Snapshot',
                'สร้าง JSON snapshot สำหรับ Hosting Admin Portal',
                'sudo digit-hosting-status-collect',
            ],
        ];

        $cards = '';

        foreach ($items as [
            $title,
            $description,
            $command
        ]) {
            $safeTitle = htmlspecialchars(
                $title,
                ENT_QUOTES,
                'UTF-8'
            );

            $safeDescription = htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            );

            $safeCommand = htmlspecialchars(
                $command,
                ENT_QUOTES,
                'UTF-8'
            );

            $cards .= <<<HTML
<article class="surface command-card">

<h3>{$safeTitle}</h3>

<p class="command-description">
{$safeDescription}
</p>

<div class="command-box">

<code>{$safeCommand}</code>

<button
    type="button"
    class="command-copy"
    data-copy-command="{$safeCommand}"
>
คัดลอก
</button>

</div>

</article>
HTML;
        }

        $content = <<<HTML
<div class="page-heading">

<div>
    <h2 class="page-title">
        Administrator Manual
    </h2>

    <p class="page-description">
        คำสั่งสำหรับบริหาร Student Hosting Server
    </p>
</div>

</div>


<div class="security-notice">

<div class="security-notice-icon">
!
</div>

<div>

<strong>
Security Notice
</strong>

<p>
หน้า Manual ไม่แสดง Password,
Private Key หรือ Credential ใด ๆ
และคำสั่งทั้งหมดต้องดำเนินการผ่าน
Server Administration Terminal เท่านั้น
</p>

</div>

</div>


<div class="command-grid">
{$cards}
</div>
HTML;

        return Layout::render(
            title: 'Manual',
            active: 'manual',
            content: $content,
            user: $user
        );
    }
}
