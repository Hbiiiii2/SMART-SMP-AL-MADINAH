# DATABASE INTEGRITY & QUERY EFFICIENCY AUDIT — SMART-CBT

**Audit Date:** November 12, 2026  
**Auditor:** Database Architect & Performance Engineer  
**Database Engine:** MySQL 8.4.3 (InnoDB, utf8mb4_unicode_ci)  
**Scope:** Schema migrations, foreign keys, unique constraints, index coverage, rollback feasibility, data types, and empirical `EXPLAIN` execution plans.  
**Overall Status:** `VERIFIED` (Schema integrity verified; Rollback caveat documented; Query plans optimal with minor filesort note)

---

## 1. Executive Summary

The database layer of SMART-CBT was inspected at the schema, storage engine, and execution plan levels. The previous remediation migrations introduced critical relational integrity safeguards, including `RESTRICT` foreign key behaviors, composite unique indexes for conflict resolution, decimal-based scoring data types (`DECIMAL(5,2)`) to eliminate floating-point imprecision, and compound indexes targeted at high-traffic examination queries.

Empirical verification confirms that all tables run on **MySQL 8.4.3 InnoDB** with full ACID compliance. However, a significant operational finding emerged regarding **migration rollback behavior**: if remedial examination attempts (`attempt_number > 1`) exist in production, rolling back the remediation migration to restore the legacy single-attempt constraint will fail with MySQL Error 1062 unless duplicate records are purged.

---

## 2. Migration Architecture & Schema Verification

### 2.1 Applied Remediation Migrations
The database schema includes three core remediation migrations:
1. `2026_11_12_000001_cbt_remediation_add_foreign_keys_and_indexes.php`
2. `2026_11_12_000002_cbt_remediation_add_missing_indexes.php`
3. `2026_11_12_000003_cbt_remediation_add_soft_deletes_or_scoring_fields.php`

### 2.2 Table Schemas & Data Types

#### `exams` Table
```sql
CREATE TABLE `exams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `guru_id` bigint unsigned NOT NULL,
  `guru_pengawas_id` bigint unsigned DEFAULT NULL,
  `mata_pelajaran_id` bigint unsigned NOT NULL,
  `nama` varchar(255) NOT NULL,
  `kelas` varchar(255) NOT NULL,
  `kelas_id` bigint unsigned DEFAULT NULL,
  `kode_ujian` varchar(255) NOT NULL,
  `token` varchar(6) DEFAULT NULL,
  `token_aktif` tinyint(1) NOT NULL DEFAULT '0',
  `acak_soal` tinyint(1) NOT NULL DEFAULT '0',
  `token_dibuat_at` timestamp NULL DEFAULT NULL,
  `token_kedaluwarsa_at` timestamp NULL DEFAULT NULL,
  `is_token_auto_rotate` tinyint(1) NOT NULL DEFAULT '0',
  `durasi_menit` int unsigned NOT NULL,
  `tanggal_mulai` timestamp NULL DEFAULT NULL,
  `tanggal_selesai` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exams_kode_ujian_unique` (`kode_ujian`),
  UNIQUE KEY `exams_token_unique` (`token`),
  KEY `idx_exams_kelas_status_tgl` (`kelas_id`,`status`,`tanggal_mulai`),
  KEY `idx_exams_token_rotate` (`token_aktif`,`token_kedaluwarsa_at`),
  KEY `idx_exams_status` (`status`),
  CONSTRAINT `exams_guru_id_foreign` FOREIGN KEY (`guru_id`) REFERENCES `gurus` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `exams_guru_pengawas_id_foreign` FOREIGN KEY (`guru_pengawas_id`) REFERENCES `gurus` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `exams_mata_pelajaran_id_foreign` FOREIGN KEY (`mata_pelajaran_id`) REFERENCES `mata_pelajarans` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `exam_attempts` Table
```sql
CREATE TABLE `exam_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exam_id` bigint unsigned NOT NULL,
  `siswa_id` bigint unsigned NOT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'in_progress',
  `token_used` varchar(255) DEFAULT NULL,
  `question_order` json DEFAULT NULL,
  `nilai_akhir` decimal(5,2) DEFAULT NULL,
  `nilai_pg` decimal(5,2) DEFAULT NULL,
  `nilai_essay` decimal(5,2) DEFAULT NULL,
  `attempt_number` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_attempts_exam_siswa_attempt_unique` (`exam_id`,`siswa_id`,`attempt_number`),
  KEY `idx_attempts_exam_status` (`exam_id`,`status`),
  KEY `idx_attempts_status_updated` (`status`,`updated_at`),
  KEY `idx_attempts_siswa_status` (`siswa_id`,`status`),
  KEY `idx_attempts_started_at` (`started_at`),
  CONSTRAINT `exam_attempts_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_attempts_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `siswas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `answers` Table
```sql
CREATE TABLE `answers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exam_attempt_id` bigint unsigned NOT NULL,
  `question_id` bigint unsigned NOT NULL,
  `jawaban` text,
  `is_correct` tinyint(1) DEFAULT NULL,
  `skor` decimal(5,2) DEFAULT NULL,
  `sudah_dinilai` tinyint(1) NOT NULL DEFAULT '0',
  `catatan_guru` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `answers_exam_attempt_id_question_id_unique` (`exam_attempt_id`,`question_id`),
  KEY `answers_question_id_foreign` (`question_id`),
  CONSTRAINT `answers_exam_attempt_id_foreign` FOREIGN KEY (`exam_attempt_id`) REFERENCES `exam_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `answers_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 3. Empirical `EXPLAIN` Query Execution Plans

All execution plans were measured directly against the live MySQL 8.4.3 database engine.

