"""ASENTRA SPK — Phase 6J: Final HTTP UI + error-handling audit.

Owner + Admin role walks, V1/V2/legacy period coverage, and the important
error-handling paths. Requires the PHP built-in server on 127.0.0.1:8080:
    C:/xampp/php/php.exe -S 127.0.0.1:8080 -t public public/index.php
"""
import re, sys, urllib.parse, http.cookiejar
from urllib.request import Request, build_opener, HTTPCookieProcessor, HTTPError
from urllib.parse import urlencode

BASE = "http://127.0.0.1:8080"
pass_ = 0
fail_ = 0


def record(name, ok, detail=""):
    global pass_, fail_
    if ok:
        pass_ += 1
    else:
        fail_ += 1
    print(("OK  : " if ok else "FAIL: ") + name + (f" — {detail}" if detail and not ok else ""))


class S:
    def __init__(self):
        self.op = build_opener(HTTPCookieProcessor(http.cookiejar.CookieJar()))

    def get(self, path):
        return self.op.open(Request(BASE + path))

    def post(self, path, data):
        req = Request(BASE + path, data=urlencode(data).encode(), method="POST")
        req.add_header("Content-Type", "application/x-www-form-urlencoded")
        return self.op.open(req)

    def read(self, r):
        return r.read().decode("utf-8", "replace")

    def csrf(self, path="/login"):
        m = re.search(r'name="csrf_token" value="([^"]+)"', self.read(self.get(path)))
        return m.group(1) if m else ""

    def login(self, user, pw):
        self.post("/login", {"csrf_token": self.csrf(), "username": user, "password": pw})


owner = S()
owner.login("owner", "owner")

# ---------------------------------------------------------------- owner pages
pages = [
    ("/owner/dashboard", "Dashboard"),
    ("/owner/ranking", "Ranking"),
    ("/owner/riwayat", "Riwayat"),
    ("/owner/laporan", "Laporan index"),
]
for path, label in pages:
    try:
        r = owner.get(path)
        html = owner.read(r)
        record(f"owner {label} loads ({path})", r.status == 200 and "Fatal error" not in html,
               f"status={r.status}")
    except HTTPError as e:
        record(f"owner {label} loads ({path})", False, f"HTTP {e.code}")

# ------------------------------------------------- period-specific reports
report_paths = [
    ("/owner/laporan/25", "V2 by id"),
    ("/owner/laporan/Q1-2026", "V2 by quarter code"),
    ("/owner/laporan/2026-08", "V1 legacy 2026-08"),
    ("/owner/laporan/LEGACY-2026-09", "LEGACY-2026-09"),
]
for path, label in report_paths:
    try:
        r = owner.get(path)
        html = owner.read(r)
        ok = r.status == 200 and "LAPORAN" in html and "Fatal error" not in html
        record(f"report {label} ({path})", ok, f"status={r.status}")
    except HTTPError as e:
        record(f"report {label} ({path})", False, f"HTTP {e.code}")

# ------------------------------------------------- error handling
# Non-existent report id -> clean message, never a raw PHP error.
try:
    r = owner.get("/owner/laporan/999999")
    html = owner.read(r)
    ok = r.status in (200, 302, 404) and "Fatal error" not in html and "Warning:" not in html
    record("non-existent report id handled cleanly", ok, f"status={r.status}")
except HTTPError as e:
    record("non-existent report id handled cleanly", e.code in (404, 302), f"HTTP {e.code}")

# Invalid period selector on ranking -> no crash.
try:
    r = owner.get("/owner/ranking?periode=NOT-A-PERIOD")
    html = owner.read(r)
    record("invalid ranking period selector clean", r.status == 200 and "Fatal error" not in html,
           f"status={r.status}")
except HTTPError as e:
    record("invalid ranking period selector clean", False, f"HTTP {e.code}")

# Unauthenticated -> redirect to login.
anon = S()
try:
    r = anon.get("/owner/dashboard")
    redir = r.url if hasattr(r, "url") else ""
    record("unauthenticated /owner/dashboard redirects to login",
           r.status == 200 and ("/login" in redir or "/login" in owner.read(r)[:400]),
           f"status={r.status} url={redir}")
except HTTPError as e:
    record("unauthenticated /owner/dashboard redirects to login", e.code in (301, 302, 303), f"HTTP {e.code}")

# ---------------------------------------------------------------- admin role
admin = S()
admin.login("admin", "admin")

# Admin must be blocked from owner routes (403 or redirect away).
try:
    r = admin.get("/owner/dashboard")
    html = admin.read(r)
    blocked = r.status == 403 or "Dashboard Owner" not in html
    record("admin blocked from /owner/dashboard", blocked, f"status={r.status}")
except HTTPError as e:
    record("admin blocked from /owner/dashboard", e.code == 403, f"HTTP {e.code}")

# Admin pages must load for admin.
for path, label in [("/admin/dashboard", "admin dashboard")]:
    try:
        r = admin.get(path)
        html = admin.read(r)
        record(f"{label} loads ({path})", r.status == 200 and "Fatal error" not in html,
               f"status={r.status}")
    except HTTPError as e:
        record(f"{label} loads ({path})", False, f"HTTP {e.code}")

# ---------------------------------------------------------------- print
print()
print("=" * 40)
print(f"RESULT: {pass_}/{pass_ + fail_} PASS")
print("=" * 40)
sys.exit(0 if fail_ == 0 else 1)
