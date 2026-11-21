# CBT REMEDIATION ROADMAP — SMART+
**Engineering Implementation & Refactoring Plan**  
**Target:** Transform SMART+ CBT into a production-grade, highly reliable, scalable examination engine capable of serving 500–1,000 concurrent students.

---

## ROADMAP OVERVIEW & EXECUTION PHASING

```
[Phase 0: Critical Fixes]  --->  [Phase 1: Performance & Indexing]  --->  [Phase 2: Architectural Decoupling]  --->  [Phase 3: Cleanup & Quality]
- Fix Cascade Deletes            - Add Database Indexes                 - Extract Service Layer                 - Standardize Form Requests
- Replace N+1 with Bulk Upsert   - Fix Monitoring Paginator             - Automated Master Data Sync            - Remove Dead Code
- Resolve Essay Grading Defect   - Enforce Redis Sessions/Cache         - Multi-Class Exam Scheduling           - Expand Test Suite & k6
```

---

## PRIORITY 0 — CRITICAL (Must Fix Before Any Live Examination)

### P0-01: Prevent Accidental Data Loss from Cascading Deletes
- **Why It Matters:** Currently, `exams.guru_id` and `answers.question_id` have `cascadeOnDelete()`. If an administrator or teacher deletes a user account or removes a question from an exam, MySQL instantly and permanently destroys all student examination attempts and answers associated with them.
- **Affected Files & Tables:**
  - Migrations: `database/migrations/2026_08_23_053501_create_exams_table.php`, `2026_08_23_053624_create_answers_table.php`
  - Controllers: `smart-cbt/app/Http/Controllers/QuestionController.php#L142`, `ExamController.php#L267`
  - Tables: `exams`, `questions`, `answers`, `exam_attempts`
- **Dependencies:** None.
- **Implementation Strategy:**
  1. Create a migration to change foreign key constraints on `exams.guru_id` and `answers.question_id` to `restrictOnDelete()`.
  2. In `QuestionController::destroy`, add guard:
     ```php
     abort_if($ujian->examAttempts()->exists(), 422, 'Soal tidak dapat dihapus karena ujian telah memiliki riwayat pengerjaan siswa.');
     ```
  3. In `QuestionController::update`, prevent changing question text, keys, or options if active attempts exist.
- **Regression Risks:** Teachers attempting to delete test/demo questions on exams that already have test attempts will be blocked until attempts are explicitly cleared.
- **Required Tests:**
  - `QuestionDeletionTest`: Attempt to delete a question with existing answers; assert `422 Unprocessable Entity` and database row preserved.
- **Acceptance Criteria:** No student answer or attempt can ever be automatically deleted via a cascade operation from a teacher or question deletion.

---

### P0-02: Eliminate N+1 Query Loops in Autosave and Submission with Bulk Upsert
- **Why It Matters:** Currently, submitting an exam runs `Answer::updateOrCreate` inside a loop over all questions within an exclusive transaction (`lockForUpdate`), firing 100+ individual queries per student. 500 concurrent students submitting within a 2-minute window generate over 50,000 queries, causing severe lock timeouts and crashing MySQL.
- **Affected Files & Tables:**
  - `smart-cbt/app/Http/Controllers/SiswaExamController.php#L206-L231` (`saveAnswers`)
  - `smart-cbt/app/Http/Controllers/SiswaExamController.php#L340-L366` (`submitAttempt`)
  - Table: `answers`
- **Dependencies:** Unique index on `['exam_attempt_id', 'question_id']` (already exists).
- **Implementation Strategy:**
  1. Prepare a payload array in memory for all submitted answers.
  2. In `saveAnswers`:
     ```php
     $payloads = [];
     $now = now();
     foreach ($answers as $qId => $ans) {
         if (in_array((int) $qId, $questionIds, true) && is_scalar($ans)) {
             $payloads[] = [
                 'exam_attempt_id' => $lockedAttempt->id,
                 'question_id' => (int) $qId,
                 'jawaban' => (string) $ans,
                 'updated_at' => $now,
                 'created_at' => $now,
             ];
         }
     }
     if (!empty($payloads)) {
         Answer::upsert($payloads, ['exam_attempt_id', 'question_id'], ['jawaban', 'updated_at']);
     }
     ```
  3. In `submitAttempt`, compute PG scores in PHP memory, then execute a single `Answer::upsert(...)` call for all 50 questions simultaneously.
