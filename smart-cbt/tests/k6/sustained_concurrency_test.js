import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Counter, Rate } from 'k6/metrics';

// Custom Metrics for Sustained Concurrency & Burst Testing
const autosaveDuration = new Trend('cbt_autosave_duration_ms');
const submitDuration = new Trend('cbt_submit_duration_ms');
const workPageDuration = new Trend('cbt_work_page_duration_ms');
const autosaveSuccess = new Counter('cbt_autosave_success_count');
const autosaveFailed = new Counter('cbt_autosave_failed_count');
const submitSuccess = new Counter('cbt_submit_success_count');
const submitFailed = new Counter('cbt_submit_failed_count');
const errorRate = new Rate('cbt_error_rate');

const TARGET_VUS = parseInt(__ENV.VUS || '100', 10);
const SUSTAINED_DURATION = __ENV.DURATION || '5m';
const BASE_URL = __ENV.BASE_URL || 'http://smart-cbt-web';
const EXAM_TOKEN = __ENV.EXAM_TOKEN || 'LOAD99';
const MAX_STUDENTS = parseInt(__ENV.MAX_STUDENTS || '300', 10);

export const options = {
    scenarios: {
        sustained_exam: {
            executor: 'per-vu-iterations',
            vus: TARGET_VUS,
            iterations: 1,
            maxDuration: '20m',
        },
    },
    thresholds: {
        'cbt_autosave_duration_ms': ['p(95)<3000'],
        'cbt_submit_duration_ms': ['p(95)<5000'],
        'cbt_error_rate': ['rate<0.02'],
    },
};

function extractCsrfToken(body) {
    if (!body) return null;
    const metaMatch = body.match(/<meta\s+name=["']csrf-token["']\s+content=["']([^"']+)["']/i);
    if (metaMatch) return metaMatch[1];

    const inputMatch = body.match(/<input[^>]+name=["']_token["'][^>]+value=["']([^"']+)["']/i);
    if (inputMatch) return inputMatch[1];

    return null;
}

export default function () {
    const vuId = ((__VU - 1) % MAX_STUDENTS) + 1;
    const pad = vuId.toString().padStart(3, '0');
    const email = `loadtest_siswa_${pad}@test.local`;
    const password = 'password123';

    // Stagger login arrival across a realistic 5-15s classroom entry window
    const loginStagger = (vuId % 30) * 0.2;
    sleep(loginStagger);

    const jar = http.cookieJar();

    // 1. Fetch Login Page
    const loginPageRes = http.get(`${BASE_URL}/login`, { jar });
    if (!check(loginPageRes, { 'login page loaded': (r) => r.status === 200 })) {
        errorRate.add(1);
        return;
    }

    const csrfToken = extractCsrfToken(loginPageRes.body);
    if (!csrfToken) {
        errorRate.add(1);
        return;
    }

    // 2. Perform Login
    const loginRes = http.post(`${BASE_URL}/login`, {
        email: email,
        password: password,
        _token: csrfToken,
    }, {
        jar,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        redirects: 5,
    });

    if (!check(loginRes, { 'logged in': (r) => r.status === 200 || r.status === 302 })) {
        errorRate.add(1);
        return;
    }

    sleep(1);

    // 3. Access Dashboard & Locate Exam
    const dashboardRes = http.get(`${BASE_URL}/siswa/dashboard`, { jar });
    let examId = __ENV.EXAM_ID;
    if (!examId) {
        const m = dashboardRes.body ? dashboardRes.body.match(/\/siswa\/ujian\/(\d+)\/token/) : null;
        examId = m ? m[1] : '402';
    }

    // 4. Visit Token Page & Start Exam
    const tokenPageRes = http.get(`${BASE_URL}/siswa/ujian/${examId}/token`, { jar });
    const tokenCsrf = extractCsrfToken(tokenPageRes.body) || csrfToken;

    const startRes = http.post(`${BASE_URL}/siswa/ujian/${examId}/mulai`, {
        token: EXAM_TOKEN,
        _token: tokenCsrf,
    }, {
        jar,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        redirects: 5,
    });

    if (!check(startRes, { 'exam started': (r) => r.status === 200 || r.status === 302 })) {
        errorRate.add(1);
        return;
    }

    // 5. Open Examination Work Page
    const wpStart = Date.now();
    const workRes = http.get(`${BASE_URL}/siswa/ujian/${examId}/kerjakan`, { jar });
    workPageDuration.add(Date.now() - wpStart);

    if (!check(workRes, { 'work page 200': (r) => r.status === 200 })) {
        errorRate.add(1);
        return;
    }

    const workCsrf = extractCsrfToken(workRes.body) || tokenCsrf;
    const qMatches = workRes.body ? [...workRes.body.matchAll(/data-question-id=["'](\d+)["']/g)] : [];
    const questionIds = qMatches.length > 0 ? qMatches.map(m => parseInt(m[1], 10)) : [1, 2, 3, 4, 5];
    const uniqueQIds = [...new Set(questionIds)];

    // 6. Sustained Examination Loop (Continuous Think Time & Monotonic Autosaves)
    const options = ['A', 'B', 'C', 'D'];
    let clientSeq = 1;

    // Run 8 rounds of autosaves to simulate sustained examination period
    for (let round = 1; round <= 8; round++) {
        // Realistic student deliberation time between questions (3 to 6 seconds)
        sleep(3 + (vuId % 4));

        const targetQIndex = (round - 1) % uniqueQIds.length;
        const targetQId = uniqueQIds[targetQIndex];
        const chosenAnswer = options[(vuId + round) % options.length];

        const payload = JSON.stringify({
            request_id: `k6_sust_${vuId}_r${round}_${Date.now()}`,
            answers: { [targetQId]: chosenAnswer },
            client_seqs: { [targetQId]: clientSeq++ },
        });

        const asStart = Date.now();
        const asRes = http.post(`${BASE_URL}/siswa/ujian/${examId}/jawaban`, payload, {
            jar,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': workCsrf,
                'Accept': 'application/json',
            },
        });
        autosaveDuration.add(Date.now() - asStart);

        if (check(asRes, { 'autosave ok': (r) => r.status === 200 })) {
            autosaveSuccess.add(1);
        } else {
            autosaveFailed.add(1);
            errorRate.add(1);
        }
    }

    // 7. Simultaneous Burst Submission Phase
    // Stagger submission slightly (0-2s) simulating students finishing near the deadline
    sleep((vuId % 10) * 0.2);

    const submitPayload = { _token: workCsrf };
    uniqueQIds.forEach((id, idx) => {
        submitPayload[`answers[${id}]`] = options[(vuId + idx) % options.length];
    });

    const subStart = Date.now();
    const submitRes = http.post(`${BASE_URL}/siswa/ujian/${examId}/kumpulkan`, submitPayload, {
        jar,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        redirects: 5,
    });
    submitDuration.add(Date.now() - subStart);

    if (check(submitRes, { 'exam submitted': (r) => r.status === 200 || r.status === 302 })) {
        submitSuccess.add(1);
    } else {
        submitFailed.add(1);
        errorRate.add(1);
    }

    // 8. Result Verification
    const resultRes = http.get(`${BASE_URL}/siswa/ujian/${examId}/hasil`, { jar });
    check(resultRes, { 'result page accessible': (r) => r.status === 200 });

    sleep(1);
}
