#!/usr/bin/env bash
# Smoke test: checks the main features against a running site.
# Usage: BASE_URL=http://localhost:8000 bash tests/smoke.sh
set -euo pipefail
BASE_URL="${BASE_URL:-http://localhost:8000}"
pass=0; fail=0
ok()   { echo "  ✓ $1"; pass=$((pass+1)); }
bad()  { echo "  ✗ $1"; fail=$((fail+1)); }
check(){ if eval "$2"; then ok "$1"; else bad "$1"; fi; }

echo "Smoke test on $BASE_URL"
TOMORROW=$(date -d tomorrow +%F 2>/dev/null || date -v+1d +%F)

shops=$(curl -fsS "$BASE_URL/api.php?action=shops")
check "API lists shops"                "echo '$shops' | grep -q '\"ok\":true'"
bike_id=$(echo "$shops" | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["shops"][0]["bikes"][0]["id"] ?? "";')
check "At least one shop with a bike"   "[ -n '$bike_id' ]"

booking=$(curl -fsS -X POST "$BASE_URL/api.php?action=book" -H 'Content-Type: application/json' \
  -d "{\"bike_id\":$bike_id,\"name\":\"Smoke Test\",\"phone\":\"9876543210\",\"date\":\"$TOMORROW\",\"days\":2}")
code=$(echo "$booking" | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["booking"]["code"] ?? "";')
check "Booking is saved and gets an ID" "[[ '$code' =~ ^GL[0-9]{6}$ ]]"

status=$(curl -fsS -X POST "$BASE_URL/api.php?action=status" -d "{\"codes\":[\"$code\"]}")
check "Booking status can be read"      "echo '$status' | grep -q '\"status\":\"new\"'"

bad_date=$(curl -sS -X POST "$BASE_URL/api.php?action=book" -d "{\"bike_id\":$bike_id,\"name\":\"T U\",\"phone\":\"9876543210\",\"date\":\"2020-01-01\",\"days\":1}")
check "Past pickup date is refused"     "echo '$bad_date' | grep -q '\"ok\":false'"

admin_page=$(curl -fsS "$BASE_URL/admin.php")
check "Admin login page loads"          "grep -q 'Admin login' <<< \"\$admin_page\""
check "Deploy hook needs a token"       "[ \"\$(curl -s -o /dev/null -w '%{http_code}' -X POST '$BASE_URL/deploy-hook.php')\" = 403 ]"
check "Migrate endpoint needs a token"  "[ \"\$(curl -s -o /dev/null -w '%{http_code}' -X POST '$BASE_URL/migrate.php')\" = 403 ]"
home_page=$(curl -fsS "$BASE_URL/index.html")
check "Home page loads"                 "grep -q 'GoLocal' <<< \"\$home_page\""
soon_page=$(curl -fsS "$BASE_URL/coming-soon.html")
check "Coming soon page loads"          "grep -q 'Launching soon' <<< \"\$soon_page\""

echo "Passed: $pass   Failed: $fail"
[ "$fail" -eq 0 ]
