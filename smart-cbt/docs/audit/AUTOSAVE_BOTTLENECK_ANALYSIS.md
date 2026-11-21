# SMART-CBT — Autosave Bottleneck Analysis & Optimization Strategy

**Audit Date:** November 12, 2026  
**Auditing Engineer:** Senior Laravel Backend Engineer, Frontend Performance Engineer, Database Optimization Specialist, & Distributed Systems Engineer  
**Project:** SMART-CBT  
**Infrastructure Target:** Single VPS ($\le$ Rp300,000/month, approx. 4 vCPU, 8 GB RAM)  
**Concurrency Target:** 1,000 actively testing students  

---

## 1. Executive Bottleneck Summary

This bottleneck analysis diagnoses the architectural, algorithmic, and infrastructure constraints in the current SMART-CBT autosave pipeline. 

Under small test batches (50–100 students), the current implementation appears reliable. However, under the target deployment of **1,000 active students on a single Rp300,000/month VPS**, the current design exhibits critical scaling and reliability bottlenecks that will induce cascading failures.

### Bottleneck Matrix

| # | Bottleneck Identifier | Severity | Impact Vector | Root Cause Location |
| :---: | :--- | :---: | :--- | :--- |
| **B-01** | **5-Question Batching & 30s Fallback Delay** | **CRITICAL** | Answers unsynced for up to 30s; data loss on device crash or battery drain | `work.blade.php#L439-L440`, `#L852` |
| **B-02** | **Zero Jitter & Synchronized Harmonic Herds** | **HIGH** | Recurring CPU and MySQL connection spikes every 30 seconds | `work.blade.php#L1244-L1249` |
| **B-03** | **Coarse Boolean Acknowledgment** | **HIGH** | UI displays "Tersimpan" prematurely; cannot handle partial or stale writes | `AnswerService.php#L150`, `work.blade.php#L790` |
| **B-04** | **Missing Idempotency Key (`request_id`)** | **MEDIUM** | Network retries trigger redundant database locking and row upserts | `work.blade.php#L777`, `AnswerService.php#L28` |
| **B-05** | **Rate Limiter Fallback to Shared NAT IP** | **CRITICAL** | Whole-school lockout (HTTP 429) behind shared school WiFi / NAT gateway | `AppServiceProvider.php#L28`, `#L33`, `#L38` |
| **B-06** | **Lack of Tab Visibility Synchronization** | **MEDIUM** | Unsynced answers when student switches apps or backgrounds browser | `work.blade.php` (missing `visibilitychange`) |
| **B-07** | **Single VPS Rp300,000 Budget Constraint** | **HIGH** | Max 4 vCPUs / 8 GB RAM cannot sustain un-smoothed, synchronized bursts | Infrastructure Sizing vs 1,000 VUs |

---

## 2. In-Depth Bottleneck Diagnosis

### Bottleneck B-01: The 5-Question Batching Delay

