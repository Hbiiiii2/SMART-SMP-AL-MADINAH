# SECURITY, QUESTION GUARDS & USER-KEYED RATE LIMITING — SMART-CBT

**Document:** `docs/SECURITY_RATE_LIMIT_VERIFICATION.md`  
**Audit Items:** BLK-02 (Question Mutation Guards) & BLK-03 (User-Keyed Rate Limiting)  
**Implementation Date:** November 12, 2026  
**Status:** `VERIFIED & PRODUCTION-READY` (11/11 Scenarios Passed)  

---

## 1. Executive Summary

This document details the implementation and verification of application security defenses, question mutation guards, and user-keyed rate limiting protocols.

### Core Problems Resolved:
1. **Unguarded Question Pathways (BLK-02):** Previously, while `update` and `destroy` checked for attempts, endpoints like `store`, `duplicate`, `reorder`, and `importBank` lacked guards, allowing instructors to add or rearrange questions while students were actively taking the exam.
2. **Single-NAT Public IP Rate Limiting Lockout (BLK-03):** Default Laravel rate limiters key by `$request->ip()`. In school environments where 500+ students connect via a shared NAT router or campus Wi-Fi AP, IP-based limits would cause campus-wide HTTP 429 lockouts within seconds.

---

## 2. Centralized Question Mutation Guards (BLK-02)

### 2.1 Implementation in `QuestionController.php`
A centralized guard was introduced in [QuestionController.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/app/Http/Controllers/QuestionController.php):

```php
private function assertExamModifiable(Exam $exam): void
{
    abort_if(
        $exam->examAttempts()->exists(),
        422,
        'Soal tidak dapat dimodifikasi karena ujian telah memiliki riwayat pengerjaan siswa.'
    );
}
```

This guard is enforced at the entry point of every modification method:
- `store()` — Adding a new question.
- `update()` — Modifying an existing question.
- `destroy()` — Deleting a question.
- `duplicate()` — Duplicating an existing question.
- `reorder()` — Reordering questions.
- `import()` — Importing an individual question from a question bank.
- `importBank()` — Bulk importing questions from a question bank.

### 2.2 Atomic Transactions for Multi-Record Operations
Operations affecting multiple question records are wrapped in database transactions:
- `reorder()`: Wraps temporary position offsetting and final renumbering.
- `importBank()`: Wraps bulk question creation and token preparation in `DB::transaction()`.
- `duplicate()`: Wraps replication, sequence calculation, and token update.

### 2.3 Model-Level Defense-in-Depth (`Question.php`)
To prevent direct model manipulation bypassing the controller (e.g. from background tasks or rogue scripts), Eloquent model events in [Question.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/app/Models/Question.php) intercept modifications:

```php
protected static function booted(): void
{
    static::updating(function (Question $question) {
        if ($question->exam && $question->exam->examAttempts()->exists()) {
            throw new \DomainException('Soal tidak dapat diubah karena ujian telah memiliki riwayat pengerjaan siswa.');
        }
    });

    static::deleting(function (Question $question) {
        if ($question->exam && $question->exam->examAttempts()->exists()) {
            throw new \DomainException('Soal tidak dapat dihapus karena ujian telah memiliki riwayat pengerjaan siswa.');
        }
    });
}
```

---

## 3. User-Keyed Rate Limiting Protocol (BLK-03)

### 3.1 Resolving the Single-NAT School Gateway Bottleneck
Rate limits are configured in [AppServiceProvider.php](file:///c:/laragon/www/smp-almadinah/smart-cbt/app/Providers/AppServiceProvider.php) using the authenticated student ID as the primary partition key:

```php
// app/Providers/AppServiceProvider.php
RateLimiter::for('exam-autosave', function (Request $request) {
    $key = $request->user()?->id ?: $request->ip();
    return Limit::perMinute(120)->by($key);
});

RateLimiter::for('exam-start', function (Request $request) {
    $key = $request->user()?->id ?: $request->ip();
    return Limit::perMinute(15)->by($key);
});

RateLimiter::for('exam-submit', function (Request $request) {
    $key = $request->user()?->id ?: $request->ip();
    return Limit::perMinute(10)->by($key);
});
```

### 3.2 Attached Web Routes (`routes/web.php`)
```php
Route::post('/ujian/{ujian}/mulai', [SiswaExamController::class, 'start'])
    ->middleware('throttle:exam-start')
    ->name('ujian.start');

Route::post('/ujian/{ujian}/jawaban', [SiswaExamController::class, 'saveAnswers'])
    ->middleware('throttle:exam-autosave')
    ->name('ujian.answers');

Route::post('/ujian/{ujian}/kumpulkan', [SiswaExamController::class, 'submit'])
    ->middleware('throttle:exam-submit')
    ->name('ujian.submit');
```

---

## 4. Automated Verification Results

### 4.1 Question Mutation Guards (`QuestionMutationGuardsTest.php`)
```bash
php artisan test tests/Feature/QuestionMutationGuardsTest.php --env=testing
```
```text
   PASS  Tests\Feature\QuestionMutationGuardsTest
  ✓ store is blocked after exam has attempts                        1.38s
  ✓ update is blocked after exam has attempts                       0.34s
  ✓ destroy is blocked after exam has attempts                      0.32s
  ✓ duplicate is blocked after exam has attempts                    0.35s
  ✓ reorder is blocked after exam has attempts                      0.31s
  ✓ import is blocked after exam has attempts                       0.41s
  ✓ import bank is blocked after exam has attempts                  0.43s
  ✓ direct model mutation throws domain exception when attempts exist 0.35s
  ✓ other teacher cannot modify exam questions                      0.28s

  Tests:    9 passed (27 assertions)
  Duration: 4.36s
```

### 4.2 Multi-Student Single-NAT Rate Limiting (`UserKeyedRateLimitingTest.php`)
```bash
php artisan test tests/Feature/UserKeyedRateLimitingTest.php --env=testing
```
```text
   PASS  Tests\Feature\UserKeyedRateLimitingTest
  ✓ two students on same nat ip have independent rate limits        1.42s
  ✓ exam start rate limiter isolated per user on same ip            0.41s

  Tests:    2 passed (14 assertions)
  Duration: 2.01s
```

**Key Verification Finding:**
In `test_two_students_on_same_nat_ip_have_independent_rate_limits`, Student 1 and Student 2 sent requests with identical simulated public IP `180.252.12.34`. Student 1 exceeded their quota and received HTTP 429. Student 2 from the exact same IP continued to receive HTTP 200 responses, proving complete rate limiter isolation per authenticated user.
