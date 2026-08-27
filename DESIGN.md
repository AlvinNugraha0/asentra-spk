# DESIGN.md --- ASENTRA SPK Dashboard

## Visual Direction: Premium Dark Enterprise Dashboard

> **Primary visual reference:** the user-provided dashboard screenshot
> in this conversation.
>
> The screenshot is the visual benchmark. Recreate its **composition,
> density, spacing, dark surfaces, rounded cards, sidebar proportions,
> typography hierarchy, table treatment, and premium minimal feeling**,
> while replacing the original business content with the ASENTRA SPK
> application content defined in the PRD.

------------------------------------------------------------------------

# 1. Design Goal

Create a polished, production-quality internal management application
for:

**CV Arsitek Semesta Nusantara (ASENTRA)**

Product:

**Sistem Pendukung Keputusan Penilaian Kinerja Teknisi Lapangan**

The application must feel like a modern premium enterprise SaaS
dashboard.

The visual target is:

-   dark;
-   elegant;
-   minimal;
-   premium;
-   compact;
-   data-focused;
-   professional;
-   highly structured;
-   modern without looking futuristic;
-   suitable for a construction company's internal management system.

The design must NOT look like a generic Bootstrap admin template.

The uploaded visual reference should be treated as the primary
inspiration for the overall visual language.

------------------------------------------------------------------------

# 2. Core Visual Concept

Use the following concept:

**"Premium dark management cockpit."**

The application consists of a very dark outer environment surrounding a
slightly lighter application shell.

The shell has:

-   a narrow vertical navigation rail/sidebar;
-   a dark topbar;
-   large rounded content container;
-   modular cards;
-   compact data tables;
-   pill-shaped status badges;
-   strong numeric typography;
-   subtle borders;
-   restrained accent colors.

The UI should have visual depth without heavy shadows.

Think:

``` text
Dark background
    ↓
Dark application shell
    ↓
Slightly lighter cards
    ↓
Bright text
    ↓
Small accent colors
    ↓
Strong data hierarchy
```

------------------------------------------------------------------------

# 3. Reference Composition

The reference screenshot has a very recognizable composition.

Preserve these characteristics:

1.  Dark page background around the application.
2.  Large centered application shell.
3.  Narrow vertical sidebar on the left.
4.  Main content area with generous internal padding.
5.  Top utility bar.
6.  Large page greeting/title.
7.  Compact KPI cards.
8.  Large data visualization / primary content card.
9.  Secondary cards below.
10. Rounded corners everywhere, but not excessively.
11. Very dark backgrounds with slightly lighter surfaces.
12. Bright white typography.
13. Small accent colors used for important states.
14. Large numbers as visual anchors.
15. Tables with dark rows and subtle separators.
16. Compact controls and pill-shaped filters.
17. Strong alignment between cards.

Do not copy the reference's business content.

Only adopt its visual design language.

------------------------------------------------------------------------

# 4. Canvas & Application Shell

Desktop is the primary design target.

Recommended viewport:

``` text
1440 × 900
```

Also support:

``` text
1280 × 800
1920 × 1080
1024 × 768
```

Application should not stretch awkwardly across extremely wide screens.

Recommended:

``` text
body background:
#242424

application max-width:
1400–1480px

application min-height:
calc(100vh - 48px)

application radius:
24px

sidebar width:
78px–92px

main content:
flex: 1
```

The reference uses a visually separated application shell floating
inside a darker page background.

Recreate that effect.

------------------------------------------------------------------------

# 5. Page Background

Use a dark neutral page background.

Primary:

``` text
#242424
```

Do not use pure black for the entire page.

The page background should feel like a dark studio around the
application.

Optional subtle radial/ambient lighting may be used at very low opacity.

Do NOT use visible gradients as a primary design element.

------------------------------------------------------------------------

# 6. Application Shell

Use:

``` text
#05030B
```

for the main application shell or very dark purple-black.

The shell should feel almost black but not completely flat.

Recommended subtle variations:

``` text
Shell:
#05030B

Surface:
#18161B

Surface Elevated:
#211F23

Surface Hover:
#28252B

Border:
#302D34
```

The contrast should come from luminance differences rather than bright
borders.

