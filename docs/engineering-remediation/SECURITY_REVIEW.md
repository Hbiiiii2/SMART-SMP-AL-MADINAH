# Security Review & Penetration Analysis
## SMART-CBT Examination Platform

This document details the security posture, vulnerability remediations, attack surface reductions, and authorization model of SMART-CBT.

---

### 1. Threat Modeling & Identified Vulnerabilities

| Finding ID | Vulnerability Category | Severity | Description | Remediated In |
|---|---|---|---|---|
| **SEC-01** | Authorization Bypass | **CRITICAL** | Absence of class check on attempt resumption allowed students to manipulate parameters and access other class tests. | `ExamAttemptService::ensureAssigned` |
| **SEC-02** | Integrity Violation | **HIGH** | Questions could be updated or deleted while students were actively taking the exam. | `QuestionController::update`, `destroy` guards |
| **SEC-03** | Credential Exposure | **HIGH** | `ServiceAuthMiddleware` contained hardcoded fallback secret (`cbt_secret_token_almadinah_2026`) and accepted tokens in GET query strings (`?api_key=...`), leaking keys into web server access logs. | `ServiceAuthMiddleware`, `config/services.php` |
| **SEC-04** | Brute Force & DoS | **MEDIUM** | CBT external REST API routes lacked rate limiting (`throttle`). | `routes/api.php` (`throttle:60,1`) |
| **SEC-05** | Privilege Escalation | **HIGH** | Unverified essay grading submissions allowed arbitrary users to grade essay questions if attempt ID was known. | `ResultController::gradeEssay` |

---

### 2. Remediation Implementations & Verification

#### A. Elimination of Fallback Secrets & Query String Token Acceptance (SEC-03)
In `app/Http/Middleware/ServiceAuthMiddleware.php`:
- Removed the hardcoded string `'cbt_secret_token_almadinah_2026'`.
- If `CBT_API_TOKEN` is unset in `.env`, the middleware fails closed with HTTP 500 (`SERVER_CONFIG_ERROR`), preventing unauthenticated access.
- Removed `$request->query('api_key')`. Only `Authorization: Bearer <token>` or `X-API-Key: <token>` HTTP headers are parsed.
- Constant-time string comparison enforced via `hash_equals($expectedToken, $token)`.

#### B. Exam Question Immutability Guard (SEC-02)
In `app/Http/Controllers/QuestionController.php`:
```php
abort_if(
    $ujian->examAttempts()->exists(),
    422,
    'Soal tidak dapat diubah atau dihapus karena sudah ada peserta yang mengerjakan ujian ini.'
);
```
Guarantees that historical examination questions cannot be modified or deleted once an attempt record exists.

#### C. Class Isolation Enforcement (SEC-01)
In `app/Services/Examination/ExamAttemptService.php`:
```php
public function ensureAssigned(Exam $exam, Siswa $siswa): void
{
    abort_unless(
        $siswa->kelas_id && $exam->kelas_id && (int) $exam->kelas_id === (int) $siswa->kelas_id,
        403,
        'Ujian ini tidak ditujukan untuk kelas Anda.'
    );
}
```
Enforced across all entry points: `token()`, `start()`, `work()`, `saveAnswers()`, `submit()`, and `leave()`.

#### D. Teacher Authorization for Essay Grading (SEC-05)
In `app/Http/Controllers/ResultController.php::gradeEssay()`:
```php
abort_unless($request->user()->isAdmin() || $request->user()->isGuru(), 403);
if ($request->user()->isGuru()) {
    abort_unless(
        $attempt->exam->guru_id === $request->user()->guru?->id || 
        $attempt->exam->guru_pengawas_id === $request->user()->guru?->id,
        403,
        'Anda tidak memiliki akses untuk menilai ujian ini.'
    );
}
```
Enforces ownership validation before any essay score or teacher notes can be written.

#### E. Rate Limiting Protection (SEC-04)
In `routes/api.php`:
Inter-service API routes are protected by `throttle:60,1` (maximum 60 requests per minute per IP), preventing denial-of-service or scraping attacks.

---

### 3. Conclusion
All identified security vulnerabilities have been remediated, verified by automated security test cases, and confirmed passing in the test suite.
