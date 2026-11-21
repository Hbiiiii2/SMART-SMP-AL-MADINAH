import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 100 }, // Warmup to 100 concurrent students
    { duration: '1m', target: 300 },  // Ramp to 300 concurrent students
    { duration: '2m', target: 500 },  // Peak load: 500 concurrent students
    { duration: '30s', target: 0 },    // Cooldown
  ],
  thresholds: {
    http_req_duration: ['p(95)<500', 'p(99)<1000'], // 95% of requests under 500ms
    http_req_failed: ['rate<0.01'],                // Error rate < 1%
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8001';
const API_TOKEN = __ENV.CBT_API_TOKEN || 'cbt_secret_token_almadinah_2026';

export default function () {
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': `Bearer ${API_TOKEN}`,
  };

  // 1. Health Check
  const healthRes = http.get(`${BASE_URL}/api/v1/health`);
  check(healthRes, {
    'health check returns 200': (r) => r.status === 200,
  });

  // 2. Fetch Exams List (External/Monitoring integration)
  const examsRes = http.get(`${BASE_URL}/api/v1/exams`, { headers });
  check(examsRes, {
    'exams list returns 200': (r) => r.status === 200,
  });

  // 3. Simulated Autosave Request (Bulk Upsert)
  const payload = JSON.stringify({
    answers: {
      1: 'A',
      2: 'B',
      3: 'C',
      4: 'D',
      5: 'E',
    },
  });

  // Student autosave simulates answer heartbeat every 15-30s
  sleep(Math.random() * 5 + 1);
}
