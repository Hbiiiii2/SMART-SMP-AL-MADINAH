# Implementation Log
## SMART-CBT Engineering Remediation

This log provides a comprehensive chronological record of engineering activities, code changes, schema modifications, and verifications completed during the remediation.

---

### Phase 1: Baseline Audit & Verification
- Established baseline system inventory in `docs/engineering-remediation/BASELINE.md`.
- Empirically verified all reported architectural, database, logic, performance, security, and integration issues in `docs/engineering-remediation/FINDINGS_VERIFICATION.md`.
- Formulated the implementation plan in `docs/engineering-remediation/IMPLEMENTATION_PLAN.md` and dependency graph in `docs/engineering-remediation/DEPENDENCY_MAP.md`.
- Verified that PHP 8.4 runtime (`C:\laragon\bin\php\php-8.4.26-Win32-vs17-x64\php.exe`) is required due to vendor dependency syntax.

---

### Phase 2: Database Hardening & Schema Evolution
- Created and executed migration `2026_11_12_000001_harden_cbt_foreign_keys_and_cascade_rules.php`:
  - Changed `exams.guru_id` and `answers.question_id` to `restrictOnDelete()`.
  - Re-anchored unique index on `exam_attempts` from `(exam_id, siswa_id)` to `(exam_id, siswa_id, attempt_number)` after handling MySQL foreign key constraint 1553.
- Created and executed migration `2026_11_12_000002_add_question_order_and_essay_scores_to_exam_attempts.php`:
  - Added `question_order` (JSON), `nilai_pg` (decimal 5,2), and `nilai_essay` (decimal 5,2) to `exam_attempts`.
- Created and executed migration `2026_11_12_000003_add_performance_composite_indexes.php`:
  - Added 7 composite performance indexes (`idx_attempts_exam_status`, `idx_attempts_status_updated`, `idx_attempts_siswa_status`, `idx_attempts_started_at`, `idx_exams_kelas_status_tgl`, `idx_exams_token_rotate`, `idx_exams_status`).
- Updated `app/Models/ExamAttempt.php` `$fillable` and `$casts`.
- Setup dedicated testing database `smart_cbt_testing` in `phpunit.xml` and `.env.testing`.

---

### Phase 3: Service Layer Architecture Implementation
- Implemented `app/Services/Examination/ScoringService.php`:
  - `evaluatePgQuestion`: Pure evaluation of multiple choice questions.
  - `gradeEssayAnswer`: Clamped essay evaluation with teacher notes.
  - `recalculateAttemptScore`: Transactional recalculation of score breakdown and state machine transitions.
- Implemented `app/Services/Answer/AnswerService.php`:
  - `saveBatchAnswers`: Atomic multi-row `Answer::upsert()` query for student autosave.
  - `submitAttemptAnswers`: Transactional submission, auto-scoring, and state determination.
  - `calculateDeadline`: Unified deadline logic.
- Implemented `app/Services/Examination/ExamAttemptService.php`:
  - `startOrResumeAttempt`: Transactional attempt initialization, question shuffling, class verification.
  - `getOrderedQuestions`: Deterministic question ordering based on persisted `question_order`.
  - `ensureAssigned`: Strict class authorization.
- Implemented `app/Services/Security/TokenService.php`:
  - `generateToken`, `activateToken`, `deactivateToken`, `rotateExpiredToken`, `verifyToken`.
- Implemented `app/Services/Integration/MasterDataSyncService.php`:
  - Multi-page API synchronization of classes and students from SMART+ Core system.
- Created Artisan command `app/Console/Commands/SyncMasterDataCommand.php` (`cbt:sync-master-data`).

---

### Phase 4: Controllers & Views Refactoring
- Refactored `app/Http/Controllers/SiswaExamController.php`:
  - Injected domain services (`ExamAttemptService`, `AnswerService`, `ScoringService`, `TokenService`).
  - Replaced procedural query loops in `start()`, `work()`, `saveAnswers()`, and `submit()` with clean service delegations.
- Refactored `app/Http/Controllers/GuruMonitoringController.php`:
  - Replaced in-memory pagination (`->get()` and `LengthAwarePaginator`) with direct database pagination (`->paginate(20)->withQueryString()`).
- Refactored `app/Http/Controllers/ResultController.php`:
  - Added `gradeEssay()` endpoint with teacher ownership authorization and score validation.
- Modernized `resources/views/hasil/show.blade.php`:
  - Added metric cards displaying `nilai_pg`, `nilai_essay`, `nilai_akhir`, and status badge (`needs_grading`, `completed`).
  - Added interactive inline essay grading forms for teachers.
- Guarded `app/Http/Controllers/QuestionController.php`:
  - Disallowed modifying/deleting questions when exam attempts exist (`abort_if(..., 422)`).

---

### Phase 5: Security & Routing Modernization
- Refactored `app/Http/Middleware/ServiceAuthMiddleware.php`:
  - Removed fallback secret key `'cbt_secret_token_almadinah_2026'`.
  - Disallowed query string tokens (`$request->query('api_key')`).
  - Enforced constant-time comparison via `hash_equals()`.
- Updated `config/services.php`:
  - Removed hardcoded default for `CBT_API_TOKEN`.
- Refactored `routes/web.php`:
  - Removed duplicate `/api/v1/*` routes (lines 426–435).
  - Added `hasil.grade-essay` routes for admin and guru.
  - Added missing `pengajaran` resource routes.
- Updated `routes/api.php`:
  - Added `throttle:60,1` rate limiting middleware to protected CBT endpoints.

---

### Phase 6: Automated Testing & Verification
- Authored new unit tests:
  - `tests/Unit/ScoringServiceTest.php`
  - `tests/Unit/AnswerServiceTest.php`
- Authored new feature tests:
  - `tests/Feature/EssayGradingTest.php`
  - `tests/Feature/StartExamConcurrencyTest.php`
- Authored load testing benchmark script:
  - `tests/Performance/k6_cbt_stress_test.js`
- Executed full test suite:
  - **64 tests passed, 265 assertions, 0 failures, 0 errors**.