- **Regression Risks:** Timestamps must be manually supplied in `upsert` arrays since Eloquent does not automatically set timestamps on bulk operations.
- **Required Tests:**
  - `AnswerPersistenceConcurrencyTest`: Simulate 20 concurrent answer save requests and 20 concurrent submissions; verify all answers and scores persist accurately with 1 database query per operation.
- **Acceptance Criteria:** Total queries during final submission reduced from 104 queries to exactly 3 queries (`SELECT attempt`, `UPSERT answers`, `UPDATE attempt`).

---

### P0-03: Resolve Broken Essay Scoring and Missing Teacher Grading Interface
- **Why It Matters:** Essay questions (`essay_1`, `essay_2`) are currently awarded 0 points automatically, but their weight is counted in `nilai_akhir`. Because no grading interface exists, student scores for exams containing essays are permanently ruined and exported as incorrect grades to E-Rapor.
- **Affected Files & Tables:**
  - `smart-cbt/app/Http/Controllers/SiswaExamController.php#L350-L366`
  - `smart-cbt/app/Http/Controllers/ResultController.php`
  - `smart-cbt/routes/web.php`
  - Tables: `exam_attempts`, `answers`
- **Dependencies:** None.
- **Implementation Strategy:**
  1. Update `submitAttempt`: If an exam has essay questions, set `exam_attempts.status = 'needs_grading'` and leave `nilai_akhir = NULL` (or store temporary PG-only score in a separate column `nilai_pg`).
  2. In `ResultController`, implement `gradeEssay(Request $request, ExamAttempt $attempt)`:
     ```php
     // Validate scores against question bobot
     // Update answers: skor, catatan_guru, sudah_dinilai = true
     // Recalculate nilai_akhir = round((total_pg_earned + total_essay_earned) / total_weight * 100, 2)
     // If all essays graded, set attempt status = 'completed'
     ```
  3. Add grading UI in `resources/views/hasil/show.blade.php` with input fields for essay scores and feedback.
- **Regression Risks:** E-Rapor sync (`CbtApiController::getExamResults`) must only import attempts where status is `completed` / `finalized` (never attempts still marked `needs_grading`).
- **Required Tests:**
  - `EssayGradingWorkflowTest`: Submit exam with 5 PG and 1 Essay; verify attempt marked `needs_grading`; submit teacher grade; verify `nilai_akhir` recalculated correctly.
- **Acceptance Criteria:** Exams with essays are never finalized with 0 score before teacher manual review.

---

### P0-04: Secure API Secret Tokens and Enforce Header-Only Authentication
- **Why It Matters:** Hardcoded token `'cbt_secret_token_almadinah_2026'` is in source code as a fallback, and tokens are accepted in URL query parameters (`?api_key=`), causing credentials to be permanently recorded in server access logs.
- **Affected Files:**
  - `smart-cbt/app/Http/Middleware/ServiceAuthMiddleware.php#L17-L21`
  - `smart-cbt/.env.example`
- **Dependencies:** Update `smart+/app/Services/CbtIntegrationService.php` to ensure it passes Bearer token in headers (already implemented via `Http::withToken()`).
- **Implementation Strategy:**
  1. In `ServiceAuthMiddleware.php`, reject requests if `CBT_API_TOKEN` is unset in `.env`:
     ```php
     $expectedToken = config('services.cbt.api_token') ?: env('CBT_API_TOKEN');
     if (empty($expectedToken)) {
         return response()->json(['error' => 'API service token is not configured on server.'], 500);
     }
     ```
  2. Disallow `$request->query('api_key')`; accept only `Authorization: Bearer <token>` or `X-API-Key` header.
- **Regression Risks:** Any manual curl or legacy script passing `?api_key=` in query params will receive 401.
- **Required Tests:**
  - `ServiceAuthTest`: Verify 401 on missing header, 401 on query parameter token, and 200 on valid Bearer header.
- **Acceptance Criteria:** No fallback secret exists in source code; URL token passing is completely rejected.

---

## PRIORITY 1 — HIGH (Required for Scalability & Integrity)

