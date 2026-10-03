#!/bin/bash
#
# One-off setup / repair for cPanel hosting without a terminal.
# Upload to /home/USER/setup.sh and run it once through a temporary cron job:
#     /bin/bash /home/USER/setup.sh
# The result is written to /home/USER/setup-log.txt. Delete the cron job afterwards.
#

HOME_DIR="${HOME:-/home/everynat1}"
APP_DIR="$HOME_DIR/everynation"
PUBLIC_DIR="$HOME_DIR/public_html"
SITE_URL="http://everynationcampusmanado.my.id"
LOG="$HOME_DIR/setup-log.txt"

PHP=/usr/local/bin/php
[ -x "$PHP" ] || PHP=php

{
    echo "=== Setup $(date)"
    cd "$APP_DIR" || { echo "ERROR: folder $APP_DIR tidak ditemukan"; exit 1; }
    $PHP -v | head -1

    echo "--- Membersihkan cache konfigurasi lama"
    $PHP artisan optimize:clear

    echo "--- Database yang dipakai"
    $PHP artisan config:show database.default
    $PHP artisan config:show database.connections.mysql.database

    echo "--- Migrasi"
    $PHP artisan migrate --force
    $PHP artisan migrate:status | tail -5

    echo "--- Seeder"
    $PHP artisan db:seed --force

    echo "--- Akun admin"
    $PHP artisan church:admin --reset-password --deactivate=admin@everynationbekasi.test

    echo "--- Storage link"
    ln -sfn "$APP_DIR/storage/app/public" "$PUBLIC_DIR/storage" && echo "OK $PUBLIC_DIR/storage"

    echo "--- Optimize"
    $PHP artisan optimize

    echo "--- Cek halaman utama"
    echo "HOMEPAGE $(curl -s -o /dev/null -w '%{http_code}' "$SITE_URL/")"

    echo "--- 40 baris terakhir log Laravel"
    latest=$(ls -t storage/logs/*.log 2>/dev/null | head -1)
    [ -n "$latest" ] && tail -n 40 "$latest" || echo "(tidak ada log)"

    echo "SELESAI"
} > "$LOG" 2>&1
