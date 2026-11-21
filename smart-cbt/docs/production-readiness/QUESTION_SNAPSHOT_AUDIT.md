# QUESTION SNAPSHOT & HISTORICAL INTEGRITY AUDIT — SMART-CBT

**Audit Date:** November 12, 2026  
**Auditor:** Principal Software Architect & Database Reliability Engineer  
**Scope:** Question mutation pathways, historical attempt integrity, question reordering, question bank synchronization, and snapshot mechanics.  
**Overall Status:** `PARTIALLY VERIFIED` (Update & Delete guarded; Mutation pathways unguarded; Snapshotting incomplete)

---

## 1. Executive Summary

A core pillar of any production-grade Computer-Based Testing (CBT) platform is the **absolute immutability of examination records**. Once a student begins an examination attempt, the questions presented—including their exact wording, multiple-choice options, point weights, and correct answer keys—must remain permanently immutable. 

An exhaustive technical inspection of SMART-CBT was performed across all question mutation pathways in [QuestionController.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/app/Http/Controllers/QuestionController.php), Question Bank integration, and [ExamAttemptService.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/app/Services/Examination/ExamAttemptService.php).

**Critical Findings:**
1. **Partial Guards:** While `QuestionController::update()` and `QuestionController::destroy()` correctly check for existing attempts (`abort_if($ujian->examAttempts()->exists(), 422)`), five other critical mutation endpoints (`store`, `reorder`, `duplicate`, `import`, and `importBank`) **lack attempt existence guards entirely**.
2. **Pseud-Snapshot Limitation:** The column `exam_attempts.question_order` stores only an ordered JSON array of primary keys (e.g., `[14, 2, 8, 21]`). It does **not** preserve question content, options, answer keys, or point weights. If a teacher or admin alters question content in the database, historical student attempts and scoring audits are retroactively mutated.

---

## 2. Exhaustive Audit of Question Mutation Pathways

| Controller / Method | Endpoint / Action | Attempt Guard Implemented? | Risk Level | Observed Behavior & Code Evidence |
|---|---|---|---|---|
| `QuestionController@update` | `PUT /ujian/{ujian}/soal/{soal}` | **YES** (`abort_if(..., 422)`) | Low | Correctly rejects modifications if `$ujian->examAttempts()->exists()`. |
| `QuestionController@destroy` | `DELETE /ujian/{ujian}/soal/{soal}` | **YES** (`abort_if(..., 422)`) | Low | Correctly rejects deletions if `$ujian->examAttempts()->exists()`. |
| `QuestionController@store` | `POST /ujian/{ujian}/soal` | **NO** | **HIGH** | Teacher can append new questions while students are actively taking the exam, altering total exam weight and question count mid-flight. |
| `QuestionController@reorder` | `POST /ujian/{ujian}/soal/reorder` | **NO** | **MEDIUM** | Modifies `urutan` column in `questions` table without checking attempts. |
| `QuestionController@duplicate` | `POST /ujian/{ujian}/soal/{soal}/duplicate` | **NO** | **HIGH** | Appends duplicate question into active exam. |
| `QuestionController@import` | `POST /ujian/{ujian}/soal/import` | **NO** | **CRITICAL** | Bulk Excel import can be executed during live examination, injecting dozens of questions into an in-progress exam. |
| `QuestionController@importBank` | `POST /ujian/{ujian}/soal/import-bank` | **NO** | **CRITICAL** | Question bank sync can inject questions into live exam without guard. |
| `Direct Model Update` | `Question::where(...)->update(...)` | **NO** | **HIGH** | No Eloquent model events or database triggers prevent row modification. |

```php
// app/Http/Controllers/QuestionController.php - Vulnerability Example
public function store(Request $request, Exam $ujian)
{
    // MISSING: abort_if($ujian->examAttempts()->exists(), 422, 'Ujian sudah dikerjakan');
    $validated = $request->validate([
        'tipe' => 'required|in:pg,essay',
        'pertanyaan' => 'required|string',
        'bobot' => 'required|numeric|min:0.1',
        // ...
    ]);
    $ujian->questions()->create($validated);
    return back()->with('success', 'Soal berhasil ditambahkan');
}
```

---

## 3. Analysis of `question_order` vs. True Question Snapshots