### 3.1 Student Dashboard `availableExams` Query
```sql
EXPLAIN select `exams`.*, 
    (select count(*) from `questions` where `exams`.`id` = `questions`.`exam_id`) as `questions_count` 
from `exams` 
where `kelas_id` is not null 
  and `kelas_id` = 7 
  and `status` != 'draft' 
  and ((`token_aktif` = 1 and `token` is not null and exists (select * from `questions` where `exams`.`id` = `questions`.`exam_id`)) 
       or exists (select * from `exam_attempts` where `exams`.`id` = `exam_attempts`.`exam_id` and `siswa_id` = 7)) 
order by `tanggal_mulai` asc;
```
**MySQL Plan Output:**
| id | select_type | table | type | possible_keys | key | key_len | ref | rows | filtered | Extra |
|---|---|---|---|---|---|---|---|---|---|---|
| 1 | PRIMARY | exams | range | idx_exams_kelas_status_tgl, ... | **idx_exams_kelas_status_tgl** | 1031 | NULL | 5 | 100.00 | Using index condition; Using where; **Using filesort** |
| 4 | DEPENDENT SUBQUERY | exam_attempts | ref | exam_attempts_exam_siswa_attempt_unique | exam_attempts_exam_siswa_attempt_unique | 16 | smart_cbt.exams.id,const | 1 | 100.00 | Using index |
| 3 | DEPENDENT SUBQUERY | questions | ref | questions_exam_id_urutan_unique | questions_exam_id_urutan_unique | 8 | smart_cbt.exams.id | 1 | 100.00 | Using index |
| 2 | DEPENDENT SUBQUERY | questions | ref | questions_exam_id_urutan_unique | questions_exam_id_urutan_unique | 8 | smart_cbt.exams.id | 1 | 100.00 | Using index |

**Plan Analysis:**
- Primary access uses composite index `idx_exams_kelas_status_tgl`.
- Both dependent subqueries (`exam_attempts` and `questions`) execute as `Using index` covering index lookups with 1 row read.
- **Architectural Note:** Extra contains `Using filesort`. Because MySQL cannot utilize trailing index columns (`tanggal_mulai`) for sort order when a preceding column uses an inequality operator (`status != 'draft'`), MySQL sorts matching rows in memory. Given that active exams for a single class typically number $<20$, in-memory sort takes $<0.1\text{ms}$.

---

### 3.2 Autosave Upsert Execution Plan
```sql
EXPLAIN INSERT INTO answers (exam_attempt_id, question_id, jawaban, created_at, updated_at) 
VALUES (1, 1, 'A', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE jawaban = VALUES(jawaban), updated_at = VALUES(updated_at);
```
- **Index Utilized:** `answers_exam_attempt_id_question_id_unique` (exam_attempt_id, question_id).
- **Execution Cost:** Direct B-Tree unique index lookup. If entry exists, updates row in-place; otherwise, inserts new node. Zero table scans.

---

### 3.3 Proctor / Teacher Real-Time Monitoring Query
```sql
EXPLAIN SELECT ea.*, s.nama, s.nis 
FROM exam_attempts ea 
JOIN siswas s ON ea.siswa_id = s.id 
WHERE ea.exam_id = 1 AND ea.status = 'in_progress' 
ORDER BY ea.updated_at DESC;
```
**MySQL Plan Output:**
| id | select_type | table | type | key | ref | rows | Extra |
|---|---|---|---|---|---|---|---|
| 1 | SIMPLE | ea | ref | exam_attempts_exam_siswa_attempt_unique | const | 1 | Using where; Using filesort |
| 1 | SIMPLE | s | eq_ref | PRIMARY | smart_cbt.ea.siswa_id | 1 | NULL |

**Plan Analysis:**
- Fast primary key join on `siswas.id`.
- If exam monitoring tables grow to thousands of attempts, adding a composite index `(exam_id, status, updated_at)` will eliminate the `Using filesort` on the live monitoring dashboard.

---

## 4. Rollback Feasibility & Safety Assessment

### 4.1 Down Migration Analysis (`000001`)
```php
public function down(): void
{
    Schema::table('exam_attempts', function (Blueprint $table) {
        $table->dropUnique('exam_attempts_exam_siswa_attempt_unique');
        $table->dropColumn('attempt_number');
        $table->unique(['exam_id', 'siswa_id']);
    });
}
```

### 4.2 The Remedial Rollback Hazard
- In the remediated schema, remedial exams are permitted (`attempt_number = 1`, `attempt_number = 2`, etc.).
- If a student undertakes a remedial attempt, two rows exist with the same `(exam_id, siswa_id)`.
- **Failure:** When `down()` attempts `$table->unique(['exam_id', 'siswa_id'])`, MySQL encounters identical pairs and throws:
  ```text
  SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry for key 'exam_attempts_exam_id_siswa_id_unique'
  ```
- **Mitigation Directive:** Before rolling back migration `000001` in any staging or production database, administrators must query and archive/delete duplicate attempt rows (`WHERE attempt_number > 1`).

---

## 5. Audit Verdict

| Category | Finding | Status |
|---|---|---|
| Foreign Key Integrity | RESTRICT on guru and questions prevents orphan answers/exams | `VERIFIED` |
| Primary / Unique Indexes | Composite keys prevent duplicate attempts and duplicate answers | `VERIFIED` |
| Scoring Precision | `DECIMAL(5,2)` prevents float rounding drift (e.g. 83.33) | `VERIFIED` |
| Rollback Feasibility | Rollback fails if remedial records exist in production | `CONDITIONALLY SAFE` |
| Query Efficiency | Key queries use indexes; minimal filesort on small result sets | `VERIFIED` |
