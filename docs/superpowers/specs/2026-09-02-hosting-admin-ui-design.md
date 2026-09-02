# DiGiT Hosting Admin — UI Structure Design

Date: 2026-09-02

## Goal

Complete the read-only web administration portal structure for
DiGiT Student Hosting before adding server-changing administrative actions.

## Architecture

Browser
  -> HTTPS / Nginx Proxy Manager
  -> 10.1.161.88 Nginx
  -> isolated PHP-FPM user: hostingadmin
  -> local read-only status cache

Status flow:

10.1.161.23
  -> restricted SSH account
  -> hostingfetch on 10.1.161.88
  -> systemd timer
  -> /var/lib/digit-hosting-admin/status/status.json
  -> hostingadmin read-only

PHP does not hold or read the SSH private key.

## Shared UI

All authenticated pages use one shared layout.

Header:
- DiGiT Hosting Admin
- Dashboard
- Students
- System
- Backup
- Manual
- Logout

Footer:
- Faculty of Digital Technology
- Chiang Rai Rajabhat University

Shared styles:
- public/assets/css/app.css

Shared layout:
- app/Views/Layout.php

## Pages

### Dashboard

Show:
- Overall system status
- Total students
- Enabled accounts
- Suspended accounts
- Warnings
- Root disk usage
- Student disk usage
- Last backup
- Status last updated

Read-only.

### Students

Show:
- Search by Student ID or domain
- Student ID
- Domain
- Enabled / Suspended
- Used quota
- Soft quota
- Hard quota
- PHP status
- Nginx status
- Database status
- Credential status

Read-only.

### System

Show:
- Nginx
- PHP-FPM
- MariaDB
- SSH
- UFW
- Root Disk
- Student Disk
- Last status update

Read-only.

No restart or service-control buttons.

### Backup

Show:
- Latest backup date/time
- Backup size
- Backup availability
- Backup retention information
- Current automated backup policy

Read-only.

No Run Backup or Delete Backup action.

### Manual

Document current server administration commands:
- Create student
- Reset SFTP password
- Reset DB password
- Change quota
- Suspend student
- Enable student
- CSV import
- Credential export
- Audit
- Legacy repair
- Health check
- Backup
- Status collection

No credentials or secrets are displayed.

## Authentication

Existing authentication remains:

- Argon2id password hashes
- DB-backed sessions
- Session token stored only as SHA-256 hash in DB
- 30-minute session lifetime
- Secure cookie on HTTPS
- HttpOnly
- SameSite=Lax
- Login rate limit: 5 failures / 15 minutes

## Security Boundaries

Phase remains read-only.

Not included:

- Create Student from browser
- Suspend / Enable from browser
- Reset Password from browser
- Quota changes from browser
- Run Backup from browser
- Restart Service
- Delete Student

Those actions require a later privileged-action design including:
- CSRF protection
- confirmation screens
- audit logging
- privileged command gateway
- strict input validation

## Code Structure

app/
  Controllers/
    DashboardController.php
    StudentsController.php
    SystemController.php
    BackupController.php
    ManualController.php

  Hosting/
    StatusRepository.php

  Views/
    Layout.php

public/
  assets/
    css/
      app.css

resources/
  manual/

tests/
  LayoutTest.php
  DashboardControllerTest.php
  StudentsControllerTest.php
  SystemControllerTest.php
  BackupControllerTest.php
  ManualControllerTest.php

## Routing

Authenticated routes:

GET /dashboard
GET /students
GET /system
GET /backup
GET /manual

POST /logout

Unauthenticated requests to authenticated pages redirect to /login.

## Error Handling

If status.json cannot be read:
- Portal remains available
- Dashboard/System/Backup show status unavailable
- No server exception or internal path is displayed to browser

## Responsive Design

Desktop:
- Full horizontal navigation
- Card grid
- Wide student table

Mobile:
- Navigation wraps cleanly
- Cards collapse to one column
- Student table scrolls horizontally

## Definition of Done

The UI phase is complete when:

- All authenticated pages use shared navigation/layout
- Dashboard works
- Students works
- System works
- Backup works
- Manual works
- Status data remains read-only
- Authentication remains functional
- Logout remains functional
- Existing PHP tests pass
- New UI tests pass
- Git working tree is clean