------------------------------------------------------------------------

# 7. Color System

## Base

``` text
Page Background       #242424
Application Background #05030B
Card Background        #18161B
Elevated Card          #211F23
Border                 #302D34
```

## Text

``` text
Primary Text           #F5F4F7
Secondary Text         #A5A1AA
Muted Text             #77727D
Disabled Text          #5B5860
```

## ASENTRA Accent

Use a refined warm gold.

``` text
Primary Gold           #D6B24C
Light Gold             #E7CC76
Dark Gold              #9F7D1F
```

Gold is the brand accent.

Use it for:

-   active navigation;
-   primary CTA;
-   ranking #1;
-   selected states;
-   key numbers;
-   subtle decorative accents.

Do not make every card gold.

## Semantic Colors

Success:

``` text
#43D17A
```

Warning:

``` text
#F0B84B
```

Danger:

``` text
#F06464
```

Info:

``` text
#6D8CFF
```

Semantic colors should be muted and integrated into the dark palette.

------------------------------------------------------------------------

# 8. Accent Color Strategy

The reference uses several small colors for data states.

ASENTRA should use:

``` text
Gold     = primary brand / ranking emphasis
Green    = positive / complete
Yellow   = warning / pending
Red      = error / destructive
Blue     = informational
```

Never use large saturated color blocks unless necessary.

A status badge should look like:

``` text
[ ● Aktif ]
```

rather than a large colorful rectangle.

------------------------------------------------------------------------

# 9. Typography

Use:

**Inter**

Fallback:

``` text
Inter,
ui-sans-serif,
system-ui,
-apple-system,
BlinkMacSystemFont,
"Segoe UI",
sans-serif
```

The reference has a modern geometric feel with strong rounded
typography.

Use:

``` text
Page title:
30–36px
font-weight: 650–700

Section heading:
18–22px
font-weight: 600–650

Card title:
14–16px
font-weight: 500–600

Large KPI:
32–42px
font-weight: 650–750

Table:
13–14px

Metadata:
11–13px
```

Large numeric values should be visually dominant.

Use tabular numerals for:

-   scores;
-   ranking;
-   percentages;
-   counts.

------------------------------------------------------------------------

# 10. Border Radius

The reference relies heavily on rounded geometry.

Use:

``` text
Application shell:
24px

Large cards:
18px

Normal cards:
14–16px

Buttons:
10–12px

Inputs:
10–12px

Badges:
999px
```

Avoid making every tiny element extremely rounded.

------------------------------------------------------------------------

# 11. Shadows

Use shadows sparingly.

Preferred:

``` text
soft black shadow
low opacity
large blur
```

The interface should primarily gain depth through:

-   surface contrast;
-   spacing;
-   borders;
-   layering.

Avoid dramatic drop shadows.

------------------------------------------------------------------------

# 12. Sidebar

The sidebar is visually narrow, similar to the reference.

Recommended width:

``` text
80px
```

The sidebar should contain icon-first navigation.

Top:

``` text
ASENTRA logo / monogram
```

Navigation:

``` text
Dashboard
Teknisi
Kriteria
Penilaian
Ranking
Riwayat
Laporan
```

Bottom:

``` text
Settings
Logout
```

Because the sidebar is narrow, use tooltips on icon hover.

Active navigation item:

-   light/white or gold circular/squircle background;
-   gold or dark icon depending on contrast;
-   obvious active state.

Inactive:

-   muted white/gray icon;
-   transparent background.

The sidebar should NOT contain large text-heavy navigation labels on
desktop.

------------------------------------------------------------------------

# 13. Sidebar Icon Style

Use a consistent outline icon family.

Recommended:

**Lucide Icons**

Suggested:

``` text
Dashboard       LayoutDashboard
Teknisi         Users
Kriteria        SlidersHorizontal
Penilaian       ClipboardCheck
Ranking         Trophy
Riwayat         History
Laporan         Printer
Settings        Settings
Logout          LogOut
Search          Search
Add             Plus
Edit            Pencil
Delete          Trash2
View             Eye
```

Icons:

``` text
18–20px
stroke width:
1.8–2
```

------------------------------------------------------------------------

# 14. Topbar

The reference has a very compact topbar.

Use:

