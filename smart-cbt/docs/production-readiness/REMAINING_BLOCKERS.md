# REMAINING TECHNICAL BLOCKERS & REMEDIATION ROADMAP — SMART-CBT

> [!NOTE]
> This document has been updated following the completion of **Phase 1 & Phase 2 Critical Reliability Remediations**.
> The authoritative, updated roadmap is also available at [REMAINING_BLOCKERS.md](file:///c:/laragon/www/smp-almadinah/smart-cbt/docs/REMAINING_BLOCKERS.md).

**Audit Date:** November 12, 2026  
**Auditor / Architect:** Principal Software Architect & Reliability Engineer  
**Scope:** Prioritized catalog of all identified architectural weaknesses, risks, and required fixes prior to full 1,000-student production release.  
**Current Status:** `READY FOR CONTROLLED PILOT (50–100 STUDENTS)`

---

## 1. Executive Summary

Following Phase 1 & Phase 2 engineering remediations and the Autosave Protocol Edge-Case Audit, the primary data integrity, concurrency, and rate limiting blockers (**BLK-01**, **BLK-02**, **BLK-03**, and **Phase 2 Question Snapshots**) have been successfully resolved and empirically validated via 106 automated tests (424 assertions, 0 failures).

The application is officially **READY FOR CONTROLLED PILOT (50–100 students)** in a supervised computer lab setting. Full campus scale (500–1,000 students) requires standalone Redis 7.x session storage (**BLK-04**) upon deployment to the production Linux server.

---

## 2. Updated Blocker Status Matrix

| Priority | ID | Blocker / Architectural Risk | Component | Original Severity | Current Status |
|---|---|---|---|---|---|
| **P1** | **BLK-01** | **Out-of-Order Autosave Overwrite (LWW)** | `AnswerService` / `work.blade.php` | **CRITICAL** | **RESOLVED** |
| **P1** | **BLK-02** | **Single-NAT IP Rate Limiting Risk** | `AppServiceProvider` / `routes/web.php` | **HIGH** | **RESOLVED** |
| **P2** | **BLK-03** | **Unguarded Question Mutation Pathways** | `QuestionController` / `Question.php` | **HIGH** | **RESOLVED** |
| **P2** | **Phase 2** | **Per-Attempt Question Snapshotting & Historical Scoring** | `ExamAttemptService` / `ScoringService` | **HIGH** | **RESOLVED** |
| **P2** | **BLK-04** | **Redis Inactivity & Session I/O Saturation** | Infrastructure / `.env` | **CRITICAL** | **REMAINING FOR FULL SCALE** |
| **P3** | **BLK-06** | **Remedial Rollback Constraint Conflict** | Database Migration `000001` | **MEDIUM** | **MITIGATED / OPERATIONAL** |
| **P3** | **BLK-07** | **Monitoring Dashboard `Using filesort`** | `exam_attempts` Indexes | **LOW** | **PENDING FULL SCALE** |

---

## 3. Resolution Verification Summary

1. **BLK-01 (Autosave LWW Protection):** Added monotonic `client_seq` to browser LocalStorage queue in [work.blade.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/resources/views/siswa/exams/work.blade.php). Added column `client_seq` in `answers` table via migration. [AnswerService.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/app/Services/Answer/AnswerService.php) skips older packets if `incoming_seq < stored_seq`. Verified by [AutosaveSequenceIntegrityTest.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/tests/Feature/AutosaveSequenceIntegrityTest.php).
2. **BLK-02 (Single-NAT IP Rate Limiting):** Partitioned rate limits by `auth()->id() ?: ip` in [AppServiceProvider.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/app/Providers/AppServiceProvider.php). Students sharing a school Wi-Fi router no longer exhaust each other's rate quotas. Verified by [UserKeyedRateLimitingTest.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/tests/Feature/UserKeyedRateLimitingTest.php).
3. **BLK-03 (Question Mutation Guards):** Centralized `assertExamModifiable` in [QuestionController.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/app/Http/Controllers/QuestionController.php) covering 7 mutation routes, plus model domain hooks in [Question.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/app/Models/Question.php). Returns HTTP 422 if attempts exist. Verified by [QuestionMutationGuardsTest.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/tests/Feature/QuestionMutationGuardsTest.php).
4. **Phase 2 (Question Snapshots):** Serialized question content, options, keys, and weights into `questions_snapshot` (JSON) on attempt creation in [ExamAttemptService.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/app/Services/Examination/ExamAttemptService.php). Answer keys stripped from student presentation. Scoring strictly reads from snapshot. Verified by [QuestionSnapshotImmutabilityTest.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/tests/Feature/QuestionSnapshotImmutabilityTest.php).

---

## 4. Remaining Action Items for Full Production Scale (500–1,000 Students)

1. **BLK-04: Standalone Redis 7.x Setup:**  
   Configure `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, and `QUEUE_CONNECTION=redis` on the target Linux production server to eliminate MySQL session table contention.
2. **BLK-07: Monitoring Index Optimization:**  
   Add composite index `(exam_id, status, updated_at DESC)` on `exam_attempts` to optimize live monitoring dashboard sorting during large-scale examination sessions.

---

## 5. Phase-Gate Deployment Recommendation

```
================================================================================
               CURRENT CLASSIFICATION: READY FOR CONTROLLED PILOT
================================================================================
  [ ] NOT READY
  [ ] CONDITIONALLY READY
  [X] READY FOR CONTROLLED PILOT (50–100 Students in Supervised Computer Lab)
  [ ] READY FOR FULL SCALE (500–1,000 Students, Pending Linux Redis Provisioning)
================================================================================
```
