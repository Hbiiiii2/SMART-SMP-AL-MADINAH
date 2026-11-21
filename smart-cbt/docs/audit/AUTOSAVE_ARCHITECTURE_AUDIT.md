# SMART-CBT — Comprehensive Autosave Architecture Audit

**Audit Date:** November 12, 2026  
**Auditor:** Senior Laravel Backend Engineer, Frontend Performance Engineer, Database Optimization Specialist, & Distributed Systems Engineer  
**Project:** SMART-CBT  
**Working Directory:** `c:/laragon/www/smp-almadinah/smart-cbt`  
**Target Environment:** Single VPS (Budget Cap: Rp300,000/month)  
**Target Scale:** Up to 1,000 concurrent active examination sessions  

---

## 1. Executive Summary

This architecture audit conducts a thorough, code-level inspection of the examination autosave system in SMART-CBT. The purpose is to evaluate whether the current implementation can support high-concurrency examination traffic (up to 1,000 active students) under the strict infrastructure constraint of a single VPS budgeted at $\le$ Rp300,000/month.

### Core Audit Takeaways

1. **State Persistence & Queueing:** The frontend employs a LocalStorage-backed queue (`cbt_pending_answers_{attemptId}`) with per-question monotonic sequences (`cbt_question_seqs_{attemptId}`). Answers survive browser crashes and reloads.
2. **Scheduling Inefficiency:** Autosave is gated by a 5-question threshold (`CONFIG.BATCH_SIZE = 5`). If fewer than 5 answers change, answers sit un-synced for up to **30 seconds** (`CONFIG.PERIODIC_INTERVAL = 30000`).
3. **Harmonic Herd Risk (Zero Jitter):** Periodic fallback autosaves run on a fixed 30-second `setInterval` across all clients. Under synchronized class starts, hundreds of clients hit the backend in identical harmonic waves.
4. **Coarse-Grained Acknowledgment:** The backend returns a bare `{ saved: true, count: N }`. The frontend cannot verify which specific answer versions were persisted or rejected.
5. **No Idempotency Key:** Network retries re-execute database upserts without a request-level idempotency identifier (`request_id`).
6. **Rate-Limiting Ingress Fragility:** Authenticated endpoints fall back to `$request->ip()` when user resolution is delayed, risking collective throttling behind shared school NAT gateways.
7. **Single VPS Realities:** 1,000 active students on a 4-vCPU VPS cannot tolerate synchronized full-payload bursts; delta-based payloads, 10-second randomized jitter, and lightweight transactions are mandatory.

---

## 2. Frontend Examination Answer State Management

