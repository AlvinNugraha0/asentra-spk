"""Owner UI HTTP integration tests."""
import re, subprocess, sys
import urllib.parse, http.cookiejar
from urllib.request import Request, build_opener, HTTPCookieProcessor, HTTPError
from urllib.parse import urlencode

BASE = "http://127.0.0.1:8080"
results = []

def record(name, ok, detail=""):
    results.append((name, ok, detail))
    print(f"{'OK' if ok else 'FAIL'}: {name} {detail}")

class S:
    def __init__(self):
        self.op = build_opener(HTTPCookieProcessor(http.cookiejar.CookieJar()))

    def get(self, path):
        return self.op.open(Request(BASE + path))

    def post(self, path, data):
        body = urlencode(data).encode("utf-8")
        req = Request(BASE + path, data=body, method="POST")
        req.add_header("Content-Type", "application/x-www-form-urlencoded")
        return self.op.open(req)

    def read(self, r):
        return r.read().decode("utf-8")

    def csrf(self, path):
        html = self.read(self.get(path))
        m = re.search(r'name="csrf_token" value="([^"]+)"', html)
        if not m:
            raise RuntimeError(f"CSRF token not found on {path}")
        return m.group(1)

    def login(self, user, pwd):
        token = self.csrf("/login")
        self.post("/login", {"csrf_token": token, "username": user, "password": pwd})


def db_query(sql):
    r = subprocess.run([r"C:\xampp\mysql\bin\mysql.exe", "-u", "root", "asentra_spk", "-e", sql],
                       capture_output=True, text=True)
    return r.stdout.strip()


def db_count(periode):
    out = db_query(f"SELECT COUNT(*) FROM tb_hasil WHERE periode='{periode}';")
    m = re.search(r"(\d+)", out)
    return int(m.group(1)) if m else -1


def db_value(sql):
    out = db_query(sql)
    m = re.search(r"(\d+)", out)
    return int(m.group(1)) if m else -1


# === Unauthenticated access ===
anon = S()
try:
    r = anon.get("/owner/dashboard")
    record("anon owner dashboard redirected", "/login" in r.geturl(), r.geturl())
except HTTPError as e:
    record("anon owner dashboard redirected", False, str(e))

# === Owner access ===
owner = S()
owner.login("owner", "owner")
r = owner.get("/owner/dashboard")
html = owner.read(r)
record("owner dashboard loads 200", r.status == 200, f"status={r.status}")
# Accept any period label the dashboard may render: a V1 Indonesian month
# label, a V2 nama_periode / quarter label, or the raw quarter code itself.
_dash_ok = ("Agustus 2026" in html) or ("September 2026" in html) \
    or ("Triwulan" in html) or ("Januari - Maret 2026" in html) \
    or ("Q1-2026" in html)
record("owner dashboard shows periode", _dash_ok, "")
record("owner dashboard shows teknisi aktif", "Teknisi Aktif" in html, "")
record("owner dashboard shows top rank", "Peringkat #1" in html, "")
record("owner dashboard empty state or top name", ("Toni" in html) or ("Belum ada hasil SAW" in html), "")

# === Owner ranking page (no periode) ===
r = owner.get("/owner/ranking")
html = owner.read(r)
record("owner ranking page loads", r.status == 200)
record("owner ranking empty state", "Pilih Periode" in html or "Pilih periode" in html)

# === Admin cannot access owner pages ===
admin = S()
admin.login("admin", "admin")
try:
    r = admin.get("/owner/dashboard")
    html = admin.read(r)
    record("admin blocked from owner dashboard", r.status == 403 and "Akses Ditolak" in html, f"status={r.status}")
except HTTPError as e:
    body = e.read().decode("utf-8")
    record("admin blocked from owner dashboard", e.code == 403 and "Akses Ditolak" in body, f"status={e.code}")

try:
    r = admin.get("/owner/ranking")
    html = admin.read(r)
    record("admin blocked from owner ranking", r.status == 403 and "Akses Ditolak" in html, f"status={r.status}")
except HTTPError as e:
    body = e.read().decode("utf-8")
    record("admin blocked from owner ranking", e.code == 403 and "Akses Ditolak" in body, f"status={e.code}")

# === Admin cannot process SAW ===
try:
    token = admin.csrf("/owner/ranking")
    r = admin.post("/owner/ranking/process", {"csrf_token": token, "periode": "2026-08"})
    html = admin.read(r)
    record("admin process SAW blocked", r.status == 403 and "Akses Ditolak" in html)
except HTTPError as e:
    body = e.read().decode("utf-8")
    record("admin process SAW blocked", e.code == 403 and "Akses Ditolak" in body, f"status={e.code}")

# === Owner processes SAW for 2026-08 ===
token = owner.csrf("/owner/ranking")
r = owner.post("/owner/ranking/process", {"csrf_token": token, "periode": "2026-08"})
record("owner process SAW 2026-08 status", r.status == 200, f"status={r.status}")
record("owner process SAW 2026-08 persisted 10", db_count("2026-08") == 10, f"count={db_count('2026-08')}")

# === Ranking page with periode shows results ===
r = owner.get("/owner/ranking?periode=2026-08")
html = owner.read(r)
record("ranking page shows Toni", "Toni" in html)
record("ranking page shows 1,000", "1,000" in html or "1,000" in html)
record("ranking page shows rank badges", ">1<" in html and ">10<" in html)
record("ranking page has Detail link", "Detail" in html and "owner/ranking/detail" in html)

