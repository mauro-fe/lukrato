/**
 * ============================================================================
 * LUKRATO — Scroll to Top + AOS Initialization
 * ============================================================================
 * Extraído de views/admin/partials/footer.php (inline <script> block)
 *
 * Responsabilidades:
 * - Inicializar AOS (Animate On Scroll) com config mobile-friendly
 * - Botão scroll-to-top (show/hide on scroll, click handler)
 * ============================================================================
 */

// ── AOS Initialization ─────────────────────────────────────────────────────
function initAOS() {
    if (typeof AOS === 'undefined') return;

    const isMobile = window.matchMedia('(max-width: 767px)').matches;
    const aosOptions = {
        offset: isMobile ? 50 : 120,
        delay: 0,
        duration: isMobile ? 500 : 1000,
        easing: 'ease',
        once: true,
        mirror: false,
        anchorPlacement: 'top-bottom',
        startEvent: 'DOMContentLoaded',
        disable: false,
        debounceDelay: 50,
        throttleDelay: 99,
        useClassNames: false,
        disableMutationObserver: false,
        animatedClassName: 'aos-animate',
        initClassName: 'aos-init'
    };

    AOS.init(aosOptions);

    // Recálculo das posições após carregamento completo no mobile
    window.addEventListener('load', () => AOS.refresh());
}

// ── Scroll to Top Button ────────────────────────────────────────────────────
function initScrollToTop() {
    const scrollBtn = document.getElementById('scrollToTopBtn');
    if (!scrollBtn) return;

    const toggleScrollButton = () => {
        if (window.scrollY > 300) {
            scrollBtn.classList.add('show');
        } else {
            scrollBtn.classList.remove('show');
        }
    };

    scrollBtn.addEventListener('click', (e) => {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    window.addEventListener('scroll', toggleScrollButton);
    toggleScrollButton();
}

// ── Init ────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    initAOS();
    initScrollToTop();
});
