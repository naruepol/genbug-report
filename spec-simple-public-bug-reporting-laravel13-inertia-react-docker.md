# Simple Public Bug Reporting System
## Laravel 13 + Inertia.js + React + Docker Compose Specification

## 1. Overview

ระบบนี้เป็นระบบสำหรับเปิดให้ผู้ใช้งานทั่วไปเข้ามาดูโปรเจ็ค ทดลองใช้งาน และรายงาน Bug หลังจากมีการนำเสนอระบบ

ระบบเน้นความง่าย โดยมีผู้ใช้งานหลักเพียง 2 กลุ่ม:

1. **Admin** — ผู้สร้างและดูแล Project
2. **Public User** — ผู้ทดลองระบบและแจ้ง Bug

Technology Stack หลัก:

```text
Laravel 13
PHP 8.4+
Inertia.js
React
TypeScript
Tailwind CSS
PostgreSQL
Laravel Socialite
Google OAuth
Docker
Docker Compose
```

ระบบทั้งหมดต้องสามารถรันผ่านคำสั่ง:

```bash
docker compose up -d
```

โดยไม่จำเป็นต้องติดตั้ง PHP, Composer, Node.js หรือ PostgreSQL บนเครื่อง Host โดยตรง

---

# 2. Main Workflow

```text
Admin Login with Google
        ↓
Create Project
        ↓
Add Demo URL
        ↓
Publish Project
        ↓
Generate QR Code
        ↓
Present Project
        ↓
Public User Scan QR / Open Link
        ↓
Try System
        ↓
Report Bug
        ↓
Bug = Pending + Open
        ↓
Admin Reviews
        ↓
Verified / Rejected / Duplicate
        ↓
Admin Adds Severity + Priority + Score
        ↓
Open → In Progress → Fixed → Closed
```

---

# 3. User Roles

## 3.1 Admin

Admin คือผู้สร้าง Project และเป็นผู้ดูแล Bug ของ Project

Admin สามารถ:

- Login ด้วย Google
- ดู Admin Dashboard
- สร้าง Project
- แก้ไข Project
- Publish Project
- Close Project
- ลบ Project
- เพิ่ม Demo URL
- เพิ่ม Repository URL
- Generate QR Code
- ดู Bug ของแต่ละ Project
- Verify Bug
- Reject Bug
- Mark Duplicate
- กำหนด Severity
- กำหนด Priority
- ให้คะแนน Bug
- เปลี่ยน Bug Status
- เพิ่ม Admin Note
- ดู Reporter Information
- ลบ Spam Bug
- ดู Dashboard และ Summary ของ Project

---

## 3.2 Public User

Public User ไม่จำเป็นต้อง Login

สามารถ:

- ดูรายการ Project ที่ Published
- ดู Project Detail
- เปิด Demo URL
- Scan QR Code
- ดู Public Bug Board
- แจ้ง Bug
- แนบ Screenshot
- ดู Bug Detail
- ดู Bug Status
- ดู Verification Status
- Search Bug
- Filter Bug

ไม่สามารถ:

- แก้ไข Project
- แก้ไข Bug
- ดู Reporter Information
- ดู Admin Note
- Verify Bug
- ให้คะแนน Bug
- เปลี่ยน Bug Status

---

# 4. Authentication

เฉพาะ Admin ที่ต้อง Login

ใช้:

```text
Laravel Socialite
Google OAuth 2.0
```

ตัวอย่าง Environment Variables:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost/auth/google/callback
```

---

# 5. Admin Authorization

MVP ใช้ Allowlist Email

กำหนดผ่าน `.env`

```env
ADMIN_EMAILS=admin@example.com,teacher@example.com
```

หลัง Google Login:

```text
Google Login Success
        ↓
Check Email in ADMIN_EMAILS
        ↓