``` text
Search field
Page/date context
Utility buttons
User avatar
```

For ASENTRA:

Left:

``` text
Search
```

Center/left:

``` text
Current page / date / period
```

Right:

``` text
Notification
Settings
User avatar
```

Search should look like a dark rounded pill:

``` text
┌─────────────────────────────┐
│ ◯  Cari teknisi...          │
└─────────────────────────────┘
```

Do not make the topbar tall.

Recommended:

``` text
64–72px
```

------------------------------------------------------------------------

# 15. User Profile

Use a small circular avatar.

If no real avatar is available:

-   use initials;
-   subtle neutral/gold background;
-   no stock photography required.

Example:

``` text
AS
Admin
```

or:

``` text
OW
Owner
```

Profile dropdown:

``` text
Profil
Pengaturan
Logout
```

------------------------------------------------------------------------

# 16. Main Content

Main content should use a responsive grid.

Recommended:

``` text
padding:
28–36px

gap:
14–18px
```

Do not leave huge empty spaces.

The reference has a compact dashboard density.

The application should feel information-rich but not crowded.

------------------------------------------------------------------------

# 17. Page Header

Every page should start with a concise header.

Example:

``` text
Dashboard
Selamat datang kembali, Admin.

[Periode: Agustus 2026]
```

For Owner:

``` text
Hasil Ranking
Evaluasi kinerja teknisi berdasarkan metode SAW.

[Agustus 2026] [Proses SAW]
```

Use a large title but avoid oversized hero typography.

------------------------------------------------------------------------

# 18. KPI Cards

KPI cards are one of the strongest visual elements from the reference.

Use compact dark cards.

Example:

``` text
┌─────────────────────────────┐
│ Teknisi Aktif           ↗   │
│                             │
│ 10                          │
│                             │
│ +2 dari periode sebelumnya  │
└─────────────────────────────┘
```

Cards should have:

-   title;
-   icon/action indicator;
-   large number;
-   small supporting text;
-   optional status indicator.

Do not fill all KPI cards with gold.

Use gold only for the most important metric.

------------------------------------------------------------------------

# 19. Admin Dashboard

Design the Admin dashboard using the reference's modular card layout.

Top:

``` text
Dashboard
Kelola data teknisi dan penilaian kinerja.
```

KPI grid:

``` text
Teknisi Aktif
10

Penilaian Periode Ini
10

Kriteria
3

Penilaian Lengkap
100%
```

Below:

``` text
┌───────────────────────────────┬───────────────────────┐
│ Penilaian Terbaru             │ Ringkasan Kriteria    │
│                               │                       │
│ table                         │ C1 30%                │
│                               │ C2 40%                │
│                               │ C3 30%                │
└───────────────────────────────┴───────────────────────┘
```

Bottom:

``` text
Quick Actions
[ + Tambah Teknisi ]
[ + Input Penilaian ]
```

------------------------------------------------------------------------

# 20. Owner Dashboard

Owner dashboard should resemble an executive analytics screen.

Header:

``` text
Dashboard Owner
Ringkasan evaluasi kinerja teknisi.
```

KPI cards:

``` text
Teknisi Aktif
10

Peringkat #1
Toni

Skor Tertinggi
1.000

Periode
Agustus 2026
```

Primary section:

``` text
Top Performance
```

Show a horizontal ranking visualization.

Example:

``` text
01  Toni             █████████████  1.000
02  Aris             ████████████   0.925
03  Rahmat Hidayat   ███████████    0.900
04  Apip             ██████████     0.850
05  Wanto            █████████      0.750
```

Bars should use subtle gold/neutral treatment.

Below:

``` text
Hasil Ranking Terbaru
```

Use a compact table.

------------------------------------------------------------------------

# 21. Data Teknisi Screen

Visual structure:

``` text
Data Teknisi
Kelola daftar teknisi lapangan.

[ Search ] [ Status ] [ + Tambah Teknisi ]
```

Then a large dark table card.

Columns:

``` text
KODE
TEKNISI
STATUS
KETERANGAN
DIPERBARUI
AKSI
```

Rows should be compact.

Action menu:

``` text
⋯
```

On click:

``` text
Lihat
Edit
Nonaktifkan
Hapus
```