In [work.blade.php:852](file:///c:/laragon/www/smp-almadinah/smart-cbt/resources/views/siswa/exams/work.blade.php#L852):
```javascript
async function checkAndSaveBatch() {
    if (state.changedQuestions.size >= CONFIG.BATCH_SIZE) { // BATCH_SIZE = 5
        ...
        await saveBatchAnswers(batchAnswers);
    }
}
```
- **The Defect:** When a student deliberates and selects an answer for Question 1, debounce fires, but `changedQuestions.size` is only 1. The batch check silently aborts.
- **The Vulnerability:** The student's answer sits strictly in browser memory and LocalStorage until:
  1. The student answers 4 more questions (which may take 10 to 15 minutes), OR
  2. The 30-second fallback timer ticks.
- If the student's mobile browser is terminated by Android OS low-memory killer (OOM), or the device runs out of battery before 30 seconds elapse, that answer is never synchronized to the server.

---

### Bottleneck B-02: Zero Jitter & Harmonic Thundering Herd

In [work.blade.php:1244-1249](file:///c:/laragon/www/smp-almadinah/smart-cbt/resources/views/siswa/exams/work.blade.php#L1244-L1249):
```javascript
periodicSaveTimer = setInterval(() => {
    const pendingCount = Object.keys(state.pendingAnswers).length;
    if (pendingCount > 0 && !state.isSaving && !state.isSubmitting) {
        saveAllPendingAnswers();
    }
}, CONFIG.PERIODIC_INTERVAL); // Exactly 30,000ms
```
- **The Defect:** Every connected student browser executes `setInterval(..., 30000)`.
- **The Physics of the Herd:** In a school environment, an examination begins when the proctor announces the token (e.g. at 08:00:00). Within a 10-second window, 500 to 1,000 students load the work page and initialize this timer.
- Because there is **zero randomized jitter**, hundreds of client timers fire in synchronized harmonic resonance at $t = 30\text{s}, 60\text{s}, 90\text{s}, \dots$.
- Instead of a smooth, continuous arrival stream of requests, the single VPS receives huge sinusoidal traffic spikes:
  $$\text{Traffic Arrival} \approx \begin{cases} 0 \text{ req/sec} & \text{for } 25 \text{ seconds} \\ 400\text{--}600 \text{ req/sec} & \text{for } 3 \text{ seconds (Thundering Herd)} \end{cases}$$
- This instant burst overwhelms the Linux socket listen queue (`listen.backlog = 512`), exhausts PHP-FPM workers, and triggers HTTP 504 Gateway Timeouts.

---

### Bottleneck B-03: Coarse Acknowledgment & Premature "Tersimpan" State

In [AnswerService.php:150](file:///c:/laragon/www/smp-almadinah/smart-cbt/app/Services/Answer/AnswerService.php#L150):
```php
return ['saved' => true, 'count' => $savedCount];
```
- **The Defect:** The server returns an opaque boolean and a count. It does not return the list of acknowledged question IDs, client sequences, or a server timestamp.
- **Client Fallback:** In [work.blade.php:795-808](file:///c:/laragon/www/smp-almadinah/smart-cbt/resources/views/siswa/exams/work.blade.php#L795-L808), the client assumes that any answer sent in that batch was successfully persisted by the database.
- **The Integrity Risk:** If the server discarded a write due to a stale sequence or validation error, the client still deletes it from `state.pendingAnswers` because `data.saved === true`.
- Furthermore, in [work.blade.php:594-598](file:///c:/laragon/www/smp-almadinah/smart-cbt/resources/views/siswa/exams/work.blade.php#L594-L598), if `pendingCount === 0`, the UI displays a green badge: **"Tersimpan"**. On page load, before a single byte has been sent or verified, the UI deceptively tells the student that their state is "Tersimpan".

---

### Bottleneck B-04: Missing Request Idempotency Key (`request_id`)

- **The Defect:** The frontend does not generate or attach a unique `request_id` to autosave payloads.
- **Retry Amplification:** In real mobile networks, a student may transmit a payload that the server successfully commits to MySQL in 15ms. However, if the mobile TCP connection experiences transient packet loss on the HTTP response path, the browser's `fetch()` throws a network error after 10 seconds.
- The client retry loop ([line 821](file:///c:/laragon/www/smp-almadinah/smart-cbt/resources/views/siswa/exams/work.blade.php#L821)) triggers Retry 1.
- Because the backend does not check an idempotency cache, it executes `DB::transaction()` again, re-acquires `ExamAttempt::lockForUpdate()`, reads `answers`, compares sequences, and re-runs `Answer::upsert()`.
- Under network turbulence, retries multiply database CPU consumption by up to $3\times$.

---

### Bottleneck B-05: Rate Limiter Ingress Fallback Behind Shared School NAT

In [AppServiceProvider.php:27-40](file:///c:/laragon/www/smp-almadinah/smart-cbt/app/Providers/AppServiceProvider.php#L27-L40):
```php
RateLimiter::for('exam-autosave', function (Request $request) {
    $key = $request->user()?->id ?: $request->ip();
    return Limit::perMinute(120)->by($key);
});
```
- **The Defect:** If `$request->user()` is null or unauthenticated during request parsing, the rate-limiting key falls back to `$request->ip()`.
- **The School Deployment Scenario:** At SMP Al-Madinah, all 1,000 student Chromebooks or mobile phones connect through the school's central WiFi router or proxy, presenting a **single public IP address** to the VPS.
- If session resolution stutters on a burst of requests, the rate limiter begins accumulating hits against that single IP.
- Once 120 hits occur in 60 seconds across the entire school (which takes less than 5 seconds with 1,000 students), the rate limiter slams shut, returning **HTTP 429 Too Many Requests to every student in the school**.

---

## 3. Financial & Hardware Constraint Analysis: Rp300,000/Month Budget

### Available VPS Tier at Rp300,000/month (approx. $18–$19 USD)
Market survey of Indonesian and global cloud providers (IDCloudHost, DomaiNesia, Biznet Gio, DigitalOcean, Hetzner, Vultr):
- **CPU:** 2 to 4 vCPUs (Modern AMD EPYC or Intel Xeon)
- **RAM:** 4 GB to 8 GB DDR4/DDR5
- **Storage:** 50 GB to 100 GB NVMe SSD
- **Network:** 1 Gbps shared interface, unmetered or 1–2 TB bandwidth

### Mathematical Feasibility of 1,000 Students on 4 vCPU / 8 GB RAM

Can 1,000 active students run on a 4-vCPU / 8 GB RAM single VPS?
Let us calculate the required compute throughput:

#### Scenario A: Unoptimized (Synchronized 30s Harmonic Waves)
- 1,000 students fire requests within a 3-second window every 30 seconds:
  $$\text{Arrival Rate} = \frac{1,000\text{ requests}}{3\text{ seconds}} \approx 333\text{ req/sec}$$
- At 333 req/sec, each request taking an average of 40ms PHP execution time:
  $$\text{Required Active FPM Workers} = 333 \times 0.040 = 13.3 \rightarrow 20\text{ workers minimum}$$
- If payloads contain full exam data, or if database lock contention occurs, response latency inflates to 500ms:
  $$\text{Required Workers at 500ms} = 333 \times 0.500 = 166.5\text{ workers}$$
- 166 PHP-FPM workers $\times$ 45 MB RAM per worker = **7.47 GB RAM** solely for PHP-FPM!
- Adding MySQL (1.5 GB), Redis (256 MB), and OS (512 MB) causes an **Out-Of-Memory (OOM) kernel panic** and system crash.

#### Scenario B: Optimized (Delta-Based, 10s Cadence, Randomized Jitter)
- Autosave interval configured to 10 seconds.
- Randomized jitter of $\pm 2.0\text{s}$ (8s to 12s window).
- Debounce collapses rapid typing into a single delta.
- Requests arrive in a smoothed, continuous Gaussian distribution:
  $$\text{Continuous Arrival Rate} \approx \frac{1,000\text{ students}}{10\text{ seconds average}} = 100\text{ req/sec}$$
- **Delta Payload:** Only 1 or 2 modified answers sent per request (payload size: $\approx 250\text{ bytes}$).
- **Optimized PHP Execution:** Fast delta validation, Redis idempotency check, lightweight single-attempt transaction:
  $$\text{Execution Time per Request} \approx 12\text{ to } 18\text{ ms}$$
- **Required Active Workers:**
  $$\text{Average FPM Workers Needed} = 100\text{ req/sec} \times 0.015\text{s} = \mathbf{1.5\text{ workers}}$$
  $$\text{Peak 95th Percentile Workers} \approx \mathbf{10\text{ to } 15\text{ workers}}$$
- **Memory Consumption:**
  - 25 PHP-FPM workers $\times$ 40 MB = 1.0 GB RAM
  - MySQL 8.4 InnoDB Buffer Pool = 1.5 GB RAM
  - Redis 7 In-Memory Store = 256 MB RAM
  - Nginx + OS kernel = 512 MB RAM
  - **Total System RAM Footprint = 3.26 GB RAM** (fits comfortably within an 8 GB or even 4 GB VPS!).

**Conclusion:** 1,000 active examination sessions on a Rp300,000/month single VPS is **mathematically and technically achievable**, provided that delta payloads, 10s intervals, randomized jitter, and lightweight execution are strictly implemented.

---

## 4. Prioritized Engineering Remediation Roadmap

To eliminate all identified bottlenecks while upholding absolute answer integrity:

```
[Phase 2: Scheduling Optimization]
  ├── Make Autosave interval configurable (default: 10s)
  ├── Introduce randomized jitter (±1.5s to ±2.5s)
  ├── Enhance debounce (500ms) to flush on single answer changes
  └── Bind window 'visibilitychange' to flush dirty answers on background/tab switch

[Phase 3: Delta-Based Autosave Engine]
  ├── Track dirty state per question
  ├── Send only modified answers in payload
  └── Prune dirty state ONLY after exact version server acknowledgment

[Phase 4: Idempotency & Concurrency Hardening]
  ├── Introduce unique 'request_id' (UUID / Ulid) per autosave operation
  ├── Retain same 'request_id' during retries
  ├── Fast-path Redis idempotency cache for duplicate request delivery
  └── Preserve Attempt 'lockForUpdate()' and unique composite key '(exam_attempt_id, question_id)'

[Phase 5: Granular Server Acknowledgment & UI States]
  ├── Return structured JSON: attempt_id, request_id, acknowledged, rejected, server_time
  ├── Implement 5-state UI indicator: Dirty, Pending, Saving, Saved, Failed
  └── Eliminate premature "Tersimpan" badge

[Phase 6: Exponential Backoff & Network Recovery]
  ├── Implement exponential backoff with jitter on network failure
  └── Preserve LocalStorage queue across temporary offline states

[Phase 7: Submission Race Protection]
  ├── Guarantee all dirty/pending answers are flushed and acknowledged before submit
  └── Seal attempt status to prevent post-submission autosave mutation

[Phase 8 & 9: Database & Rate Limiting Optimization]
  ├── Tune MySQL connection pool and buffer pool for single VPS
  └── Eliminate '$request->ip()' fallback on authenticated rate limiters
```