Allowed → Admin Dashboard
Not Allowed → 403 Forbidden
```

ไม่จำเป็นต้องมี Role / Permission Management ใน MVP

---

# 6. Project Management

Admin สามารถสร้าง Project

ข้อมูล:

```text
Project Name
Project Code
Description
Project Image
Demo URL
Repository URL
Technology Stack
Presentation Date
Status
```

---

# 7. Project Status

```text
Draft
Published
Closed
```

## Draft

- Public ไม่เห็น Project
- ยังแจ้ง Bug ไม่ได้

## Published

- Public ดู Project ได้
- Public แจ้ง Bug ได้

## Closed

- Public ยังดู Project ได้
- Public ยังดู Bug ได้
- ไม่สามารถส่ง Bug ใหม่

---

# 8. Public Project Page

URL:

```text
/project/{project-code}
```

แสดง:

- Project Name
- Description
- Project Image
- Technology Stack
- Demo URL
- Repository URL
- Presentation Date
- Total Bugs
- Verified Bugs
- Fixed Bugs
- QR Code
- Try System Button
- Report Bug Button
- Public Bug Board

---

# 9. QR Code

เมื่อ Project ถูก Publish ระบบสร้าง QR Code อัตโนมัติ

QR Code ชี้ไปที่:

```text
/project/{project-code}
```

Admin สามารถ:

- ดู QR Code
- Download QR Code
- ใช้ใน Slide
- ใช้ใน Poster
- ใช้ในหน้าจอนำเสนอ

---

# 10. Public Bug Reporting

URL:

```text
/project/{project-code}/report-bug
```

Required:

```text
Bug Title
Description
```

Optional:

```text
Category
Steps to Reproduce
Expected Result
Actual Result
Page / Screen
Browser
Operating System
Device
Screenshot
Name
Email
```

---

# 11. Anonymous Reporting

Default:

```text
Anonymous = true
```

Public UI ต้องแสดง:

```text
Anonymous User
```

แม้ Reporter จะกรอก Name หรือ Email

Reporter Information ใช้สำหรับ Admin เท่านั้น

---

# 12. Bug Category

```text
Functional
UI/UX
Performance
Compatibility
Content
Other
Not Sure
```

---

# 13. Bug Severity

Public User ไม่ต้องกำหนด Severity

Admin กำหนดหลังตรวจสอบ

```text
Critical
High
Medium
Low
```

---

# 14. Bug Priority

```text
High
Medium
Low
```

---

# 15. Bug Score

Admin ให้คะแนน:

```text
1 - 10
```

คำอธิบาย:

```text
1-3   Low
4-6   Medium
7-8   High
9-10  Critical
```

ระบบไม่ต้องคำนวณคะแนนอัตโนมัติใน MVP

---

# 16. Verification Status

Bug ใหม่:

```text
Pending
```

Admin สามารถเปลี่ยนเป็น:

```text
Verified
Rejected
Duplicate
```

---

# 17. Bug Status

Workflow:

```text
Open
 ↓
In Progress
 ↓
Fixed
 ↓
