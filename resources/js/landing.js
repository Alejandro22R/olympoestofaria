/**
 * Landing de impacto (PLAN.md §2.2). Solo se carga en la home.
 * Reglas: animar únicamente transform y opacity; 200–300 ms en interacciones, 600–900 ms en entradas;
 * easing power3.out; sin JS o con "reducir movimiento" todo el contenido queda visible y estático.
 */
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';

gsap.registerPlugin(ScrollTrigger);

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    || document.documentElement.hasAttribute('data-reduce-motion');
let lenis = null;
let ctx = null;

function initSmoothScroll() {
    lenis = new Lenis({ duration: 1.1, easing: (t) => 1 - Math.pow(1 - t, 3) });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((time) => lenis?.raf(time * 1000));
    gsap.ticker.lagSmoothing(0);
}

/**
 * El titular y el subtítulo entran con CSS desde el primer pintado (no esperan a este bundle,
 * así el LCP no se retrasa). Aquí solo se anima el logo.
 */
function heroIntro() {
    gsap.fromTo('[data-hero-logo]', { scale: 0.6, rotate: -20 }, { scale: 1, rotate: 0, duration: 0.9, ease: 'power3.out' });
}

function circleTransition() {
    const circle = document.querySelector('[data-circle]');
    if (!circle) return;
    gsap.fromTo(circle, { scale: 0.05 }, {
        scale: 1,
        ease: 'none',
        scrollTrigger: { trigger: '[data-circle-section]', start: 'top bottom', end: 'center center', scrub: true },
    });
}

function reveals() {
    gsap.utils.toArray('[data-reveal]').forEach((el) => {
        gsap.fromTo(el, { opacity: 0, y: 40 }, {
            opacity: 1, y: 0, duration: 0.8, ease: 'power3.out',
            scrollTrigger: { trigger: el, start: 'top 85%' },
        });
    });
    gsap.utils.toArray('[data-stagger]').forEach((group) => {
        gsap.fromTo(group.children, { opacity: 0, y: 30 }, {
            opacity: 1, y: 0, duration: 0.7, ease: 'power3.out', stagger: 0.08,
            scrollTrigger: { trigger: group, start: 'top 80%' },
        });
    });
}

function processTimeline() {
    const line = document.querySelector('[data-process-line]');
    if (!line) return;
    gsap.fromTo(line, { scaleY: 0 }, {
        scaleY: 1, ease: 'none', transformOrigin: 'top',
        scrollTrigger: { trigger: '[data-process]', start: 'top 60%', end: 'bottom 60%', scrub: true },
    });
}

function counters() {
    document.querySelectorAll('[data-count]').forEach((el) => {
        const target = Number(el.dataset.count);
        const obj = { v: 0 };
        ScrollTrigger.create({
            trigger: el, start: 'top 90%', once: true,
            onEnter: () => gsap.to(obj, {
                v: target, duration: 1.6, ease: 'power3.out',
                onUpdate: () => { el.textContent = Math.round(obj.v).toLocaleString(document.documentElement.lang); },
            }),
        });
    });
}

/** Inclinación sutil (tilt) de las tarjetas con el puntero, solo en dispositivos con hover. */
function tilt() {
    if (!window.matchMedia('(hover: hover)').matches) return;
    document.querySelectorAll('[data-tilt]').forEach((card) => {
        const rotX = gsap.quickTo(card, 'rotationX', { duration: 0.3, ease: 'power3.out' });
        const rotY = gsap.quickTo(card, 'rotationY', { duration: 0.3, ease: 'power3.out' });
        gsap.set(card, { transformPerspective: 800 });
        card.addEventListener('pointermove', (e) => {
            const r = card.getBoundingClientRect();
            rotY(((e.clientX - r.left) / r.width - 0.5) * 8);
            rotX(-((e.clientY - r.top) / r.height - 0.5) * 8);
        });
        card.addEventListener('pointerleave', () => { rotX(0); rotY(0); });
    });
}

/** Parallax suave de la textura del material con el cursor. */
function materialParallax() {
    if (!window.matchMedia('(hover: hover)').matches) return;
    document.querySelectorAll('[data-parallax]').forEach((box) => {
        const layer = box.querySelector('[data-parallax-layer]');
        if (!layer) return;
        const x = gsap.quickTo(layer, 'x', { duration: 0.6, ease: 'power3.out' });
        const y = gsap.quickTo(layer, 'y', { duration: 0.6, ease: 'power3.out' });
        box.addEventListener('pointermove', (e) => {
            const r = box.getBoundingClientRect();
            x(((e.clientX - r.left) / r.width - 0.5) * -24);
            y(((e.clientY - r.top) / r.height - 0.5) * -24);
        });
    });
}

function init() {
    if (reduceMotion) return;
    document.documentElement.classList.add('js-motion');
    initSmoothScroll();
    ctx = gsap.context(() => {
        heroIntro();
        circleTransition();
        reveals();
        processTimeline();
        counters();
        tilt();
        materialParallax();
    });
    // Recalcula posiciones cuando cargan imágenes/fuentes.
    window.addEventListener('load', () => ScrollTrigger.refresh());
}

function destroy() {
    ctx?.revert();
    ScrollTrigger.getAll().forEach((t) => t.kill());
    lenis?.destroy();
    lenis = null;
    document.documentElement.classList.remove('js-motion');
}

// Compatibilidad con wire:navigate: limpiar al salir y reiniciar al volver.
document.addEventListener('livewire:navigating', destroy);
document.addEventListener('livewire:navigated', () => { if (document.querySelector('[data-landing]')) init(); });

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