Use a confirmation modal for delete.

------------------------------------------------------------------------

# 22. Kriteria & Bobot Screen

Header:

``` text
Kriteria & Bobot
Parameter yang digunakan dalam perhitungan SAW.
```

Main card:

``` text
┌────┬──────────────────────┬─────────┬────────┐
│CODE│ KRITERIA             │ ATRIBUT │ BOBOT  │
├────┼──────────────────────┼─────────┼────────┤
│ C1 │ Kedisiplinan         │ Benefit │ 30%    │
│ C2 │ Kualitas Hasil Kerja │ Benefit │ 40%    │
│ C3 │ Tanggung Jawab       │ Benefit │ 30%    │
└────┴──────────────────────┴─────────┴────────┘
```

At the bottom/right:

``` text
Total Bobot
100%
```

If invalid:

``` text
⚠ Total bobot harus 100%
```

------------------------------------------------------------------------

# 23. Input Penilaian Screen

This page should use the same card language as the reference.

Header:

``` text
Input Penilaian
Evaluasi kinerja teknisi berdasarkan tiga kriteria.
```

Top form:

``` text
Periode
[ Agustus 2026 ]

Teknisi
[ Toni ▼ ]
```

Then three equal cards:

``` text
┌───────────────────┐
│ C1                │
│ Kedisiplinan      │
│                   │
│ Bobot 30%         │
│ Benefit           │
│                   │
│ ○ 4 Sangat Baik   │
│ ○ 3 Baik          │
│ ○ 2 Cukup         │
│ ○ 1 Kurang        │
└───────────────────┘
```

Repeat for C2 and C3.

Use selected-state highlighting.

The selected rating should have a gold outline or subtle gold
background.

Bottom:

``` text
[Batal] [Simpan Penilaian]
```

Primary button uses gold.

------------------------------------------------------------------------

# 24. Penilaian Screen

Header:

``` text
Penilaian Kinerja
```

Filter bar:

``` text
[ Periode ] [ Teknisi ] [ Status ] [ Search ]
```

Main table:

``` text
NO
TEKNISI
PERIODE
C1
C2
C3
STATUS
DINILAI OLEH
TANGGAL
AKSI
```

Rating values can use compact circular/square badges.

Example:

``` text
C1  [4]
C2  [4]
C3  [3]
```

------------------------------------------------------------------------

# 25. Hasil Ranking Screen

This should be the **hero screen of the application**.

The visual treatment should be the most polished screen.

Header:

``` text
Hasil Ranking Teknisi
Ranking berdasarkan perhitungan Simple Additive Weighting.
```

Top-right:

``` text
[ Agustus 2026 ▼ ]
[ Proses SAW ]
[ Cetak Laporan ]
```

Method summary cards:

``` text
Metode
SAW

Kriteria
3

Bobot
30 / 40 / 30

Atribut
Benefit
```

Main ranking card:

``` text
┌──────┬────────────────────┬────┬────┬────┬──────────────┐
│ RANK │ TEKNISI            │ C1 │ C2 │ C3 │ NILAI SAW    │
├──────┼────────────────────┼────┼────┼────┼──────────────┤
│  01  │ Toni               │ 4  │ 4  │ 4  │ 1.000        │
│  02  │ Aris               │ 4  │ 4  │ 3  │ 0.925        │
│  03  │ Rahmat Hidayat     │ 4  │ 3  │ 4  │ 0.900        │
│  04  │ Apip               │ 3  │ 4  │ 3  │ 0.850        │
└──────┴────────────────────┴────┴────┴────┴──────────────┘
```

Highlight the first row subtly.

Do NOT create a giant trophy illustration.

The data itself is the hero.

------------------------------------------------------------------------

# 26. Ranking #1 Card

Above or beside the table, optionally display a compact winner card:

``` text
TOP PERFORMER

01

Toni

Nilai SAW
1.000

Kinerja terbaik pada periode ini.
```

Use a subtle gold accent.

The card should be elegant, not gamified.

------------------------------------------------------------------------

# 27. Detail Perhitungan SAW

This screen should visually communicate mathematical transparency.

Header:

``` text
Detail Perhitungan SAW
```

Technician identity card:

``` text
TONI
A1

Agustus 2026

Nilai Akhir
1.000

Ranking
#1
```

Then four stacked/side-by-side sections:

``` text
01  Matriks Keputusan
02  Normalisasi
03  Pembobotan
04  Nilai Preferensi
```

Each section is a dark card.

Example:

``` text
NORMALISASI

C1
4 / 4
1.000

C2
4 / 4
1.000

C3
4 / 4
1.000
```

Use monospace or tabular numeric styling for formulas if appropriate.

------------------------------------------------------------------------

# 28. SAW Calculation Visual Language

The mathematical result should look precise and technical.

Use:

``` text
4 ÷ 4 = 1.000
```

not verbose paragraphs.

For weighted result:

``` text
1.000 × 0.30 = 0.300
1.000 × 0.40 = 0.400
1.000 × 0.30 = 0.300
```

Final:

``` text
0.300 + 0.400 + 0.300 = 1.000
```

Use subtle gold for the final value.

------------------------------------------------------------------------

# 29. Riwayat Ranking

Use the same table treatment.

Header:

``` text
Riwayat Ranking
```

Filters:

``` text
Periode
Tanggal Proses
Search
```

Table:

``` text
PERIODE
TEKNISI TERATAS
NILAI TERTINGGI
JUMLAH TEKNISI
DIPROSES
AKSI
```

Action:

``` text
Lihat Detail
Cetak
```

------------------------------------------------------------------------

# 30. Report Preview

The report preview should switch from dark application UI to a clean
document surface.

The report itself should be:

-   white;
-   highly readable;
-   professional;
-   print-friendly.

Do NOT print the dark dashboard directly.

Structure:

``` text
CV ARSITEK SEMESTA NUSANTARA

LAPORAN PENILAIAN KINERJA TEKNISI

Periode: Agustus 2026

Metode:
Simple Additive Weighting (SAW)

Kriteria:
C1 Kedisiplinan       30%
C2 Kualitas Hasil     40%
C3 Tanggung Jawab     30%

[Tabel Ranking]

Kesimpulan
...

Tanggal
...
```

------------------------------------------------------------------------

# 31. Search

Search controls should resemble the reference:

-   dark filled background;
-   rounded;
-   compact;
-   subtle border;
-   search icon;
-   placeholder in muted gray.

Example:

``` text
╭────────────────────────────╮
│ ◯  Cari teknisi...         │
╰────────────────────────────╯
```

------------------------------------------------------------------------

# 32. Filters

Use compact pill controls.

Example:

``` text
[ Agustus 2026 ▼ ]
[ Semua Status ▼ ]
[ Semua Teknisi ▼ ]
```

Active filters can use a subtle gold border.

Avoid oversized filter panels.

------------------------------------------------------------------------

# 33. Buttons

Primary:

``` text
background: gold
text: dark
```

Example:

``` text
[ + Tambah Teknisi ]
```

Secondary:

``` text
dark elevated surface
light text
```

Ghost:

``` text
transparent
muted text
```

Danger:

``` text
dark/red subtle treatment
```

Buttons should be compact, similar to the reference.

------------------------------------------------------------------------

# 34. Badges

Use pill badges.

Examples:

``` text
[ Aktif ]
[ Tidak Aktif ]
[ Lengkap ]
[ Menunggu ]
```

For rating:

``` text
[4]
[3]
[2]
[1]
```

Do not use oversized badges.

------------------------------------------------------------------------

# 35. Tables

Tables are a primary UI element.

Visual style:

``` text
card background
dark header
subtle row separators
small typography
compact row height
```

Do not use bright white table backgrounds.

Header text:

``` text
uppercase
small
muted
letter spacing
```

Data text:

``` text
white
medium weight
```

Secondary metadata:

``` text
muted gray
```

------------------------------------------------------------------------

# 36. Empty State

Match the same dark card style.

Example:

``` text
          ◯

Belum ada data penilaian

Belum terdapat penilaian pada periode ini.

[ Input Penilaian ]
```

Keep it compact.

Do not use huge illustrations.

------------------------------------------------------------------------

# 37. Loading State

Use skeleton loaders that match the dark surfaces.

Example:

``` text
████████████████
████████
████████████████████
```

