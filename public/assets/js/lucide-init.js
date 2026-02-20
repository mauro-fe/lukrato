/**
 * Lucide Icons — Inicialização Global + Auto-refresh para conteúdo dinâmico
 *
 * Substitui Font Awesome por Lucide Icons no Lukrato.
 * Brand icons (WhatsApp, Pix, Visa, etc.) continuam usando FA Brands.
 *
 * Uso em HTML:
 *   <i data-lucide="check"></i>
 *   <i data-lucide="wallet" class="icon-sm"></i>
 *   <i data-lucide="loader-2" class="icon-spin"></i>
 *
 * Uso em JS (após inserir HTML dinâmico):
 *   LK.refreshIcons()          — re-escaneia todo o DOM
 *   LK.refreshIcons(container) — escaneia só dentro do container
 *
 * @version 1.0.0
 */
(function () {
    'use strict';

    // ── CONFIGURAÇÃO ─────────────────────────────────────────────
    const LUCIDE_DEFAULTS = {
        nameAttr: 'data-lucide',
        'stroke-width': 2,
        // NÃO definimos width/height aqui — deixamos o CSS controlar via 1em
        // Isso permite que ícones herdem o tamanho da fonte do elemento pai
    };

    // ── INIT ─────────────────────────────────────────────────────
    function initIcons(root) {
        if (typeof lucide === 'undefined') {
            console.warn('[Lucide] Biblioteca não carregada. Verifique o script.');
            return;
        }
        try {
            // Lucide.createIcons() busca todos [data-lucide] e substitui por <svg>
            // Não passamos width/height — o CSS (.lucide { width:1em; height:1em }) controla o tamanho
            lucide.createIcons({
                nameAttr: LUCIDE_DEFAULTS.nameAttr,
                attrs: {
                    'stroke-width': LUCIDE_DEFAULTS['stroke-width'],
                },
            });
        } catch (err) {
            console.error('[Lucide] Erro ao inicializar ícones:', err);
        }
    }

    // ── MUTATION OBSERVER ────────────────────────────────────────
    // Detecta automaticamente novos elementos [data-lucide] adicionados ao DOM
    // (modais, conteúdo AJAX, SweetAlert, etc.)
    let observerDebounce = null;

    function setupObserver() {
        if (typeof MutationObserver === 'undefined') return;

        const observer = new MutationObserver((mutations) => {
            let needsRefresh = false;

            for (const mutation of mutations) {
                if (mutation.type === 'childList') {
                    for (const node of mutation.addedNodes) {
                        if (node.nodeType !== Node.ELEMENT_NODE) continue;
                        if (node.hasAttribute?.('data-lucide') || node.querySelector?.('[data-lucide]')) {
                            needsRefresh = true;
                            break;
                        }
                    }
                }
                if (needsRefresh) break;
            }

            if (needsRefresh) {
                // Debounce para evitar múltiplas chamadas seguidas
                clearTimeout(observerDebounce);
                observerDebounce = setTimeout(() => initIcons(), 50);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }

    // ── BOOTSTRAP ────────────────────────────────────────────────
    function bootstrap() {
        initIcons();
        setupObserver();

        // Expor no namespace LK
        window.LK = window.LK || {};
        window.LK.refreshIcons = function (container) {
            initIcons(container || document);
        };
    }

    // Inicializar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrap);
    } else {
        bootstrap();
    }

    // Fallback: re-init após window.load (para elementos carregados depois)
    window.addEventListener('load', () => {
        setTimeout(() => initIcons(), 100);
    });

})();
