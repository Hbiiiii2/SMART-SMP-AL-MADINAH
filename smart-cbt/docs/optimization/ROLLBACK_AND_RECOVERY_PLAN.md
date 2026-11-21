# SMART-CBT — Rollback Procedures & Disaster Recovery Plan

## 1. Executive Summary

This operational runbook provides contingency procedures, database rollback commands, and disaster recovery workflows for **SMART-CBT**. 

Because examination data is legally and academically sensitive, all recovery procedures prioritize:
1. **Preserving student answers already persisted in MySQL**.
2. **Recovering in-flight answers stored in client LocalStorage**.
3. **Minimizing examination disruption for active examinees**.

---

## 2. Codebase Rollback Procedures

### 2.1 Git Reversion Strategy
All optimizations applied during this remediation are tracked on branch `remediation/engineering-remediation`.

To revert backend or frontend components to the pre-optimization state:

```bash
# Check working directory status
git status

# To revert specific services without affecting other modules:
git checkout origin/remediation/engineering-remediation -- app/Services/Answer/AnswerService.php
git checkout origin/remediation/engineering-remediation -- app/Services/Examination/ExamAttemptService.php
git checkout origin/remediation/engineering-remediation -- resources/views/siswa/exams/work.blade.php
git checkout origin/remediation/engineering-remediation -- config/database.php

# Clear configuration, route, and view caches across all application instances:
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 2.2 Docker Container Synchronization
If running inside a multi-container Docker cluster:
```bash
# Sync reverted files to PHP-FPM containers
docker cp app/Services/Answer/AnswerService.php smart-cbt-app:/var/www/html/app/Services/Answer/AnswerService.php
docker cp app/Services/Answer/AnswerService.php smart-cbt-app-2:/var/www/html/app/Services/Answer/AnswerService.php
docker cp app/Services/Answer/AnswerService.php smart-cbt-app-3:/var/www/html/app/Services/Answer/AnswerService.php

# Restart PHP-FPM processes gracefully
docker exec smart-cbt-app kill -USR2 1
docker exec smart-cbt-app-2 kill -USR2 1
docker exec smart-cbt-app-3 kill -USR2 1
```

---

## 3. Database Contingency & Backup Runbook

### 3.1 Pre-Exam Snapshot Backup
Always execute a consistent snapshot dump before an examination begins:
```bash
mysqldump -u smart_cbt -psmart_cbt_password \
  --single-transaction \
  --quick \
  --triggers \
  --routines \
  --databases smart_cbt > /backup/smart_cbt_pre_exam_$(date +%Y%m%d_%H%M%S).sql
```

### 3.2 Transaction Isolation Fallback
If for any reason `READ-COMMITTED` needs to be reverted to `REPEATABLE-READ`:
1. In `config/database.php`, remove the line:
   ```php
   \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED',
   ```
2. Clear the configuration cache:
   ```bash
   php artisan config:clear
   ```
3. Restart MySQL or reload connections.

### 3.3 Redis Cache Flush
If stale idempotency keys or rate limiter counters lock out legitimate users:
```bash
# Flush idempotency and limiter keys only (without dropping session data)
redis-cli --scan --pattern "cbt:idempotency:*" | xargs -r redis-cli del
redis-cli --scan --pattern "*exam-autosave*" | xargs -r redis-cli del

# Full emergency flush (if necessary)
redis-cli FLUSHDB
```

---

## 4. Disaster Recovery Scenarios

### Scenario A: School Wi-Fi or Internet Outage
- **Symptoms**: Autosave status displays `Gagal menyimpan (percobaan X/4)`.
- **System Behavior**:
  1. Frontend halts automatic retry flooding; enters exponential backoff with jitter (up to 10s intervals).
  2. All student keystrokes continue to be safely stored in the browser's `localStorage` journal (`cbt_exam_answers_{id}`).
  3. Students can continue answering remaining questions without disruption.
- **Proctor Action**:
  - Instruct students **NOT to refresh or close their browsers**.
  - Once Wi-Fi is restored, the frontend automatically detects connectivity and flushes the entire pending delta queue.
  - The status will turn green (`Tersimpan`) across all student screens.

### Scenario B: Computer Lab Power Failure / Machine Crash
- **Symptoms**: A computer abruptly powers off while a student is in the middle of an exam.
- **System Behavior**:
  1. The student's attempt remains in `in_progress` status on the server.
  2. All answers previously acknowledged by the server are already committed to MySQL `answers` table.
- **Recovery Procedure**:
  1. Move the student to a working computer or power on the machine.
  2. Have the student log in again at `/login`.
  3. Access `/siswa/ujian/{id}` and enter the exam token.
  4. `ExamAttemptService::startOrResumeAttempt` detects the existing attempt, updates `token_used`, and redirects to `/siswa/ujian/{id}/kerjakan`.
  5. The student's previously saved answers are loaded from the database into the exam view.
  6. The timer resumes with the remaining time based on the authoritative `started_at` timestamp.

### Scenario C: Unsent Answers on Crashed Browser (Manual Extraction)
If a student completed answers while offline, and the computer cannot connect to the server before the exam ends:
1. Open Chrome / Edge Developer Tools on the student's machine (`F12` $\rightarrow$ `Console`).
2. Run the recovery extraction script in the console:
   ```javascript
   const examId = window.location.pathname.split('/')[3] || '709';
   console.log("LOCAL ANSWERS:", localStorage.getItem('cbt_exam_answers_' + examId));
   console.log("LOCAL SEQUENCES:", localStorage.getItem('cbt_exam_seq_' + examId));
   ```
3. Copy the resulting JSON string and import it via the Teacher / Administrator Exam Management interface.

---

## 5. Verification Checklist After Rollback or Recovery

After any rollback or disaster recovery operation, verify the following before resuming examinations:

- [ ] `php artisan test --filter=Autosave` passes 100%.
- [ ] Database connection check: `SELECT @@transaction_isolation;` returns the expected level.
- [ ] Redis responds to `PING` with `PONG`.
- [ ] Rate limiters are functioning: verify rate limiter keys use `student:{id}` format.
- [ ] Proctor dashboard displays all active students in `in_progress` status.
- [ ] MySQL query confirms answer counts match active students:
  ```sql
  SELECT COUNT(DISTINCT exam_attempt_id) AS active_attempts, COUNT(*) AS total_answers FROM answers;
  ```