### 3.1 What `question_order` Actually Does
In `ExamAttemptService::startOrResumeAttempt()`:
```php
if ($exam->acak_soal) {
    $questionIds = $exam->questions()->pluck('id')->all();
    shuffle($questionIds);
    $questionOrder = array_values($questionIds);
}
```
When `ExamAttempt` is persisted, the column `question_order` holds:
```json
[22, 14, 5, 18, 9]
```
When questions are rendered for the student in `ExamAttemptService::getOrderedQuestions()`, Eloquent joins directly against the live `questions` table:
```php
$exam->load('questions');
$questions = $exam->questions;
$orderMap = array_flip($order);
return $questions->sortBy(fn($q) => $orderMap[$q->id] ?? 999999)->values();
```

### 3.2 Failure Modes Under Question Changes
1. **Key Alteration Retroactivity:** If Question 22 originally had correct key `'A'` with weight `2.0`, and an instructor edits the question row after the exam to have correct key `'B'`, re-running `ScoringService::recalculateAttemptScore()` will re-evaluate all past student answers against the new key `'B'`.
2. **Orphaned IDs in `question_order`:** If questions were deleted via a direct SQL or seeder, `orderMap[$q->id]` will fail to find the question, silently shrinking the exam size for that student.
3. **Weight Discrepancy:** If question weight is updated from `1.0` to `5.0`, past percentages are distorted because the denominator `total_bobot` dynamically sums live question weights.

---

## 4. Architectural Recommendation: Immutable Question Snapshotting

To provide enterprise-grade auditability without breaking existing historical attempts, SMART-CBT should adopt a **Two-Tier Snapshot Strategy**.

### Option A: Complete Attempt JSON Snapshot (Recommended for Immediate Implementation)
Store the full immutable snapshot directly in `exam_attempts` upon attempt creation.

1. **Non-Destructive Migration:** Add a nullable JSON column `questions_snapshot` to `exam_attempts`:
   ```sql
   ALTER TABLE `exam_attempts` 
   ADD COLUMN `questions_snapshot` LONGTEXT NULL AFTER `question_order`;
   ```
2. **Snapshot Population in `ExamAttemptService`:**
   ```php
   $questions = $exam->questions()->get();
   if ($exam->acak_soal) {
       $questions = $questions->shuffle();
   }
   
   $snapshot = $questions->map(fn($q) => [
       'id' => $q->id,
       'tipe' => $q->tipe,
       'pertanyaan' => $q->pertanyaan,
       'pilihan_a' => $q->pilihan_a,
       'pilihan_b' => $q->pilihan_b,
       'pilihan_c' => $q->pilihan_c,
       'pilihan_d' => $q->pilihan_d,
       'pilihan_e' => $q->pilihan_e,
       'kunci' => $q->kunci,
       'bobot' => (float) $q->bobot,
   ])->values()->all();

   $attempt = ExamAttempt::create([
       'exam_id' => $exam->id,
       'siswa_id' => $siswa->id,
       'attempt_number' => 1,
       'started_at' => $now,
       'status' => 'in_progress',
       'token_used' => $cleanToken,
       'question_order' => array_column($snapshot, 'id'),
       'questions_snapshot' => json_encode($snapshot),
   ]);
   ```
3. **Fallback Compatibility:** If `questions_snapshot` is null (for historical attempts created before the migration), `getOrderedQuestions()` transparently falls back to querying `questions` table using `question_order`.

### Option B: Normalized Snapshot Table (`exam_question_snapshots`)
For large schools with 10,000+ attempts where JSON duplication is undesirable:
- A table `exam_question_snapshots` populated once per exam version or per exam activation.
- An attempt references `exam_version_id`.

---

## 5. Immediate Protection Measures (Zero Schema Changes)

Before running any live exam pilot, add the standard lock check to the remaining methods in `QuestionController.php`:

```php
// In QuestionController.php: store, reorder, duplicate, import, importBank
private function assertExamNotStarted(Exam $ujian): void
{
    if ($ujian->examAttempts()->exists()) {
        abort(422, 'Tidak dapat memodifikasi soal karena ujian sudah mulai dikerjakan.');
    }
}
```

---

## 6. Audit Verdict

| Metric | Rating | Detail |
|---|---|---|
| **Attempt Edit/Delete Defense** | `VERIFIED` | Existing attempts block standard question updates and deletes via UI. |
| **Pathway Coverage** | `PARTIALLY VERIFIED` | `store`, `reorder`, `import`, and `duplicate` are currently exposed. |
| **Historical Immutability** | `NOT VERIFIED` | Content, keys, and weights remain mutable in `questions` table. |
| **Pilot Readiness** | **Conditionally Ready** | Safe for pilot if instructors are instructed not to touch exams once launched; snapshot migration required for formal release. |
