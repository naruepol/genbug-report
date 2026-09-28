# Public Bug Reporting

ระบบเปิดให้ผู้ชมทดลองใช้โปรเจ็คหลังการนำเสนอ แล้วแจ้ง Bug ได้ทันทีโดยไม่ต้อง Login
Admin สร้าง Project → Publish → ได้ QR Code ไปใส่ Slide / Poster → ผู้ชมสแกนแล้วแจ้ง Bug → Admin ตรวจสอบ ให้ Severity / Priority / Score และติดตามสถานะจนปิดงาน

**Stack:** Laravel 13 · PHP 8.4 · Inertia.js v3 · React 19 + TypeScript · Tailwind CSS 4 · PostgreSQL 17 · Laravel Socialite (Google OAuth) · Docker Compose

## ความสามารถหลัก

| ผู้ใช้ | ทำอะไรได้ |
|---|---|
| **Public User** (ไม่ต้อง Login) | ดูรายการ Project, เปิด Demo (Try System), สแกน QR, แจ้ง Bug พร้อม Screenshot (PNG/JPG/WEBP ≤ 5 MB), ดู Public Bug Board, ค้นหา/กรอง Bug, ดูรายละเอียด Bug — ชื่อผู้แจ้งแสดงเป็น **Anonymous User** เสมอ |
| **Admin** (Google Login + Email Allowlist) | Dashboard, สร้าง/แก้ไข/ลบ Project, Publish/Close, **เปิด/ปิดการรับแจ้ง Bug ราย Project**, สร้าง/ดาวน์โหลด QR Code (PNG/SVG) และโหมด Present เต็มจอ, Verify/Reject/Duplicate, กำหนด Severity/Priority/Score (1–10), เปลี่ยน Status, Admin Note, ดูข้อมูลผู้แจ้ง, ลบ Spam, Dashboard ราย Project พร้อมกราฟ Bug by Status/Severity/Category, Audit Log |

ป้องกัน Spam ด้วย Rate Limit (5 รายงาน / 10 นาที / IP), Honeypot และ Server-side Validation

### เปิด/ปิดการรับแจ้ง Bug ราย Project

Admin สลับได้จากสวิตช์ **Bug reporting** ในหน้า Project, ในรายการ Projects (สวิตช์ท้ายแต่ละแถว) หรือในฟอร์มแก้ไข Project
ค่าเริ่มต้นคือเปิด — Project จะรับรายงานใหม่ได้เมื่อเป็น **Published และเปิดสวิตช์** เท่านั้น

เมื่อปิด: หน้า Project และ Public Bug Board ยังเปิดดูได้ตามปกติ แต่ปุ่ม Report Bug จะถูกแทนด้วยข้อความแจ้ง
และระบบไม่รับรายงานใหม่ (ทั้งหน้าแบบฟอร์มและการส่งข้อมูล) — ทุกครั้งที่สลับจะถูกบันทึกใน Audit Log

## เริ่มต้นใช้งาน

ต้องมีเพียง Docker Desktop (ไม่ต้องติดตั้ง PHP, Composer, Node.js หรือ PostgreSQL บนเครื่อง)

```bash
cp .env.example .env
docker compose up -d
```

ครั้งแรก container `app` จะทำให้อัตโนมัติ: `composer install`, สร้าง `APP_KEY`, `storage:link` และ `migrate`
ส่วน container `node` จะ `npm install` แล้วเปิด Vite dev server (HMR)

ใส่ข้อมูลตัวอย่าง (Admin, Project "Student Portfolio AI" / `PORTFOLIO-AI` และ Bug ตัวอย่าง):

```bash
docker compose exec app php artisan db:seed
```

เปิด <http://localhost:8080>

## การเข้าระบบของ Admin

Admin เข้าระบบได้ 2 วิธี ขึ้นกับว่าตั้งค่า Google แล้วหรือยัง — ถ้า `.env` ยังไม่มีค่า Google
(เช่นเพิ่งคัดลอกมาจาก `.env.example`) จะใช้ได้เฉพาะวิธีที่ 1

### วิธีที่ 1: ลิงก์เข้าระบบชั่วคราว (ทดลองได้ทันที)

```bash
docker compose exec app php artisan admin:login-link
```

