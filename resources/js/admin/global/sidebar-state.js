/**
 * ============================================================================
 * LUKRATO — Sidebar Collapsed State (Pre-render)
 * ============================================================================
 * Extraído de views/admin/partials/header.php (inline <script> block)
 *
 * Restaura o estado colapsado da sidebar antes do paint para evitar
 * flash de layout. Executa sincronamente antes do body render.
 * ============================================================================
 */

(function () {
    try {
        const STORAGE_KEY = 'lk.sidebar';
        const prefersCollapsed = localStorage.getItem(STORAGE_KEY) === '1';
        const isDesktop = window.matchMedia('(min-width: 993px)').matches;

        if (prefersCollapsed && isDesktop) {
            document.body.classList.add('sidebar-collapsed');
        }
    } catch (err) {
        console.error('Erro ao restaurar estado da sidebar:', err);
    }
})();
