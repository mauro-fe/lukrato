/**
 * Back to Top — show/hide button on scroll, smooth scroll to top
 */

export function init() {
    const btn = document.getElementById('lkBackToTop');
    if (!btn) return;

    const SHOW_AFTER = 420;
    let ticking = false;

    function update() {
        const y = window.scrollY || document.documentElement.scrollTop || 0;
        if (y > SHOW_AFTER) btn.classList.add('is-visible');
        else btn.classList.remove('is-visible');
        ticking = false;
    }

    window.addEventListener('scroll', () => {
        if (!ticking) { window.requestAnimationFrame(update); ticking = true; }
    }, { passive: true });

    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    update();
}
