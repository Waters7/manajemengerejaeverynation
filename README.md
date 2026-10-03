# Every Nation Bekasi — Church Management & Discipleship System

> **HONOR GOD. MAKE DISCIPLES.**

Website publik + Church Management & Discipleship System untuk Every Nation Bekasi, dibangun dengan Laravel 13, Livewire 4, Alpine.js, Tailwind CSS 4 dan MySQL.

## Dokumen perencanaan

| Step | Dokumen |
|---|---|
| 1–2 Sitemap & user journey | [docs/01-sitemap-and-journeys.md](docs/01-sitemap-and-journeys.md) |
| 3 Database ERD | [docs/02-erd.md](docs/02-erd.md) |
| 4–8 Permission matrix & workflows | [docs/03-permissions-and-workflows.md](docs/03-permissions-and-workflows.md) |
| 9 Wireframes | [docs/04-wireframes.md](docs/04-wireframes.md) |

## Modul

| Phase | Modul |
|---|---|
| 1 | Auth & public sign-up (role USER, pending verification) · Members (profil dengan tab Overview/Discipleship/LifeGroup/Classes/Ministry/Events/Attendance/Timeline) · Newcomers · **Get Involved** (form publik, minat configurable, ministry interest, workflow NEW → ACTIVE, assign follow-up, WhatsApp template) · Connect Card (`/connect`) · Follow-ups / Needs Follow-Up · LifeGroups (dashboard, anggota, meeting & absensi, join request; link grup WA hanya setelah approve) · Users, Roles & Permissions |
| 2 | Discipleship 4E (Engage → Establish → Equip → Empower) · Journey · One 2 One · Curriculum (Stage → Program → Chapter, configurable) · Books · Classes (batch, sesi, absensi) · Victory Weekend · Disciplers & Discipleship Tree |
| 3 | Leadership Pipeline (approval hanya oleh pastor) · Ministries · Volunteers · Volunteer Applications · Serving Schedule · Campus Ministry (scope per kampus) |
| 4 | Devotionals · Sermons (series, key points, YouTube/Spotify) · Events (registrasi, kapasitas, waiting list, QR ticket, check-in) · Gallery (multi upload WebP + lightbox) · Pages & Homepage CMS |
| 5 | Prayer Requests (visibility pastor/leader/prayer team, terenkripsi) · Pastoral Care (restricted, terenkripsi) · Announcements · Birthdays · 13 Reports + export Excel/CSV · Audit Log · Settings & template WhatsApp · Media library · Otomasi scheduler |

Hak akses data diterapkan terpusat oleh `App\Services\AccessScope` (leader hanya LifeGroup & disciples-nya, campus ministry hanya kampusnya, coordinator hanya ministry-nya) dan diperiksa ulang di Policies.

## Kebutuhan

- PHP 8.3+ (ekstensi `pdo_mysql`, `gd`, `intl`, `zip`, `fileinfo`)
- Composer 2
- Node.js 20+
- MySQL 8 / MariaDB 10.6+

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
# atur DB_* dan ADMIN_EMAIL / ADMIN_PASSWORD di .env, lalu:
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

`php artisan migrate --seed` membuat roles & permissions, kurikulum 4E default, daftar minat, ministries, kategori, dan akun Super Admin dari `ADMIN_EMAIL` / `ADMIN_PASSWORD`. Di luar production juga dibuat data demo (akun demo per role tercantum di `database/seeders/DemoSeeder.php`). Untuk production, set `ADMIN_PASSWORD` sebelum seeding dan biarkan `SEED_DEMO=false`.

## Production

Jalankan queue worker (notifikasi) dan scheduler (publikasi konten terjadwal, pengingat ulang tahun, digest follow-up harian):

```bash
php artisan queue:work
```

```bash
php artisan schedule:work
```

## Testing

```bash
php artisan test
```
