#!/usr/bin/env bash
# Deploy backend Wariga ke server produksi (pola releases/ + symlink current).
# Jalankan dari komputer lokal:
#   ssh balisantih 'bash -s' < scripts/deploy-production.sh
# Rollback: arahkan kembali symlink ke rilis lama yang dicetak di akhir skrip,
# lalu `systemctl reload php8.3-fpm`. Backup database ada di /root/wariga-backups.
set -euo pipefail

APP=/var/www/wariga
REPO=https://github.com/komangindra86/wariga.git
ENGINE_VERSION=0.4.0
CUR=$(readlink -f "$APP/current")
TS=$(date +%Y%m%d%H%M%S)
NEW=$APP/releases/$TS
BK=/root/wariga-backups

mkdir -p "$BK" && chmod 700 "$BK"

echo "== 1. Backup database"
envv() { grep -E "^$1=" "$CUR/.env" | head -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'; }
CNF=$(mktemp) && chmod 600 "$CNF"
printf '[client]\nuser=%s\npassword=%s\nhost=%s\n' \
    "$(envv DB_USERNAME)" "$(envv DB_PASSWORD)" "$(envv DB_HOST)" > "$CNF"
mysqldump --defaults-extra-file="$CNF" --single-transaction --no-tablespaces \
    "$(envv DB_DATABASE)" | gzip > "$BK/wariga-db-$TS.sql.gz"
rm -f "$CNF"
ls -la "$BK/wariga-db-$TS.sql.gz"

echo "== 2. Siapkan rilis $TS"
sudo -u devops git clone -q --depth 1 "$REPO" "$NEW"
sudo -u devops git -C "$NEW" log --oneline -1
cd "$NEW"
sudo -u devops rm -rf storage bootstrap/cache
sudo -u devops ln -s "$APP/shared/storage" storage
sudo -u devops ln -s "$APP/shared/bootstrap-cache" bootstrap/cache
sudo -u devops cp -p "$CUR/.env" .env
sudo -u devops sed -i "s/^WARIGA_ENGINE_VERSION=.*/WARIGA_ENGINE_VERSION=$ENGINE_VERSION/" .env
if [ -d "$CUR/public/build" ]; then sudo -u devops cp -a "$CUR/public/build" public/build; fi
sudo -u devops composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction -q

echo "== 3. Migrasi dan cache"
sudo -u devops php artisan migrate --force
sudo -u devops php artisan config:cache
sudo -u devops php artisan route:cache
sudo -u devops php artisan view:cache

echo "== 4. Aktifkan rilis"
sudo -u devops ln -sfn "$NEW" "$APP/current.tmp"
sudo -u devops mv -Tf "$APP/current.tmp" "$APP/current"
systemctl reload php8.3-fpm
sudo -u devops php "$APP/current/artisan" queue:restart || true

echo "== 5. Pemeriksaan"
curl -fsS -o /dev/null -w "health %{http_code}\n" https://wariga.balisantih.com/health
curl -fsS https://wariga.balisantih.com/api/v1/calendar/today | grep -o '"engine_version":"[^"]*"' | head -1

echo "Selesai. Rilis aktif: $NEW"
echo "Rilis sebelumnya (untuk rollback): $CUR"
