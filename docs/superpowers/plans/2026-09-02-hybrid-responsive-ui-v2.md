# Hybrid Responsive UI v2 Implementation Plan

Goal:
Redesign DiGiT Hosting Admin into a responsive Hybrid IT Admin UI
without changing authentication, database, status sync, or server actions.

Architecture:
- Shared authenticated shell using Layout.php
- Fixed desktop sidebar
- Responsive mobile drawer
- Topbar with page title and admin profile
- Shared CSS and JavaScript
- All existing routes and backend behavior preserved

Files:
- app/Views/Layout.php
- app/Controllers/AuthController.php
- app/Controllers/DashboardController.php
- app/Controllers/StudentsController.php
- app/Controllers/SystemController.php
- app/Controllers/BackupController.php
- app/Controllers/ManualController.php
- public/assets/css/app.css
- public/assets/js/app.js
- tests/LayoutTest.php
- tests/AuthControllerTest.php
- tests/DashboardControllerTest.php
- tests/StudentsControllerTest.php
- tests/SystemControllerTest.php
- tests/BackupControllerTest.php
- tests/ManualControllerTest.php

Tasks:

1. Responsive application shell
- Sidebar desktop layout
- Mobile off-canvas drawer
- Responsive topbar
- Shared navigation
- Active navigation state
- Logout
- Responsive CSS
- Drawer JavaScript

2. Login UI v2
- Hybrid branded login screen
- Responsive two-panel desktop layout
- Single-column mobile layout
- Existing login fields and backend unchanged

3. Dashboard v2
- Summary cards
- System health
- Disk progress bars
- Last backup
- Last status sync

4. Students v2
- Search
- Status filter
- Responsive horizontal table
- Status and infrastructure badges
- Account summary

5. System v2
- Service health cards
- Disk usage progress
- Last sync status

6. Backup v2
- Backup status hero
- File size
- Last backup
- Schedule
- Retention policy

7. Manual v2
- Command categories
- Responsive command cards
- Copy command buttons
- No secrets displayed

8. Verification
- PHP syntax checks
- Full test suite
- unauthenticated route redirects
- CSS and JS HTTP 200
- desktop and mobile browser review

Security constraints:
- UI only
- No server-changing actions
- No privileged SSH access from PHP
- No password, DB secret, SSH key, or credential display
- Existing HTTPS/session/rate-limit behavior unchanged
