#!/usr/bin/env bash
# Phase 6K — full regression runner: 20 PHP suites + 5 Python HTTP suites.
set -uo pipefail
cd "D:/Aplikasi/asentra-spk"
PHP="C:/xampp/php/php.exe"
PY="python"
OUT="$LOCALAPPDATA/Temp/phase6k_regression.out"
: > "$OUT"

run_php() {
  local f="$1"
  local r
  r=$("$PHP" "tests/$f" 2>&1)
  local code=$?
  local line
  line=$(printf '%s' "$r" | grep -a 'RESULT:' | tail -1)
  [ -z "$line" ] && line='(no RESULT line)'
  if [ $code -eq 0 ]; then
    printf 'PASS  %-38s %s\n' "$f" "$line" | tee -a "$OUT"
  else
    printf 'FAIL  %-38s %s\n' "$f" "$line" | tee -a "$OUT"
  fi
}

run_py() {
  local f="$1"
  local r
  r=$("$PY" "tests/$f" 2>&1)
  local code=$?
  local line
  line=$(printf '%s' "$r" | grep -aE '(RESULT|PASS|OK.*/.*PASS)' | tail -1)
  [ -z "$line" ] && line='(see log)'
  if [ $code -eq 0 ]; then
    printf 'PASS  %-38s %s\n' "$f" "$line" | tee -a "$OUT"
  else
    printf 'FAIL  %-38s %s\n' "$f" "$line" | tee -a "$OUT"
  fi
}

echo '=== PHP SUITES (20) ===' | tee -a "$OUT"
for f in db_connect_test.php auth_service_test.php password_verify_test.php \
         saw_engine_test.php saw_service_test.php saw_v2_test.php saw_deep_verify.php \
         workflow_v2_test.php phase6b_e2e_excel_test.php phase6c_tabulation_test.php \
         phase6d_graphs_test.php phase6e_review_ux_test.php phase6f_confirmation_test.php \
         phase6g_saw_integration_test.php phase6h_ranking_ui_test.php \
         phase6i_reports_history_test.php phase6j_final_e2e_test.php \
         phase6k_auto_periode_test.php periode_validation_test.php \
         operational_calculator_test.php excel_import_test.php; do
  run_php "$f"
done

echo | tee -a "$OUT"
echo '=== PYTHON HTTP SUITES (5) — needs dev server on 127.0.0.1:8080 ===' | tee -a "$OUT"
"$PHP" -S 127.0.0.1:8080 -t public public/index.php >/dev/null 2>&1 &
SRV=$!
sleep 3
trap 'kill $SRV 2>/dev/null' EXIT
for f in phase6h_ranking_ui_http_test.py phase6i_http_test.py laporan_test.py \
         owner_ui_test.py phase6j_final_e2e_http_test.py; do
  run_py "$f"
done
kill $SRV 2>/dev/null

echo | tee -a "$OUT"
echo "=== SUMMARY ===" | tee -a "$OUT"
printf 'PHP   PASS=%s FAIL=%s\n' "$(grep -c '^PASS' "$OUT")" "$(grep -c '^FAIL' "$OUT")" | tee -a "$OUT"
exit 0
