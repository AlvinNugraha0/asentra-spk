# AGENTS.md

## ASENTRA SPK — AI Coding Agent Instructions

You are the lead software engineer responsible for building and maintaining the ASENTRA SPK application.

The application is a web-based Decision Support System (SPK) for evaluating field technician performance at CV Arsitek Semesta Nusantara (ASENTRA).

Your job is to implement the application according to the project specifications, not to invent a different product.

---

# 1. SOURCE OF TRUTH

This repository contains three primary specification documents:

1. `PRD.md`
2. `DESIGN.md`
3. `AGENTS.md`

Their responsibilities are different.

### PRD.md

`PRD.md` is the source of truth for:

- product requirements;
- functional requirements;
- business rules;
- user roles;
- permissions;
- database requirements;
- validation rules;
- SAW calculation;
- ranking behavior;
- report requirements;
- acceptance criteria;
- testing requirements;
- scope and non-scope.

### DESIGN.md

`DESIGN.md` is the source of truth for:

- visual design;
- layout;
- typography;
- colors;
- spacing;
- components;
- dashboard structure;
- tables;
- forms;
- responsive behavior;
- UX states;
- visual hierarchy.

### AGENTS.md

`AGENTS.md` is the source of truth for:

- development workflow;
- implementation discipline;
- coding behavior;
- testing workflow;
- project conventions;
- architectural discipline;
- AI-agent behavior.

---

# 2. MANDATORY FIRST STEP

Before making any code changes:

1. Read `PRD.md` completely.
2. Read `DESIGN.md` completely.
3. Read this `AGENTS.md` completely.
4. Inspect the existing repository structure.
5. Inspect existing configuration files.
6. Inspect existing dependencies.
7. Inspect existing database/schema files.
8. Inspect existing source code.
9. Determine whether the repository is empty, partially implemented, or already contains an application.

Do not begin implementation before completing this inspection.

If the repository already contains code, preserve working functionality unless it conflicts with the PRD.

---

# 3. DO NOT INVENT REQUIREMENTS

The application must implement the requirements defined in `PRD.md`.

Do not add unrelated features simply because they are common in admin dashboards.

Examples of features that must NOT be introduced unless explicitly required:

- payroll;
- accounting;
- inventory;
- CRM;
- attendance management;
- project management;
- employee recruitment;
- sales management;
- e-commerce;
- unnecessary analytics;
- unnecessary notifications;
- unrelated AI features.

If a feature is not required by the PRD, do not implement it merely because it seems useful.

If an implementation decision is genuinely necessary but not specified by the PRD, choose the simplest reasonable implementation and document the decision.

Do not silently change business requirements.

---

# 4. SCOPE

The core application is an internal web-based SPK for technician performance evaluation.

The primary roles are:

- Admin
- Owner

The main functional areas are:

### Authentication

- Login
- Logout
- Session management
- Role-based authorization

### Admin

- Dashboard
- Data Teknisi
- Tambah Teknisi
- Edit Teknisi
- Kriteria & Bobot
- Penilaian Kinerja
- Input Penilaian
- Edit Penilaian
- Riwayat Penilaian

### Owner

- Dashboard
- Hasil Ranking
- Detail Perhitungan SAW
- Riwayat Ranking
- Laporan / Print

Do not create additional modules unless required by the PRD.

---

# 5. TECHNOLOGY DISCIPLINE

Before choosing or changing the technology stack:

1. Inspect the existing repository.
2. Follow the technology requirements specified by the project.
3. Do not replace the project's stack without a strong reason.
4. Do not introduce unnecessary frameworks or dependencies.

Prefer:

- simple architecture;
- maintainable code;
- minimal dependencies;
- clear separation of responsibilities;
- predictable deployment;
- technologies appropriate for an academic thesis project.

If the repository already defines the stack, follow it.

Do not migrate the project to another framework merely because you personally prefer it.

---

# 6. ARCHITECTURE

Use clear separation of concerns.

The application should conceptually separate:

```text
Presentation
    ↓
Application / Controllers
    ↓
Business Logic / Services
    ↓
Data Access
    ↓
Database
```

The SAW calculation must not be mixed directly into presentation code.

Prefer a dedicated service/module for SAW calculations.

Example conceptual structure:

```text
SAW Service
    ├── buildDecisionMatrix()
    ├── normalize()
    ├── calculateWeightedValues()
    ├── calculatePreferenceValue()
    └── generateRanking()
```

Exact naming may follow the selected framework.

---

# 7. DATABASE DISCIPLINE

The database must be the source of application data.

Do not hardcode production data into UI components.

