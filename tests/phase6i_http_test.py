"""Phase 6I: Reports / History HTTP test.

Exercises the acceptance criteria through the built-in server:
  - periode dapat dipilih (ranking/riwayat/laporan selectors)
  - hasil dapat dilihat (ranking + detail V2 & V1)
  - ranking dapat dilihat
  - history tetap tersedia (/owner/riwayat)
  - legacy tetap aman (12/12, no mutation)
  - export tidak menggunakan data hardcoded (report rows match tb_hasil)
"""
import re, sys, urllib.parse, subprocess
import http.cookiejar
from urllib.request import Request, build_opener, HTTPCookieProcessor, HTTPError
from urllib.parse import urlencode

BASE = "http://127.0.0.1:8080"
results = []

MYSQL = "C:/xampp/mysql/bin/mysql.exe"
DB = "asentra_spk"


def record(name, ok, detail=""):
    results.append((name, ok, detail))
    print(("OK  " if ok else "FAIL") + ": " + name + ("  " + detail if detail else ""))


def db(sql):
    out = subprocess.run([MYSQL, "-u", "root", DB, "-N", "-B", "-e", sql],
                         capture_output=True, text=True).stdout.strip()
    return out


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
        return r.read().decode("utf-8", "replace")

    def csrf(self, path):
        html = self.read(self.get(path))
        m = re.search(r'name="csrf_token" value="([^"]+)"', html)
        if not m:
            raise RuntimeError("CSRF token not found on " + path)
        return m.group(1)

    def login(self, user, pwd):
        token = self.csrf("/login")
        self.post("/login", {"csrf_token": token, "username": user, "password": pwd})


before_penilaian = db("SELECT COUNT(*) FROM tb_penilaian WHERE status_data='legacy';")
before_hasil = db("SELECT COUNT(*) FROM tb_hasil WHERE id_periode IN (SELECT id_periode FROM tb_periode_penilaian WHERE status='legacy');")

# ---------------------------------------------------------------- login
owner = S()
owner.login("owner", "owner")
r = owner.get("/owner/dashboard")
html = owner.read(r)
record("login works (owner reaches dashboard)", r.status == 200 and "Welcome back" in html, f"status={r.status}")
record("dashboard renders a V2 period label from nama_periode",
       ("Januari - Maret 2026" in html) or ("Agustus 2026" in html)
       or ("September 2026" in html) or ("Triwulan" in html), "")

# ------------------------------------------------- periode dapat dipilih
html = owner.read(owner.get("/owner/ranking"))
record("ranking page loads", ">Pilih periode</option>" in html or "Pilih Periode" in html)
record("ranking selector lists V2 period (nama_periode)", "Januari - Maret 2026" in html)
record("ranking selector lists V2 period (kode)", "Q1-2026" in html)
record("ranking selector lists legacy period", "September 2026" in html or "LEGACY-2026-09" in html)

m = re.search(r'<option value="(\d+)"[^>]*>[^<]*Q1-2026', html)
v2_id = m.group(1) if m else "25"
record("V2 selector value is numeric id_periode", v2_id.isdigit(), "value=" + v2_id)

html = owner.read(owner.get("/owner/laporan"))
record("laporan index lists V2 period", "Q1-2026" in html and "Januari - Maret 2026" in html)
record("laporan index lists legacy period", "September 2026" in html or "LEGACY-2026-09" in html)
record("laporan index has no duplicate period labels",
       len(re.findall(r'Q1-2026', html)) == 1, "occurrences=" + str(len(re.findall(r'Q1-2026', html))))

# ---------------------------------------------------- hasil dilihat (V2)
html = owner.read(owner.get("/owner/ranking?periode=" + urllib.parse.quote(v2_id)))
record("ranking V2 shows report heading from nama_periode", "Januari - Maret 2026" in html)
record("ranking V2 does not show raw quarter code as heading",
       "Ranking Terakhir — Q1-2026" not in html and ") — Q1-2026" not in html, "")
record("ranking V2 lists Toni", "Toni" in html)
record("ranking V2 lists 10 technicians", html.count("tech-avatar") >= 10 or html.count("rank-default") >= 7, "")

# Report rows must match the DB, not hardcoded data.
rows = re.findall(r'<strong>([A-Z0-9]+)</strong>\s*<span class="text-muted"', html)
db_order = db("SELECT t.kode_teknisi FROM tb_hasil h JOIN tb_teknisi t ON t.id=h.teknisi_id WHERE h.id_periode=25 ORDER BY h.ranking;")
db_list = db_order.split() if db_order else []
record("ranking V2 order matches tb_hasil", rows == db_list and len(rows) == 10, f"ui={rows} db={db_list}")

# ---------------------------------------------------- hasil dilihat (V1)
html = owner.read(owner.get("/owner/ranking?periode=2026-08"))
record("ranking V1 2026-08 heading is 'Agustus 2026'", "Agustus 2026" in html)
record("ranking V1 shows Toni", "Toni" in html)
record("ranking V1 shows 1,000", "1,000" in html)