**File:** [work.blade.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/resources/views/siswa/exams/work.blade.php#L438-L570)

### State Structure
The client application manages examination state in a single global `state` object:
```javascript
const state = {
    pendingAnswers: {},        // { [questionId]: answerString }
    pendingSeqs: {},           // { [questionId]: monotonicSequenceInt }
    questionSequences: {},     // Monotonic sequence tracker per question
    changedQuestions: new Set(),// Set<questionId> tracking unsaved changes
    savedAnswers: new Map(),   // Map<questionId, answerString> (server confirmed)
    isSaving: false,           // Mutex flag preventing concurrent HTTP saves
    savingError: null,         // Error message from the last save attempt
    retryCount: 0,             // Current consecutive retry counter
    pageUnloading: false,      // Flag set during pagehide / beforeunload
    isSubmitting: false,       // Flag set when student initiates submit
    submitStarted: false,      // Global mutex blocking race conditions
};
```

### Storage Backing
- **Keys:**
  - `cbt_pending_answers_{attemptId}`: Serialized JSON object of unacknowledged answers.
  - `cbt_question_seqs_{attemptId}`: Serialized JSON object of unacknowledged client sequence numbers.
- **Initialization & Authoritative Recovery:**
  On page load ([lines 1114–1170](file:///c:/laragon/www/smp-almadinah/smart-cbt/resources/views/siswa/exams/work.blade.php#L1114-L1170)), the client seeds `state.questionSequences` from the authoritative server array `SERVER_SEQS = @json($answerSeqs ?? [])`. If local storage contains a sequence lower than the server's sequence, the stale local answer is discarded. If local storage contains a higher sequence (un-synced local edits), the higher sequence is retained.

---

## 3. Autosave Scheduling, Lifecycle, and Triggers

### 1. Batch Threshold Logic
Autosave is triggered on input events via [lines 1208–1241](file:///c:/laragon/www/smp-almadinah/smart-cbt/resources/views/siswa/exams/work.blade.php#L1208-L1241):
```javascript
input.addEventListener('input', (e) => {
    trackAnswerChange(questionId, currentValue);
    updateAnswerProgress();
    queueSave();
});
```
`queueSave()` invokes `checkAndSaveBatch()` after a 500ms debounce. However, [line 852](file:///c:/laragon/www/smp-almadinah/smart-cbt/resources/views/siswa/exams/work.blade.php#L852) dictates:
```javascript
async function checkAndSaveBatch() {
    if (state.changedQuestions.size >= CONFIG.BATCH_SIZE) { // BATCH_SIZE = 5
        const itemsToSave = Array.from(state.changedQuestions).slice(0, CONFIG.BATCH_SIZE);
        ...
        await saveBatchAnswers(batchAnswers);
    }
}
```
**Defect:** If a student answers 1, 2, 3, or 4 questions, `checkAndSaveBatch()` silently aborts.

### 2. Periodic Fallback Interval
Because single answer changes do not meet the 5-item threshold, they depend entirely on the fallback timer ([lines 1244–1249](file:///c:/laragon/www/smp-almadinah/smart-cbt/resources/views/siswa/exams/work.blade.php#L1244-L1249)):
```javascript
periodicSaveTimer = setInterval(() => {
    const pendingCount = Object.keys(state.pendingAnswers).length;
    if (pendingCount > 0 && !state.isSaving && !state.isSubmitting) {
        saveAllPendingAnswers().catch(err => console.warn('Periodic save warning:', err));
    }
}, CONFIG.PERIODIC_INTERVAL); // 30,000ms (30 seconds)
```
- Answers sit vulnerable in local browser storage for up to 30 seconds before network transmission.
- All clients share the exact same 30,000ms duration with **zero randomized jitter**. When a school class starts an exam within a tight window, hundreds of client timers trigger on identical harmonic cadences, generating recurring CPU spikes on the web and database servers.

### 3. Missing Tab Visibility Lifecycle
There is no `document.addEventListener('visibilitychange')` listener. When students minimize their mobile browser, switch to another application, or restore the tab, pending answers are neither flushed nor synchronized.

### 4. Page Unload Handling
- Uses `pagehide` with `fetch(..., { keepalive: true })` ([lines 1060–1082](file:///c:/laragon/www/smp-almadinah/smart-cbt/resources/views/siswa/exams/work.blade.php#L1060-L1082)).
- Uses `beforeunload` warning dialog if pending answers remain ([lines 1099–1105](file:///c:/laragon/www/smp-almadinah/smart-cbt/resources/views/siswa/exams/work.blade.php#L1099-L1105)).

---

## 4. Debounce and Request In-Flight Management

1. **Debounce Implementation:**
   - 500ms timeout in `queueSave()`:
     ```javascript
     function queueSave() {
         clearTimeout(saveTimer);
         saveTimer = setTimeout(checkAndSaveBatch, 500);
     }
     ```
   - Rapid radio clicks or essay keystrokes collapse into one timer reset. However, because `checkAndSaveBatch` requires 5 changed questions, debounce does not trigger an actual network flush for single question edits.
2. **In-Flight Serialization:**
   - [Lines 749–751](file:///c:/laragon/www/smp-almadinah/smart-cbt/resources/views/siswa/exams/work.blade.php#L749-L751):
     ```javascript
     if (activeSavePromise) {
         return activeSavePromise.then(() => saveBatchAnswers(answers, attempt));
     }
     ```
   - Chaining promises prevents concurrent HTTP requests from the same client, avoiding out-of-order execution on normal networks.
3. **Lack of Request Abort:**
   - No `AbortController` is used. If a student modifies an answer while an earlier save of that same question is stalled in network flight, the stalled request cannot be cancelled.

---

## 5. Backend Autosave Pipeline & Controllers

### 1. Route & Middleware Pipeline
- **Route:** `POST /siswa/ujian/{ujian}/jawaban` ([web.php:186](file:///c:/laragon/www/smp-almadinah/smart-cbt/routes/web.php#L186))
- **Route Name:** `siswa.ujian.answers`
- **Middleware:** `web`, `auth`, `role:siswa`, `throttle:exam-autosave`

### 2. Controller Action
- **Method:** `SiswaExamController::saveAnswers(Request $request, Exam $ujian)` ([SiswaExamController.php:168-186](file:///c:/laragon/www/smp-almadinah/smart-cbt/app/Http/Controllers/SiswaExamController.php#L168-L186))
- **Authorization & Context Guards:**
  - `ensureAssigned($ujian, $siswa)` ensures student enrollment in target class.
  - Checks `$attempt->status === 'in_progress'`.
  - Verifies session token binding `$request->session()->get('exam_token_attempt_' . $ujian->id) === $attempt->id`.
  - Validates payload `$answers` is an array.

### 3. Service Layer
- **Class:** `App\Services\Answer\AnswerService` ([AnswerService.php:28-152](file:///c:/laragon/www/smp-almadinah/smart-cbt/app/Services/Answer/AnswerService.php#L28-L152))
- **Method:** `saveBatchAnswers(ExamAttempt $attempt, array $answers, Exam $exam, array $clientSeqs = []): array`

---

## 6. Database Entities, Schema, and Locking Mechanics

### 1. Relational Entities
- **Attempt Model:** `App\Models\ExamAttempt` (Table: `exam_attempts`)
- **Answer Model:** `App\Models\Answer` (Table: `answers`)
- **Relationship:** `ExamAttempt hasMany Answer` via foreign key `exam_attempt_id`.

### 2. Indexes & Schema Integrity
```sql
Table: answers
- PRIMARY KEY (id)
- UNIQUE KEY answers_exam_attempt_id_question_id_unique (exam_attempt_id, question_id)
- FOREIGN KEY answers_question_id_foreign (question_id) REFERENCES questions(id)
- FOREIGN KEY answers_exam_attempt_id_foreign (exam_attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE
```
The composite unique key `(exam_attempt_id, question_id)` guarantees relational integrity. Duplicate rows for the same question within an attempt are physically rejected by MySQL.

### 3. Locking Strategy: Attempt-Level `lockForUpdate()`
Inside `AnswerService::saveBatchAnswers()`:
```php
return DB::transaction(function () use ($attempt, $answers, $exam, $clientSeqs): array {
    $lockedAttempt = ExamAttempt::whereKey($attempt->id)->lockForUpdate()->first();
    ...
}, 5);
```
- **Concurrency Scope:** Locks only the primary key row in `exam_attempts` for that student. Different students access different rows and never block one another at the attempt level.
- **Safety Benefit:** Completely prevents race conditions between autosave and final submission. If `submitAttemptAnswers()` sets `status = 'submitted'`, any waiting autosave fails immediately with `InvalidArgumentException`.
- **Performance Evaluation:** Row-level locks in MySQL InnoDB on primary keys are in-memory pointer acquisitions ($\le 0.1\text{ms}$). Attempt-level locking is correct and safe; it must **not** be removed.

### 4. Bulk Write Query
```php
usort($payload, fn ($a, $b) => $a['question_id'] <=> $b['question_id']);
Answer::upsert(
    $payload,
    ['exam_attempt_id', 'question_id'],
    ['jawaban', 'client_seq', 'updated_at']
);
```
- Deterministic sorting by `question_id` eliminates deadlocks.
- `Answer::upsert()` compiles to `INSERT INTO answers ... ON DUPLICATE KEY UPDATE`.

---

## 7. Versioning & Monotonic Sequencing

1. **Client Monotonic Sequence (`client_seq`):**
   - Each answer update increments an integer counter per question ($seq \leftarrow seq + 1$).
   - Sequenced in [work.blade.php:714](file:///c:/laragon/www/smp-almadinah/smart-cbt/resources/views/siswa/exams/work.blade.php#L714).
2. **Server-Side Validation:**
   - Validates $0 \le seq \le 4,294,967,295$.
   - **Downgrade Protection:** An unsequenced payload cannot overwrite an answer that already has a sequence number.
   - **Stale Write Rejection:** If incoming $seq < existing\ seq$, the incoming write is discarded (prevents network jitter and delayed packet overwrites).
   - **Collision Protection:** If incoming $seq == existing\ seq$ but the answer text differs, the write is discarded.
3. **The Missing Element: Idempotency Key (`request_id`):**
   - Currently, no `request_id` or idempotency token is sent with the HTTP payload.
   - If a client network timeout occurs after the server committed the transaction but before the client received the response, the client retries the entire payload. The server has no fast-path cache to return the previous acknowledgment and must re-evaluate all database rows.

---

## 8. Server Acknowledgment & UI Synchronization State

### Current Response Contract
```json
{
  "saved": true,
  "count": 5
}
```

### Critical Flaws in Current UI State
1. **Premature "Tersimpan" Indicator:**
   In [work.blade.php:594-598](file:///c:/laragon/www/smp-almadinah/smart-cbt/resources/views/siswa/exams/work.blade.php#L594-L598), when `pendingCount === 0`, the UI displays a green badge: **"Tersimpan"**. On initial page load, this displays "Tersimpan" even before the student has interacted or synchronized with the server.
2. **No Correlation Data:**
   The server response does not return the list of acknowledged question IDs, client sequences, or timestamps. The client simply deletes all items that match the sent keys.
3. **No Partial Failure Handling:**
   If the server were to accept 3 questions but reject 1 due to validation, the boolean `{ saved: false }` or exception causes all 4 answers to remain pending, or an unhandled state.

---

## 9. Rate-Limiting & Shared NAT Infrastructure Vulnerability

In [AppServiceProvider.php:27-30](file:///c:/laragon/www/smp-almadinah/smart-cbt/app/Providers/AppServiceProvider.php#L27-L30):
```php
RateLimiter::for('exam-autosave', function (Request $request) {
    $key = $request->user()?->id ?: $request->ip();
    return Limit::perMinute(120)->by($key);
});
```
- **Vulnerability:** If `$request->user()` evaluates to null (e.g. during rapid round-robin handshakes or session initialization), `$key` falls back to `$request->ip()`.
- In a school deployment where 500–1,000 students take an exam over a shared school WiFi / NAT proxy, all students share the identical public IP. Once 120 unauthenticated/fallback requests occur across the entire school within 60 seconds, **every student behind that IP receives HTTP 429 Too Many Requests**.

---

## 10. Summary Audit Matrix

| Architectural Dimension | Current State | Bottleneck / Risk Level | Target Production State |
| :--- | :--- | :---: | :--- |
| **Autosave Cadence** | Batch of 5, fallback 30s | **HIGH** (Unsynced answers for 30s) | Configurable 10s interval + 500ms debounce |
| **Request Jitter** | 0 ms (Fixed 30s timer) | **HIGH** (Harmonic thundering herd) | Configurable randomized jitter ($\pm 20\%$) |
| **Payload Size** | Up to full batch sent | **MEDIUM** (Redundant network/CPU load) | Strict delta payload (modified answers only) |
| **Idempotency** | None (`request_id` absent) | **MEDIUM** (Redundant DB execution on retry) | Unique `request_id` with Redis deduplication |
| **Server ACK** | Bare `{ saved: true, count }` | **HIGH** (Coarse UI state, no audit trail) | Detailed correlation ACK with timestamps |
| **UI State Model** | Binary Pending / Tersimpan | **HIGH** (Premature saved badge) | 5-state model: Dirty, Pending, Saving, Saved, Failed |
| **Network Retries** | Linear delay (1s, 2s, 3s) | **MEDIUM** (Fixed retry storms) | Exponential backoff with randomized jitter |
| **Rate Limiter Key** | User ID with IP fallback | **CRITICAL** (NAT lockout risk) | User ID or Session ID; no shared IP fallback |
| **Attempt Locking** | `ExamAttempt::lockForUpdate()`| **VERIFIED CORRECT** | Retain row-level pessimistic serialization |
| **Relational Schema** | Composite unique constraint | **VERIFIED CORRECT** | Retain engine-level uniqueness |
