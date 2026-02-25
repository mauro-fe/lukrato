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
 * @version 1.1.0
 */
(function () {
    'use strict';

    // ── CONFIGURAÇÃO ─────────────────────────────────────────────
    const LUCIDE_DEFAULTS = {
        nameAttr: 'data-lucide',
        'stroke-width': 2,
    };

    // ── STRIP SVG SIZE ATTRS ─────────────────────────────────────
    // Lucide JS hardcodes width="24" height="24" como atributos HTML no SVG.
    // Esses atributos conflitam com CSS em produção (podem ter prioridade
    // sobre regras CSS dependendo do cache/ordem de carregamento).
    // Removê-los garante que APENAS o CSS controle o tamanho.
    function stripSvgSizeAttrs() {
        var svgs = document.querySelectorAll('svg.lucide');
        for (var i = 0; i < svgs.length; i++) {
            if (svgs[i].hasAttribute('width')) svgs[i].removeAttribute('width');
            if (svgs[i].hasAttribute('height')) svgs[i].removeAttribute('height');
        }
    }

    // ── MONKEY-PATCH lucide.createIcons ──────────────────────────
    // Vários arquivos (categorias-manager.js, faturas, etc.) chamam
    // lucide.createIcons() diretamente. Sem o patch, esses calls
    // recriam TODOS os SVGs com width="24" height="24" padrão.
    // O patch intercepta TODAS as chamadas e garante que:
    // 1. SVGs já processados NÃO sejam re-processados (evita corrupção do DOM)
    // 2. Atributos de tamanho sejam removidos após cada criação de ícones.
    function patchCreateIcons() {
        if (typeof lucide === 'undefined' || !lucide.createIcons) return;
        if (lucide._lkPatched) return; // Já patcheado

        var originalCreateIcons = lucide.createIcons.bind(lucide);

        lucide.createIcons = function (opts) {
            // Proteger SVGs já processados: remove data-lucide temporariamente
            var existingSvgs = document.querySelectorAll('svg[data-lucide]');
            var savedAttrs = [];
            for (var i = 0; i < existingSvgs.length; i++) {
                savedAttrs.push(existingSvgs[i].getAttribute('data-lucide'));
                existingSvgs[i].removeAttribute('data-lucide');
            }

            try {
                originalCreateIcons(opts);
            } catch (err) {
                console.error('[Lucide] Erro em createIcons:', err);
            }

            // Restaurar data-lucide nos SVGs protegidos
            for (var j = 0; j < existingSvgs.length; j++) {
                if (existingSvgs[j].parentNode && savedAttrs[j]) {
                    existingSvgs[j].setAttribute('data-lucide', savedAttrs[j]);
                }
            }

            // SEMPRE remove width/height após qualquer createIcons()
            stripSvgSizeAttrs();
        };

        lucide._lkPatched = true;
    }

    // ── INIT ─────────────────────────────────────────────────────
    function initIcons() {
        if (typeof lucide === 'undefined') {
            console.warn('[Lucide] Biblioteca não carregada. Verifique o script.');
            return;
        }

        // Garantir que o patch esteja ativo antes de qualquer chamada
        patchCreateIcons();

        try {
            lucide.createIcons({
                nameAttr: LUCIDE_DEFAULTS.nameAttr,
                attrs: {
                    'stroke-width': LUCIDE_DEFAULTS['stroke-width'],
                },
            });
            // stripSvgSizeAttrs() já é chamado automaticamente pelo patch
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
                        // Só processa <i data-lucide> (não SVGs já processados)
                        if (node.tagName === 'I' && node.hasAttribute('data-lucide')) {
                            needsRefresh = true;
                            break;
                        }
                        if (node.querySelector && node.querySelector('i[data-lucide]')) {
                            needsRefresh = true;
                            break;
                        }
                    }
                }
                if (needsRefresh) break;
            }

            if (needsRefresh) {
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