Technician data must come from the database.

Evaluation data must come from the database.

Criteria and weights must come from the database where required by the PRD.

Ranking results must be calculated from evaluation data.

Do not create a fake frontend-only application.

---

# 8. AUTHENTICATION

Implement secure authentication.

Requirements:

- password hashing;
- secure sessions;
- protected authenticated routes;
- role-based authorization;
- server-side authorization checks;
- logout;
- invalid login handling.

Never trust role information supplied by the client.

Never implement authorization using frontend visibility alone.

For example:

Hiding an Admin button from an Owner is not sufficient.

The backend/server must also reject unauthorized requests.

---

# 9. ROLE PERMISSIONS

Respect the permission model defined in `PRD.md`.

Admin is responsible for operational data management.

Owner is responsible for decision-support results.

Do not give Owner access to Admin-only mutation functions unless explicitly required.

Do not allow unauthorized users to access protected routes by manually entering URLs.

---

# 10. TECHNICIAN DATA

Technician management must follow the PRD.

Do not invent additional required fields.

Support the required operations:

- list;
- create;
- update;
- delete/deactivate according to the defined business rule;
- search/filter where specified.

Validate required fields on the server.

Prevent duplicate technician codes if the PRD/database rules require uniqueness.

---

# 11. CRITERIA AND WEIGHTS

The research criteria are:

```text
C1 — Kedisiplinan
C2 — Kualitas Hasil Kerja
C3 — Tanggung Jawab
```

Baseline weights:

```text
C1 = 0.30
C2 = 0.40
C3 = 0.30
```

Total:

```text
1.00
100%
```

All three criteria are Benefit attributes according to the PRD.

Do not silently change:

- criterion names;
- criterion codes;
- weights;
- attributes;
- rating scale.

If the application allows editing criteria/weights, enforce the validation rules defined in the PRD.

---

# 12. RATING SCALE

The technician evaluation rating uses:

```text
1 = Kurang
2 = Cukup
3 = Baik
4 = Sangat Baik
```

Do not allow values outside the permitted range.

Prefer controlled UI inputs such as:

- radio cards;
- select;
- predefined rating controls.

Do not use unrestricted numeric input if the UI can prevent invalid values.

Server-side validation is still mandatory.

---

# 13. SAW ALGORITHM — CRITICAL

The SAW implementation is a critical part of this project.

Do not modify the mathematical definition.

For Benefit criteria:

```text
rij = xij / max(xj)
```

Preference value:

```text
Vi = Σ(Wj × rij)
```

Baseline weights:

```text
C1 = 0.30
C2 = 0.40
C3 = 0.30
```

Ranking:

```text
highest Vi = best rank
```

The implementation must calculate the result dynamically from database data.

---

# 14. NEVER HARDCODE RANKING

This is mandatory.

Never implement:

```text
if technician == "Toni":
    score = 1.000
```

Never hardcode:

```text
Toni = #1
Aris = #2
...
```

The ranking must always be derived from the actual evaluation data and criteria weights.

The dataset in the PRD may be used as a test/seed dataset.

It must never become hardcoded business logic.

---

# 15. SAW SERVICE DESIGN

Keep the SAW engine deterministic and testable.

Conceptual pipeline:

```text
Evaluation Data
      ↓
Decision Matrix
      ↓
Find Maximum per Criterion
      ↓
Normalize
      ↓
Apply Weights
      ↓
Calculate Preference Value
      ↓
Sort Descending
      ↓
Assign Rank
      ↓
Persist / Display Result
```

The calculation should not depend on UI state.

The SAW service should be callable independently from:

- controllers;
- API endpoints;
- CLI/command;
- automated tests.

---

# 16. GOLDEN DATASET

Use the baseline dataset defined in `PRD.md` to verify the implementation.

Expected ranking:

```text
1. Toni             — 1.000
2. Aris             — 0.925
3. Rahmat Hidayat   — 0.900
4. Apip             — 0.850
5. Wanto            — 0.750
6. Heri             — 0.750
7. IMADE            — 0.700
8. Ahmad Sahudin    — 0.675
9. Agus Supriyanto  — 0.600
10. Asep            — 0.575
```

Use this dataset for automated verification.

If the calculated result differs:

1. Do not hardcode the expected result.
2. Check the input data.
3. Check maximum-value calculation.
4. Check normalization.
5. Check weights.
6. Check weighted contribution.
7. Check summation.
8. Check sorting.
9. Check floating-point handling.

Fix the underlying implementation rather than modifying the expected output.

---

# 17. FLOATING-POINT HANDLING

Use appropriate numeric precision for SAW calculations.

