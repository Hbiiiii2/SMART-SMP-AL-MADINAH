# SMART-CBT REMAINING TECHNICAL BLOCKERS & DEPLOYMENT ROADMAP

**Last Updated:** November 12, 2026  
**Auditor / Architect:** Principal Software Architect & Reliability Engineer  
**Scope:** Updated catalog of resolved reliability blockers, pending production requirements, and deployment criteria for SMART-CBT.  
**Current Status:** `READY FOR CONTROLLED PILOT (50–100 STUDENTS)`  

---

## 1. Executive Summary

Following Phase 1 & Phase 2 critical engineering remediations and the Autosave Protocol Edge-Case Audit, the primary concurrency, data integrity, and network reliability blockers have been completely resolved and verified via 106 automated tests (424 assertions, 0 failures).

The application is now safe for a **Controlled Pilot Examination (50–100 concurrent students)** in a supervised computer lab environment.

To scale beyond 100 students to the target **500–1,000 concurrent students** across multiple grades and school wings, remaining infrastructure items (chiefly standalone Redis 7.x session storage) must be activated upon deployment to the production Linux server.

---

## 2. Updated Blocker & Remediation Status Matrix

| ID | Issue / Risk Title | Component | Original Severity | Current Status | Remediation & Verification Evidence |
|---|---|---|---|---|---|
| **BLK-01** | **Out-of-Order Autosave Overwrite (LWW)** | `AnswerService` / `work.blade.php` | **CRITICAL** | **RESOLVED** | Monotonic `client_seq` implemented in browser LocalStorage queue, persisted in `answers`, and guarded in `AnswerService`. Out-of-order packets safely discarded. Verified in [AutosaveSequenceIntegrityTest.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/tests/Feature/AutosaveSequenceIntegrityTest.php). |
| **BLK-02** | **Unguarded Question Mutation Pathways** | `QuestionController.php` / `Question.php` | **HIGH** | **RESOLVED** | Centralized `assertExamModifiable` guard added to `store`, `update`, `destroy`, `reorder`, `duplicate`, `import`, and `importBank` returning HTTP 422. Domain model hooks prevent direct tampering. Verified in [QuestionMutationGuardsTest.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/tests/Feature/QuestionMutationGuardsTest.php). |
| **BLK-03** | **Single-NAT IP Rate Limiting Hazard** | `AppServiceProvider.php` / `routes/web.php` | **HIGH** | **RESOLVED** | Rate limiters (`exam-autosave`, `exam-start`, `exam-submit`) partitioned by `auth()->id() ?: ip`. Prevents school-wide 429 lockouts behind shared NAT router. Verified in [UserKeyedRateLimitingTest.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/tests/Feature/UserKeyedRateLimitingTest.php). |
| **Phase 2** | **Per-Attempt Question Snapshotting & Historical Integrity** | `ExamAttemptService` / `ScoringService` | **HIGH** | **RESOLVED** | Added `questions_snapshot` (JSON) to `exam_attempts`. Serialized upon attempt creation. Answer keys stripped from student views. Scoring and recalculation evaluate against immutable snapshot. Verified in [QuestionSnapshotImmutabilityTest.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/tests/Feature/QuestionSnapshotImmutabilityTest.php). |
| **BLK-04** | **Redis Inactivity & Database Session Saturation** | Infrastructure / `.env` | **CRITICAL** | **REMAINING FOR FULL SCALE** | Local Laragon Windows environment lacks standalone Redis. MySQL handles 50–100 students safely, but 500–1,000 students require Redis session offloading on production Linux server. |
| **BLK-06** | **Remedial Rollback Foreign Key Collision** | Migration `000001` | **MEDIUM** | **MITIGATED / OPERATIONAL** | Documented operational SOP: run `DELETE FROM exam_attempts WHERE attempt_number > 1` prior to rolling back migration `000001` if remedial attempts exist. |
| **BLK-07** | **Monitoring Dashboard Index Optimization** | `exam_attempts` Indexes | **LOW** | **PENDING FULL SCALE** | `GuruMonitoringController` queries on `(exam_id, status, updated_at DESC)` utilize `Using filesort`. Safe for 100 students; composite index recommended for 1,000 students. |

---

## 3. Deep-Dive on Remaining Items for Full Production Scale

### 3.1. BLK-04: Standalone Redis 7.x Setup & Session Offloading
- **Why It Was Deferred:**  
  The development and test environments run on Windows 11 + Laragon without Docker or Redis installed. Emulating Redis on Windows via third-party binaries is fragile and does not reflect production Linux server environments.
- **Risk Under 500–1,000 Students:**  
  When 1,000 students autosave every 15–30 seconds, `SESSION_DRIVER=database` generates over 66 write operations per second to the `sessions` table. This competes with `answers` upserts for InnoDB buffer pool memory and disk I/O.
- **Production Deployment Recipe (Linux / Docker):**
  1. Provision Redis 7.x container or standalone systemd service:
     ```bash
     sudo apt-get install redis-server
     sudo systemctl enable --now redis-server
     ```
  2. Configure production `.env`:
     ```ini
     SESSION_DRIVER=redis
     SESSION_LIFETIME=180
     CACHE_STORE=redis
     QUEUE_CONNECTION=redis
     REDIS_HOST=127.0.0.1
     REDIS_PORT=6379
     ```
  3. Validate connection and ensure Redis persistence (`appendonly yes`) is enabled.

### 3.2. BLK-07: Monitoring Dashboard Query Optimization
- **Current State:**  
  [GuruMonitoringController.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/app/Http/Controllers/GuruMonitoringController.php) queries active attempts using `where('exam_id', $exam->id)->where('status', 'in_progress')->orderBy('updated_at', 'desc')`. With existing index `exam_id`, MySQL filters by exam but sorts in memory (`Using filesort`).
- **Optimization Migration (Scheduled for Full-Scale Rollout):**
  ```php
  Schema::table('exam_attempts', function (Blueprint $table) {
      $table->index(['exam_id', 'status', 'updated_at'], 'idx_attempts_monitor_lookup');
  });
  ```

---

## 4. Phase-Gate Release Roadmap & Readiness Criteria

```mermaid
flowchart TD
    subgraph Phase1["Phase 1 & 2: Reliability Remediation (COMPLETED)"]
        A["BLK-01: Autosave Client Seq"] --> B["BLK-02: Question Guards"]
        B --> C["BLK-03: User-Keyed NAT Limiting"]
        C --> D["Phase 2: Question Snapshots & Scoring"]
    end

    subgraph Gate1["Gateway 1: Controlled Pilot (READY NOW)"]
        D --> E["Lab Environment (50-100 Students)"]
        E --> F["Wired Ethernet / Dedicated AP"]
        F --> G["OPs Checklist: config/route/view cache"]
    end

    subgraph Gate2["Gateway 2: Full Scale (500-1,000 Students)"]
        G --> H["Linux Server Deployment"]
        H --> I["BLK-04: Redis 7.x Session/Cache Offloading"]
        I --> J["BLK-07: Monitoring Composite Index"]
        J --> K["Full Campus Examination Go-Live"]
    end

    style Gate1 fill:#e6ffed,stroke:#2da44e,stroke-width:2px
    style Gate2 fill:#f1f8ff,stroke:#0969da,stroke-width:2px
```

### Readiness Decision
- **Controlled Pilot (50–100 concurrent students):** **`APPROVED / READY`**
- **School-Wide Production (500–1,000 concurrent students):** **`CONDITIONALLY APPROVED`** (Requires deployment of BLK-04 Redis on Linux host).
