/**
 * ============================================
 * LUKRATO ACCESSIBILITY ENHANCEMENTS
 * ============================================
 * Melhorias de acessibilidade dinâmicas para
 * suporte a leitores de tela e navegação por teclado.
 */

(function () {
    'use strict';

    const MODAL_SELECTOR = [
        '.modal',
        '.lk-modal-overlay',
        '.fin-modal-overlay',
        '.payment-modal',
        '.modal-fatura-overlay',
        '.parcelas-modal-overlay',
        '[id$="CustomizeModalOverlay"]',
        '.swal2-popup',
        '[data-lk-modal-scope]'
    ].join(', ');

    const MODAL_TITLE_SELECTOR = [
        '.modal-title',
        '.lk-modal-title',
        '.swal2-title',
        '.payment-modal__title',
        '.fin-modal-header h3',
        '.sys-customize-title',
        '.rel-customize-title',
        '.dash-modal__title',
        '[id$="CustomizeModalTitle"]',
        '.card-detail-info h2',
        '.parcelas-modal-title',
        'h2',
        'h3'
    ].join(', ');

    const MODAL_DESCRIPTION_SELECTOR = [
        '.modal-body p',
        '.lk-modal-description',
        '.swal2-html-container',
        '.payment-modal__subtitle',
        '.sys-customize-desc',
        '.rel-customize-desc',
        '.dash-modal__desc',
        '[class*="customize-desc"]'
    ].join(', ');

    const MODAL_CLOSE_SELECTOR = [
        '.btn-close',
        '.close',
        '.lk-modal-close-btn',
        '.swal2-close',
        '.payment-modal__close',
        '.btn-fechar-fatura',
        '.parcelas-modal-close',
        '.sys-customize-close',
        '.rel-customize-close',
        '.dash-modal__close',
        '[data-action="fecharModalPost"]',
        '[data-action="fecharModalCupom"]',
        '[data-action="close-parcelas"]',
        '[data-close-modal]',
        '[aria-label="Fechar"]',
        '[aria-label="Fechar modal"]'
    ].join(', ');

    // ============================================
    // ARIA LIVE REGION PARA NOTIFICAÇÕES
    // ============================================

    function createLiveRegion() {
        // Verificar se já existe
        if (document.getElementById('lk-live-region')) return;

        const liveRegion = document.createElement('div');
        liveRegion.id = 'lk-live-region';
        liveRegion.setAttribute('role', 'status');
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.className = 'sr-only'; // Visualmente oculto
        liveRegion.style.cssText = `
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        `;
        document.body.appendChild(liveRegion);

        // Region para alertas urgentes
        const alertRegion = document.createElement('div');
        alertRegion.id = 'lk-alert-region';
        alertRegion.setAttribute('role', 'alert');
        alertRegion.setAttribute('aria-live', 'assertive');
        alertRegion.setAttribute('aria-atomic', 'true');
        alertRegion.className = 'sr-only';
        alertRegion.style.cssText = liveRegion.style.cssText;
        document.body.appendChild(alertRegion);
    }

    /**
     * Anuncia uma mensagem para leitores de tela
     */
    function announce(message, priority = 'polite') {
        const regionId = priority === 'assertive' ? 'lk-alert-region' : 'lk-live-region';
        const region = document.getElementById(regionId);

        if (region) {
            // Limpar e depois adicionar para garantir que seja lido
            region.textContent = '';
            setTimeout(() => {
                region.textContent = message;
            }, 50);
        }
    }

    // ============================================
    // MELHORAR MODAIS
    // ============================================

    function enhanceModals() {
        document.querySelectorAll(MODAL_SELECTOR).forEach(enhanceModal);

        // Observar novos modais
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        if (node.matches?.(MODAL_SELECTOR)) {
                            enhanceModal(node);
                        }
                        // Buscar modais dentro do nó
                        const innerModals = node.querySelectorAll?.(MODAL_SELECTOR);
                        innerModals?.forEach(enhanceModal);
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    function enhanceModal(modal) {
        if (!(modal instanceof HTMLElement) || modal.classList.contains('modal-backdrop')) {
            return;
        }

        if (modal.dataset.lkAccessibilityEnhanced === '1') {
            return;
        }

        modal.dataset.lkAccessibilityEnhanced = '1';

        // Garantir role="dialog"
        if (!modal.getAttribute('role')) {
            modal.setAttribute('role', 'dialog');
        }

        // Garantir aria-modal
        modal.setAttribute('aria-modal', 'true');
        if (!modal.hasAttribute('aria-hidden')) {
            const isVisible = modal.classList.contains('active')
                || modal.classList.contains('show')
                || modal.classList.contains('payment-modal--open');
            modal.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
        }

        // Buscar título e associar
        const title = modal.querySelector(MODAL_TITLE_SELECTOR);
        if (title && !modal.getAttribute('aria-labelledby')) {
            const titleId = title.id || `modal-title-${Date.now()}`;
            title.id = titleId;
            modal.setAttribute('aria-labelledby', titleId);
        }

        // Buscar descrição
        const description = modal.querySelector(MODAL_DESCRIPTION_SELECTOR);
        if (description && !modal.getAttribute('aria-describedby')) {
            const descId = description.id || `modal-desc-${Date.now()}`;
            description.id = descId;
            modal.setAttribute('aria-describedby', descId);
        }

        // Focus trap
        setupFocusTrap(modal);
    }

    // ============================================
    // FOCUS TRAP PARA MODAIS
    // ============================================

    function setupFocusTrap(modal) {
        if (!(modal instanceof HTMLElement) || modal.dataset.lkFocusTrapBound === '1') {
            return;
        }

        modal.dataset.lkFocusTrapBound = '1';

        const focusableSelectors = [
            'button:not([disabled])',
            'input:not([disabled])',
            'select:not([disabled])',
            'textarea:not([disabled])',
            'a[href]',
            '[tabindex]:not([tabindex="-1"])'
        ].join(', ');

        modal.addEventListener('keydown', (e) => {
            if (e.key !== 'Tab') return;

            const focusables = modal.querySelectorAll(focusableSelectors);
            if (focusables.length === 0) {
                return;
            }

            const first = focusables[0];
            const last = focusables[focusables.length - 1];

            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last?.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first?.focus();
            }
        });
    }

    // ============================================
    // MELHORAR FORMULÁRIOS
    // ============================================

    function enhanceForms() {
        // Associar labels a inputs
        document.querySelectorAll('input, select, textarea').forEach((input) => {
            if (input.id && !input.getAttribute('aria-labelledby')) {
                const label = document.querySelector(`label[for="${input.id}"]`);
                if (label) {
                    input.setAttribute('aria-labelledby', label.id || (label.id = `label-${input.id}`));
                }
            }
        });

        // Observar validação de campos
        document.addEventListener('invalid', (e) => {
            const field = e.target;
            field.setAttribute('aria-invalid', 'true');

            // Anunciar erro
            const errorMsg = field.validationMessage || 'Campo inválido';
            announce(`Erro no campo: ${errorMsg}`, 'assertive');
        }, true);

        // Limpar aria-invalid quando corrigido
        document.addEventListener('input', (e) => {
            const field = e.target;
            if (field.validity?.valid) {
                field.removeAttribute('aria-invalid');
            }
        });
    }

    // ============================================
    // MELHORAR BOTÕES E LINKS
    // ============================================

    function enhanceInteractiveElements() {
        // Botões sem texto visível
        document.querySelectorAll('button, a').forEach((el) => {
            if (!el.textContent?.trim() && !el.getAttribute('aria-label')) {
                // Tentar usar title ou inferir do ícone (svg.lucide, i[data-lucide], ou FA Brands)
                const icon = el.querySelector('svg.lucide, i[data-lucide], i[class*="fa-"]');
                if (icon) {
                    // Lucide SVG processado → "lucide lucide-home" → extrair "home"
                    const lucideClass = Array.from(icon.classList).find(c => c.startsWith('lucide-') && c !== 'lucide');
                    if (lucideClass) {
                        const label = lucideClass.replace('lucide-', '').replace(/-/g, ' ');
                        el.setAttribute('aria-label', capitalizeFirst(label));
                    }
                    // Lucide não processado ainda → data-lucide="home"
                    else if (icon.getAttribute('data-lucide')) {
                        const label = icon.getAttribute('data-lucide').replace(/-/g, ' ');
                        el.setAttribute('aria-label', capitalizeFirst(label));
                    }
                    // FA Brands
                    else {
                        const iconClass = Array.from(icon.classList).find(c => c.startsWith('fa-'));
                        if (iconClass) {
                            const label = iconClass.replace('fa-', '').replace(/-/g, ' ');
                            el.setAttribute('aria-label', capitalizeFirst(label));
                        }
                    }
                } else if (el.title) {
                    el.setAttribute('aria-label', el.title);
                }
            }
        });

        // Garantir que botões de fechar tenham aria-label
        document.querySelectorAll('.btn-close, .close, [data-dismiss], [data-bs-dismiss]').forEach((btn) => {
            if (!btn.getAttribute('aria-label')) {
                btn.setAttribute('aria-label', 'Fechar');
            }
        });
    }

    function capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // ============================================
    // MELHORAR TABELAS
    // ============================================

    function enhanceTables() {
        document.querySelectorAll('table').forEach((table) => {
            // Garantir role se não tiver
            if (!table.getAttribute('role')) {
                table.setAttribute('role', 'table');
            }

            // Scope para headers
            table.querySelectorAll('th').forEach((th) => {
                if (!th.getAttribute('scope')) {
                    // Verificar se está em thead ou na primeira coluna
                    const isRowHeader = th.parentElement?.parentElement?.tagName === 'TBODY';
                    th.setAttribute('scope', isRowHeader ? 'row' : 'col');
                }
            });
        });
    }

    // ============================================
    // SKIP LINK
    // ============================================

    function addSkipLink() {
        // Verificar se já existe
        if (document.querySelector('.skip-link')) return;

        const main = document.querySelector('main, .main-content, .lk-main, [role="main"]');
        if (!main) return;

        // Garantir que main tenha id
        main.id = main.id || 'main-content';
        main.setAttribute('tabindex', '-1');

        const skipLink = document.createElement('a');
        skipLink.href = `#${main.id}`;
        skipLink.className = 'skip-link';
        skipLink.textContent = 'Pular para conteúdo principal';
        skipLink.style.cssText = `
            position: absolute;
            top: -40px;
            left: 0;
            background: var(--primary-color, #6366f1);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            z-index: 100000;
            transition: top 0.3s ease;
            border-radius: 0 0 8px 0;
        `;

        skipLink.addEventListener('focus', () => {
            skipLink.style.top = '0';
        });

        skipLink.addEventListener('blur', () => {
            skipLink.style.top = '-40px';
        });

        document.body.insertBefore(skipLink, document.body.firstChild);
    }

    // ============================================
    // LOADING STATES
    // ============================================

    function enhanceLoadingStates() {
        // Observar elements com classe loading
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const target = mutation.target;
                    const isLoading = target.classList.contains('loading') ||
                        target.classList.contains('is-loading');

                    if (isLoading) {
                        target.setAttribute('aria-busy', 'true');
                        announce('Carregando...');
                    } else {
                        target.removeAttribute('aria-busy');
                    }
                }
            });
        });

        observer.observe(document.body, {
            attributes: true,
            subtree: true,
            attributeFilter: ['class']
        });
    }

    // ============================================
    // KEYBOARD NAVIGATION ENHANCEMENTS
    // ============================================

    function enhanceKeyboardNavigation() {
        const isVisible = (element) => {
            if (!(element instanceof HTMLElement)) return false;
            if (element.getAttribute('aria-hidden') === 'true') return false;
            if (element.classList.contains('modal') && !element.classList.contains('show')) return false;
            if (element.classList.contains('payment-modal') && !element.classList.contains('payment-modal--open')) return false;
            return element.getClientRects().length > 0;
        };

        const getOpenModal = () => {
            const candidates = Array.from(document.querySelectorAll(MODAL_SELECTOR))
                .filter((element) => {
                    if (!(element instanceof HTMLElement) || element.classList.contains('modal-backdrop')) {
                        return false;
                    }

                    const isOpen = element.classList.contains('active')
                        || element.classList.contains('show')
                        || element.classList.contains('payment-modal--open')
                        || element.getAttribute('aria-hidden') === 'false';

                    return isOpen && isVisible(element);
                });

            return candidates.length > 0 ? candidates[candidates.length - 1] : null;
        };

        // Escape fecha modais
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                // Tentar fechar modal aberto
                const openModal = getOpenModal();
                if (openModal) {
                    const closeBtn = openModal.querySelector(MODAL_CLOSE_SELECTOR);
                    closeBtn?.click();
                }
            }
        });

        // Enter/Space ativa elementos focados
        document.addEventListener('keydown', (e) => {
            if ((e.key === 'Enter' || e.key === ' ') &&
                e.target.matches('[role="button"], [tabindex="0"]')) {
                e.preventDefault();
                e.target.click();
            }
        });
    }

    // ============================================
    // INICIALIZAÇÃO
    // ============================================

    function init() {
        createLiveRegion();
        addSkipLink();
        enhanceModals();
        enhanceForms();
        enhanceInteractiveElements();
        enhanceTables();
        enhanceLoadingStates();
        enhanceKeyboardNavigation();

        // Re-executar quando DOM mudar significativamente
        const observer = new MutationObserver(() => {
            enhanceInteractiveElements();
            enhanceTables();
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // ============================================
    // API PÚBLICA
    // ============================================

    window.LKAccessibility = {
        init,
        announce,
        enhanceModal,
        enhanceForms,
    };

    // Auto-inicializar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
