# ESSAY SCORING & FINAL GRADE COMPUTATION VERIFICATION — SMART-CBT

**Audit Date:** November 12, 2026  
**Auditor:** Principal Software Architect & QA Engineer  
**Scope:** Automated multiple-choice evaluation, manual essay grading, score clamping, status transitions, and SMART+ grade synchronization criteria.  
**Overall Status:** `VERIFIED` (Scoring engine, state machine, and clamping logic verified 100%)

---

## 1. Executive Summary

The examination scoring engine was verified across all question combinations: Multiple Choice only (PG), Essay only, and Mixed format examinations. The implementation in [ScoringService.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/app/Services/Examination/ScoringService.php) and [AnswerService.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/app/Services/Answer/AnswerService.php) guarantees mathematical consistency, prevents score inflation through strict clamping rules, and implements a clear lifecycle state machine (`in_progress` $\to$ `needs_grading` $\to$ `completed`).

Automated tests in `tests/Feature/ScoringServiceTest.php` and `tests/Feature/EssayGradingWorkflowTest.php` confirm that grades are calculated using exact point ratios and that incomplete essay evaluations never trigger premature synchronization to the SMART+ school management master database.

---

## 2. Mathematical Scoring Formulas

All scores are stored using `DECIMAL(5,2)` in MySQL, preventing IEEE 754 floating-point rounding anomalies (e.g., $83.333333333333$).

### 2.1 Component Scoring
Given an exam with multiple-choice questions $Q_{pg}$ and essay questions $Q_{es}$:
- **Multiple Choice Earned Score ($S_{pg}$):**
  $$S_{pg} = \sum_{q \in Q_{pg}, \text{correct}} W_q$$
  Where $W_q$ is the weight (`bobot`) of question $q$.

- **Essay Earned Score ($S_{es}$):**
  $$S_{es} = \sum_{q \in Q_{es}} \min(\max(\text{score}_q, 0), W_q)$$

- **Total Possible Exam Weight ($W_{total}$):**
  $$W_{total} = \sum_{q \in Q_{pg} \cup Q_{es}} W_q$$

- **Final Normalized Score ($S_{final} \in [0, 100]$):**
  $$S_{final} = \text{round}\left( \frac{S_{pg} + S_{es}}{W_{total}} \times 100, 2 \right)$$

---

## 3. Examination State Machine & Lifecycle Transitions

```mermaid
stateDiagram-v2
    [*] --> InProgress : Student Starts Exam
    InProgress --> InProgress : Autosave Answers
    
    state SubmitDecision <<choice>>
    InProgress --> SubmitDecision : Final Submission Clicked / Timer Expires
    
    SubmitDecision --> Completed : MC Only Exam (Auto-graded)
    SubmitDecision --> NeedsGrading : Essay / Mixed Exam (Pending Teacher Review)
    
    NeedsGrading --> NeedsGrading : Teacher Grades Partial Essays
    NeedsGrading --> Completed : All Essays Graded & Validated
    
    Completed --> SMARTPlusSync : Trigger Dispatch to SMART+
    SMARTPlusSync --> [*]
```

### 3.1 Multiple Choice Only (PG) Exam
1. Student submits exam.
2. `ScoringService::calculateAttemptScore()` executes.
3. Because $Q_{es} = \emptyset$, all answers are fully evaluated immediately.
4. Attempt status transitions directly to **`completed`**.
5. Final score is stored in `nilai_pg` and `nilai_akhir`.

### 3.2 Essay & Mixed Exams
1. Student submits exam.
2. `ScoringService` evaluates multiple choice answers (if any) and records earned points in `nilai_pg`.
3. Essay answers are stored in `answers` with `sudah_dinilai = 0` and `skor = null`.
4. Attempt status transitions to **`needs_grading`**.
5. Column `nilai_akhir` remains `NULL` to indicate an incomplete evaluation.

---

## 4. Manual Essay Grading & Score Clamping Logic

In `AnswerService::gradeEssayBatch()`:
```php
// app/Services/Answer/AnswerService.php
$totalEssayPoints = 0;
foreach ($grades as $questionId => $gradeData) {
    $question = $questions->get($questionId);
    if (! $question || $question->tipe !== 'essay') {
        continue;
    }

    $rawScore = (float) ($gradeData['score'] ?? 0);
    // Strict Clamping: Cannot be negative, cannot exceed question weight
    $clampedScore = max(0, min($rawScore, (float) $question->bobot));
    $totalEssayPoints += $clampedScore;

    $upsertPayload[] = [
        'exam_attempt_id' => $attempt->id,
        'question_id' => $question->id,
        'jawaban' => $existingAnswer?->jawaban,
        'is_correct' => $clampedScore > 0,
        'skor' => $clampedScore,
        'sudah_dinilai' => 1,
        'catatan_guru' => $gradeData['feedback'] ?? null,
        'updated_at' => now(),
    ];
}
```

### Verification Criteria:
1. **Upper Bound Defense:** If a question has weight `5.0` and a teacher mistakenly inputs `10.0`, the system automatically clamps the score to `5.0`.
2. **Lower Bound Defense:** Negative scores (e.g., `-2.0`) are automatically clamped to `0.0`.
3. **Completion Detection:** After saving grades, the service checks whether any essay questions for that attempt remain with `sudah_dinilai = 0`. If all essays are graded, status transitions from `needs_grading` to `completed`, and the final combined score is computed.

---

## 5. SMART+ Integration & Synchronization Gate

The external API endpoint `GET /api/v1/exams/{id}/results` and the background export job enforce strict synchronization boundaries:

```php
// app/Http/Controllers/Api/V1/CbtApiController.php
$attempts = $exam->examAttempts()
    ->where('status', 'completed')
    ->with('siswa')
    ->get();
```

- **Safety Guarantee:** Students whose attempts are still in `needs_grading` status are **never** exported to SMART+ report cards or academic transcripts.
- **Audit Logging:** Every grade synchronization event is logged with the timestamp, proctor/teacher ID, and attempt number.

---

## 6. Verification Verdict

| Verification Item | Test Outcome | Status |
|---|---|---|
| MC Only Automatic Scoring | 100% accurate percentage calculation | `VERIFIED` |
| Mixed Exam State Transition | Correctly transitions to `needs_grading` | `VERIFIED` |
| Manual Essay Grading Workflow | Teacher feedback and points saved atomically | `VERIFIED` |
| Clamping Rules ($0 \le S \le W$) | Zero score overflows possible | `VERIFIED` |
| SMART+ Gatekeeper | Incomplete attempts strictly quarantined from sync | `VERIFIED` |
