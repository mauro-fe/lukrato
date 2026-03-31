/**
 * ============================================================================
 * LUKRATO — Categorias / Entry Point
 * ============================================================================
 * Bootstraps the categorias manager: imports all modules, wires up
 * DOMContentLoaded, and exposes globals that PHP views expect
 * (window.categoriasManager with editarCategoria, excluirCategoria, etc.).
 * ============================================================================
 */

import '../../../css/admin/categorias/index.css';
import { Modules } from './state.js';
import { CategoriasManager, EventListeners } from './app.js';
import { SubcategoriasModule } from './subcategorias.js';
import { initCustomize } from './customize.js';

// ─── Guard against double-loading ────────────────────────────────────────────
// Bloqueia admin-categorias-index.js (loader legado) — este arquivo é o principal
if (!window.__LK_CATEGORIAS_LOADER__) {
    window.__LK_CATEGORIAS_LOADER__ = true;

    // ─── Expose globals for PHP views ────────────────────────────────────────
    // onclick="" handlers in rendered HTML reference `categoriasManager.xxx`
    window.categoriasManager = CategoriasManager;

    // ─── Bootstrap ───────────────────────────────────────────────────────────
    const init = () => {
        const bootstrap = () => {
            initCustomize();
            CategoriasManager.init();
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootstrap);
        } else {
            bootstrap();
        }
    };

    init();
}

// ─── Named exports for other ES modules that may need them ───────────────────
export { Modules, CategoriasManager };