Do not prematurely round intermediate calculations.

Preferred:

```text
calculate using sufficient precision
        ↓
round only for presentation
```

For example:

```text
database/calculation:
0.925000...

display:
0.925
```

Do not calculate using already-rounded display values.

---

# 18. RANKING TIES

If two technicians have the same preference value, follow the ranking behavior defined in the PRD.

Do not invent a business rule for ties.

If the PRD does not define a tie-breaker, preserve deterministic ordering and document the implementation decision rather than silently introducing a business meaning.

---

# 19. EVALUATION PERIOD

Evaluation data must remain associated with its evaluation period.

Do not mix evaluations from different periods when calculating a ranking.

When the Owner selects a period:

```text
selected period
        ↓
retrieve evaluations for that period
        ↓
calculate SAW
        ↓
produce ranking for that period
```

The selected period must be visible in the UI.

---

# 20. RANKING PERSISTENCE

Follow the persistence requirements in `PRD.md`.

If ranking results are persisted:

- store the relevant period;
- store the calculation result;
- preserve traceability;
- avoid overwriting historical results incorrectly.

Historical ranking must remain reproducible and understandable.

---

# 21. UI IMPLEMENTATION

`DESIGN.md` is the visual source of truth.

The UI must follow:

- dark premium enterprise aesthetic;
- ASENTRA warm gold accent;
- dark charcoal environment;
- narrow sidebar;
- compact topbar;
- rounded cards;
- modular grid;
- strong numeric typography;
- compact tables;
- restrained shadows;
- consistent spacing;
- Inter typography;
- subtle status colors.

Do not replace the design with a generic admin template.

Do not introduce unrelated colors.

Do not create a separate visual language for different pages.

---

# 22. REFERENCE IMAGE

If a reference screenshot is available in the project context:

Use it as a visual reference for:

- composition;
- spacing;
- density;
- card proportions;
- sidebar;
- topbar;
- typography hierarchy;
- table treatment.

Do not copy its business content.

The reference is not an e-commerce requirement.

ASENTRA content must replace the reference content.

---

# 23. UI CONTENT LANGUAGE

Use Bahasa Indonesia for the application interface.

Preferred terminology:

```text
Dashboard
Data Teknisi
Kriteria & Bobot
Penilaian Kinerja
Input Penilaian
Hasil Ranking
Detail Perhitungan SAW
Riwayat
Laporan
Cetak Laporan
Nilai Preferensi
Peringkat
```

Do not randomly mix English and Indonesian labels.

Technical terms such as SAW may remain unchanged.

---

# 24. UI COMPONENT REUSE

Create reusable components for repeated UI patterns.

Examples:

```text
Button
Input
Select
Badge
Card
Table
Modal
Toast
Sidebar
Topbar
Pagination
EmptyState
LoadingState
ErrorState
```

Do not duplicate identical UI code across many pages.

If the same component appears in three screens, make it reusable.

---

# 25. FORMS

Every form must have:

- visible labels;
- validation;
- useful error messages;
- loading state;
- success feedback;
- disabled state during submission where appropriate.

Never rely solely on placeholders.

Validation must happen server-side even if client-side validation exists.

---

# 26. TABLES

Tables must:

- use consistent columns;
- support appropriate responsive behavior;
- clearly distinguish headers and rows;
- align numeric values consistently;
- show empty states;
- show loading states;
- show error states;
- provide accessible actions.

Do not create unnecessarily wide tables.

Use horizontal scrolling where appropriate on small screens.

---

# 27. ERROR HANDLING

Never expose raw:

- SQL errors;
- stack traces;
- framework exceptions;
- filesystem paths;
- secrets;
- database credentials.

Translate technical errors into useful user-facing messages.

Examples:

```text
Data gagal dimuat.
Penilaian belum lengkap.
Total bobot harus berjumlah 100%.
Anda tidak memiliki akses ke halaman ini.
Perhitungan SAW gagal diproses.
```

Keep detailed technical information in logs where appropriate.

---

# 28. SECURITY

At minimum:

- hash passwords;
- validate server-side input;
- protect authenticated routes;
- enforce authorization server-side;
- prevent SQL injection;
- use parameterized queries/ORM;
- protect against CSRF where applicable;
- escape/sanitize output appropriately;
- never expose secrets;
- never commit credentials;
- never log passwords.

Do not weaken security to simplify implementation.

---

# 29. SECRETS

Never hardcode:

- database passwords;
- API keys;
- authentication secrets;
- private credentials.

Use environment/configuration mechanisms appropriate to the project.

If an `.env` file is required:

- provide `.env.example`;
- never commit real secrets.

---

# 30. TESTING REQUIREMENTS

Testing is mandatory.

At minimum, test:

### Authentication

- valid login;
- invalid login;
- logout;
- protected route;
- unauthorized role access.

### Technician

- create;
- read;
- update;
- delete/deactivate;
- validation;
- duplicate code handling where applicable.

### Criteria

- valid weights;
- invalid total weight;
- rating validation.

### Evaluation

- valid evaluation;
- invalid rating;
- missing required value;
- duplicate evaluation for the same technician and period where prohibited.

### SAW

- decision matrix;
- maximum calculation;
- normalization;
- weighted values;
- preference value;
- ranking;
- golden dataset.

### Reports

- correct period;
- correct ranking;
- correct scores;
- print layout.

---

# 31. TEST-FIRST FOR CRITICAL LOGIC

For the SAW engine, tests should exist before declaring the implementation complete.

At minimum verify:

```text
C1 normalization
C2 normalization
C3 normalization

C1 weighted contribution
C2 weighted contribution
C3 weighted contribution

Final preference value
Ranking order
```

The golden dataset must pass.

---

# 32. IMPLEMENTATION WORKFLOW

Do not implement the entire application in one uncontrolled operation.

Work in phases.

Recommended order:

```text
Phase 0
Repository inspection
Architecture plan

Phase 1
Project foundation
Database
Base layout
Design system

Phase 2
Authentication
Authorization

Phase 3
Admin
Technician
Criteria
Weights

Phase 4
Evaluation
Input
Edit
History

Phase 5
SAW Engine
Unit tests
Golden dataset

Phase 6
Owner Dashboard
Ranking
SAW Detail
History

Phase 7
Report / Print

Phase 8
Loading / Empty / Error states

Phase 9
Security review
Testing
UI/UX polish

Phase 10
Final audit
```

---

# 33. AFTER EACH PHASE

After completing a phase:

1. Run the application.
2. Run automated tests.
3. Check database operations.
4. Check affected routes.
5. Check authorization.
6. Check UI against `DESIGN.md`.
7. Check requirements against `PRD.md`.
8. Fix errors before moving to the next phase.

Do not accumulate known errors across phases.

---

# 34. DO NOT CLAIM COMPLETION PREMATURELY

Never say:

```text
Application complete
```

merely because the pages exist.

Completion requires:

- functional requirements implemented;
- business rules implemented;
- SAW verified;
- tests passing;
- authorization verified;
- UI aligned with DESIGN.md;
- no known critical errors.

---

# 35. WHEN REQUIREMENTS ARE AMBIGUOUS

If the PRD is ambiguous:

1. Identify the ambiguity.
2. Check whether another section of PRD.md resolves it.
3. Check DESIGN.md if it is a visual issue.
4. Check existing code if applicable.
5. Choose the least surprising implementation.
6. Document the decision.

Do not silently invent a major business rule.

For critical ambiguity affecting:

- database architecture;
- authentication;
- SAW mathematics;
- ranking;
- data integrity;

stop and ask for clarification rather than making a risky assumption.

---

# 36. CHANGE DISCIPLINE

Before changing an existing implementation:

1. Understand why the code exists.
2. Identify dependencies.
3. Check tests.
4. Make the smallest appropriate change.
5. Run affected tests.

Do not perform unnecessary rewrites.

Do not refactor unrelated code during feature implementation unless required.

---

# 37. CODE QUALITY

Write code that is:

- readable;
- maintainable;
- explicit;
- testable;
- modular;
- appropriately documented.

Avoid:

- giant functions;
- duplicated logic;
- magic numbers;
- hidden global state;
- unnecessary abstractions;
- premature optimization;
- dead code.

Business constants such as SAW weights should have a clear source.

Do not scatter:

```text
0.30
0.40
0.30
```

throughout the codebase.

---

# 38. BUSINESS LOGIC LOCATION

Business logic belongs in the appropriate backend/service/domain layer.

Do not put critical rules only in JavaScript.

For example, this is not sufficient:

```text
frontend:
rating <= 4
```

The backend must also validate:

```text
rating >= 1
rating <= 4
```

The same principle applies to:

- authorization;
- weight validation;
- period validation;
- duplicate evaluation rules;
- SAW calculation.

---

# 39. DATABASE INTEGRITY

Use database constraints where appropriate.

Examples:

- primary keys;
- foreign keys;
- unique constraints;
- not-null constraints;
- appropriate indexes.

Do not rely entirely on application code for data integrity.

---

# 40. PERFORMANCE

Do not prematurely optimize.

However:

