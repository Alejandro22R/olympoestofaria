// Auditoría de accesibilidad (WCAG 2.1 AA) con axe-core sobre las páginas principales.
// Uso: servidor corriendo (php artisan serve) y luego:
//   BASE_URL=http://127.0.0.1:8000 node tests/a11y/audit.mjs   (THEME=light para el panel en modo claro)
// Opcional: ADMIN_EMAIL / ADMIN_PASSWORD para auditar el panel (por defecto, el dueño demo).
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');
const axeSource = readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');

const base = process.env.BASE_URL ?? 'http://127.0.0.1:8000';
const publicPages = ['/', '/catalogo', '/projeto', '/privacidade', '/termos', '/?lang=es'];
const adminPages = ['/admin', '/admin/appointments', '/admin/work-orders', '/admin/production', '/admin/quotes', '/admin/reports', '/admin/settings/backups', '/admin/settings/privacy'];

const audit = async (page, url) => {
    await page.goto(base + url, { waitUntil: 'load' });
    await page.waitForTimeout(1200); // el video y los sockets nunca dejan la red quieta
    await page.addScriptTag({ content: axeSource });
    const result = await page.evaluate(async () => window.axe.run(document, {
        runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] },
    }));
    return result.violations.map((v) => ({ url, id: v.id, impact: v.impact, nodes: v.nodes.length, help: v.help, sample: v.nodes[0]?.target?.join(' ') }));
};

const browser = await chromium.launch();
const violations = [];

for (const viewport of [{ width: 390, height: 844 }, { width: 1366, height: 900 }]) {
    const page = await browser.newPage({ viewport, reducedMotion: 'reduce' });
    for (const url of publicPages) {
        violations.push(...(await audit(page, url)).map((v) => ({ ...v, viewport: viewport.width })));
    }
    await page.close();
}

// THEME=light audita el panel en modo claro (por defecto, el oscuro).
const adminContext = await browser.newContext({ viewport: { width: 1366, height: 900 } });
if (process.env.THEME === 'light') {
    await adminContext.addInitScript(() => localStorage.setItem('filament:theme:mode', 'light'));
}
const admin = await adminContext.newPage();
await admin.goto(base + '/admin/login');
await admin.fill('input[type=email]', process.env.ADMIN_EMAIL ?? 'owner@olympo.test');
await admin.fill('input[type=password]', process.env.ADMIN_PASSWORD ?? 'password');
await admin.click('button[type=submit]');
await admin.waitForURL('**/admin');
for (const url of adminPages) {
    violations.push(...(await audit(admin, url)).map((v) => ({ ...v, viewport: 1366 })));
}

await browser.close();

if (violations.length === 0) {
    console.log('OK: sin violaciones WCAG 2.1 AA en', publicPages.length * 2 + adminPages.length, 'páginas.');
} else {
    console.table(violations);
    process.exitCode = 1;
}
