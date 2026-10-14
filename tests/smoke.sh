#!/usr/bin/env bash
# Install a throwaway copy of the site, open every main page and fail on any
# HTTP error or PHP warning. Run from the repository root: bash tests/smoke.sh
set -euo pipefail

ROOT=$(pwd)
WORK=$(mktemp -d)
PORT=${PORT:-8765}
U="http://127.0.0.1:$PORT"
trap 'kill $SERVER 2>/dev/null || true; rm -rf "$WORK"' EXIT

git ls-files -co --exclude-standard | tar cf - -T - | tar xf - -C "$WORK"
cd "$WORK"
php -d error_reporting=-1 -d display_errors=0 -d log_errors=1 -d error_log="$WORK/php-errors.log" -S 127.0.0.1:$PORT >/dev/null 2>&1 &
SERVER=$!
for _ in $(seq 1 50); do curl -s -o /dev/null "$U/install.php" && break; sleep 0.1; done

J="$WORK/cookies.txt"
csrf() { grep -o 'name="csrf" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//'; }
T=$(curl -s -c "$J" -b "$J" "$U/install.php" | csrf)
curl -s -c "$J" -b "$J" -o /dev/null -X POST "$U/install.php" --data-urlencode csrf="$T" \
  -d site_name=Test -d site_description=Desc -d site_url="$U" -d admin_email=a@b.co \
  -d 'admin_password=Passw0rd!Strong1' -d 'admin_password_again=Passw0rd!Strong1' -d color_scheme=toa
test -f .qfa-installed || { echo "install failed"; exit 1; }

fail=0
check() {
  local want=$1 path=$2 got
  got=$(curl -s -o /dev/null -w '%{http_code}' "$U/$path")
  if [ "$got" != "$want" ]; then echo "FAIL $got (want $want) /$path"; fail=1; else echo "ok   $got /$path"; fi
}
for p in "" "index.php?surah=2" "index.php?action=quran" "index.php?action=tafseer&type=1&surah=1&ayah=1" \
  "index.php?action=languages" "index.php?action=translate&l=en&surah=1" "index.php?action=translate&l=cz&surah=1" \
  "index.php?action=books" "index.php?action=books_category&category_id=124" "index.php?action=qibla" \
  "index.php?action=tasbeeh" "index.php?action=contact" "index.php?action=date_converter" \
  "index.php?action=morning_adhkar" "index.php?action=evening_adhkar" "admin-login.php" "forgot-password.php"; do
  check 200 "$p"
done
# Private pages must send visitors to the login page.
for p in admin.php admin-settings.php admin-pages.php admin-messages.php admin-security.php admin-integrity.php "index.php?action=sadaqah_agent"; do
  check 302 "$p"
done

if [ -s "$WORK/php-errors.log" ]; then
  echo "PHP reported problems:"; sort "$WORK/php-errors.log" | sed 's/^\[[^]]*\] //' | sort | uniq -c | head -40
  fail=1
fi
exit $fail