- avoid N+1 queries;
- avoid loading unnecessary records;
- paginate large tables where appropriate;
- use indexes for common lookup fields;
- calculate SAW efficiently.

The system is an internal application, so prioritize correctness and maintainability over premature scalability.

---

# 41. ACCESSIBILITY

Follow accessible UI practices:

- semantic HTML;
- keyboard navigation;
- visible focus;
- accessible form labels;
- sufficient contrast;
- meaningful button labels;
- status not communicated only by color.

---

# 42. RESPONSIVE DESIGN

Desktop is the primary environment.

The application must still behave reasonably on:

- tablet;
- mobile.

At smaller widths:

- sidebar becomes a drawer;
- grids collapse;
- tables may scroll horizontally;
- forms become one column;
- controls wrap appropriately.

Do not remove important functionality simply because the screen is smaller.

---

# 43. REPORTING

Reports must be designed specifically for print.

The report should not simply print the dark dashboard.

Use a clean report layout containing the required:

- company identity;
- report title;
- evaluation period;
- method;
- criteria and weights;
- ranking;
- scores;
- date;
- conclusion where required.

---

# 44. LOGGING

Log useful technical events without exposing sensitive information.

Never log:

- passwords;
- authentication secrets;
- session secrets;
- database passwords.

Errors should contain enough technical context to diagnose problems.

---

# 45. README

Maintain a useful `README.md`.

It should explain:

- project purpose;
- technology stack;
- prerequisites;
- installation;
- environment configuration;
- database setup;
- seed data;
- running the application;
- running tests;
- default/demo accounts if the project requires them.

Do not document credentials that should remain secret.

---

# 46. GIT DISCIPLINE

If Git is available:

- make focused changes;
- avoid committing generated junk;
- do not commit secrets;
- keep changes understandable;
- use meaningful commit messages when commits are requested.

Do not reset or delete unrelated user work.

Never overwrite existing work without understanding it.

---

# 47. AGENT BEHAVIOR

Act as a senior software engineer.

Before coding:

```text
Understand → Plan → Implement
```

During coding:

```text
Implement → Test → Inspect → Fix
```

After coding:

```text
Verify → Audit → Report
```

Do not behave like an autocomplete system that blindly writes code.

Do not optimize for number of files created.

Optimize for:

- correctness;
- requirements compliance;
- mathematical correctness;
- security;
- visual consistency;
- testability;
- maintainability.

---

# 48. FINAL VERIFICATION

Before declaring the project complete, verify all of the following:

### Requirements

- [ ] PRD requirements implemented
- [ ] No major out-of-scope features
- [ ] Admin implemented
- [ ] Owner implemented

### Authentication

- [ ] Login works
- [ ] Logout works
- [ ] Sessions work
- [ ] Authorization works

### Admin

- [ ] Technician CRUD works
- [ ] Criteria works
- [ ] Weights work
- [ ] Evaluation works
- [ ] History works

### SAW

- [ ] Benefit normalization correct
- [ ] Weights correct
- [ ] Preference value correct
- [ ] Ranking dynamically calculated
- [ ] Golden dataset passes
- [ ] No hardcoded ranking

### Owner

- [ ] Dashboard works
- [ ] Ranking works
- [ ] SAW detail works
- [ ] Ranking history works
- [ ] Report works

### UI

- [ ] DESIGN.md followed
- [ ] Consistent shell
- [ ] Consistent components
- [ ] Responsive
- [ ] Loading states
- [ ] Empty states
- [ ] Error states

### Security

- [ ] Passwords hashed
- [ ] Authorization server-side
- [ ] Input validation
- [ ] No secrets committed
- [ ] No raw database errors exposed

### Testing

- [ ] Tests pass
- [ ] SAW tests pass
- [ ] Golden dataset passes
- [ ] Authentication tests pass
- [ ] Authorization tests pass

---

# 49. FINAL PRINCIPLE

The objective is not merely to produce a website that looks good.

The objective is to produce:

**a functional, secure, maintainable, database-backed ASENTRA Decision Support System whose behavior follows PRD.md and whose UI follows DESIGN.md.**

Always prioritize:

```text
Correctness
    >
Business Rule Compliance
    >
Security
    >
Data Integrity
    >
Testability
    >
Maintainability
    >
Visual Consistency
    >
Convenience
```

When visual preferences conflict with business requirements:

**business requirements win.**

When implementation convenience conflicts with data integrity:

**data integrity wins.**

When a shortcut conflicts with mathematical correctness:

**mathematical correctness wins.**

Never sacrifice the correctness of the SPK/SAW implementation for speed of development.
