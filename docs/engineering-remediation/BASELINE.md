# ENGINEERING REMEDIATION: SYSTEM BASELINE & INVENTORY
**Project:** SMART-CBT (SMP Al-Madinah Examination System)  
**Date:** November 2026 | **Auditor & Lead Architect:** Principal Software Architect  
**Branch:** `remediation/engineering-remediation`

---

## 1. RUNTIME & HOST ENVIRONMENT

| Component | Current Reality / Verified State | Specification in Config / Docs |
|---|---|---|
| **OS** | Windows 10/11 (Laragon Environment) | Windows Server / Linux VPS Docker |
| **PHP Binary** | PHP 8.4.26 CLI (`C:\laragon\bin\php\php-8.4.26-Win32-vs17-x64\php.exe`) | Declared `^8.3` in `composer.json` (PHP 8.4 required by vendor) |
| **Framework** | Laravel Framework `13.26.1` (`smart-cbt`), Laravel `12.26.4` (`smart+`) | Laravel 11/12/13 modular monolith |
| **Primary CBT Database** | MySQL 8.0 (127.0.0.1:3306, `smart_cbt`) | MySQL 8.0 in Laragon / PostgreSQL 16 in Docker |
| **Testing CBT Database** | MySQL 8.0 (127.0.0.1:3306, `smart_cbt_testing`) | Isolated test database configured via `.env.testing` |
| **Core SMART+ Database**| MySQL 8.0 (127.0.0.1:3306, `smart`) | Master academic records |
| **Session Driver** | `SESSION_DRIVER=database` (sessions table) | Redis configured in Docker, Database in local |
| **Cache Driver** | `CACHE_STORE=database` (cache table) | Redis configured in Docker, Database in local |
| **Queue Driver** | `QUEUE_CONNECTION=database` (jobs table) | Redis configured in Docker, Database in local |

---

## 2. REPOSITORY & CODE INVENTORY

### A. Controllers (`smart-cbt/app/Http/Controllers/`)
1. `SiswaExamController.php` (368 lines): Student dashboard, exam start, question delivery, autosave batch, submit, result.
2. `ExamController.php` (546 lines): Admin exam CRUD, legacy question component management, time combination.
3. `GuruExamController.php` (99 lines): Teacher exam dashboard, exam detail with question bank imports.
4. `GuruMonitoringController.php` (115 lines): Live attempt monitoring with in-memory collection pagination.
5. `GuruTokenController.php` (75 lines): Token generation, activation toggle, auto-rotate status.
6. `QuestionController.php` (312 lines): Question CRUD, image attachments, duplicate, order swap hack.
7. `QuestionBankController.php` (541 lines): Question bank CRUD, Excel import, auto-sync on GET.
8. `ResultController.php` (108 lines): Attempt list and detail view for teachers/admins.
9. `ExamCardController.php` (158 lines): Exam participant card generator and PDF export.
10. `ExportController.php` (245 lines): FastExcel export for questions, results, class recapitulations.
11. `Api/V1/CbtApiController.php` (426 lines): REST API for exams, attempts, results integration with SMART+.
12. `KelasController.php`, `GuruController.php`, `SiswaController.php`, `MataPelajaranController.php`, `PengajaranController.php`, `ProfileController.php`.

### B. Service & Abstraction Layer
- `smart-cbt/app/Services/`: **EMPTY (0 services currently implemented)**.
- `smart-cbt/app/Repositories/`: **EMPTY (0 repositories)**.
- `smart-cbt/app/Policies/`: **EMPTY (0 policies; authorization is manual in controllers)**.
- `smart-cbt/app/Jobs/`: **EMPTY (0 background jobs)**.

### C. Models (`smart-cbt/app/Models/`)
1. `Exam.php`: Fillable fields, casts, token generation, 10-minute rotation, relations.
2. `ExamAttempt.php`: Fields, timestamps, relations to Exam, Siswa, Answers.
3. `Answer.php`: Attempt, Question, jawaban, is_correct, skor, sudah_dinilai, catatan_guru.
4. `Question.php`: Flat options (`opsi_a`..`opsi_e`, images), kunci, bobot, urutan.
5. `QuestionBank.php` & `BankQuestion.php`: Duplicate structure of questions for reuse.
6. `Siswa.php`: Links to `User`, `Kelas`, `ExamAttempt`.
7. `Guru.php`: Links to `User`, `Exam`, `QuestionBank`.
8. `Kelas.php`, `MataPelajaran.php`, `Pengajaran.php`, `ExamCardPrint.php`, `ExamCardSetting.php`, `User.php`.

---

## 3. INITIAL TEST SUITE BASELINE

- **Existing Tests:** 19 test files in `tests/Feature`.
- **Pre-Remediation Passing Status:**
  - `ExampleTest`: Failed initially due to expecting 200 on `/` which redirects to `/login` (302). Fixed baseline.
  - `SiswaExamResumeTest`: Failed initially due to string truncation (`'SAME123'` 7 chars on `VARCHAR(6)` column). Fixed baseline.
- **Database Safety for Tests:** Configured `.env.testing` and `smart_cbt_testing` database to ensure tests never mutate developer or production examination records.
