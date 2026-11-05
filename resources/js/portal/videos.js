/**
 * Videos del portal: solo se reproducen visibles (IntersectionObserver), se pausan al salir
 * y en móvil hay como máximo 2 reproduciéndose. Con "reducir movimiento" o ahorro de datos, solo póster.
 */
export function registerVideoAutoplay() {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const saveData = navigator.connection?.saveData || ['slow-2g', '2g'].includes(navigator.connection?.effectiveType);
    if (reduce || saveData) return;

    const maxPlaying = window.matchMedia('(max-width: 767px)').matches ? 2 : 4;
    const playing = new Set();

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(({ target, isIntersecting }) => {
            if (isIntersecting && playing.size < maxPlaying) {
                if (!target.src && target.dataset.src) {
                    target.querySelectorAll('source[data-src]').forEach((s) => { s.src = s.dataset.src; });
                    target.load();
                }
                target.play().then(() => playing.add(target)).catch(() => {});
            } else if (!isIntersecting) {
                target.pause();
                playing.delete(target);
            }
        });
    }, { threshold: 0.25 });

    const scan = () => document.querySelectorAll('video[data-autoplay]').forEach((v) => observer.observe(v));
    document.addEventListener('DOMContentLoaded', scan);
    document.addEventListener('livewire:navigated', scan);

    // Hover-play en tarjetas (y toque en móvil).
    document.addEventListener('pointerover', (e) => {
        const v = e.target.closest?.('[data-hover-video]')?.querySelector('video');
        if (v) { v.querySelectorAll('source[data-src]').forEach((s) => { if (!s.src) { s.src = s.dataset.src; v.load(); } }); v.play().catch(() => {}); }
    });
    document.addEventListener('pointerout', (e) => {
        const card = e.target.closest?.('[data-hover-video]');
        if (card && !card.contains(e.relatedTarget)) card.querySelector('video')?.pause();
    });
}