# === Cetak Laporan link in ranking toolbar ===
record("ranking page has Cetak Laporan link", "Cetak Laporan" in html)
record("Cetak Laporan links to /owner/laporan/2026-08", "/owner/laporan/2026-08" in html)

# No period selected -> no broken report link
r = owner.get("/owner/ranking")
html_noperiod = owner.read(r)
record("ranking page without period has no Cetak Laporan", "Cetak Laporan" not in html_noperiod)

# Admin cannot access report (report is owner-only)
try:
    r = admin.get("/owner/laporan/2026-08")
    h = admin.read(r)
    record("admin blocked from report", r.status == 403 and "Akses Ditolak" in h, f"status={r.status}")
except HTTPError as e:
    body = e.read().decode("utf-8")
    record("admin blocked from report", e.code == 403 and "Akses Ditolak" in body, f"status={e.code}")


# === Golden dataset verification via HTTP ===
r = owner.get("/owner/ranking?periode=2026-08")
html = owner.read(r)
# Extract ranking order by parsing table rows: the table contains kode_teknisi in order
rows = re.findall(r"<td>\s*<span class=\"badge[^\"]*\">(\d+)</span>\s*</td>\s*<td>\s*<strong>([A-Z0-9-]+)</strong>", html)
# Fallback: parse technician kode by strong tag
kodes = re.findall(r"<strong>([A-Z0-9]+)</strong>\s*<span class=\"text-muted\"", html)
# Golden ranking order by kode_teknisi (SAW preference descending, teknisi_id tie-break)
expected_kodes = ["A1", "A6", "A4", "A2", "A9", "A10", "A7", "A5", "A3", "A8"]
record("ranking page lists 10 teknisi in golden order", kodes == expected_kodes, f"got={kodes}")

# === Detail SAW page ===
# Find a Toni id
toni_id = db_value("SELECT id FROM tb_hasil WHERE periode='2026-08' AND ranking=1;")
record("toni hasil id found", toni_id > 0, f"id={toni_id}")
r = owner.get(f"/owner/ranking/detail/{toni_id}")
html = owner.read(r)
record("detail SAW page loads", r.status == 200, f"status={r.status}")
record("detail SAW shows original C1", "C1" in html and "C2" in html and "C3" in html)
record("detail SAW shows normalized R1", "R1" in html and "R2" in html and "R3" in html)
record("detail SAW shows bobot 30% / 40% / 30%", "30%" in html and "40%" in html)
record("detail SAW shows kontribusi", "Kontribusi" in html or "kontribusi" in html)
record("detail SAW shows Vi", "Nilai Preferensi" in html)
record("detail SAW shows rank", "#1" in html or ">1<" in html)
record("detail SAW shows formula", "x<sub>ij</sub>" in html and "max(x" in html)

# Admin cannot access detail
try:
    r = admin.get(f"/owner/ranking/detail/{toni_id}")
    html = admin.read(r)
    record("admin blocked from detail", r.status == 403 and "Akses Ditolak" in html)
except HTTPError as e:
    body = e.read().decode("utf-8")
    record("admin blocked from detail", e.code == 403 and "Akses Ditolak" in body, f"status={e.code}")

# === Re-run same period replaces results ===
token = owner.csrf("/owner/ranking")
owner.post("/owner/ranking/process", {"csrf_token": token, "periode": "2026-08"})
record("re-run same period keeps 10", db_count("2026-08") == 10, f"count={db_count('2026-08')}")

# === Cross-period isolation: process another period ===
db_query("INSERT INTO tb_penilaian (teknisi_id, periode, c1, c2, c3, created_by) VALUES (1, '2026-07', 4, 4, 4, 1);")
token = owner.csrf("/owner/ranking")
owner.post("/owner/ranking/process", {"csrf_token": token, "periode": "2026-07"})
record("cross-period: 2026-07 has 1 result", db_count("2026-07") == 1)
record("cross-period: 2026-08 still 10", db_count("2026-08") == 10)
# Re-run 2026-08 and ensure 2026-07 untouched
token = owner.csrf("/owner/ranking")
owner.post("/owner/ranking/process", {"csrf_token": token, "periode": "2026-08"})
record("re-run 2026-08 doesn't affect 2026-07", db_count("2026-07") == 1)

# === Empty period ===
token = owner.csrf("/owner/ranking")
r = owner.post("/owner/ranking/process", {"csrf_token": token, "periode": "2025-12"})
record("empty period not persisted", db_count("2025-12") == 0)

# === Empty period ranking page ===
r = owner.get("/owner/ranking?periode=2025-12")
html = owner.read(r)
record("empty period ranking shows empty state", "tidak tersedia" in html.lower() or "belum diproses" in html.lower() or "data ranking tidak tersedia" in html.lower())

# === History page ===
r = owner.get("/owner/riwayat")
html = owner.read(r)
record("history page loads", r.status == 200)
record("history page shows periode", "2026-08" in html or "Agustus 2026" in html)

r = owner.get("/owner/riwayat?periode=2026-08")
html = owner.read(r)
record("history with periode shows ranking", "Toni" in html and "1,000" in html)

# === Teardown test-only data ===
db_query("DELETE FROM tb_hasil WHERE periode = '2026-07';")
db_query("DELETE FROM tb_penilaian WHERE periode = '2026-07';")

# === Result ===
failed = [r for r in results if not r[1]]
print(f"\nOWNER HTTP SUMMARY: {len(results)-len(failed)}/{len(results)} passed")
for name, ok, detail in failed:
    print(f"FAIL: {name} {detail}")
sys.exit(1 if failed else 0)