- คำสั่งจะแสดงลิงก์ เปิดในเบราว์เซอร์ภายใน 15 นาทีก็จะเข้าหน้า Admin ได้เลย
- ค่าเริ่มต้นใช้อีเมลแรกใน `ADMIN_EMAILS` (ใน `.env.example` คือ `admin@example.com`) ถ้าจะใช้อีเมลอื่นให้ระบุต่อท้าย
  เช่น `docker compose exec app php artisan admin:login-link teacher@example.com` — อีเมลนั้นต้องอยู่ใน `ADMIN_EMAILS`
- ใช้ได้เฉพาะเมื่อ `APP_ENV=local` เพราะเป็นทางลัดสำหรับเครื่องพัฒนา ระบบจริงไม่มีช่องทางนี้

### วิธีที่ 2: เข้าด้วย Google (ใช้งานจริง)

1. ที่ Google Cloud Console สร้าง **OAuth client ID** แบบ **Web application**
2. ใส่ **Authorized redirect URI** ให้ตรงกับพอร์ตที่ใช้: `http://localhost:8080/auth/google/callback` (หรือโดเมนจริงของคุณ)
3. ถ้าแอปใน Google ยังอยู่ในโหมด **Testing** ต้องเพิ่มบัญชี Google ของ Admin เป็น **Test user**
   (เมนู OAuth consent screen / Audience) ไม่อย่างนั้นจะ login ไม่ผ่าน
4. ใส่ค่าใน `.env` — บันทึกแล้วมีผลทันที ไม่ต้อง restart (ถ้า cache config ไว้ด้วย `php artisan optimize` ให้รันคำสั่งนั้นใหม่)

   ```env
   GOOGLE_CLIENT_ID=xxxxxxxx.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=xxxxxxxx
   GOOGLE_REDIRECT_URI=http://localhost:8080/auth/google/callback
   ADMIN_EMAILS=your-email@gmail.com,teacher@example.com
   ```

5. เปิด <http://localhost:8080/login> (หรือกดลิงก์ **Admin sign in** ท้ายหน้าเว็บ) แล้วกด **Sign in with Google**

### ข้อควรรู้

- เฉพาะอีเมลใน `ADMIN_EMAILS` เท่านั้นที่เข้าได้ และต้องเป็นอีเมลที่ Google ยืนยันแล้ว บัญชีอื่นจะได้หน้า **403 Forbidden**
  ถ้ามี Admin หลายคนให้คั่นอีเมลด้วยจุลภาค
- ถ้าเปิด `/admin` ขณะยังไม่ได้ login ระบบจะพาไปหน้า login ให้เอง ส่วนปุ่มออกจากระบบอยู่มุมขวาบนของหน้า Admin
- Google ไม่รับ redirect URI ที่เป็น IP ในวง LAN (เช่น `192.168.x.x`) Admin จึงควร login ผ่าน `localhost` บนเครื่องที่รันระบบ
  หรือผ่านโดเมนจริงที่เป็น HTTPS ส่วนผู้ชมที่สแกน QR ด้วยมือถือไม่ต้อง login