Use subtle shimmer only.

------------------------------------------------------------------------

# 38. Modal

Modal:

``` text
background:
#211F23

border:
#302D34

radius:
18px
```

Example delete confirmation:

``` text
Hapus Teknisi?

Data teknisi ini akan dihapus.
Tindakan ini tidak dapat dibatalkan.

[Batal] [Hapus]
```

Keep dialogs compact.

------------------------------------------------------------------------

# 39. Toast

Use compact notification toast.

Success:

``` text
✓ Penilaian berhasil disimpan
```

Error:

``` text
× Gagal menyimpan data
```

Position:

``` text
bottom-right
```

------------------------------------------------------------------------

# 40. Spacing System

Use a consistent spacing scale:

``` text
4px
8px
12px
16px
20px
24px
32px
40px
48px
```

Primary dashboard gap:

``` text
16px
```

Card internal padding:

``` text
20–24px
```

Page padding:

``` text
28–36px
```

Avoid inconsistent arbitrary spacing.

------------------------------------------------------------------------

# 41. Grid System

Use CSS Grid.

Dashboard:

``` text
4-column KPI grid
```

Main content:

``` text
12-column grid
```

Example:

``` text
KPI:
3 / 3 / 3 / 3

Primary content:
8 / 4

Secondary:
4 / 4 / 4
```

The layout should resemble the modular composition of the reference.

------------------------------------------------------------------------

# 42. Visual Density

The reference is relatively dense.

ASENTRA should also be dense enough to feel like a professional
management tool.

Do not make:

-   enormous cards;
-   huge empty margins;
-   oversized buttons;
-   excessive vertical whitespace.

Every major area should communicate useful information.

------------------------------------------------------------------------

# 43. Decorative Elements

Decorative elements should be extremely subtle.

Allowed:

-   thin curved line;
-   soft abstract grid;
-   subtle architectural line pattern;
-   tiny gold accent;
-   faint geometric motif.

Avoid:

-   large construction photos;
-   stock worker images;
-   cartoon illustrations;
-   3D objects;
-   neon effects;
-   excessive glassmorphism.

The data and interface should remain the focus.

------------------------------------------------------------------------

# 44. Construction / Architecture Motif

Because ASENTRA is a construction/interior company, the interface may
include a subtle architectural motif.

Examples:

-   thin blueprint lines;
-   geometric grid;
-   corner marks;
-   measurement-inspired lines;
-   subtle structural lines.

Use opacity below approximately 5--8%.

The motif must never interfere with readability.

------------------------------------------------------------------------

# 45. Responsive Behavior

Desktop is primary.

At tablet:

-   sidebar collapses;
-   grid changes from 4 columns to 2;
-   tables remain scrollable.

At mobile:

-   sidebar becomes drawer;
-   cards become one column;
-   topbar simplifies;
-   tables scroll horizontally;
-   filters wrap;
-   action buttons stack when necessary.

Do not remove essential information.

------------------------------------------------------------------------

# 46. Accessibility

Ensure:

-   high contrast;
-   visible focus state;
-   keyboard navigation;
-   semantic HTML;
-   labels for inputs;
-   accessible buttons;
-   color is not the only status indicator.

Gold text on black must remain readable.

------------------------------------------------------------------------

# 47. Motion

Use restrained micro-interactions.

Allowed:

-   hover elevation;
-   subtle card transition;
-   sidebar slide;
-   modal fade;
-   button feedback;
-   toast animation.

Duration:

``` text
120–220ms
```

Do not use flashy animations.

------------------------------------------------------------------------

# 48. Design Tokens

Use centralized design tokens.

Example:

``` css
--bg-page: #242424;
--bg-app: #05030B;
--bg-surface: #18161B;
--bg-elevated: #211F23;
--border: #302D34;

--text-primary: #F5F4F7;
--text-secondary: #A5A1AA;
--text-muted: #77727D;

--gold: #D6B24C;
--gold-light: #E7CC76;
--gold-dark: #9F7D1F;

--success: #43D17A;
--warning: #F0B84B;
--danger: #F06464;
--info: #6D8CFF;
```

Do not scatter arbitrary color values throughout the application.

------------------------------------------------------------------------

