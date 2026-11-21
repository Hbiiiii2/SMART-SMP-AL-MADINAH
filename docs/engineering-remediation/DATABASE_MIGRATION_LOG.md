# Database Migration Log
## SMART-CBT Engineering Remediation

This log documents all database schema modifications, integrity hardening migrations, pre-flight checks, and rollback strategies executed during the engineering remediation.

---

### 1. Migration Inventory

| Migration File | Target Table(s) | Type | Purpose | Status |
|---|---|---|---|---|
| `2026_11_12_000001_harden_cbt_foreign_keys_and_cascade_rules.php` | `exams`, `answers`, `exam_attempts` | DDL / Constraint | Change `exams.guru_id` and `answers.question_id` to `restrictOnDelete()`. Update unique constraint on `exam_attempts` from `(exam_id, siswa_id)` to `(exam_id, siswa_id, attempt_number)`. | Applied & Verified |
| `2026_11_12_000002_add_question_order_and_essay_scores_to_exam_attempts.php` | `exam_attempts` | Schema Extension | Add `question_order` (JSON), `nilai_pg` (decimal 5,2), and `nilai_essay` (decimal 5,2). | Applied & Verified |
| `2026_11_12_000003_add_performance_composite_indexes.php` | `exam_attempts`, `exams` | Performance DDL | Add composite indexes for student dashboards, autosave monitoring, token rotation, and teacher result filtering. | Applied & Verified |

---

### 2. Pre-Flight Verification & Foreign Key Nuances

#### Challenge Encountered: MySQL Error 1553
During development of migration `000001`, attempting to drop unique index `exam_attempts_exam_id_siswa_id_unique` resulted in:
`SQLSTATE[HY000]: General error: 1553 Cannot drop index 'exam_attempts_exam_id_siswa_id_unique': needed in a foreign key constraint`

#### Technical Root Cause
MySQL requires an index on any column referenced by a foreign key. The original foreign key `exam_attempts_exam_id_foreign` was sharing the leading column of `(exam_id, siswa_id)`. Dropping the index without first dropping the foreign key violated MySQL constraint validation.

#### Remediation Strategy Implemented in Migration
```php
Schema::table('exam_attempts', function (Blueprint $table) {
    $table->dropForeign(['exam_id']);
    $table->dropUnique('exam_attempts_exam_id_siswa_id_unique');
    $table->unique(['exam_id', 'siswa_id', 'attempt_number'], 'exam_attempts_exam_siswa_attempt_unique');
    $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnDelete();
});
```

---

### 3. Detailed Schema Alterations

#### A. Foreign Key Hardening
- **`exams.guru_id`**: Previously cascaded deletes. If a teacher record was deleted or re-assigned, all exams and student submissions were permanently destroyed. Updated to `restrictOnDelete()`. An exam cannot be deleted while assigned to an active teacher.
- **`answers.question_id`**: Previously cascaded deletes. Deleting a question from a bank or exam deleted historical student answer records. Updated to `restrictOnDelete()`.

#### B. Exam Attempt Lifecycle Columns
```sql
ALTER TABLE `exam_attempts` 
    ADD COLUMN `question_order` JSON NULL AFTER `token_used`,
    ADD COLUMN `nilai_pg` DECIMAL(5,2) NULL AFTER `nilai_akhir`,
    ADD COLUMN `nilai_essay` DECIMAL(5,2) NULL AFTER `nilai_pg`;
```

#### C. Composite Indexes Created
```sql
-- High-frequency teacher live monitoring query
CREATE INDEX `idx_attempts_exam_status` ON `exam_attempts` (`exam_id`, `status`);

-- Online status heartbeat query (updated_at >= now - 5 mins)
CREATE INDEX `idx_attempts_status_updated` ON `exam_attempts` (`status`, `updated_at`);

-- Student dashboard query
CREATE INDEX `idx_attempts_siswa_status` ON `exam_attempts` (`siswa_id`, `status`);
CREATE INDEX `idx_attempts_started_at` ON `exam_attempts` (`started_at`);

-- Available exams for class query
CREATE INDEX `idx_exams_kelas_status_tgl` ON `exams` (`kelas_id`, `status`, `tanggal_mulai`);

-- Background token rotation cron scheduler
CREATE INDEX `idx_exams_token_rotate` ON `exams` (`token_aktif`, `token_expired_at`);
CREATE INDEX `idx_exams_status` ON `exams` (`status`);
```

---

### 4. Rollback Plan

Every migration includes a fully tested `down()` method:
1. `2026_11_12_000001`: Restores `cascadeOnDelete()` on `exams.guru_id` and `answers.question_id`, drops the 3-column unique key, and restores `(exam_id, siswa_id)` unique key.
2. `2026_11_12_000002`: Drops columns `question_order`, `nilai_pg`, and `nilai_essay`.
3. `2026_11_12_000003`: Drops all 7 named indexes using `dropIndex(['idx_attempts_...'])`.

---

### 5. Production Safety Verification

Prior to executing migrations on `smart_cbt`:
- Checked total database records: 81 students, 14 teachers, 3 classes.
- Zero records truncated or dropped.
- Tested migrations reversibility on isolated test database `smart_cbt_testing`.
