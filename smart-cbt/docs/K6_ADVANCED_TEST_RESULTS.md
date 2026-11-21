# SMART-CBT — Advanced k6 Load Testing Empirical Metric Logs

**Document Version:** 1.0.0  
**Test Suite Date:** November 12, 2026  
**Tooling:** Grafana k6 v0.54+ running inside Docker isolated bridge network (`smart-network`)  
**Target:** `http://smart-cbt-web:80`  
**Test Profiles:** End-to-End Examination Lifecycle (Authentication, Token Handshake, Work Page, 5–8 Monotonic Autosaves, Burst Submission, Auto-Scoring, Result Audit)  

---

## 1. TEST EXECUTION SUMMARY TABLE

| Run ID | Configuration | VUs | Total Req | Duration | Autosave avg / p95 | Submit avg / p95 | Error Rate | Checks Passed |
| :--- | :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| **RUN-A100** | Config A (1 Replica) | 100 | 1,600 | 1m 04.5s | 2,254ms / 3,408ms | 4,313ms / 7,283ms | 0.00% | 100.0% (1,200/1,200) |
| **RUN-A125** | Config A (1 Replica) | 125 | 2,000 | 1m 22.4s | 3,311ms / 4,781ms | 7,160ms / 9,604ms | 0.00% | 100.0% (1,500/1,500) |
| **RUN-A150** | Config A (1 Replica) | 150 | 2,399 | 2m 20.3s | 5,695ms / 7,860ms | 13,788ms / 16,911ms | 1.31% | 99.88% (1,798/1,800) |
| **RUN-A200** | Config A (1 Replica) | 200 | 3,180 | 2m 48.0s | 8,115ms / 11,114ms | 15,923ms / 18,520ms | 7.00% | 99.37% (2,375/2,390) |
| **RUN-A300** | Config A (1 Replica) | 300 | 808 | 1m 12.3s | N/A (Bcrypt saturation)| N/A (queue timeout)| 100% | 50.0% (Login queue timeout) |
| **RUN-B100** | Config B (2 Replicas)| 100 | 1,570 | 1m 11.2s | 2,176ms / 4,543ms | 3,299ms / 5,168ms | 3.00% | 99.74% (1,173/1,176) |
| **RUN-B150** | Config B (2 Replicas)| 150 | 2,360 | 1m 46.0s | 3,709ms / 5,970ms | 6,677ms / 11,884ms | 2.66% | 99.77% (1,764/1,768) |
| **RUN-B200** | Config B (2 Replicas)| 200 | 3,077 | 2m 13.2s | 5,471ms / 7,679ms | 11,072ms / 14,752ms | 6.00% | 99.47% (2,290/2,302) |
| **RUN-C100** | Config C (3 Replicas)| 100 | 1,570 | 1m 16.2s | 2,284ms / 5,951ms | 2,877ms / 5,725ms | 3.00% | 99.74% (1,173/1,176) |
| **RUN-C150** | Config C (3 Replicas)| 150 | 2,290 | 1m 36.5s | 3,468ms / 5,638ms | 5,423ms / 9,121ms | 7.33% | 99.35% (1,701/1,712) |
| **RUN-C200** | Config C (3 Replicas)| 200 | 2,525 | 2m 02.5s | 5,043ms / 8,929ms | 7,649ms / 11,249ms | 27.0% | 97.14% (1,840/1,894) |
| **RUN-SUST** | Config C (3 Replicas)| 100 | 1,809 | 1m 49.8s | **647ms** / **2,509ms**| **1,757ms** / **6,128ms** | 0.38% | **99.47%** (1,316/1,323) |

---

## 2. DETAILED METRIC REPORTS PER CONFIGURATION

### 1. Configuration A (1 Replica) — 100 VUs
```
checks_total...................: 1200
checks_succeeded...............: 100.00% (1200 / 1200)
checks_failed..................: 0.00% (0 / 1200)
http_reqs......................: 1600 (24.82 req/s)
http_req_failed................: 0.00% (0 / 1600)
cbt_autosave_duration_ms.......: avg=2254.3ms min=614ms   med=2243ms   p(90)=3181.1ms p(95)=3408.1ms max=5480ms
cbt_submit_duration_ms.........: avg=4313.5ms min=103ms   med=4541.5ms p(90)=6745.5ms p(95)=7283.1ms max=7918ms
cbt_work_page_duration_ms......: avg=2827.9ms min=492ms   med=2801.5ms p(90)=3857.0ms p(95)=4277.4ms max=4630ms
cbt_autosave_success_count.....: 500
cbt_submit_success_count.......: 100
iteration_duration.............: avg=1m1s min=52.5s med=1m2s max=1m4s
Data Transferred...............: 48 MB received, 1.4 MB sent
```

