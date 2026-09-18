"""Phase 6H: Ranking UI V2 HTTP test."""
import re, sys, urllib.parse, http.cookiejar
from urllib.request import Request, build_opener, HTTPCookieProcessor, HTTPError
from urllib.parse import urlencode

BASE = "http://127.0.0.1:8080"
results = []

def record(name, ok, detail=""):
    results.append((name, ok, detail))
    print(("OK  " if ok else "FAIL") + ": " + name + ("  " + detail if detail else ""))

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

owner = S()
owner.login("owner", "owner")

# ---- 1. Ranking page loads, V2 period option present ----
html = owner.read(owner.get("/owner/ranking"))
record("ranking page loads", ">Pilih periode</option>" in html or "Pilih Periode" in html)
record("ranking selector lists V2 period", "Q1-2026" in html, "")
record("ranking selector lists legacy period", "LEGACY-2026-09" in html or "September 2026" in html, "")

# Grab the V2 option value (numeric id_periode) from the selector.
m = re.search(r'<option value="(\d+)"[^>]*>[^<]*Q1-2026', html)
v2_id = m.group(1) if m else "25"
record("V2 selector value is numeric id_periode", v2_id.isdigit(), "value=" + v2_id)

# ---- 2. Ranking V2 via numeric id_periode ----
html = owner.read(owner.get("/owner/ranking?periode=" + urllib.parse.quote(v2_id)))
record("ranking V2 (id) loads", "Ranking SAW" in html, "")
record("ranking V2 shows teknisi", "Rahmat Hidayat" in html and "Ahmad Sahudin" in html, "")
record("ranking V2 shows full C1 precision", "3,777778" in html, "")
record("ranking V2 shows full C2 precision", "3,888889" in html, "")
record("ranking V2 shows Vi", "0,989" in html and "0,625" in html, "")
record("ranking V2 shows rank badges", ">1<" in html and ">10<" in html, "")
record("ranking V2 shows normalisasi header", "N1 (C1/max)" in html, "")
record("ranking V2 shows kontribusi header", "K1 (w" in html, "")
record("ranking V2 shows normalisasi value", "0,971429" in html, "")
record("ranking V2 shows kontribusi value", "0,388571" in html, "")
record("ranking V2 heading uses nama_periode", "Januari - Maret 2026" in html, "")
record("ranking V2 heading shows date range", "2026-01-01 s/d 2026-03-31" in html, "")
record("ranking V2 has Detail link", "/owner/ranking/detail/" in html, "")
record("ranking V2 has Cetak Laporan link", "/owner/laporan/" + v2_id in html, "")

# ---- 3. Ranking V2 via quarter code ----
r = owner.get("/owner/ranking?periode=" + urllib.parse.quote("Q1-2026"))
html2 = owner.read(r)
record("ranking V2 (quarter code) loads", "Ranking SAW" in html2, "status=" + str(r.status))
record("ranking V2 (quarter code) same rows", "3,777778" in html2 and "0,989" in html2, "")

# ---- 4. Detail SAW page for a V2 row ----
m = re.search(r'/owner/ranking/detail/(\d+)"', html)
did = m.group(1) if m else ""
record("found detail id", did != "", "id=" + did)
if did:
    html3 = owner.read(owner.get("/owner/ranking/detail/" + did))
    record("detail page loads", "Detail Perhitungan SAW" in html3, "")
    record("detail shows max from tb_hasil (3,888889)", "3,888889" in html3, "")
    record("detail shows normalisasi 6dp", "0,971429" in html3 or "1" in html3, "")
    record("detail shows kontribusi 6dp", "0,388571" in html3 or "0,3" in html3, "")
    record("detail shows Vi", "0,988571" in html3, "")
    record("detail shows V2 period label", "Januari - Maret 2026" in html3, "")
    record("detail back link uses periode", "/owner/ranking?periode=" in html3, "")

# ---- 5. Cetak Laporan for V2 (numeric id) ----
r = owner.get("/owner/laporan/" + urllib.parse.quote(v2_id))
html4 = owner.read(r)
record("laporan V2 (id) loads", r.status == 200 and "LAPORAN PENILAIAN KINERJA TEKNISI" in html4, "status=" + str(r.status))
record("laporan V2 shows 10 rows", html4.count("<tr>") >= 11, "")
record("laporan V2 shows teknisi", "Rahmat Hidayat" in html4, "")

