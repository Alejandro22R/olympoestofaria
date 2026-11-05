// Prueba de carga con k6 (https://k6.io) para el servidor de producción o staging.
// k6 run -e BASE_URL=https://tu-dominio tests/load/k6-portal.js
// Usa varias IPs de origen o sube PORTAL_RATE_LIMIT en staging: el portal limita 120 req/min por IP.
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    stages: [
        { duration: '1m', target: 20 },
        { duration: '3m', target: 50 },
        { duration: '1m', target: 0 },
    ],
    thresholds: {
        http_req_failed: ['rate<0.01'],
        http_req_duration: ['p(95)<800'],
    },
};

const base = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const pages = ['/', '/catalogo', '/projeto', '/privacidade'];

export default function () {
    const res = http.get(base + pages[Math.floor(Math.random() * pages.length)]);
    check(res, { 'status 200': (r) => r.status === 200 });
    sleep(Math.random() * 3 + 1);
}
