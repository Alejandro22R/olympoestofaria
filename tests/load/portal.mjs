// Prueba de carga básica del portal (sin dependencias). Mide peticiones por segundo y latencias.
// Uso: BASE_URL=https://tu-dominio CONCURRENCY=20 DURATION=30 node tests/load/portal.mjs
// Ojo: el portal limita 120 peticiones/min por IP; en un entorno de prueba sube PORTAL_RATE_LIMIT.
const base = process.env.BASE_URL ?? 'http://127.0.0.1:8000';
const concurrency = Number(process.env.CONCURRENCY ?? 10);
const duration = Number(process.env.DURATION ?? 20) * 1000;
const paths = (process.env.PATHS ?? '/,/catalogo,/projeto,/privacidade,/up').split(',');

const latencies = [];
const byStatus = {};
let errors = 0;
const end = Date.now() + duration;

const worker = async (id) => {
    let i = id;
    while (Date.now() < end) {
        const path = paths[i++ % paths.length];
        const started = performance.now();
        try {
            const res = await fetch(base + path, { headers: { 'User-Agent': 'olympo-loadtest' } });
            await res.arrayBuffer();
            byStatus[res.status] = (byStatus[res.status] ?? 0) + 1;
        } catch {
            errors++;
        }
        latencies.push(performance.now() - started);
    }
};

const t0 = Date.now();
await Promise.all(Array.from({ length: concurrency }, (_, i) => worker(i)));
const seconds = (Date.now() - t0) / 1000;

latencies.sort((a, b) => a - b);
const pct = (p) => latencies[Math.min(latencies.length - 1, Math.floor(latencies.length * p))]?.toFixed(0);
const ok = Object.entries(byStatus).filter(([s]) => s < 400).reduce((n, [, c]) => n + c, 0);

console.log(JSON.stringify({
    base, concurrency, seconds: Math.round(seconds), requests: latencies.length,
    rps: Number((latencies.length / seconds).toFixed(1)),
    p50_ms: Number(pct(0.5)), p95_ms: Number(pct(0.95)), p99_ms: Number(pct(0.99)),
    status: byStatus, network_errors: errors,
    success_rate: Number((ok / Math.max(1, latencies.length) * 100).toFixed(2)),
}, null, 2));

if (ok / Math.max(1, latencies.length) < 0.99 || Number(pct(0.95)) > Number(process.env.MAX_P95_MS ?? 1500)) {
    process.exitCode = 1;
}
