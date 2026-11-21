# CBT MODULE DEPENDENCY MAP & INTERACTION MATRIX
**Project:** SMART-CBT | **Date:** November 2026 | **Auditor & Lead Architect:** Principal Software Architect  

---

## 1. COMPONENT DEPENDENCY GRAPH

```mermaid
graph TD
    subgraph HTTP_Entry["HTTP & API Entry Layer"]
        WebRoutes["routes/web.php"]
        ApiRoutes["routes/api.php"]
        Middleware["App/Http/Middleware<br/>(Auth, Siswa, Guru, Admin, ServiceAuth)"]
    end

    subgraph Controllers["Controller Layer (To be Slimmed)"]
        SiswaCtrl["SiswaExamController"]
        ExamCtrl["ExamController"]
        GuruExamCtrl["GuruExamController"]
        MonitorCtrl["GuruMonitoringController"]
        TokenCtrl["GuruTokenController"]
        QuestionCtrl["QuestionController"]
        QBankCtrl["QuestionBankController"]
        ResultCtrl["ResultController"]
        ApiCtrl["Api/V1/CbtApiController"]
    end

    subgraph Services["Target Service Layer (Domain Orchestration)"]
        Svc_Lifecycle["Examination/ExamLifecycleService"]
        Svc_Attempt["Examination/ExamAttemptService"]
        Svc_Question["Examination/QuestionService"]
        Svc_Scoring["Examination/ScoringService"]
        Svc_Answer["Answer/AnswerService"]
        Svc_Token["Security/TokenService"]
        Svc_Sync["Integration/MasterDataSyncService"]
    end

    subgraph Models["Eloquent Domain Models"]
        M_Exam["Exam"]
        M_Attempt["ExamAttempt"]
        M_Answer["Answer"]
        M_Question["Question"]
        M_QBank["QuestionBank & BankQuestion"]
        M_Siswa["Siswa & User"]
        M_Kelas["Kelas & MataPelajaran"]
    end

    subgraph External["External Services & Datastores"]
        SmartAPI["SMART+ Platform API (/api/v1)"]
        MySQL_CBT[("smart_cbt Database")]
        Redis_Store[("Redis (Sessions & Cache)")]
    end

    %% Route connections
    WebRoutes --> Middleware
    ApiRoutes --> Middleware
    Middleware --> SiswaCtrl
    Middleware --> ExamCtrl
    Middleware --> GuruExamCtrl
    Middleware --> MonitorCtrl
    Middleware --> TokenCtrl
    Middleware --> QuestionCtrl
    Middleware --> QBankCtrl
    Middleware --> ResultCtrl
    Middleware --> ApiCtrl

    %% Controller to Service
    SiswaCtrl --> Svc_Attempt
    SiswaCtrl --> Svc_Answer
    SiswaCtrl --> Svc_Scoring
    ExamCtrl --> Svc_Lifecycle
    TokenCtrl --> Svc_Token
    QuestionCtrl --> Svc_Question
    QBankCtrl --> Svc_Question
    ResultCtrl --> Svc_Scoring
    ApiCtrl --> Svc_Lifecycle
    ApiCtrl --> Svc_Scoring

    %% Service to Model
    Svc_Lifecycle --> M_Exam
    Svc_Attempt --> M_Attempt
    Svc_Attempt --> M_Exam
    Svc_Answer --> M_Answer
    Svc_Scoring --> M_Attempt
    Svc_Scoring --> M_Answer
    Svc_Question --> M_Question
    Svc_Question --> M_QBank
    Svc_Token --> M_Exam
    Svc_Sync --> M_Siswa
    Svc_Sync --> M_Kelas
    Svc_Sync --> SmartAPI

    %% Model to DB
    M_Exam --> MySQL_CBT
    M_Attempt --> MySQL_CBT
    M_Answer --> MySQL_CBT
    M_Question --> MySQL_CBT
    M_Siswa --> MySQL_CBT
```

---

## 2. DATA FLOW & LIFECYCLE DEPENDENCIES

### 2.1 Student Examination Lifecycle
1. **Start Attempt:**
   - Dependent on: `User` -> `Siswa` (must be `status_aktif = true`) -> `Exam` (must be `status = 'aktif'`, `token_aktif = true`, `questions()->exists()`, date valid).
   - Generates: `ExamAttempt` (`status = 'in_progress'`, `started_at = now()`, `token_used`).
   - Session State: Verified attempt ID in session `siswa.exam_token_verified.{exam_id}`.
2. **Question Delivery:**
   - Dependent on: `ExamAttempt` (`status = 'in_progress'`), `Exam::questions`.
   - Sequencing: Shuffled by `question_order` array (deterministic per attempt).
3. **Autosave Persistence:**
   - Dependent on: `ExamAttempt` (`status = 'in_progress'`, `deadline > now() - 10m`).
   - Writes to: `answers` via atomic bulk upsert (`exam_attempt_id`, `question_id`, `jawaban`).
4. **Final Submission:**
   - Dependent on: `ExamAttempt` (`status = 'in_progress'`).
   - Writes to: `answers` (all answers synced), `exam_attempts` (`status = 'submitted'` or `'needs_grading'`, `submitted_at = now()`, `nilai_akhir`).

### 2.2 SMART+ Integration Contract
- **CBT Exposure:**
  - `GET /api/v1/exams`: Consumed by SMART+ `CbtIntegrationService::fetchAvailableExams`.
  - `GET /api/v1/exam-results`: Consumed by SMART+ `CbtIntegrationService::syncCbtViaApi`.
- **Contract Schema Requirements:**
  - Each item MUST contain: `id`, `attempt_external_id`, `exam_external_id`, `student_external_id`, `student_nisn`, `student_nis`, `score`, `status`, `started_at`, `submitted_at`.
  - Breaking any field in this response breaks the E-Rapor gradebook import on SMART+.