### P1-01: Apply Performance Composite Indexes on Core CBT Tables
- **Why It Matters:** Frequent queries in monitoring, results, student dashboards, and 10-minute token rotation crons do full table scans on `exam_attempts` and `exams`.
- **Affected Tables:** `exam_attempts`, `exams`, `answers`
- **Dependencies:** None.
- **Implementation Strategy:**
  Create migration `add_performance_indexes_to_cbt_tables`:
  ```php
  Schema::table('exam_attempts', function (Blueprint $table) {
      $table->index(['exam_id', 'status'], 'idx_attempts_exam_status');
      $table->index(['status', 'updated_at'], 'idx_attempts_status_updated');
      $table->index(['siswa_id', 'status'], 'idx_attempts_siswa_status');
      $table->index('started_at', 'idx_attempts_started_at');
  });

  Schema::table('exams', function (Blueprint $table) {
      $table->index(['kelas_id', 'status', 'tanggal_mulai'], 'idx_exams_kelas_status_tgl');
      $table->index(['token_aktif', 'token_kedaluwarsa_at'], 'idx_exams_token_rotate');
  });
  ```
- **Regression Risks:** Adding indexes on large tables locks tables briefly during migration. Must run during maintenance window.
- **Required Tests:** Execute `EXPLAIN` on `availableExams()`, `monitoring()`, and `getExamResults()`; verify `type = ref` or `range` instead of `ALL`.
- **Acceptance Criteria:** Zero unindexed queries on `exam_attempts` and `exams` under standard examination workflows.

---

### P1-02: Fix Memory-Leak Pattern in GuruMonitoringController
- **Why It Matters:** `GuruMonitoringController` calls `$attemptsQuery->get()` to fetch all attempts across all owned exams into PHP memory, then paginates in memory using `LengthAwarePaginator`.
- **Affected Files:** `smart-cbt/app/Http/Controllers/GuruMonitoringController.php#L60-L78`
- **Dependencies:** P1-01.
- **Implementation Strategy:**
  Replace in-memory slicing with Eloquent query pagination:
  ```php
  $attempts = $attemptsQuery->paginate(20)->withQueryString();
  ```
- **Regression Risks:** None. Blade template already iterates over standard LengthAwarePaginator instance.
- **Required Tests:** `MonitoringPaginationTest`: Verify query returns page 1 with 20 items without executing `->get()` on the full collection.
- **Acceptance Criteria:** Memory usage for monitoring endpoint remains constant (< 16 MB) regardless of total attempt count.

---

### P1-03: Implement Master Data Synchronization Pipeline Between SMART+ and CBT
- **Why It Matters:** Current databases have diverged (81 students in CBT vs 83 in SMART+). New students in SMART+ cannot take exams in CBT.
- **Affected Files & Tables:**
  - `smart+/app/Http/Controllers/Api/V1/SmartApiController.php`
  - `smart-cbt/app/Console/Commands/SyncMasterDataCommand.php` (new)
  - Tables: `siswas`, `gurus`, `kelas` in `smart_cbt`
- **Dependencies:** REST API contracts in PRD v2.0.
- **Implementation Strategy:**
  1. Build Artisan command `cbt:sync-master-data` in `smart-cbt` that calls `GET /api/v1/students` and `GET /api/v1/classes` on SMART+.
  2. Use `updateOrCreate` keyed on `nisn` to sync student records, class names, and active statuses.
  3. Schedule the sync command in `bootstrap/app.php` every morning or upon admin trigger.
- **Regression Risks:** Ensure students already mapped to `exam_attempts` keep their existing `id` primary key.
- **Required Tests:** `MasterDataSyncTest`: Mock SMART+ API response; verify student created in CBT with matching NISN and class.
- **Acceptance Criteria:** Exact 1:1 match in active student counts between SMART+ and CBT.

---

### P1-04: Persist Deterministic Question Ordering per Student Attempt
- **Why It Matters:** When `acak_soal` is true, questions are shuffled in memory on every request using `crc32($attempt->id)` as seed. If questions are reordered or edited, the student's question ordering changes mid-exam.
- **Affected Files & Tables:**
  - `smart-cbt/database/migrations/...`
  - `smart-cbt/app/Http/Controllers/SiswaExamController.php#L121` & `L157`
  - Table: `exam_attempts`
