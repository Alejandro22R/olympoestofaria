const EASE = 'cubic-bezier(0.22, 1, 0.36, 1)';
const reduce = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export function registerComponents(Alpine) {
    /**
     * Galería filtrable con técnica FLIP: las tarjetas se reordenan animando solo transform/opacity.
     * Cada tarjeta lleva data-tags="marca:vw material:leather servicio:seats".
     */
    Alpine.data('flipGallery', () => ({
        active: 'all',
        matches(el) {
            return this.active === 'all' || (el.dataset.tags ?? '').split(' ').includes(this.active);
        },
        filter(value) {
            const items = [...this.$refs.grid.children];
            const first = new Map(items.map((el) => [el, el.hidden ? null : el.getBoundingClientRect()]));
            this.active = value;
            items.forEach((el) => { el.hidden = !this.matches(el); });
            if (reduce()) return;
            items.forEach((el) => {
                if (el.hidden) return;
                const f = first.get(el);
                const l = el.getBoundingClientRect();
                if (!f) {
                    el.animate([{ opacity: 0, transform: 'scale(0.92)' }, { opacity: 1, transform: 'none' }], { duration: 300, easing: EASE });
                    return;
                }
                const dx = f.left - l.left;
                const dy = f.top - l.top;
                if (dx || dy) {
                    el.animate([{ transform: `translate(${dx}px, ${dy}px)` }, { transform: 'none' }], { duration: 450, easing: EASE });
                }
            });
        },
    }));

    /**
     * Antes y después arrastrable. La capa "antes" se recorta con dos translateX opuestos
     * (sin animar width ni clip-path). Accesible con el teclado (input range).
     */
    Alpine.data('beforeAfter', () => ({
        pos: 50,
        dragging: false,
        set(clientX) {
            const r = this.$el.getBoundingClientRect();
            this.pos = Math.min(100, Math.max(0, ((clientX - r.left) / r.width) * 100));
        },
        outer() { return `transform: translateX(${this.pos - 100}%)`; },
        inner() { return `transform: translateX(${100 - this.pos}%)`; },
        handle() { return `transform: translateX(${this.pos}%)`; },
    }));

    /** Panel lateral con el detalle del material (entra con translateX). */
    Alpine.data('materialPanel', () => ({
        open: false,
        item: null,
        show(item) {
            this.item = item;
            this.open = true;
            this.$nextTick(() => this.$refs.close?.focus());
        },
        close() { this.open = false; },
    }));

    /** Demo del configurador en la landing (mismo visualizador que el constructor). */
    Alpine.data('configuratorDemo', (initial) => ({
        demo: { seats: '#B5651D', steering_wheel: '#111111', doors: '#B5651D', headliner: '#2E2E2E', flooring: '#141414', stitching: '#C9C9C9', ...initial },
        set(zone, color) { this.demo = { ...this.demo, [zone]: color }; },
    }));
}
