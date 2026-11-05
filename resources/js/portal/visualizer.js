/**
 * Visualizador del interior (constructor y demo de la landing).
 * Cada zona tiene dos capas: la de abajo con el color actual y la de arriba que
 * entra con un fundido de opacidad con el color nuevo (solo se anima opacity).
 */
export function registerVisualizer(Alpine) {
    Alpine.data('interiorVisualizer', (initial = {}) => ({
        zones: {},
        init() {
            const colors = typeof initial === 'function' ? initial() : initial;
            Object.entries(colors).forEach(([zone, color]) => {
                this.zones[zone] = { base: color, top: color, fading: false };
            });
        },
        paint(zone, color) {
            if (!color) return;
            const z = this.zones[zone] ?? (this.zones[zone] = { base: color, top: color, fading: false });
            if (z.top === color && !z.fading) return;
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            z.base = z.top;
            z.top = color;
            if (reduce) {
                z.base = color;
                return;
            }
            z.fading = true;
            requestAnimationFrame(() => requestAnimationFrame(() => { z.fading = false; }));
        },
        baseOf(zone) { return this.zones[zone]?.base ?? 'var(--color-ink-700)'; },
        topOf(zone) { return this.zones[zone]?.top ?? 'var(--color-ink-700)'; },
        opacityOf(zone) { return this.zones[zone]?.fading ? 0 : 1; },
    }));
}