- **Dependencies:** None.
- **Implementation Strategy:**
  1. Add column `question_order` (`JSON NULL`) to `exam_attempts`.
  2. When the attempt is created in `start()`, generate the randomized question ID array once and save it:
     ```php
     $questionIds = $ujian->questions()->pluck('id')->all();
     if ($ujian->acak_soal) {
         shuffle($questionIds);
     }
     $attempt->question_order = $questionIds;
     $attempt->save();
     ```
  3. In `work()`, load questions matching `question_order`:
     ```php
     $questions = $ujian->questions->sortBy(function($q) use ($attempt) {
         return array_search($q->id, $attempt->question_order ?? []);
     });
     ```
- **Regression Risks:** Existing legacy attempts with `question_order = NULL` must fall back to default order.
- **Required Tests:** `DeterministicRandomizationTest`: Create attempt; record order; update question bank; assert attempt question order remains 100% identical.
- **Acceptance Criteria:** Question sequence is fixed permanently upon exam start and immutable thereafter.

---

## PRIORITY 2 — MEDIUM (Architecture, Maintainability & Refactoring)

### P2-01: Extract Domain Services from Fat Controllers
- **Why It Matters:** Controllers contain up to 546 lines with tangled business rules, preventing clean testing and CLI automation.
- **Target Services:**
  - `App\Services\ExamAttemptService`: Manages start, resume, session token verification, and deadline math.
  - `App\Services\AnswerService`: Manages bulk persistence and answer validation.
  - `App\Services\ScoringService`: Manages PG auto-grading, essay grading, and gradebook payload generation.
- **Files to Refactor:** `SiswaExamController.php`, `ExamController.php`, `ResultController.php`.
- **Acceptance Criteria:** Controller methods contain fewer than 25 lines, delegating domain actions to tested services.

### P2-02: Support Multi-Class Exam Administration
- **Why It Matters:** Teachers cannot schedule an exam for 7A, 7B, and 7C without creating 3 redundant exam definitions.
- **Implementation:** Introduce `exam_classes` pivot table (`exam_id`, `kelas_id`). Update `ensureAssigned()` to check if student's `kelas_id` is within the exam's assigned classes.
- **Acceptance Criteria:** One exam definition can be assigned to multiple classes simultaneously.

### P2-03: Clean Up 2NF Violation on Question Options
- **Why It Matters:** 10 static columns on `questions` (`opsi_a`..`opsi_e`, images) make option shuffling impossible and restrict question formats.
- **Implementation:** Migrate options to a dedicated table `question_options` (`id`, `question_id`, `label`, `option_text`, `option_image`, `is_correct`, `order`).
- **Acceptance Criteria:** Clean 1-to-many relationship supporting any number of options per question, with native option randomization support.

---

## PRIORITY 3 — LOW (Code Polish & Standardization)

### P3-01: Standardize Form Requests Across CBT Operations
- Create `ExamStoreRequest`, `ExamUpdateRequest`, `SaveAnswerRequest`, `SubmitExamRequest` to replace inline `$request->validate()` in controllers.

### P3-02: Remove Redundant Duplicate API Route Registration
- Remove duplicate `/api/v1/*` routes from `smart-cbt/routes/web.php` lines 426–435.

### P3-03: Align PHP Version Declarations in composer.json
- Update `smart-cbt/composer.json` from `"php": "^8.3"` to `"php": "^8.4"` to match runtime vendor requirements.

---

## VERIFICATION & LOAD TESTING PLAN (k6)

To empirically certify production readiness for 500–1,000 concurrent students, execute the following k6 stress test on staging before deployment:

```javascript
// k6_cbt_stress_test.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '2m', target: 100 },  // Ramp up to normal
    { duration: '3m', target: 300 },  // Ramp up to heavy
    { duration: '2m', target: 500 },  // Peak load: 500 concurrent students
    { duration: '2m', target: 0 },    // Ramp down (submission peak)
  ],
  thresholds: {
    http_req_duration: ['p(95)<1500'], // 95% of requests must complete under 1.5s
    http_req_failed: ['rate<0.01'],    // Error rate must be less than 1%
  },
};

export default function () {
  // 1. Save answers (Autosave endpoint)
  const res = http.post('http://localhost:8001/siswa/ujian/1/jawaban', JSON.stringify({
    answers: { '1': 'A', '2': 'B', '3': 'C', '4': 'D', '5': 'E' }
  }), {
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
  });
  check(res, { 'status is 200': (r) => r.status === 200 });
  sleep(5);
}
```

**Success Benchmark:** 500 virtual users running continuous autosave and simultaneous final submission with `p(95) < 1.5s` and 0 database lock timeouts.
