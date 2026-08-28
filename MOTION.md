# MOTION.md — ASENTRA SPK Motion Design System

> Acuan implementasi motion/animation untuk AI agent yang membangun dan memperbaiki UI/UX ASENTRA SPK.
>
> Karakter: **Smooth + Premium + Professional + Context-aware**.
>
> Prinsip tertinggi: **Usability + Accessibility + Performance > Motion Quality > Visual Fidelity**.

## 1. Tujuan

Motion bukan sekadar dekorasi. Setiap animasi harus membantu:
- menunjukkan perubahan state;
- memberi feedback atas aksi user;
- memperjelas hierarchy dan hubungan antar elemen;
- membantu orientasi saat navigasi;
- menjelaskan flow SAW;
- meningkatkan perceived quality tanpa membuat aplikasi terasa lambat.

Jangan menambahkan animasi hanya karena terlihat keren.

## 2. Prioritas

Jika motion bertentangan dengan pengalaman pengguna:
1. Usability
2. Accessibility
3. Performance
4. Motion quality
5. Visual fidelity

Aturan:
- Jangan menghambat klik/input.
- Jangan menambahkan artificial delay.
- Jangan membuat loading percentage palsu.
- Jangan mengulang animasi tanpa alasan.
- Jangan menganimasikan ratusan row satu per satu.
- Jika terasa lambat, pendekkan atau hilangkan.
- Hormati `prefers-reduced-motion`.
- Saat print, decorative motion OFF.

## 3. Motion Personality

ASENTRA harus terasa:
- smooth;
- sophisticated;
- restrained;
- responsive;
- modern;
- professional;
- data-oriented.

Bukan:
- game UI;
- flashy landing page;
- overly playful;
- penuh bounce/glow;
- UI yang membuat user menunggu.

## 4. Easing & Duration

Gunakan easing berdasarkan konteks, bukan satu easing untuk semuanya.

Default: premium ease-out / smooth cubic-bezier.
Micro interaction: fast ease-out.
Page transition: smooth cubic-bezier.
Modal/sidebar: subtle spring bila sesuai.

Guideline duration:
- Micro: **100–200ms**
- Normal UI: **200–350ms**
- Page/modal: **300–500ms**
- Stagger: **20–80ms** antar item

Semakin banyak data, semakin kecil stagger. Prioritaskan responsiveness.

## 5. Page Entrance & Transition

First visit menggunakan **Orchestrated First Load**:

```text
Background/gradient
    ↓
Sidebar
    ↓
Main content
    ↓
Header
    ↓
KPI cards
    ↓
Table/content
```

Sidebar masuk dari kiri, main content dari kanan, header fade, cards/table stagger.

Tidak ada splash screen atau delay buatan.

Browser reload menggunakan **Smart Reload**:
- entrance lebih singkat;
- state dipertahankan jika memungkinkan;
- tidak menunggu animasi.

Navigasi menggunakan **direction-aware transition**. Back/Forward membalik arah dan memulihkan state jika memungkinkan.

## 6. Sidebar & Responsive

Sidebar:
- expand/collapse: width + content fade + content morph + subtle spring;
- hover: subtle background preview + icon micro-motion;
- active: background morph, lebih kuat daripada hover;
- jangan bounce.

Mobile:
- desktop sidebar → mobile drawer;
- drawer slide dari kiri;
- backdrop smooth;
- layout morph secara halus.

## 7. Dashboard & KPI

KPI:
- staggered entrance;
- subtle lift;
- count-up untuk angka KPI;
- micro-motion saat interaction.

Hover hanya sedikit menaikkan card dan elevation.

## 8. Table & Data

Gunakan adaptive motion:

```text
0 data       → contextual empty state
1–10 rows    → full subtle stagger
11–50 rows   → reduced stagger
50+ rows     → minimal reveal
100+ rows    → no per-row animation
```

Row hover harus subtle. Jangan membuat tabel lambat.

## 9. Ranking

Setelah SAW selesai:

```text
SAW complete
    ↓
#1 reveal
    ↓
#2 reveal
    ↓
#3 reveal
    ↓
remaining ranking
```

Top 3 lebih expressive:
- #1: lift + highlight paling prominent;
- #2: medium;
- #3: subtle;
- #4+: normal.

Tidak ada confetti, game-like celebration, atau glow berlebihan.

## 10. Detail SAW

Detail SAW boleh lebih expressive karena motion membantu menjelaskan algoritma:

```text
Nilai Awal
    ↓
Normalisasi
    ↓
Bobot
    ↓
Kontribusi
    ↓
Vi
    ↓
Ranking
```

Gunakan sequential reveal, animated connector, interactive highlight, dan stagger.

Motion hanya memvisualisasikan hasil. **Jangan menghitung ulang SAW di view/JS.** Business logic tetap berada pada `SawEngine`/service.

## 11. Proses SAW

Flow:

```text
[ Proses SAW ]
      ↓
[ ◌ Memproses... ]
      ↓
loading/progress feedback
      ↓
[ ✓ SAW Selesai ]
      ↓
Top 3 reveal
      ↓
ranking lainnya
```