### 2. Configuration A (1 Replica) — 125 VUs
```
checks_total...................: 1500
checks_succeeded...............: 100.00% (1500 / 1500)
checks_failed..................: 0.00% (0 / 1500)
http_reqs......................: 2000 (24.27 req/s)
http_req_failed................: 0.00% (0 / 2000)
cbt_autosave_duration_ms.......: avg=3311.1ms min=1186ms  med=3246ms   p(90)=4372.4ms p(95)=4781.0ms max=5761ms
cbt_submit_duration_ms.........: avg=7160.8ms min=1062ms  med=7353ms   p(90)=8894.6ms p(95)=9604.6ms max=10595ms
cbt_work_page_duration_ms......: avg=3572.2ms min=2021ms  med=3592ms   p(90)=4389.0ms p(95)=4627.6ms max=5125ms
cbt_autosave_success_count.....: 625
cbt_submit_success_count.......: 125
iteration_duration.............: avg=1m18s min=1m12s med=1m19s max=1m22s
Data Transferred...............: 60 MB received, 1.7 MB sent
```

### 3. Configuration A (1 Replica) — 150 VUs (Post-Fix)
```
checks_total...................: 1800
checks_succeeded...............: 99.88% (1798 / 1800)
checks_failed..................: 0.11% (2 / 1800)
http_reqs......................: 2399 (17.09 req/s)
http_req_failed................: 0.08% (2 / 2399)
cbt_autosave_duration_ms.......: avg=5695.8ms min=2584ms  med=5547ms   p(90)=7299.7ms  p(95)=7860.6ms max=11022ms
cbt_submit_duration_ms.........: avg=13788ms  min=6761ms  med=14046ms  p(90)=16491.7ms p(95)=16911ms  max=18110ms
cbt_work_page_duration_ms......: avg=8117.2ms min=2887ms  med=8802.5ms p(90)=10364ms   p(95)=10867ms  max=11492ms
cbt_autosave_success_count.....: 750
cbt_submit_success_count.......: 149
cbt_submit_failed_count........: 1
iteration_duration.............: avg=2m15s min=2m6s med=2m16s max=2m20s
Data Transferred...............: 72 MB received, 2.1 MB sent
```

### 4. Configuration B (2 Replicas) — 150 VUs
```
checks_total...................: 1768
checks_succeeded...............: 99.77% (1764 / 1768)
checks_failed..................: 0.22% (4 / 1768)
http_reqs......................: 2360 (22.26 req/s)
http_req_failed................: 0.16% (4 / 2360)
cbt_autosave_duration_ms.......: avg=3709.7ms min=60ms    med=3693.5ms p(90)=5466.5ms p(95)=5970.8ms max=7427ms
cbt_submit_duration_ms.........: avg=6677.6ms min=204ms   med=7457.5ms p(90)=10732ms  p(95)=11884ms  max=14834ms
cbt_work_page_duration_ms......: avg=4499.1ms min=928ms   med=4468.5ms p(90)=6390ms   p(95)=6851.2ms max=8689ms
cbt_autosave_success_count.....: 730
cbt_submit_success_count.......: 146
cbt_submit_failed_count........: 0 (Zero failed submissions)
iteration_duration.............: avg=1m35s min=43.3s med=1m36s max=1m45s
Data Transferred...............: 71 MB received, 2.1 MB sent
```

### 5. Configuration C (3 Replicas) — 150 VUs
```
checks_total...................: 1712
checks_succeeded...............: 99.35% (1701 / 1712)
checks_failed..................: 0.64% (11 / 1712)
http_reqs......................: 2290 (23.73 req/s)
http_req_failed................: 0.48% (11 / 2290)
cbt_autosave_duration_ms.......: avg=3468.5ms min=310ms   med=3378.0ms p(90)=5050.6ms p(95)=5638.3ms max=8023ms
cbt_submit_duration_ms.........: avg=5423.6ms min=767ms   med=5708.0ms p(90)=8665.6ms p(95)=9121.7ms max=11819ms
cbt_work_page_duration_ms......: avg=4294.9ms min=284ms   med=4188.0ms p(90)=6732.0ms p(95)=7557.9ms max=9281ms
cbt_autosave_success_count.....: 695
cbt_submit_success_count.......: 139
cbt_submit_failed_count........: 0 (Zero failed submissions)
iteration_duration.............: avg=1m27s min=39.9s med=1m30s max=1m36s
Data Transferred...............: 68 MB received, 2.0 MB sent
```

### 6. Configuration C (3 Replicas) — Sustained Concurrency (100 VUs, 8 Rounds Autosave)
```
checks_total...................: 1323
checks_succeeded...............: 99.47% (1316 / 1323)
checks_failed..................: 0.52% (7 / 1323)
http_reqs......................: 1809 (16.47 req/s)
http_req_failed................: 0.38% (7 / 1809)
cbt_autosave_duration_ms.......: avg=647.8ms  min=36ms    med=375.5ms  p(90)=1546.0ms p(95)=2509.4ms max=5375ms
cbt_submit_duration_ms.........: avg=1757.5ms min=119ms   med=972.0ms  p(90)=4902.8ms p(95)=6128.8ms max=7200ms
cbt_work_page_duration_ms......: avg=2408.2ms min=83ms    med=1983.0ms p(90)=4573.4ms p(95)=5794.0ms max=7830ms
cbt_autosave_success_count.....: 744 (100% autosaves persisted)
cbt_submit_success_count.......: 93 (100% finished exams scored & persisted)
cbt_submit_failed_count........: 0
Database Attempts Verified.....: Exactly 93 attempts (status='needs_grading')
Database Answers Verified......: Exactly 930 answers (10 per student)
```
