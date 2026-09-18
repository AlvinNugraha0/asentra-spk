"""Phase 6 — Laporan (Report/Print) HTTP integration tests."""
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


# === 1. Owner can access report index ===
owner = S()
owner.login("owner", "owner")
r = owner.get("/owner/laporan")
html = owner.read(r)
record("owner report index loads 200", r.status == 200, f"status={r.status}")

# === 2. Admin receives 403 ===
admin = S()
admin.login("admin", "admin")
try:
    r = admin.get("/owner/laporan")
    html = admin.read(r)
    record("admin report index 403", r.status == 403 and "Akses Ditolak" in html, f"status={r.status}")
except HTTPError as e:
    body = e.read().decode("utf-8")
    record("admin report index 403", e.code == 403 and "Akses Ditolak" in body, f"status={e.code}")

# === 3. Anonymous redirected to login ===
anon = S()
r = anon.get("/owner/laporan")
record("anon report index redirected", "/login" in r.geturl(), r.geturl())

# === 4. Process SAW for 2026-08 so report has data ===
token = owner.csrf("/owner/ranking")
owner.post("/owner/ranking/process", {"csrf_token": token, "periode": "2026-08"})
record("SAW processed for 2026-08", db_count("2026-08") == 10, f"count={db_count('2026-08')}")

# === 5. Report show page for 2026-08 ===
r = owner.get("/owner/laporan/2026-08")
html = owner.read(r)
record("report 2026-08 loads 200", r.status == 200, f"status={r.status}")

# === 6. Report identity + title + period ===
record("report has ASENTRA identity", "CV ARSITEK SEMESTA NUSANTARA" in html)
record("report has title", "LAPORAN PENILAIAN KINERJA TEKNISI" in html)
record("report shows period", "Agustus 2026" in html)

# === 7. Report criteria/weights ===
record("report shows C1 Kedisiplinan", "Kedisiplinan" in html)
record("report shows C2 Kualitas Hasil Kerja", "Kualitas Hasil Kerja" in html)
record("report shows C3 Tanggung Jawab", "Tanggung Jawab" in html)
record("report shows weights 30/40/30", "30%" in html and "40%" in html)

# === 8. Golden dataset: names + values + order ===
golden = [
    ("Toni", "1,000", 1),
    ("Aris", "0,925", 2),
    ("Rahmat Hidayat", "0,900", 3),
    ("Apip", "0,850", 4),
    ("Wanto", "0,750", 5),
    ("Heri", "0,750", 6),
    ("IMADE", "0,700", 7),
    ("Ahmad Sahudin", "0,675", 8),
    ("Agus Supriyanto", "0,600", 9),
    ("Asep", "0,575", 10),
]
# Extract ranking rows: (kode, nama, c1,c2,c3, nilai, rank) — parse table body
rows = re.findall(
    r"<td class=\"numeric\">(\d+)</td>\s*<td>([A-Z0-9-]+)</td>\s*<td>([^<]+)</td>\s*<td class=\"numeric\">(\d)</td>\s*<td class=\"numeric\">(\d)</td>\s*<td class=\"numeric\">(\d)</td>\s*<td class=\"numeric\">([\d,]+)</td>\s*<td class=\"numeric\">(\d+)</td>",
    html,
)
report_ok = len(rows) == 10
for i, (name, vi, rank) in enumerate(golden):
    if i >= len(rows):
        report_ok = False
        break
    r_no, kode, nama, c1, c2, c3, nilai, r_rank = rows[i]
    if nama.strip() != name or nilai != vi or int(r_rank) != rank:
        report_ok = False
        break
record("report golden dataset exact (name+Vi+rank)", report_ok, f"rows={len(rows)}")

# === 9. Report has conclusion + date ===
record("report has conclusion", "Kesimpulan" in html)
record("report has print date", "Tanggal cetak" in html)

# === 10. Print action + print CSS present ===
record("report has print button", "window.print()" in html or "Cetak Laporan" in html)
record("report loads print.css", "print.css" in html)
record("report hides toolbar on print", "no-print" in html)

# === 11. No SAW recalculation markers (report is presentation only) ===
record("report has no process form", "ranking/process" not in html)
record("report has no recompute script", "SawEngine" not in html and "calculate(" not in html)

# === 12. Empty period shows empty state ===
r = owner.get("/owner/laporan/2025-12")
# should redirect to /owner/laporan with flash (empty state), not render fake report
record("empty period does not render fake report", "LAPORAN PENILAIAN" not in owner.read(r), "")

# === 13. Invalid period rejected ===
r = owner.get("/owner/laporan/INVALID")
record("invalid period rejected", "LAPORAN PENILAIAN" not in owner.read(r))

# === 14. Period isolation: create + process 2026-07, verify report shows only that ===
db_query("INSERT INTO tb_penilaian (teknisi_id, periode, c1, c2, c3, created_by) VALUES (1, '2026-07', 4, 4, 4, 1);")
token = owner.csrf("/owner/ranking")
owner.post("/owner/ranking/process", {"csrf_token": token, "periode": "2026-07"})
r = owner.get("/owner/laporan/2026-07")
html07 = owner.read(r)
record("report 2026-07 only 1 teknisi", "Asep" not in html07 and "Rahmat Hidayat" not in html07 and "Toni" in html07)

# === 15. Different periods remain isolated ===
r = owner.get("/owner/laporan/2026-08")
html08 = owner.read(r)
record("report 2026-08 still 10 teknisi after 2026-07 process", "Asep" in html08 and "Rahmat Hidayat" in html08)

# === Teardown test-only data ===
db_query("DELETE FROM tb_hasil WHERE periode = '2026-07';")
db_query("DELETE FROM tb_penilaian WHERE periode = '2026-07';")

# === Result ===
failed = [r for r in results if not r[1]]
print(f"\nPHASE 6 LAPORAN SUMMARY: {len(results)-len(failed)}/{len(results)} passed")
for name, ok, detail in failed:
    print(f"FAIL: {name} {detail}")
sys.exit(1 if failed else 0)