Closed
```

สถานะเพิ่มเติม:

```text
Rejected
Duplicate
```

เมื่อ Public User Submit Bug:

```text
verification_status = Pending
status = Open
```

---

# 18. Public Bug Board

แสดง:

| Bug ID | Title | Severity | Score | Status | Verification |
|---|---|---|---:|---|---|
| BUG-001 | Login button not working | High | 8 | In Progress | Verified |
| BUG-002 | Mobile layout broken | Medium | 5 | Open | Verified |
| BUG-003 | Typo on home page | Low | 2 | Fixed | Verified |

ห้ามแสดง:

```text
Reporter Name
Reporter Email
IP Address
Admin Note
```

---

# 19. Bug Detail

## Public View

แสดง:

```text
Bug ID
Project
Title
Description
Category
Severity
Priority
Score
Status
Verification Status
Steps to Reproduce
Expected Result
Actual Result
Screenshot
Created Date
Updated Date
```

## Admin View

เพิ่ม:

```text
Reporter Name
Reporter Email
Admin Note
```

---

# 20. Admin Bug Management

หน้า Admin ต้องสามารถ:

- ดู Bug ทั้งหมด
- Filter ตาม Project
- Filter ตาม Status
- Filter ตาม Severity
- Filter ตาม Verification
- Search Bug
- เปิด Bug Detail
- Verify
- Reject
- Mark Duplicate
- Set Severity
- Set Priority
- Set Score
- Change Status
- Add Admin Note
- Delete Spam

---

# 21. Search

Public:

```text
Bug ID
Bug Title
```

Admin:

```text
Bug ID
Bug Title
Description
Reporter Email
```

---

# 22. Filter

Public:

```text
Status
Severity
Verification
```

Admin:

```text
Project
Status
Severity
Priority
Verification
Date
```

---

# 23. Dashboard

Admin Dashboard แสดง:

```text
Total Projects
Published Projects
Total Bugs
Pending Bugs
Verified Bugs
Open Bugs
In Progress Bugs
Fixed Bugs
Closed Bugs
```

---

# 24. Project Dashboard

แต่ละ Project แสดง:

```text
Total Bugs
Pending
Verified
Open
In Progress
Fixed
Closed
```

Charts:

```text
Bug by Status
Bug by Severity
Bug by Category
```

---

# 25. Screenshot Upload

MVP รองรับ:

```text
PNG
JPG
JPEG
WEBP
```

Maximum File Size:

```text
5 MB
```

Storage Development:

```text
Laravel storage/app/public
```

ต้องสร้าง Symbolic Link:

```bash
php artisan storage:link
```

ใน Container

---

# 26. Anti-Spam

Public User ไม่ต้อง Login ดังนั้นระบบต้องมี:

```text
Rate Limiting
Honeypot
Server-side Validation
```

Rate Limit ตัวอย่าง:

```text
5 Bug Reports / 10 Minutes / IP
```

Phase 2:

```text
Cloudflare Turnstile
reCAPTCHA
```

---

# 27. Database

ใช้:

```text
PostgreSQL
```

Encoding:

```text
UTF-8
```

---

# 28. Database Schema

## users

สำหรับ Admin ที่ Login ด้วย Google

```text
id
google_id
name
email
avatar_url
created_at
updated_at
```

---

## projects

```text
id
project_code
name
description
image_url
demo_url
repository_url
technology_stack
presentation_date
status
created_at
updated_at
```

---

## bugs

```text
id
project_id
bug_code
title
description
category
steps_to_reproduce
expected_result
actual_result
page_screen
browser
operating_system
device
reporter_name
reporter_email
severity
priority
score
verification_status
status
admin_note
created_at
updated_at
```

---

## bug_attachments

```text
id
bug_id
file_name
file_path
mime_type
file_size
created_at
```

---

## audit_logs

```text
id
admin_id
action
entity_type
entity_id
created_at
```

---

# 29. Laravel Models

ต้องมีอย่างน้อย:

```text
User
Project
Bug
BugAttachment
AuditLog
```

Relationships:

```text
Project hasMany Bug
Bug belongsTo Project

Bug hasMany BugAttachment
BugAttachment belongsTo Bug

User hasMany AuditLog
AuditLog belongsTo User
```

---

# 30. Laravel Architecture

แนะนำโครงสร้าง:

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── ProjectController.php
│   │   │   └── BugController.php
│   │   ├── Auth/
│   │   │   └── GoogleController.php
│   │   ├── PublicProjectController.php
│   │   └── PublicBugController.php
│   ├── Middleware/
│   │   └── EnsureAdmin.php
│   └── Requests/
│       ├── StoreProjectRequest.php
│       ├── UpdateProjectRequest.php
│       ├── StoreBugRequest.php
│       └── UpdateBugRequest.php
├── Models/
│   ├── User.php
│   ├── Project.php
│   ├── Bug.php
│   ├── BugAttachment.php
│   └── AuditLog.php
└── Services/
    └── QrCodeService.php
```

---

# 31. Inertia.js + React Structure

Frontend:

```text
resources/js/
├── Components/
│   ├── BugBadge.tsx
│   ├── BugTable.tsx
│   ├── ProjectCard.tsx
│   ├── ProjectQRCode.tsx
│   └── Pagination.tsx
├── Layouts/
│   ├── AdminLayout.tsx
│   └── PublicLayout.tsx
├── Pages/
│   ├── Public/
│   │   ├── Home.tsx
│   │   ├── Projects/
│   │   │   ├── Index.tsx
│   │   │   └── Show.tsx
│   │   └── Bugs/
│   │       ├── Create.tsx
│   │       └── Show.tsx
│   └── Admin/
│       ├── Dashboard.tsx
│       ├── Projects/
│       │   ├── Index.tsx
│       │   ├── Create.tsx
│       │   ├── Edit.tsx
│       │   └── Show.tsx
│       └── Bugs/
│           ├── Index.tsx
│           └── Show.tsx
└── app.tsx
```

---

# 32. Routing

## Public Routes

```php
Route::get('/', ...);

Route::get('/projects', ...);
Route::get('/project/{project:project_code}', ...);
Route::get('/project/{project:project_code}/report-bug', ...);
Route::post('/project/{project:project_code}/report-bug', ...);

Route::get('/bug/{bug:bug_code}', ...);
```

---

## Google Auth Routes

```php
Route::get('/auth/google', ...);
Route::get('/auth/google/callback', ...);
Route::post('/logout', ...);
```

---

## Admin Routes

ต้องผ่าน:

```text
auth
admin
```

ตัวอย่าง:

```php
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->group(function () {
        // dashboard
        // projects
        // bugs
    });
```

---

# 33. Inertia Form Handling

ใช้ Inertia Form Helper

ตัวอย่างแนวทาง:

```text
useForm()
router.get()
router.post()
router.put()
router.delete()
```

Validation Error ต้อง Return จาก Laravel Form Request

Frontend แสดง Validation Error ใต้ Field

---

# 34. React Requirements

ใช้:

```text
React
TypeScript
Functional Components
Hooks
```

ไม่ใช้ Redux ใน MVP

State หลักใช้:

```text
Inertia Props
Local State
useForm
```

---

# 35. Styling

ใช้:

```text
Tailwind CSS
```

UI ต้องรองรับ:

```text
Desktop
Tablet
Mobile
```

Public Report Bug Form ต้อง Mobile Friendly เนื่องจากผู้เข้าชมอาจ Scan QR และแจ้ง Bug ผ่าน Smartphone

---

# 36. Docker Architecture

ระบบต้องรันผ่าน Docker Compose

Services ขั้นต่ำ:

```text
app
web
postgres
node
```

Development Architecture:

```text
Browser
   ↓
Nginx
   ↓
Laravel PHP-FPM
   ↓
PostgreSQL

Node/Vite
   ↓
React + Inertia HMR
```

---

# 37. Docker Compose Services

## app

Laravel / PHP-FPM

Base Image:

```text
php:8.4-fpm
```

ติดตั้ง Extensions:

```text
pdo_pgsql
pgsql
mbstring
bcmath
intl
zip
gd
opcache
```

ต้องมี Composer

---

## web

ใช้:

```text
nginx:alpine
```

หน้าที่:

```text
Serve Laravel public/
Forward PHP requests to app:9000
```

---

## postgres

ใช้:

```text
postgres:17-alpine
```

Database:

```text
bug_reporting
```

---

## node

ใช้:

```text
node:22-alpine
```

ใช้สำหรับ:

```text
npm install
npm run dev
Vite HMR
React Build
```

---

# 38. docker-compose.yml Requirement

Project ต้องมี:

```text
docker-compose.yml
```

หรือ:

```text
compose.yaml
```

ตัวอย่างโครงสร้าง:

```yaml
services:

  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    volumes:
      - .:/var/www/html
    depends_on:
      - postgres

  web:
    image: nginx:alpine
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

  postgres:
    image: postgres:17-alpine
    environment:
      POSTGRES_DB: bug_reporting
      POSTGRES_USER: laravel
      POSTGRES_PASSWORD: secret
    volumes:
      - postgres_data:/var/lib/postgresql/data
    ports:
      - "5432:5432"

  node:
    image: node:22-alpine
    working_dir: /var/www/html
    volumes:
      - .:/var/www/html
      - node_modules:/var/www/html/node_modules
    ports:
      - "5173:5173"
    command: npm run dev -- --host 0.0.0.0

volumes:
  postgres_data:
  node_modules:
```

---

# 39. Docker Directory Structure

```text
project-root/
├── app/
├── bootstrap/
├── config/
├── database/
├── docker/
│   ├── nginx/
│   │   └── default.conf
│   └── php/
│       └── Dockerfile
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
├── compose.yaml
├── .env.example
├── artisan
├── composer.json
├── package.json
└── vite.config.ts
```

---

# 40. Environment Variables

`.env.example`

```env
APP_NAME="Public Bug Reporting"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=bug_reporting
DB_USERNAME=laravel
DB_PASSWORD=secret

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

FILESYSTEM_DISK=public

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:8080/auth/google/callback

ADMIN_EMAILS=admin@example.com
```

---

# 41. Initial Setup

หลัง Clone Project:

```bash
cp .env.example .env
```

Build:

```bash
docker compose build
```

Start:

```bash
docker compose up -d
```

Install Composer Dependencies:

```bash
docker compose exec app composer install
```

Generate Key:

```bash
docker compose exec app php artisan key:generate
```

Install Node Dependencies:

```bash
docker compose run --rm node npm install
```

Migration:

```bash
docker compose exec app php artisan migrate
```

Storage Link:

```bash
docker compose exec app php artisan storage:link
```

จากนั้นเปิด:

```text
http://localhost:8080
```

---

# 42. Recommended Startup Automation

ควรสร้าง:

```text
docker/entrypoint.sh
```

เพื่อทำ:

```text
composer install
php artisan storage:link
php artisan migrate --force
php-fpm
```

อย่างไรก็ตาม Production ไม่ควร Run Migration แบบไม่ควบคุมทุกครั้งหาก Deployment Process มีหลาย Instance

---

# 43. Development Commands

Start:

```bash
docker compose up -d
```

Stop:

```bash
docker compose down
```

Logs:

```bash
docker compose logs -f
```

Laravel Artisan:

```bash
docker compose exec app php artisan
```

Migration:

```bash
docker compose exec app php artisan migrate
```

Reset Database:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Run Tests:

```bash
docker compose exec app php artisan test
```

Composer:

```bash
docker compose exec app composer install
```

NPM:

```bash
docker compose run --rm node npm install
```

Build React:

```bash
docker compose run --rm node npm run build
```

---

# 44. Database Seeder

ต้องมี Development Seeder

สร้าง:

```text
Admin User
Sample Project
Sample Bugs
```

ตัวอย่าง Project:

```text
Project Name: Student Portfolio AI
Project Code: PORTFOLIO-AI
Status: Published
```

---

# 45. Testing Requirements

ใช้ Laravel Test Framework

ต้องมี Feature Tests:

```text
AdminLoginTest
ProjectManagementTest
PublicProjectTest
BugSubmissionTest
BugVerificationTest
BugPrivacyTest
BugStatusTest
ScreenshotUploadTest
```

---

# 46. Critical Privacy Test

ต้องมี Test ว่า Public Response ไม่มี:

```text
reporter_name
reporter_email
admin_note
```

ทั้งใน:

```text
Project Bug List
Bug Detail
Inertia Props
JSON Response
```

---

# 47. Validation

## Project

```text
name               required
project_code       required|unique
description        required
demo_url           nullable|url
repository_url     nullable|url
presentation_date  nullable|date
status             required
```

## Bug

```text
title              required|max:255
description        required
category           nullable
screenshot         nullable|image|max:5120
email              nullable|email
```

---

# 48. Security

ต้องมี:

```text
Laravel CSRF Protection
Google OAuth
Admin Email Allowlist
Input Validation
File Validation
Rate Limiting
SQL Injection Protection via Eloquent
XSS Protection via React Rendering
Secure Session
```

Production:

```text
APP_DEBUG=false
HTTPS=true
Secure Cookie=true
```

---

# 49. Performance

ต้องใช้ Pagination

Default:

```text
20 Bugs / Page
```

Database Index:

```text
projects.project_code
projects.status

bugs.project_id
bugs.bug_code
bugs.status
bugs.verification_status
bugs.severity
bugs.created_at
```

---

# 50. MVP Scope

MVP มี:

1. Laravel 13
2. Inertia.js
3. React
4. TypeScript
5. Tailwind CSS
6. PostgreSQL
7. Docker Compose
8. Google Login สำหรับ Admin
9. Admin Email Allowlist
10. Project CRUD
11. Publish / Close Project
12. Demo URL
13. Project QR Code
14. Public Project Page
15. Public Bug Submission
16. Anonymous Reporter
17. Screenshot Upload
18. Public Bug Board
19. Bug Detail
20. Admin Verification
21. Severity
22. Priority
23. Score 1-10
24. Bug Status
25. Search
26. Filter
27. Dashboard
28. Feature Tests

---

# 51. Out of Scope

MVP ยังไม่ทำ:

```text
Public User Login
Developer Role
Project Member
Permission Management
Bug Assignment
Retest Workflow
Notification
Email Notification
AI Features
GitHub Integration
GitLab Integration
Jira Integration
Realtime WebSocket
Multiple Admin Roles
Complex Scoring Formula
```

---

# 52. Definition of Done

ระบบถือว่าพร้อมใช้งานเมื่อ:

```text
docker compose up -d
```

สามารถ Start Application ได้

และ:

- Laravel เชื่อม PostgreSQL ได้
- Inertia + React ทำงานได้
- Vite HMR ทำงานใน Development
- Admin Login ด้วย Google ได้
- Admin สร้าง Project ได้
- Public เปิด Project ได้
- Public แจ้ง Bug ได้
- Screenshot Upload ได้
- Public ไม่เห็น Reporter Identity
- Admin Verify Bug ได้
- Admin ให้ Severity / Priority / Score ได้
- Admin เปลี่ยน Status ได้
- Search / Filter ทำงานได้
- Dashboard ทำงานได้
- Feature Tests ผ่าน
- `php artisan test` ผ่าน

---

# 53. Final Architecture

```text
                    ┌─────────────────┐
                    │   Public User   │
                    └────────┬────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │      Nginx      │
                    │ Docker Service  │
                    └────────┬────────┘
                             │
                             ▼
                 ┌───────────────────────┐
                 │ Laravel 13            │
                 │ Inertia.js            │
                 │ PHP-FPM               │
                 │ Docker Service: app   │
                 └──────────┬────────────┘
                            │
              ┌─────────────┴─────────────┐
              │                           │
              ▼                           ▼
     ┌─────────────────┐        ┌─────────────────┐
     │   PostgreSQL    │        │ Public Storage  │
     │ Docker Service  │        │   Screenshots   │
     └─────────────────┘        └─────────────────┘

              React + TypeScript + Vite
                       ▲
                       │
               ┌───────────────┐
               │ Node Service  │
               │ Docker Compose│
               └───────────────┘
```

---

# 54. Final MVP Concept

ระบบถูกออกแบบให้เรียบง่ายที่สุด:

```text
Admin
  ↓
Create Project
  ↓
Publish Project
  ↓
QR / Public Link
  ↓
Public User
  ↓
Report Bug
  ↓
Admin Review
  ↓
Verify + Score + Status
  ↓
Public Bug Board
```

และการพัฒนาทั้งหมดต้องยึดหลัก:

```text
Laravel 13
+
Inertia.js
+
React
+
PostgreSQL
+
Docker Compose
```

เป็นสถาปัตยกรรมหลักของระบบตั้งแต่ Development จนถึง Deployment
