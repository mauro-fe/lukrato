/**
 * Menu — Hamburger toggle + overlay
 */

export function init() {
    if (window.lkLandingBootstrapped) return;
    window.lkLandingBootstrapped = true;

    const burger = document.querySelector('.lk-site-burger');
    const header = document.querySelector('.lk-site-header');
    const body   = document.body;

    if (!burger || !header) return;

    const overlay = document.createElement('div');
    overlay.className = 'lk-site-menu-overlay';
    header.appendChild(overlay);

    function closeMenu() {
        header.classList.remove('is-open');
        body.classList.remove('lk-nav-open');
        burger.setAttribute('aria-expanded', 'false');
    }

    function toggleMenu() {
        const willOpen = !header.classList.contains('is-open');
        header.classList.toggle('is-open', willOpen);
        body.classList.toggle('lk-nav-open', willOpen);
        burger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    }

    burger.setAttribute('aria-expanded', 'false');
    burger.addEventListener('click', toggleMenu);
    overlay.addEventListener('click', closeMenu);

    window.addEventListener('resize', () => {
        if (window.innerWidth > 768 && header.classList.contains('is-open')) closeMenu();
    });

    header.addEventListener('click', (e) => {
        const link = e.target.closest('.lk-site-nav-link');
        if (link && header.classList.contains('is-open')) closeMenu();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && header.classList.contains('is-open')) closeMenu();
    });
}
