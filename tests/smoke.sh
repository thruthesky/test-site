#!/bin/sh
set -eu

BASE_URL="${BASE_URL:-http://localhost:8080}"
COOKIE_JAR="$(mktemp)"

cleanup() {
  rm -f "$COOKIE_JAR"
}
trap cleanup EXIT

extract_csrf() {
  curl -s -c "$COOKIE_JAR" "$BASE_URL/" | sed -n 's/.*"csrfToken":"\([^"]*\)".*/\1/p'
}

CSRF_TOKEN="$(extract_csrf)"
STAMP="$(date +%s)"
EMAIL="admin-${STAMP}@example.com"
USERNAME="admin${STAMP}"

echo "Checking homepage..."
curl -fsS "$BASE_URL/" >/dev/null

echo "Registering user..."
curl -fsS -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $CSRF_TOKEN" \
  -d "{\"email\":\"$EMAIL\",\"username\":\"$USERNAME\",\"display_name\":\"Admin\",\"password\":\"secret123\",\"bio\":\"first user\"}" \
  "$BASE_URL/api.php?route=/auth/register" >/dev/null

echo "Creating categories..."
curl -fsS -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $CSRF_TOKEN" \
  -d '{"name":"공지","slug":"notice","sort_order":1,"is_enabled":true}' \
  "$BASE_URL/api.php?route=/admin/categories" >/dev/null

curl -fsS -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $CSRF_TOKEN" \
  -d '{"name":"자유","slug":"free","sort_order":2,"is_enabled":true}' \
  "$BASE_URL/api.php?route=/admin/categories" >/dev/null

echo "Fetching categories..."
CATEGORIES="$(curl -fsS -b "$COOKIE_JAR" "$BASE_URL/api.php?route=/categories")"
FREE_ID="$(printf '%s' "$CATEGORIES" | php -r '$payload=json_decode(stream_get_contents(STDIN), true); foreach($payload["data"] as $item){ if($item["slug"]==="free"){ echo $item["id"]; } }')"

echo "Creating post..."
POST_RESPONSE="$(curl -fsS -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $CSRF_TOKEN" \
  -d "{\"category_id\":$FREE_ID,\"title\":\"첫 글\",\"content\":\"안녕하세요\"}" \
  "$BASE_URL/api.php?route=/posts")"
POST_ID="$(printf '%s' "$POST_RESPONSE" | php -r '$payload=json_decode(stream_get_contents(STDIN), true); echo $payload["data"]["id"];')"

echo "Creating comment..."
curl -fsS -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $CSRF_TOKEN" \
  -d '{"content":"첫 댓글입니다."}' \
  "$BASE_URL/api.php?route=/posts/$POST_ID/comments" >/dev/null

echo "Checking post page..."
curl -fsS "$BASE_URL/post/$POST_ID" >/dev/null

echo "Smoke test completed."