- ถ้าเปลี่ยนพอร์ต (`APP_PORT` ดูหัวข้อ [Port](#port)) ต้องแก้ `GOOGLE_REDIRECT_URI` ใน `.env`
  และ redirect URI ใน Google Cloud Console ให้ตรงกันด้วย

## ใช้งานตอนนำเสนอ (สแกน QR ด้วยมือถือ)

QR Code ชี้ไปที่ `APP_URL/project/{PROJECT-CODE}` ดังนั้น `APP_URL` ต้องเป็นที่อยู่ที่มือถือเข้าถึงได้

1. ตั้ง `APP_URL` เป็น IP ของเครื่องในวง LAN หรือโดเมน เช่น `APP_URL=http://192.168.1.50:8080`
2. ให้มือถือโหลด JavaScript ได้ เลือกอย่างใดอย่างหนึ่ง
   - Build assets แล้วปิด dev server: `docker compose run --rm node npm run build` และ `docker compose stop node`
   - หรือใช้ dev server ต่อ โดยตั้ง `HMR_HOST=192.168.1.50` ใน `.env` แล้ว `docker compose up -d node`
3. ในหน้า Admin ของ Project กด **Regenerate** เพื่อสร้าง QR Code ใหม่ตาม `APP_URL`
4. ถ้ารันบน Docker Desktop (Windows/macOS) แอปจะเห็นผู้ชมทุกคนเป็น IP เดียวกัน (gateway ของ Docker)
   ทำให้ Rate Limit 5 รายงาน / 10 นาที ถูกใช้ร่วมกันทั้งห้อง — ตอนนำเสนอให้เพิ่ม `BUG_REPORT_RATE_LIMIT` (เช่น `100`)
   ใน `.env` หรือ deploy บน Linux server / หลัง reverse proxy ที่ตั้ง `TRUSTED_PROXIES`

## คำสั่งที่ใช้บ่อย

```bash
docker compose up -d                                   # Start
docker compose down                                    # Stop
docker compose logs -f                                 # Logs
docker compose exec app php artisan migrate            # Migration
docker compose exec app php artisan migrate:fresh --seed   # Reset database + ข้อมูลตัวอย่าง
docker compose exec app php artisan test               # Run tests
docker compose exec app php artisan test --filter=BugPrivacyTest   # รันเฉพาะ test ที่ต้องการ
docker compose exec app vendor/bin/pint                # จัดรูปแบบโค้ด PHP
docker compose exec app composer install               # Composer
docker compose run --rm node npm install               # NPM
docker compose run --rm node npm run types             # TypeScript type check
docker compose run --rm node npm run build             # Build React (production)
```

Test ใช้ฐานข้อมูล PostgreSQL แยก `bug_reporting_test` (สร้างโดย `docker/postgres/init` ตอนสร้าง volume ครั้งแรก)

### Port

ค่าเริ่มต้นคือ 8080 (web), 5432 (PostgreSQL), 5173 (Vite) ถ้าชนกับโปรแกรมอื่น ให้เปลี่ยนใน `.env`
แล้วปรับ `APP_URL` และ `GOOGLE_REDIRECT_URI` ให้ตรงกัน

```env
APP_PORT=8081
FORWARD_DB_PORT=5433
DEV_SERVER_PORT=5174
```

### หมายเหตุ Docker

- `vendor/` อยู่ใน named volume ของ container (เร็วกว่า bind mount มากบน Windows/macOS) — ติดตั้งแพ็กเกจผ่าน `docker compose exec app composer ...` เสมอ
- `node_modules/` อยู่ใน named volume ของ service `node`
- Compose project ชื่อ `public-bug-reporting` (กำหนดใน `compose.yaml`) จึงไม่ชนกับโปรเจ็คอื่นที่อยู่ในโฟลเดอร์ชื่อเดียวกัน

## Production

- `APP_ENV=production`, `APP_DEBUG=false`, ใช้ HTTPS และ `SESSION_SECURE_COOKIE=true`
- อยู่หลัง reverse proxy ที่ทำ HTTPS ให้ตั้ง `TRUSTED_PROXIES` (เช่น `*`) เพื่อให้ IP ผู้ใช้ (ใช้กับ Rate Limit) และ URL ถูกต้อง
- Build assets (`npm run build`) และไม่รัน service `node`
- ถ้ามีหลาย instance ให้ตั้ง `AUTO_MIGRATE=false` แล้วรัน `php artisan migrate --force` เป็นขั้นตอน deploy แยก
- `php artisan optimize` เพื่อ cache config/routes

## โครงสร้างสำคัญ

```text
app/Http/Controllers/    PublicProjectController, PublicBugController, Admin/*, Auth/GoogleController
app/Http/Resources/      PublicBugResource (whitelist ฟิลด์สาธารณะ), AdminBugResource, ProjectResource
app/Enums/               ProjectStatus, BugStatus, VerificationStatus, BugSeverity, BugPriority, BugCategory
app/Services/QrCodeService.php
app/Support/             BugFilters (search/filter), BugStats (dashboard/กราฟ)
resources/js/Pages/      Public/*, Admin/*, Auth/Login, Error
resources/js/Components/ BugBadge, BugTable, ProjectCard, ProjectQRCode, Pagination, ...
docker/                  php (Dockerfile, entrypoint), nginx, postgres/init
tests/Feature/           AdminLogin, ProjectManagement, PublicProject, BugSubmission,
                         BugVerification, BugPrivacy, BugStatus, ScreenshotUpload, BugReportingToggle
```