# 49. Screen Priority

The following screens receive the highest visual attention:

## Priority 1

1.  Owner Dashboard
2.  Hasil Ranking
3.  Detail Perhitungan SAW

## Priority 2

4.  Admin Dashboard
5.  Input Penilaian
6.  Data Teknisi

## Priority 3

7.  Kriteria & Bobot
8.  Penilaian
9.  Riwayat
10. Report

------------------------------------------------------------------------

# 50. Consistency Rules

Every page must share:

-   same sidebar;
-   same topbar;
-   same typography;
-   same card system;
-   same button system;
-   same table system;
-   same spacing;
-   same border radius;
-   same status colors;
-   same interaction behavior.

Do not redesign components separately for every page.

------------------------------------------------------------------------

# 51. Important: Do Not Copy the Reference Content

The reference image is a **visual reference only**.

Do NOT use:

-   revenue;
-   orders;
-   customers;
-   product categories;
-   shopping cart concepts;
-   e-commerce terminology;
-   Apple product examples;
-   PayPal;
-   sales charts.

Replace them completely with ASENTRA SPK content.

Correct domain:

``` text
Teknisi
Kriteria
Penilaian
Kedisiplinan
Kualitas Hasil Kerja
Tanggung Jawab
SAW
Nilai Preferensi
Ranking
Laporan
```

------------------------------------------------------------------------

# 52. Important: Preserve PRD Business Logic

`DESIGN.md` controls visual design only.

The following must come from the PRD:

-   user roles;
-   permissions;
-   database;
-   criteria;
-   rating scale;
-   weights;
-   SAW formula;
-   business rules;
-   ranking;
-   acceptance criteria.

Do not modify business logic for visual reasons.

------------------------------------------------------------------------

# 53. Stitch Generation Instruction

When generating the UI from this DESIGN.md:

1.  Treat the supplied reference screenshot as the visual benchmark.
2.  Recreate its overall dark premium dashboard composition.
3.  Use ASENTRA's construction/management domain.
4.  Use Bahasa Indonesia for all UI labels.
5.  Use the ASENTRA gold accent instead of the reference's blue primary
    accent.
6.  Preserve the compact sidebar.
7.  Preserve the modular card layout.
8.  Preserve the dark rounded tables.
9.  Preserve the strong KPI typography.
10. Preserve the restrained use of color.
11. Make the ranking page the central decision-support experience.
12. Make the SAW detail page visually transparent and easy to
    understand.
13. Do not create a marketing website.
14. Do not create an e-commerce dashboard.
15. Do not use generic stock illustrations.
16. Do not overuse gradients.
17. Do not overuse glassmorphism.
18. Do not overuse gold.
19. Do not make the UI excessively spacious.
20. Do not sacrifice usability for visual decoration.

------------------------------------------------------------------------

# 54. Final Visual Target

The final result should feel like:

``` text
                 ASENTRA
        Premium Dark Dashboard

┌────┬──────────────────────────────────────────────┐
│    │ Search              Period     User          │
│ A  ├──────────────────────────────────────────────┤
│ S  │                                              │
│ E  │ Page Title                                   │
│ N  │ Supporting description                       │
│ T  │                                              │
│ R  │ ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐ │
│ A  │ │ KPI    │ │ KPI    │ │ KPI    │ │ KPI    │ │
│    │ └────────┘ └────────┘ └────────┘ └────────┘ │
│    │                                              │
│    │ ┌─────────────────────┐ ┌─────────────────┐ │
│    │ │ Main Decision Data  │ │ Summary         │ │
│    │ │                     │ │                 │ │
│    │ │ Ranking / Chart     │ │ Criteria        │ │
│    │ └─────────────────────┘ └─────────────────┘ │
│    │                                              │
│    │ ┌─────────────────────────────────────────┐ │
│    │ │ Data Table                              │ │
│    │ │                                         │ │
│    │ └─────────────────────────────────────────┘ │
└────┴──────────────────────────────────────────────┘
```

Overall feeling:

**Dark + premium + compact + precise + trustworthy + modern
enterprise.**

The application should look credible enough to be presented as a
finished software product during a thesis defense while remaining
practical enough for daily use by ASENTRA management.
