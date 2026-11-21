# AUDIT FINDINGS INDEPENDENT VERIFICATION REPORT
**Project:** SMART-CBT | **Date:** November 2026 | **Auditor & Lead Architect:** Principal Software Architect  
**Purpose:** Rigorous empirical code and database verification of each audit finding prior to refactoring.

---

## 1. VERIFICATION MATRIX & CLASSIFICATION

| Finding ID | Audit Claim | Verified Status | Root Cause & Code Evidence | Technical Decision / Action |
|---|---|---|---|---|
| **ARCH-01** | Fat controllers, complete absence of Service classes | **VERIFIED** | Directory `app/Services` is completely empty. `SiswaExamController.php` (368 lines) directly executes database locks, scoring algorithms, session verification, and deadline math. | Extract domain services (`ExamLifecycleService`, `ExamAttemptService`, `AnswerService`, `ScoringService`, `TokenService`). |
| **ARCH-02** | Duplicate route definitions for REST API | **VERIFIED** | Lines 426–435 in `routes/web.php` register `/api/v1/*` identically to `routes/api.php` lines 12–30, applying `web` middleware group unnecessarily. | Remove duplicate registration from `web.php`; keep stateless API in `routes/api.php`. |
| **ARCH-03** | HTTP GET index endpoint mutates DB state | **VERIFIED** | `QuestionBankController.php#L18-19` invokes `$this->syncExamQuestionsIntoBanks($guruId)` on every GET `/guru/bank-soal`, running `firstOrCreate` write queries on read operations. | Decouple automatic sync into a manual teacher button or an event listener upon exam creation. |
| **DB-01** | Missing question snapshot table; cascade delete wipes history | **VERIFIED** | `answers.question_id` has `CASCADE ON DELETE` in `2026_08_23_053624_create_answers_table.php#L21-22`. `QuestionController::destroy` deletes questions without checking if attempts exist, permanently deleting student answers. | Restrict question deletion if attempts exist; lock questions against modification once attempts begin. |
| **DB-02** | Missing critical indexes on high-frequency columns | **VERIFIED** | `SHOW INDEX FROM exam_attempts` proves 0 indexes on `status`, `started_at`, `submitted_at`, `nilai_akhir`. `SHOW INDEX FROM exams` proves 0 indexes on `status`, `tanggal_mulai`, `token_aktif`. | Add composite indexes via safe backward-compatible migration. |
| **DB-03** | Single-class exam limitation (`exams.kelas_id`) | **VERIFIED** | `exams.kelas_id` is a single foreign key. Teachers must duplicate exams 3x to give UTS to 7A, 7B, and 7C. | Keep backward-compatible `kelas_id` as default, introduce `exam_classes` pivot table for multi-class assignment. |
| **DB-04** | Multiple attempts blocked by DB unique constraint | **VERIFIED** | Migration `2026_08_23_053604_create_exam_attempts_table.php#L44` enforces `$table->unique(['exam_id', 'siswa_id'])`. Retakes/remedials fail with duplicate key SQL error. | Modify unique index to `['exam_id', 'siswa_id', 'attempt_number']`. |
| **LOGIC-01**| Essay scoring broken & grading interface missing | **VERIFIED** | `SiswaExamController.php#L354-L365` hardcodes 0 points for non-PG questions and includes essay weight in `nilai_akhir`. `ResultController.php` only has `index` and `show` with 0 grading actions. | Set attempt status to `needs_grading` when essays exist; build essay grading interface in `ResultController` with recalculation service. |
| **LOGIC-02**| Randomization unstable under question bank changes | **VERIFIED** | `SiswaExamController.php#L157-L169` shuffles questions dynamically on every request via `crc32($attempt->id)` over `exams.questions`. Modifying question order changes the sequence for students mid-test. | Persist deterministic question order array in `exam_attempts.question_order` upon attempt start. |
| **LOGIC-03**| Start race condition creates duplicate attempts | **VERIFIED** | `SiswaExamController.php#L118-L127` executes `attempt = attempt()` followed by `ExamAttempt::create()` outside of a transaction or lock. Concurrent start clicks trigger 500 error. | Wrap attempt start in transaction with `firstOrCreate` and lock. |
| **PERF-01** | N+1 queries in autosave and final submission | **VERIFIED** | `SiswaExamController.php#L224-228` and `L359-363` loop through answers calling `Answer::updateOrCreate()` one by one, executing ~100 queries per student submission. | Replace updateOrCreate loops with `Answer::upsert(...)`, reducing queries to 1 per batch/submission. |
| **PERF-02** | In-memory pagination in GuruMonitoringController | **VERIFIED** | `GuruMonitoringController.php#L69-78` loads all attempt models with relations into PHP memory via `->get()`, then calls `new LengthAwarePaginator($rows->forPage(...))`. | Replace with query-builder pagination `$attemptsQuery->paginate(20)`. |
| **PERF-03** | Database session driver contention under load | **VERIFIED** | `.env` has `SESSION_DRIVER=database`, forcing all student requests to write to the MySQL `sessions` table. Docker environment has Redis. | Document production Redis switch; optimize database session cleanup. |
| **SEC-01**  | Hardcoded fallback secret in ServiceAuthMiddleware | **VERIFIED** | `ServiceAuthMiddleware.php#L17` contains `'cbt_secret_token_almadinah_2026'` as default fallback if `.env` key is missing. | Remove fallback; abort with 500 if server token is unconfigured. |
| **SEC-02**  | API token accepted in URL query parameters | **VERIFIED** | `ServiceAuthMiddleware.php#L19` checks `$request->query('api_key')`, exposing secrets in web server access logs. | Remove `$request->query('api_key')`; enforce Authorization Bearer or X-API-Key header only. |
| **SEC-03**  | Missing rate limiting on external REST API | **VERIFIED** | `routes/api.php` has no rate limiting middleware applied. | Apply `throttle:api` or `throttle:60,1` to external API routes. |
| **INTEG-01**| Database desynchronization between SMART+ and CBT | **VERIFIED** | Live DB query confirms `smart_cbt.siswas` has 81 records, while `smart.siswa` has 83 records. `smart.siswa` lacks `nis` column. | Create Artisan command `cbt:sync-master-data` mapping `nisn` to sync students and classes idempotently. |

---

## 2. DISCREPANCY & AUDIT REFINEMENTS

1. **Option Normalization (2NF) Strategy:**
   - *Audit Suggestion:* Break `opsi_a`..`opsi_e` into a separate `question_options` table immediately.
   - *Architectural Evaluation & Decision:* Normalizing options into a new table during active operational periods requires migrating thousands of existing question rows, rewriting 8 Blade views, and altering export/import templates. 
   - *Refined Action:* Preserve the existing flat column structure for backward compatibility, but ensure option access and serialization are abstracted cleanly in `QuestionService` so future migration to dynamic options can occur without breaking existing exams.

2. **Database Cascade Deletion Refinement:**
   - *Audit Suggestion:* Drop all foreign keys and alter cascade deletes to restrict.
   - *Architectural Evaluation & Decision:* In MySQL, dropping foreign keys requires careful constraint name resolution. We must implement pre-migration checks to ensure no orphan records exist before altering constraints to `restrictOnDelete`.
