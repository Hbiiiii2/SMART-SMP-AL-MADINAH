# SMART-CBT INITIAL REPOSITORY & ARCHITECTURE AUDIT

**Audit Date:** November 12, 2026  
**Auditor:** Principal Software Architect, DevOps & Database Reliability Engineer  
**Scope:** Repository discovery, database schema, architecture integrity, critical mutation pathways, and Docker configuration before containerized validation.  

---

## 1. Executive Summary

An architectural and codebase audit was conducted across the SMART-CBT repository on branch `remediation/engineering-remediation`.

While the application demonstrates robust core business logic with passing local unit and feature tests, **significant infrastructure discrepancies were identified in the existing Docker configuration**:
1. The existing `docker-compose.yml` configures `postgres:16`, whereas the entire codebase and test suite are architected for **MySQL 8.4 InnoDB**.
2. The `Dockerfile` strips developer dependencies via `--no-dev`, preventing regression test execution inside the container.
3. `phpunit.xml` enforces `DB_HOST=127.0.0.1` with `force="true"`, preventing containerized tests from discovering the MySQL service.
4. Redis 7 service, Queue Worker, and k6 load testing services are completely absent from `docker-compose.yml`.

---

## 2. Classified Audit Findings

| Priority | ID | Finding / Architectural Risk | Component | Severity | Description |
|---|---|---|---|---|---|
| **P1** | **AUD-01** | **Docker Database Engine Mismatch (Postgres vs MySQL)** | `docker-compose.yml`, `.env.docker` | **CRITICAL** | Compose defines `postgres:16` and `.env.docker` sets `DB_CONNECTION=pgsql`. The application migrations and index optimizations require MySQL 8.4 InnoDB. |
| **P1** | **AUD-02** | **Composer `--no-dev` Blocks Container Testing** | `Dockerfile` | **CRITICAL** | Multi-stage build executes `composer install --no-dev`, removing Pest/PHPUnit from the container. |
| **P1** | **AUD-03** | **Hardcoded `force="true"` in `phpunit.xml`** | `phpunit.xml` | **HIGH** | `DB_HOST` is forced to `127.0.0.1`, preventing containerized test discovery of the `smart-cbt-db` service. |
| **P2** | **AUD-04** | **Missing Redis, Queue Worker, and k6 Services** | `docker-compose.yml` | **HIGH** | Redis 7, asynchronous queue worker, and k6 containers are missing from compose configuration. |
| **P2** | **AUD-05** | **Query Builder Bypass of Question Mutation Guards** | `Question.php` / Eloquent | **MEDIUM** | Eloquent model events (`static::updating`, `static::deleting`) do not fire on direct Query Builder queries (`DB::table('questions')->where(...)->update(...)`). Mitigated for new attempts by `questions_snapshot`. |
| **P2** | **AUD-06** | **Multi-Device Same-Question Edit Race (LSW)** | `work.blade.php` / `AnswerService` | **MEDIUM** | If a student opens two devices simultaneously and modifies the *same* question, the higher sequence will overwrite the lower sequence without three-way conflict merging. |
| **P3** | **AUD-07** | **Monitoring Dashboard `Using filesort`** | `GuruMonitoringController.php` | **LOW** | Filtering on `exam_id` and `status` while sorting by `created_at desc` relies on `filesort` in MySQL. |

---

## 3. Database Architecture Detailed Audit

### 3.1 Primary & Foreign Keys
- `users`: Standard bigint auto-increment primary key.
- `exams`: Foreign key `guru_id` references `gurus(id)` with `RESTRICT` on delete to preserve historical exams.
- `questions`: Foreign key `exam_id` references `exams(id)` with `CASCADE` on delete.
- `exam_attempts`:
  - Foreign key `exam_id` references `exams(id)` with `CASCADE` on delete.
  - Foreign key `siswa_id` references `siswas(id)` with `RESTRICT` on delete.
  - Unique composite index `exam_attempts_exam_siswa_attempt_unique` on `(exam_id, siswa_id, attempt_number)`.
- `answers`:
  - Foreign key `exam_attempt_id` references `exam_attempts(id)` with `CASCADE` on delete.
  - Foreign key `question_id` references `questions(id)` with `RESTRICT` on delete (hardened in migration `000001`).
  - Unique composite index on `(exam_attempt_id, question_id)` guarantees at most one answer per question per attempt.

### 3.2 Index Coverage
- `exam_attempts`:
  - `idx_attempts_exam_status` (`exam_id`, `status`)
  - `idx_attempts_status_updated` (`status`, `updated_at`)
  - `idx_attempts_siswa_status` (`siswa_id`, `status`)
  - `idx_attempts_started_at` (`started_at`)
- `exams`:
  - `idx_exams_kelas_status_tgl` (`kelas_id`, `status`, `tanggal_mulai`)
  - `idx_exams_token_rotate` (`token_aktif`, `token_kedaluwarsa_at`)

---

## 4. Remediation Plan Before Container Launch

1. **Re-architect Docker Environment:**
   - Replace PostgreSQL with `mysql:8.4` (InnoDB, dedicated application credentials `smart_cbt` / `smart_cbt_password`).
   - Add `redis:7-alpine` container (`smart-redis`).
   - Add Queue Worker container (`smart-cbt-queue`).
   - Add k6 testing container (`smart-cbt-k6`).
   - Update `docker-compose.yml` to use standard internal named volumes and internal network `smart-network`.
2. **Update `Dockerfile`:**
   - Retain dev dependencies or add Composer into the image to support `php artisan test` inside the container.
   - Install required extensions: `pdo_mysql`, `redis`, `gd`, `bcmath`, `mbstring`, `zip`, `intl`, `opcache`, `pcntl`.
3. **Update `phpunit.xml`:**
   - Remove `force="true"` from database environment variables so container-injected variables (`DB_HOST=smart-cbt-db`) take precedence during testing.
4. **Update `.env.docker` & `.env.docker.example`:**
   - Configure `DB_CONNECTION=mysql`, `DB_HOST=smart-cbt-db`, `DB_PORT=3306`, `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`.
