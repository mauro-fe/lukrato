/**
 * Header Bridge — moves simple landing interactions out of Alpine inline expressions
 * so the CSP build can keep x-data/x-show bindings without parsing window-based code.
 */

function getAlpineState(element) {
    return element && element.__x && element.__x.$data ? element.__x.$data : null;
}

export function init() {
    const header = document.querySelector('[data-landing-header]');
    const headerInner = document.querySelector('[data-landing-header-inner]');
    const logo = document.querySelector('[data-landing-logo]');
    const stickyCta = document.querySelector('[data-sticky-cta]');
    const hero = document.querySelector('section[aria-label*=principal]');

    let ticking = false;

    function syncHeaderScrolled(scrolled) {
        if (!header) return;

        header.dataset.scrolled = scrolled ? 'true' : 'false';
        header.classList.toggle('bg-transparent', !scrolled);
        header.classList.toggle('backdrop-blur-none', !scrolled);
        header.classList.toggle('border-transparent', !scrolled);
        header.classList.toggle('bg-white/80', scrolled);
        header.classList.toggle('dark:bg-[#1c2c3c]/80', scrolled);
        header.classList.toggle('backdrop-blur-xl', scrolled);
        header.classList.toggle('shadow-[0_1px_3px_rgba(0,0,0,0.08)]', scrolled);
        header.classList.toggle('dark:shadow-[0_1px_3px_rgba(0,0,0,0.3)]', scrolled);
        header.classList.toggle('border-gray-200/50', scrolled);
        header.classList.toggle('dark:border-white/10', scrolled);

        if (headerInner) {
            headerInner.classList.toggle('h-20', !scrolled);
            headerInner.classList.toggle('h-16', scrolled);
        }

        if (logo) {
            logo.classList.toggle('h-8', !scrolled);
            logo.classList.toggle('sm:h-14', !scrolled);
            logo.classList.toggle('h-7', scrolled);
            logo.classList.toggle('sm:h-12', scrolled);
        }

        const state = getAlpineState(header);
        if (state && state.scrolled !== scrolled) {
            state.scrolled = scrolled;
        }
    }

    function updateScrollState() {
        const scrolled = (window.scrollY || window.pageYOffset || 0) > 50;
        syncHeaderScrolled(scrolled);
        ticking = false;
    }

    if (header) {
        syncHeaderScrolled((window.scrollY || window.pageYOffset || 0) > 50);
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(updateScrollState);
                ticking = true;
            }
        }, { passive: true });
    }

    if (stickyCta && hero && typeof IntersectionObserver !== 'undefined') {
        const stickyState = getAlpineState(stickyCta);
        const observer = new IntersectionObserver((entries) => {
            const entry = entries[0];
            if (!stickyState || !entry) return;
            stickyState.showSticky = !entry.isIntersecting;
        }, { threshold: 0 });

        observer.observe(hero);
    }
}