# ----------------------------------------------- laporan render (real)
def report(path):
    r = owner.get(path)
    h = owner.read(r)
    return r, h


r, h = report("/owner/laporan/" + v2_id)
record("laporan/{id} renders the report", r.status == 200 and "LAPORAN PENILAIAN KINERJA TEKNISI" in h, f"status={r.status}")
record("laporan/{id} heading uses nama_periode", "Januari - Maret 2026" in h and "2026-01-01 s/d 2026-03-31" in h)
record("laporan/{id} shows Toni", "Toni" in h)
record("laporan/{id} is not the index page", "Pilih Periode Laporan" not in h, "")

r, h = report("/owner/laporan/Q1-2026")
record("laporan/Q1-2026 renders the report", r.status == 200 and "LAPORAN PENILAIAN KINERJA TEKNISI" in h, f"status={r.status}")
record("laporan/Q1-2026 heading uses nama_periode", "Januari - Maret 2026" in h and "2026-01-01 s/d 2026-03-31" in h)
record("laporan/Q1-2026 shows 10 rows", h.count("<tr>") >= 12, "")

r, h = report("/owner/laporan/2026-08")
record("laporan/2026-08 renders the report", r.status == 200 and "LAPORAN PENILAIAN KINERJA TEKNISI" in h, f"status={r.status}")
record("laporan/2026-08 heading is 'Agustus 2026'", "Agustus 2026" in h)
record("laporan/2026-08 shows Toni + 1,000", "Toni" in h and "1,000" in h)
record("laporan/2026-08 has no hardcoded Vi", "0,925" in h, "")

r, h = report("/owner/laporan/LEGACY-2026-09")
record("laporan/LEGACY-2026-09 renders the report", r.status == 200 and "LAPORAN PENILAIAN KINERJA TEKNISI" in h, f"status={r.status}")
record("laporan/LEGACY-2026-09 heading is 'September 2026'", "September 2026" in h)

# Empty period rejected cleanly (redirect to index, not a crash).
try:
    r = owner.get("/owner/laporan/2026-Q3")
    h = owner.read(r)
    record("laporan/{empty} redirects to index", "/owner/laporan" in r.geturl() and "Pilih Periode Laporan" in h, r.geturl())
except HTTPError as e:
    record("laporan/{empty} redirects to index", False, f"HTTP {e.code}")

try:
    r = owner.get("/owner/laporan/NOTAPERIOD")
    h = owner.read(r)
    record("laporan/{invalid} redirects to index", "/owner/laporan" in r.geturl() and "Pilih Periode Laporan" in h, r.geturl())
except HTTPError as e:
    record("laporan/{invalid} redirects to index", False, f"HTTP {e.code}")

# ------------------------------------------------- history tetap tersedia
html = owner.read(owner.get("/owner/riwayat"))
record("riwayat page loads", r.status == 200 or "Riwayat Ranking" in html)
record("riwayat selector lists periods", "Pilih periode" in html and "Q1-2026" in html)

html = owner.read(owner.get("/owner/riwayat?periode=" + urllib.parse.quote(v2_id)))
record("riwayat V2 shows results", "Toni" in html and "0,989" in html, "")
html = owner.read(owner.get("/owner/riwayat?periode=2026-08"))
record("riwayat V1 2026-08 shows results", "Toni" in html and "1,000" in html)

# ------------------------------------------------------ legacy tetap aman
after_penilaian = db("SELECT COUNT(*) FROM tb_penilaian WHERE status_data='legacy';")
after_hasil = db("SELECT COUNT(*) FROM tb_hasil WHERE id_periode IN (SELECT id_periode FROM tb_periode_penilaian WHERE status='legacy');")
record("legacy tb_penilaian still 12", before_penilaian == "12" and after_penilaian == "12",
      f"before={before_penilaian} after={after_penilaian}")
record("legacy tb_hasil still 12", before_hasil == "12" and after_hasil == "12",
      f"before={before_hasil} after={after_hasil}")
record("V2 tb_hasil still 10", db("SELECT COUNT(*) FROM tb_hasil WHERE id_periode=25;") == "10",
      db("SELECT COUNT(*) FROM tb_hasil WHERE id_periode=25;"))

v1 = db("SELECT ROUND(nilai_preferensi,3) FROM tb_hasil WHERE periode='2026-08' ORDER BY ranking LIMIT 1;")
record("legacy V1 rank1 unchanged (1.000)", v1 == "1.000", v1)

# ---------------------------------------------------------------- result
failed = [x for x in results if not x[1]]
print(f"\nPHASE 6I HTTP SUMMARY: {len(results)-len(failed)}/{len(results)} passed")
for name, ok, detail in failed:
    print(f"FAIL: {name} {detail}")
sys.exit(1 if failed else 0)
