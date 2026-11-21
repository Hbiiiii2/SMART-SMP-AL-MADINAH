# ENGINEERING REMEDIATION IMPLEMENTATION PLAN
**Project:** SMART-CBT | **Date:** November 2026 | **Lead Architect:** Principal Software Architect  
**Methodology:** Incremental refactoring, test-driven validation, zero-downtime additive migrations, zero historical data loss.

---

## EXECUTION SCHEDULE BY PHASES

### PHASE 1 — CRITICAL DATA PROTECTION & INTEGRITY
1. **P1.1 — Safe Migration: Restrict Cascading Deletes & Snapshot Safety**
   - Migration: `2026_11_12_000001_harden_cbt_foreign_keys_and_cascade_rules.php`
   - Modify `exams.guru_id` and `answers.question_id` to `restrictOnDelete()`.
   - Add model guards in `QuestionController::destroy` and `update` preventing mutation of questions when attempts exist.
2. **P1.2 — Question Order Persistence**
   - Migration: `2026_11_12_000002_add_question_order_to_exam_attempts_table.php`
   - Add nullable JSON column `question_order` to `exam_attempts`.
   - Update `ExamAttemptService` / `SiswaExamController::start` to generate and persist question order once upon attempt start.
3. **P1.3 — Atomic Bulk Upsert Answer Persistence**
   - Implement `AnswerService::saveBatchAnswers` and `AnswerService::submitAttemptAnswers`.
   - Replace loop of `Answer::updateOrCreate()` with atomic `Answer::upsert(...)`.
4. **P1.4 — Attempt Start Race Condition Protection**
   - Implement transactional `firstOrCreate` with row-level locking on exam in `ExamAttemptService`.
5. **P1.5 — Essay Grading & Score Recalculation Engine**
   - Migration: `2026_11_12_000003_add_essay_scoring_fields_to_exam_attempts.php` (adds `nilai_pg`, `nilai_essay`).
   - Implement `ScoringService::evaluateSubmission`, `ScoringService::gradeEssayAnswer`, and `ScoringService::recalculateFinalScore`.
   - Add teacher essay grading routes, controller methods in `ResultController`, and Blade UI in `resources/views/hasil/show.blade.php`.

---

### PHASE 2 — SECURITY & DATABASE PERFORMANCE
1. **P2.1 — Database Performance Indexes**
   - Migration: `2026_11_12_000004_add_performance_composite_indexes.php`.
   - Add composite indexes:
     - `exam_attempts`: `(exam_id, status)`, `(status, updated_at)`, `(siswa_id, status)`, `started_at`.
     - `exams`: `(kelas_id, status, tanggal_mulai)`, `(token_aktif, token_kedaluwarsa_at)`.
2. **P2.2 — Query Memory Optimization in Monitoring**
   - Refactor `GuruMonitoringController::index` to use database pagination (`paginate(20)`) instead of loading all attempts into memory.
3. **P2.3 — API Security Hardening**
   - Refactor `ServiceAuthMiddleware.php`: remove hardcoded fallback, disallow query parameter tokens, enforce constant-time hash comparison.
   - Apply rate limiting `throttle:60,1` to external API routes in `routes/api.php`.
   - Remove redundant API routes from `routes/web.php`.

---

### PHASE 3 — ARCHITECTURAL DECOUPLING & DOMAIN SERVICES
1. **P3.1 — Service Layer Extraction**
   - Create `app/Services/Examination/ExamLifecycleService.php`
   - Create `app/Services/Examination/ExamAttemptService.php`
   - Create `app/Services/Examination/QuestionService.php`
   - Create `app/Services/Examination/ScoringService.php`
   - Create `app/Services/Answer/AnswerService.php`
   - Create `app/Services/Security/TokenService.php`
2. **P3.2 — Controller Refactoring**
   - Slim down `SiswaExamController`, `ExamController`, `ResultController`, `QuestionController`.
3. **P3.3 — Form Request Validation**
   - Create dedicated Form Requests for exam workflows (`SaveAnswersRequest`, `SubmitExamRequest`, `GradeEssayRequest`).

---

### PHASE 4 — MASTER DATA SYNCHRONIZATION & MULTI-CLASS SUPPORT
1. **P4.1 — Master Data Sync Pipeline**
   - Create `app/Services/Integration/MasterDataSyncService.php`.
   - Create Artisan command `php artisan cbt:sync-master-data` to sync students and classes idempotently from SMART+.
2. **P4.2 — Multi-Class Exam Assignment**
   - Migration: `2026_11_12_000005_create_exam_classes_table.php`.
   - Backward-compatible pivot table allowing an exam definition to be shared across multiple classes while preserving existing `exams.kelas_id`.

---

### PHASE 5 — AUTOMATED TESTING & LOAD TESTING VALIDATION
1. **P5.1 — Unit & Feature Test Suite**
   - Write tests for `AnswerService`, `ScoringService`, `ExamAttemptService`, `EssayGradingTest`, `ConcurrencyTest`.
2. **P5.2 — k6 Load Testing**
   - Execute load testing simulating concurrent autosave and submission bursts.

---

### PHASE 6 — FINAL DOCUMENTATION & SIGN-OFF
1. Complete all documentation files in `docs/engineering-remediation/`.
2. Produce `FINAL_ENGINEERING_REPORT.md`.
