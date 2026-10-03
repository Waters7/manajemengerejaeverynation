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
# atur DB_* di .env, lalu:
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

Jalankan queue worker dan scheduler di production:

```bash
php artisan queue:work
php artisan schedule:work
```
