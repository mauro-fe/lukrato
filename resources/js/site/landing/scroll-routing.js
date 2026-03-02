/**
 * Scroll Routing — URL-based smooth scroll by slug
 * Handles /planos, /funcionalidades etc. as virtual routes that scroll to sections.
 */

export function init() {
    function getBasePath() {
        const meta = document.querySelector('meta[name="base-url"]');
        if (meta?.content) {
            try { return new URL(meta.content).pathname.replace(/\/?$/, '/'); }
            catch { return meta.content.replace(/\/?$/, '/'); }
        }
        const path = location.pathname;
        const idx = path.indexOf('/public/');
        if (idx !== -1) return path.substring(0, idx + 8);
        return '/';
    }

    const basePath = getBasePath();
    const slugs = [
        'funcionalidades',
        'beneficios',
        'gamificacao',
        'planos',
        'indicacao',
        'contato'
    ];

    function headerOffset() {
        const h = document.querySelector('header, .lk-site-header');
        return h ? h.offsetHeight + 10 : 0;
    }

    function scrollToSlug(slug) {
        const el = document.getElementById(slug);
        if (!el) return;
        const y = el.getBoundingClientRect().top + window.pageYOffset - headerOffset();
        window.scrollTo({ top: y, behavior: 'smooth' });
    }

    function getSlugFromPath(pathname) {
        if (!pathname.startsWith(basePath)) return null;
        const rest = pathname.slice(basePath.length).replace(/^\/+/, '');
        const slug = rest.split('/')[0];
        return slugs.includes(slug) ? slug : null;
    }

    // Direct load (/planos)
    const slug = getSlugFromPath(location.pathname);
    if (slug) {
        history.replaceState(null, '', basePath + slug);
        requestAnimationFrame(() => scrollToSlug(slug));
    }

    // Menu clicks (without #)
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[href]');
        if (!link) return;

        let href = link.getAttribute('href');
        if (!href) return;

        try { href = new URL(href, location.origin).pathname; }
        catch { return; }

        const s = getSlugFromPath(href);
        if (!s) return;

        e.preventDefault();
        history.pushState(null, '', basePath + s);
        scrollToSlug(s);
    });

    // Back / forward
    window.addEventListener('popstate', () => {
        const s = getSlugFromPath(location.pathname);
        if (s) scrollToSlug(s);
    });
}