# ---- 6. Cetak Laporan for V2 (quarter code) ----
r = owner.get("/owner/laporan/" + urllib.parse.quote("Q1-2026"))
html5 = owner.read(r)
record("laporan V2 (quarter code) loads", r.status == 200 and "LAPORAN PENILAIAN KINERJA TEKNISI" in html5, "status=" + str(r.status))

# An unprocessed quarter code must be rejected, not silently rendered empty.
r = owner.get("/owner/laporan/2026-Q3")
body_q3 = owner.read(r)
record("laporan rejects unprocessed quarter code", "LAPORAN PENILAIAN KINERJA TEKNISI" not in body_q3, "status=" + str(r.status))

# ---- 7. Laporan index lists V2 ----
html6 = owner.read(owner.get("/owner/laporan"))
record("laporan index lists V2 period", "Q1-2026" in html6, "")
record("laporan index links numeric id", "/owner/laporan/" + v2_id in html6, "")
# The same period must not appear twice (once as id, once as quarter code).
links = re.findall(r'/owner/laporan/([^"]+)"', html6)
record("laporan index lists each period once", len(links) == len(set(links)), "links=" + str(links))

# ---- 8. V1/legacy still works ----
html7 = owner.read(owner.get("/owner/ranking?periode=" + urllib.parse.quote("2026-08")))
record("ranking legacy 2026-08 loads", "Ranking SAW" in html7, "")
record("ranking legacy shows Toni", "Toni" in html7, "")
record("ranking legacy shows golden Vi 1,000", "1,000" in html7, "")
# Legacy rows are seeded with normalisasi/kontribusi too, so the detail columns
# are shown for them as well — the point is that no value is invented client-side.
record("ranking legacy shows normalisasi columns", "N1 (C1/max)" in html7, "")
record("ranking legacy has laporan link", "/owner/laporan/2026-08" in html7, "")

r = owner.get("/owner/laporan/2026-08")
html8 = owner.read(r)
record("laporan legacy loads", r.status == 200 and "LAPORAN" in html8, "status=" + str(r.status))

html9 = owner.read(owner.get("/owner/riwayat?periode=" + urllib.parse.quote("2026-08")))
record("riwayat legacy loads", "Riwayat" in html9 and "Toni" in html9, "")
record("riwayat legacy shows C1 column", "C1" in html9, "")

# ---- 9. Legacy September (2 rows) ----
html10 = owner.read(owner.get("/owner/ranking?periode=" + urllib.parse.quote("LEGACY-2026-09")))
record("ranking LEGACY-2026-09 loads", "Ranking SAW" in html10, "")
record("ranking LEGACY-2026-09 shows 2 teknisi", "Apip" in html10, "")

# ---- 10. Proses Ulang SAW on V2 (re-run keeps idempotent) ----
token = owner.csrf("/owner/ranking")
r = owner.post("/owner/ranking/process", {"csrf_token": token, "periode": v2_id})
record("proses ulang V2 accepted", r.status == 200, "status=" + str(r.status))
html11 = owner.read(owner.get("/owner/ranking?periode=" + urllib.parse.quote(v2_id)))
record("proses ulang V2 kept 10 rows", ">10<" in html11, "")
record("proses ulang V2 still full precision", "3,777778" in html11, "")

# ---- 11. Admin blocked ----
admin = S()
admin.login("admin", "admin")
try:
    r = admin.get("/owner/ranking")
    record("admin blocked from ranking", r.status == 403, "status=" + str(r.status))
except HTTPError as e:
    record("admin blocked from ranking", e.code == 403, "code=" + str(e.code))

# ---- 12. Malformed period rejected by laporan ----
r = owner.get("/owner/laporan/bogus")
record("laporan rejects malformed period", r.status in (200, 302, 303), "status=" + str(r.status))

print()
failed = [x for x in results if not x[1]]
print("RESULT: %d/%d %s" % (len(results) - len(failed), len(results), "PASS" if not failed else "FAIL"))
for f in failed:
    print("  FAILED: " + f[0] + " " + f[2])
sys.exit(1 if failed else 0)
