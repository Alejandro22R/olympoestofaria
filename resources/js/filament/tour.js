// Tutoriales guiados del panel (driver.js). Los pasos llegan del servidor ya traducidos
// en <script id="olympo-tour" type="application/json"> (ver resources/views/filament/tour.blade.php).
import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';

const readConfig = () => {
    const el = document.getElementById('olympo-tour');
    if (! el) return null;
    try {
        return JSON.parse(el.textContent);
    } catch {
        return null;
    }
};

// Un paso apunta a un elemento solo si existe y se ve (p. ej. el menú lateral se oculta en móvil);
// si no, el globo aparece centrado con la explicación.
const resolve = (selector) => {
    if (! selector) return undefined;
    const el = document.querySelector(selector);
    if (! el) return undefined;
    const rect = el.getBoundingClientRect();
    return rect.width > 0 && rect.height > 0 ? el : undefined;
};

const markCompleted = (config) => {
    if (config.completed || ! config.completeUrl) return;
    config.completed = true;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    fetch(config.completeUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
    }).catch(() => {});
};

const start = (config) => {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const tour = driver({
        showProgress: true,
        progressText: config.labels.progress,
        nextBtnText: config.labels.next,
        prevBtnText: config.labels.prev,
        doneBtnText: config.labels.done,
        popoverClass: 'olympo-tour',
        overlayColor: '#000',
        overlayOpacity: 0.6,
        stagePadding: 6,
        stageRadius: 12,
        smoothScroll: ! reduceMotion,
        animate: ! reduceMotion,
        onDestroyed: () => markCompleted(config),
        steps: config.steps.map((step) => ({
            element: resolve(step.element),
            popover: { title: step.title, description: step.body, side: step.side ?? 'bottom', align: 'start' },
        })),
    });
    tour.drive();
};

document.addEventListener('click', (event) => {
    if (! event.target.closest('[data-olympo-tour-start]')) return;
    event.preventDefault();
    const config = readConfig();
    if (config) start(config);
});

const boot = () => {
    const config = readConfig();
    if (config?.autostart) {
        // Espera a que Filament termine de pintar la página (tablas, widgets).
        window.setTimeout(() => start(config), 800);
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