Button melakukan press + morph + loading + success.

Jika backend tidak menyediakan progress aktual, gunakan indeterminate loading. **Jangan membuat persentase palsu.**

## 12. Buttons, Forms & Validation

Button:
- press scale sekitar 97–98%;
- loading tanpa layout shift;
- success/error state.

Form focus:
- smooth border;
- subtle focus glow;
- icon micro-motion;
- tidak memakai floating label secara default.

Validation:

```text
invalid
 ↓
error highlight
 ↓
error message fade + slide
 ↓
1x subtle micro-shake
```

Shake kecil, hanya sekali, tidak looping.

Success save:

```text
Button success
 ↓
checkmark
 ↓
list transition
 ↓
new/updated row highlight
 ↓
toast
```

## 13. Delete

```text
Hapus
 ↓
Konfirmasi
 ↓
delete
 ↓
row fade-out
 ↓
row collapse
 ↓
layout reposition
 ↓
toast
```

Tidak ada efek dramatis.

## 14. Toast & Modal

Toast:
- slide dari kanan + subtle scale/fade;
- reverse saat close.

Modal:
- backdrop dim/blur;
- modal 96% → 100% + fade;
- close reverse;
- blur subtle;
- tidak menyebabkan layout shift.

## 15. Dropdown, Tooltip & Notification

Dropdown/select/popover:
- context-aware direction;
- fade + subtle scale;
- slide 4–8px;
- item stagger kecil.

Tooltip:
- fade + scale 98→100%;
- slide 4–8px;
- arah mengikuti posisi;
- hover delay kecil;
- accessible via focus bila relevan.

Notification panel:
- muncul dari arah icon;
- slide + fade;
- item stagger kecil;
- reverse saat close.

## 16. Icon Motion

Gunakan contextual motion, bukan semua icon bergerak.

Contoh:
- icon umum: micro-scale;
- chevron: rotate;
- arrow: directional movement;
- check: draw-in;
- loading: rotation;
- search: subtle interaction;
- delete: micro movement.

## 17. Ambient Background

Gunakan **subtle gradient movement** pada background dark.

Tidak menggunakan:
- particle system;
- floating shapes berlebihan;
- cursor-reactive background;
- dramatic parallax.

Ambient motion harus hampir tidak terasa.

## 18. State, Search, Filter & Tabs

Filter/periode:
- crossfade + subtle slide.

Sorting:
- row movement halus.

Status:
- badge/state transition + subtle highlight.

Search:
- row yang hilang fade/collapse;
- hasil baru subtle fade/slide;
- tetap terasa instant.

Tabs:
- active indicator morph;
- tab sebelah: slide + fade;
- tab tidak berurutan: crossfade.

## 19. Scroll, Expand & Back to Top

Scroll reveal:
- fade + subtle slide-up;
- internal stagger;
- hanya sekali per section;
- jangan menyebabkan layout jump.

Expand/collapse:
- slide + fade + internal stagger + subtle spring.

Back to Top:
- muncul setelah threshold;
- fade + slide + subtle scale;
- smooth scroll saat klik;
- hilang ketika tidak relevan.

## 20. Mobile Touch & Cursor

Mobile memakai **context-aware touch interaction**, bukan hover:
- button → press scale;
- card → subtle press;
- drawer → transition;
- row → highlight;
- toggle → slide/spring;
- destructive action → loading/feedback.

Desktop cursor interaction hanya pada elemen yang cocok:
- CTA/button;
- interactive card tertentu;
- ranking card tertentu.

Hindari global tilt, magnetic effect, dan cursor-following background.

## 21. Responsive & Data-Adaptive Motion

Responsive:
- layout morph;
- grid/card menyesuaikan;
- sidebar → mobile drawer.

Motion harus menyesuaikan jumlah data dan kemampuan device. Jangan membuat 100+ item dianimasikan satu per satu.

## 22. Empty State & Charts

Empty state contextual:
- no data → icon fade/scale;
- no search result → search micro-motion;
- ranking belum diproses → process indicator;
- success → checkmark draw-in.

Jika chart ditambahkan:
- bar → grow dari bawah;
- line → draw kiri ke kanan;
- donut → sweep;
- data point → scale + fade.

Chart animation hanya first reveal.

## 23. Pagination & Breadcrumb

Pagination:
- Next: old data keluar kiri, new data masuk kanan;
- Previous: kebalikannya;
- dataset kecil boleh crossfade;
- indicator ikut transition.

Breadcrumb:
- fade + subtle slide;
- active state morph;
- intensitas sangat rendah.

## 24. Login, Logout & Workspace

Login:
```text
background
→ branding
→ heading
→ form
→ button
```

Submit:
- press;
- loading;
- success.

Login → Dashboard:
- **seamless morph**;
- background/branding terasa continuous;
- login fade/scale out;
- dashboard orchestrated entrance.

Logout:
- dashboard fade out + direction-aware transition;
- kembali ke login;
- tanpa artificial delay.

Admin ↔ Owner workspace:
- sidebar morph;
- main content transition;
- workspace identity transition.
Gunakan hanya saat konteks workspace benar-benar berubah.

## 25. Report / Print

