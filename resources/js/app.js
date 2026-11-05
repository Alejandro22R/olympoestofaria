// Punto de entrada del portal (todas las páginas). Lo pesado (GSAP/Lenis) va en landing.js;
// Echo (tiempo real) en echo.js, solo donde se use.
import { registerComponents } from './portal/components';
import { registerVideoAutoplay } from './portal/videos';
import { registerVisualizer } from './portal/visualizer';

const register = (Alpine) => {
    registerVisualizer(Alpine);
    registerComponents(Alpine);
};

if (window.Livewire) {
    // Páginas con componentes Livewire (constructor): Livewire trae y arranca su propio Alpine.
    document.addEventListener('alpine:init', () => register(window.Alpine));
} else {
    // Páginas sin Livewire (landing, catálogo): Alpine liviano, sin el runtime de Livewire.
    import('alpinejs').then(({ default: Alpine }) => {
        window.Alpine = Alpine;
        register(Alpine);
        Alpine.start();
    });
}

registerVideoAutoplay();
