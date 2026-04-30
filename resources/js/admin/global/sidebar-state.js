/**
 * ============================================================================
 * LUKRATO — Sidebar Collapsed State (Pre-render)
 * ============================================================================
 * A lógica principal agora é feita pelo script inline no <head> do header.php
 * para garantir que execute ANTES do primeiro paint.
 *
 * Este módulo existe apenas como fallback e para manter compatibilidade
 * com o import no index.js.
 * ============================================================================
 */

(function () {
    try {
        const STORAGE_KEY = 'lk.sidebar';
        const prefersCollapsed = localStorage.getItem(STORAGE_KEY) === '1';
        const isDesktop = window.matchMedia('(min-width: 993px)').matches;

        // O inline script no <head> normalmente já cuida disso,
        // mas garantimos aqui como fallback
        if (prefersCollapsed && isDesktop && !document.body.classList.contains('sidebar-collapsed')) {
            document.body.classList.add('sidebar-collapsed');
        }
    } catch {
        // silently ignore
    }
})();