Ranking → Laporan:
- button feedback;
- document transition;
- report subtle scale/fade;
- A4 document reveal.

Laporan lebih restrained daripada dashboard.

Print:
- decorative motion OFF;
- hover OFF;
- transition OFF.

## 26. Theme

Jika Dark/Light Mode ditambahkan:
- gunakan crossfade;
- jangan gunakan radial wipe/dramatic theme animation.

## 27. State Preservation

Pertahankan state relevan jika memungkinkan:

```text
Data Teknisi → search + filter + scroll
Penilaian    → search + periode + filter
Ranking      → periode
Riwayat      → periode
Detail SAW   → section position
Form         → input state jika aman
```

Jangan menyimpan data sensitif secara tidak aman.

## 28. Depth

Layer hierarchy:

```text
Background
    ↓
Sidebar / Main
    ↓
Card
    ↓
Dropdown / Popover
    ↓
Modal
```

Gunakan soft elevation. Saat interaction, elevation meningkat sedikit.

## 29. Performance

Prefer:
- CSS `transform`;
- `opacity`;
- compositor-friendly properties.

Hindari animasi layout properties secara intensif bila menyebabkan jank.

Gunakan Intersection Observer secara efisien. Animation instance/listener harus dibersihkan sesuai lifecycle.

Jangan menambah library animation besar tanpa alasan.

## 30. Accessibility / Reduced Motion

Wajib mendukung:

```css
@media (prefers-reduced-motion: reduce) {
  /* disable/reduce decorative motion */
}
```

Reduced motion:
- matikan/reduce page sliding, large movement, stagger, ambient animation, dramatic spring, decorative scroll reveal, cursor effects;
- pertahankan focus, success/error, dan essential state feedback.

Motion tidak boleh menjadi satu-satunya cara menyampaikan informasi.

Jangan gunakan flashing/strobing.

## 31. Motion Tokens

Gunakan satu sumber token, misalnya:

```css
--motion-fast: 150ms;
--motion-normal: 250ms;
--motion-slow: 400ms;
--ease-premium: ...;
--ease-out: ...;
```

Nilai dapat disesuaikan setelah implementasi, tetapi hindari durasi acak di banyak file.

## 32. Motion Intensity

Relative guidance:

```text
Ambient background → 10%
Sidebar            → 25%
Table              → 25%
Form               → 30%
Modal              → 40%
Dashboard          → 40%
Login              → 50%
SAW Processing     → 50%
Ranking            → 50%
Top 3              → 60%
Detail SAW         → 60%
```

Ini bukan literal CSS value. Gunakan untuk menentukan hierarchy.

## 33. Architecture Contract

Motion harus terpisah dari business logic:

```text
Backend / SawEngine
        ↓
Persisted Result
        ↓
Controller
        ↓
View
        ↓
UI State
        ↓
Motion Layer
```

Motion hanya merespons state seperti:
`loading`, `success`, `error`, `empty`, `active`, `expanded`, `selected`, `hovered`.

Jangan mengubah business rule atau SAW algorithm demi animation.

## 34. AI Agent Contract

Sebelum mengubah UI, agent wajib membaca:
1. `PRD.md`
2. `DESIGN.md`
3. `MOTION.md`
4. `AGENTS.md`

Agent wajib:
- mengikuti design tokens/component system;
- tidak mengubah business logic untuk kebutuhan visual;
- tidak mengubah SAW mathematics;
- tidak menambah dependency besar tanpa alasan;
- memprioritaskan transform/opacity;
- menyesuaikan motion dengan jumlah data;
- menghormati reduced motion;
- menjaga keyboard/focus accessibility;
- tidak membuat fake progress;
- tidak membuat artificial delay;
- menjaga print report bebas decorative motion;
- menjalankan test relevan setelah perubahan.

Jika sebuah motion tidak memiliki alasan UX yang jelas, **jangan tambahkan**.

## 35. Definition of Done

- [ ] Page transition konsisten.
- [ ] Sidebar expand/collapse smooth.
- [ ] KPI entrance/count-up bekerja.
- [ ] Table motion adaptive.
- [ ] Ranking Top 3 memiliki emphasis.
- [ ] Detail SAW memiliki sequential flow.
- [ ] Proses SAW memiliki loading/success tanpa fake progress.
- [ ] Button memiliki press/loading/success/error.
- [ ] Toast slide + scale.
- [ ] Modal scale + fade + backdrop.
- [ ] Dropdown/tooltip context-aware.
- [ ] Form focus/validation memiliki feedback.
- [ ] Delete fade + collapse.
- [ ] Empty state contextual.
- [ ] Responsive/mobile touch bekerja.
- [ ] Reduced motion didukung.
- [ ] Print bebas decorative motion.
- [ ] Tidak ada regression business logic.
- [ ] Motion tetap performant pada dataset besar.

## Final Principle

> **ASENTRA harus terasa hidup, bukan bergerak tanpa alasan.**

Motion harus membuat user merasa sistem responsif, perubahan state jelas, navigasi natural, hasil SAW mudah dipahami, dan interface premium — tanpa mengorbankan keseriusan aplikasi bisnis.
