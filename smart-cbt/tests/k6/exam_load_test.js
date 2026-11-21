import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Counter, Rate } from 'k6/metrics';

// Custom Metrics
const autosaveDuration = new Trend('cbt_autosave_duration_ms');
const workPageDuration = new Trend('cbt_work_page_duration_ms');
const submitDuration = new Trend('cbt_submit_duration_ms');
const autosaveSuccess = new Counter('cbt_autosave_success_count');
const autosaveFailed = new Counter('cbt_autosave_failed_count');
const submitSuccess = new Counter('cbt_submit_success_count');
const submitFailed = new Counter('cbt_submit_failed_count');
const errorRate = new Rate('cbt_error_rate');

const TARGET_VUS = parseInt(__ENV.VUS || '10', 10);
const DURATION = __ENV.DURATION || '45s';
const BASE_URL = __ENV.BASE_URL || 'http://smart-cbt-web';
const EXAM_TOKEN = __ENV.EXAM_TOKEN || 'LOAD99';

const EXECUTOR = __ENV.EXECUTOR || 'per-vu-iterations';

export const options = {
    scenarios: {
        exam_run: EXECUTOR === 'per-vu-iterations' ? {
            executor: 'per-vu-iterations',
            vus: TARGET_VUS,
            iterations: 1,
            maxDuration: __ENV.MAX_DURATION || '10m',
        } : {
            executor: 'constant-vus',
            vus: TARGET_VUS,
            duration: DURATION,
        },
    },
    thresholds: {
        'cbt_autosave_duration_ms': ['p(95)<500'],
        'cbt_work_page_duration_ms': ['p(95)<1000'],
        'cbt_submit_duration_ms': ['p(95)<2000'],
        'cbt_error_rate': ['rate<0.01'],
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
    const maxStudents = parseInt(__ENV.MAX_STUDENTS || '300', 10);
    const vuId = ((__VU - 1) % maxStudents) + 1;
    const pad = vuId.toString().padStart(3, '0');
    const email = `loadtest_siswa_${pad}@test.local`;
    const password = 'password123';

    // Stagger initial arrival to simulate realistic student classroom entry (default 0s)
    const staggerMs = parseInt(__ENV.STAGGER_MS || '0', 10);
    if (staggerMs > 0) {
        sleep(((__VU - 1) % 30) * (staggerMs / 1000));
    }

    const jar = http.cookieJar();

    // 1. Visit Login Page to get CSRF token & session cookie
    const loginPageRes = http.get(`${BASE_URL}/login`, { jar });
    check(loginPageRes, {
        'login page loaded (200)': (r) => r.status === 200,
    }) || errorRate.add(1);

    const csrfToken = extractCsrfToken(loginPageRes.body);
    if (!csrfToken) {
        errorRate.add(1);
        return;
    }

    // 2. Perform Login
    const loginPayload = {
        email: email,
        password: password,
        _token: csrfToken,
    };

    const loginRes = http.post(`${BASE_URL}/login`, loginPayload, {
        jar,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        redirects: 5,
    });

    const loggedIn = check(loginRes, {
        'login succeeded': (r) => r.status === 200 || r.status === 302,
    });
    if (!loggedIn) {
        errorRate.add(1);
        return;
    }

    sleep(1);

    // 3. Access Dashboard
    const dashboardRes = http.get(`${BASE_URL}/siswa/dashboard`, { jar });
    check(dashboardRes, {
        'dashboard loaded': (r) => r.status === 200,
    }) || errorRate.add(1);

    // Discover the load test exam ID from the page or environment
    let examId = __ENV.EXAM_ID;
    if (!examId) {
        const examMatch = dashboardRes.body.match(/\/siswa\/ujian\/(\d+)\/token/);
        if (examMatch) {
            examId = examMatch[1];
        } else {
            const fallbackMatch = dashboardRes.body.match(/\/siswa\/ujian\/(\d+)/);
            if (fallbackMatch) examId = fallbackMatch[1];
        }
    }

    if (!examId) {
        // Fallback default ID 1
        examId = '1';
    }

    // 4. Visit Token Page
    const tokenPageRes = http.get(`${BASE_URL}/siswa/ujian/${examId}/token`, { jar });
    const tokenCsrf = extractCsrfToken(tokenPageRes.body) || csrfToken;

    // 5. Submit Exam Token to Start/Resume Exam
    const startRes = http.post(`${BASE_URL}/siswa/ujian/${examId}/mulai`, {
        token: EXAM_TOKEN,
        _token: tokenCsrf,
    }, {
        jar,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        redirects: 5,
    });

    const startCheck = check(startRes, {
        'exam started or resumed': (r) => r.status === 200 || r.status === 302,
    });
    if (!startCheck) {
        console.error(`[VU ${vuId}] start failed: status=${startRes.status}, body=${startRes.body ? startRes.body.substring(0, 200) : ''}`);
        errorRate.add(1);
        return;
    } else {
        errorRate.add(0);
    }

    // 6. Access Work Page
    const workStart = Date.now();
    const workRes = http.get(`${BASE_URL}/siswa/ujian/${examId}/kerjakan`, { jar });
    workPageDuration.add(Date.now() - workStart);

    const workLoaded = check(workRes, {
        'work page loaded (200)': (r) => r.status === 200,
    });
    if (!workLoaded) {
        errorRate.add(1);
        return;
    }

    const workCsrf = extractCsrfToken(workRes.body) || tokenCsrf;

    // Extract Question IDs from question panels
    const qMatches = [...workRes.body.matchAll(/data-question-id=["'](\d+)["']/g)];
    const questionIds = qMatches.length > 0 ? qMatches.map(m => parseInt(m[1], 10)) : [1, 2, 3, 4, 5];
    const uniqueQIds = [...new Set(questionIds)];

    // 7. Autosave Simulation (Realistic Examination Flow)
    const options = ['A', 'B', 'C', 'D'];
    let seq = 1;

    for (let i = 0; i < Math.min(uniqueQIds.length, 5); i++) {
        const qId = uniqueQIds[i];
        const chosenOption = options[i % options.length];

        sleep(1); // Realistic student think time

        const autosavePayload = JSON.stringify({
            request_id: `k6_${vuId}_q${qId}_s${seq}_${Date.now()}`,
            answers: { [qId]: chosenOption },
            client_seqs: { [qId]: seq++ },
        });

        const asStart = Date.now();
        const autosaveRes = http.post(`${BASE_URL}/siswa/ujian/${examId}/jawaban`, autosavePayload, {
            jar,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': workCsrf,
                'Accept': 'application/json',
            },
        });
        autosaveDuration.add(Date.now() - asStart);

        const asOk = check(autosaveRes, {
            'autosave status 200': (r) => r.status === 200,
        });

        if (asOk) {
            autosaveSuccess.add(1);
        } else {
            autosaveFailed.add(1);
            errorRate.add(1);
        }
    }

    // 8. Submit Examination
    sleep(1);
    const submitPayload = {
        _token: workCsrf,
    };
    uniqueQIds.forEach(id => {
        submitPayload[`answers[${id}]`] = 'A';
    });

    const submitStart = Date.now();
    const submitRes = http.post(`${BASE_URL}/siswa/ujian/${examId}/kumpulkan`, submitPayload, {
        jar,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        redirects: 5,
    });
    submitDuration.add(Date.now() - submitStart);

    const submitOk = check(submitRes, {
        'exam submitted successfully': (r) => r.status === 200 || r.status === 302,
    });

    if (submitOk) {
        submitSuccess.add(1);
    } else {
        console.error(`[VU ${vuId}] submit failed: status=${submitRes.status}, body=${submitRes.body ? submitRes.body.substring(0, 200) : ''}`);
        submitFailed.add(1);
        errorRate.add(1);
    }

    // 9. Verify Result Page
    const resultRes = http.get(`${BASE_URL}/siswa/ujian/${examId}/hasil`, { jar });
    check(resultRes, {
        'result page accessible': (r) => r.status === 200,
    }) || errorRate.add(1);

    sleep(2);
}
