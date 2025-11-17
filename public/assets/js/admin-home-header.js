// ============================================================================
// HEADER.JS - Sistema de Header e Sidebar
// ============================================================================
// Comportamentos do HEADER: 
// - Seletor de conta
// - Logout com confirmação
// - Highlight de sidebar
// - Toggle sidebar (desktop/mobile)
// ============================================================================

(function (global) {
    'use strict';

    // ========================================================================
    // CONSTANTES
    // ========================================================================
    const STORAGE_KEY = 'lukrato.account_id';
    const SIDEBAR_STORAGE_KEY = 'lk.sidebar';
    const MOBILE_BREAKPOINT = 992;

    // ========================================================================
    // FUNÇÃO PRINCIPAL - INIT HEADER
    // ========================================================================
    function initHeader(userOpts = {}) {
        const opts = {
            selectors: {
                accountSelect: '#headerConta',
                logoutButton: '#btn-logout',
                sidebarLinks: '.sidebar .nav-item[href]',
            },
            api: {
                accounts: 'api/accounts?only_active=1',
            },
            storageKey: STORAGE_KEY,
            baseUrl: getBaseUrl(),
            getCsrf: () => (window.CSRF || ''),
            confirmLogout: defaultConfirmLogout,
            onAccountChange: (accountId) => {
                document.dispatchEvent(new CustomEvent('lukrato:account-changed', {
                    detail: { account_id: accountId }
                }));
            },
            ...userOpts
        };

        const selAccount = qs(opts.selectors.accountSelect);
        const logoutBtn = qs(opts.selectors.logoutButton);

        // 1) Popular seletor de contas do header
        if (selAccount) {
            bindHeaderAccountPicker(selAccount, opts)
                .catch(err => console.error('[Header] Erro ao carregar contas:', err));
        }

        // 2) Logout com confirmação
        if (logoutBtn) {
            logoutBtn.addEventListener('click', handleLogoutClick(logoutBtn, opts));
        }

        // 3) Highlight da sidebar
        initSidebarActive(opts.selectors.sidebarLinks);

        // 4) Toggle da sidebar
        initSidebarToggle();

        return {
            refreshAccounts: () => selAccount 
                ? bindHeaderAccountPicker(selAccount, opts, { force: true }) 
                : Promise.resolve(),
        };
    }

    // ========================================================================
    // HELPERS UTILITÁRIOS
    // ========================================================================
    
    /**
     * Query selector helper
     */
    function qs(selector, scope = document) {
        return scope.querySelector(selector);
    }

    /**
     * Obtém a base URL do sistema
     */
    function getBaseUrl() {
        const meta = qs('meta[name="base-url"]');
        if (meta?.content) return meta.content.replace(/\/?$/, '/');
        if (window.BASE_URL) return String(window.BASE_URL).replace(/\/?$/, '/');
        return (location.origin + '/').replace(/\/?$/, '/');
    }

    /**
     * Fetch JSON com tratamento de erros
     */
    async function fetchJSON(baseUrl, path, init = {}) {
        const url = baseUrl.replace(/\/?$/, '/') + path.replace(/^\/?/, '');
        
        try {
            const response = await fetch(url, { 
                credentials: 'include', 
                ...init 
            });
            
            let json = null;
            try { 
                json = await response.json(); 
            } catch (e) {
                throw new Error('Resposta inválida do servidor');
            }
            
            if (!response.ok) {
                const msg = json?.message || json?.error || `Erro ${response.status}`;
                throw new Error(msg);
            }
            
            if (json && (json.error || json.status === 'error')) {
                throw new Error(json?.message || json?.error || 'Erro na requisição');
            }
            
            return json;
        } catch (error) {
            console.error('[Header] Erro na requisição:', error);
            throw error;
        }
    }

    // ========================================================================
    // SELETOR DE CONTAS
    // ========================================================================
    
    /**
     * Popula e gerencia o seletor de contas do header
     */
    async function bindHeaderAccountPicker(selectEl, opts, { force = false } = {}) {
        if (selectEl.dataset.bound === '1' && !force) return;

        const currentValue = selectEl.value;
        
        try {
            const accounts = await fetchJSON(opts.baseUrl, opts.api.accounts);

            // Limpa e repopula o select
            selectEl.innerHTML = '<option value="">Todas as contas (opcional)</option>';
            
            (Array.isArray(accounts) ? accounts : []).forEach(account => {
                const option = document.createElement('option');
                option.value = account.id;
                option.textContent = account.instituicao 
                    ? `${account.nome} - ${account.instituicao}` 
                    : account.nome;
                selectEl.appendChild(option);
            });

            // Restaura valor salvo
            const savedValue = sessionStorage.getItem(opts.storageKey) || currentValue || '';
            if (savedValue) selectEl.value = savedValue;

            // Bind evento de mudança (apenas uma vez)
            if (selectEl.dataset.bound !== '1') {
                selectEl.addEventListener('change', handleAccountChange(selectEl, opts));
                selectEl.dataset.bound = '1';
            }
        } catch (error) {
            console.error('[Header] Erro ao carregar contas:', error);
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Não foi possível carregar as contas.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            }
        }
    }

    /**
     * Handler de mudança de conta
     */
    function handleAccountChange(selectEl, opts) {
        return () => {
            const value = selectEl.value;
            
            if (value) {
                sessionStorage.setItem(opts.storageKey, value);
            } else {
                sessionStorage.removeItem(opts.storageKey);
            }
            
            opts.onAccountChange(value ? Number(value) : null);
        };
    }

    // ========================================================================
    // LOGOUT
    // ========================================================================
    
    /**
     * Handler de clique no botão de logout
     */
    function handleLogoutClick(logoutBtn, opts) {
        return async (e) => {
            e.preventDefault();
            const href = logoutBtn.getAttribute('href');
            if (!href) return;

            const confirmed = await opts.confirmLogout();
            if (confirmed) {
                window.location.href = href;
            }
        };
    }

    /**
     * Confirmação padrão de logout (SweetAlert2 ou confirm nativo)
     */
    function defaultConfirmLogout() {
        if (window.Swal?.fire) {
            return window.Swal.fire({
                title: 'Deseja realmente sair?',
                text: 'Sua sessão será encerrada.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, sair',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#95a5a6'
            }).then(result => Boolean(result.isConfirmed));
        }
        return Promise.resolve(window.confirm('Deseja realmente sair?'));
    }

    // ========================================================================
    // SIDEBAR ACTIVE STATE
    // ========================================================================
    
    /**
     * Gerencia o estado ativo dos links da sidebar
     */
    function initSidebarActive(selector) {
        const links = Array.from(document.querySelectorAll(selector));
        const normalize = (path) => (path || '').replace(/\/+$/, '');
        const currentPath = normalize(location.pathname);

        // Remove estado ativo de todos os links primeiro
        document.querySelectorAll('.sidebar .nav-item.active, [aria-current="page"]')
            .forEach(link => {
                if (link && !link.hasAttribute('data-no-active') && link.id !== 'btn-logout') {
                    link.classList.remove('active');
                    link.removeAttribute('aria-current');
                }
            });

        // Adiciona estado ativo ao link correto
        let hasActive = false;
        links.forEach(link => {
            if (link.id === 'btn-logout' || link.hasAttribute('data-no-active')) return;

            try {
                const linkPath = normalize(
                    new URL(link.getAttribute('href'), location.origin).pathname
                );
                
                if (linkPath && (
                    linkPath === currentPath || 
                    (linkPath !== '/' && currentPath.startsWith(linkPath + '/'))
                )) {
                    link.classList.add('active');
                    link.setAttribute('aria-current', 'page');
                    hasActive = true;
                }
            } catch (error) {
                console.warn('[Header] URL inválida no link:', link);
            }
        });

        // Bind clique para atualizar estado ativo
        links.forEach(link => {
            link.addEventListener('click', () => {
                if (link.id === 'btn-logout' || link.hasAttribute('data-no-active')) return;
                
                links.forEach(l => {
                    l.classList.remove('active');
                    l.removeAttribute('aria-current');
                });
                
                link.classList.add('active');
                link.setAttribute('aria-current', 'page');
            });
        });
    }

    // ========================================================================
    // SIDEBAR TOGGLE (Desktop/Mobile)
    // ========================================================================
    
    /**
     * Gerencia o toggle da sidebar (colapso desktop + overlay mobile)
     */
    function initSidebarToggle() {
        const body = document.body;
        const aside = document.getElementById('sidebar-main');
        const btn = document.getElementById('edgeMenuBtn') || 
                    document.getElementById('btn-toggle-sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const icon = btn?.querySelector('i');

        if (!aside || !btn || !body) return;

        const media = window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT}px)`);

        // ====================================================================
        // Helpers
        // ====================================================================
        
        const getSavedState = () => {
            const saved = localStorage.getItem(SIDEBAR_STORAGE_KEY);
            return saved === null ? false : saved === '1';
        };

        const setIcon = () => {
            if (!icon) return;
            
            const isMobile = media.matches;
            const isClosed = isMobile
                ? !body.classList.contains('sidebar-open-mobile')
                : body.classList.contains('sidebar-collapsed');
            
            icon.classList.remove('fa-angle-right', 'fa-angle-left');
            icon.classList.add(isClosed ? 'fa-angle-right' : 'fa-angle-left');
        };

        const setDesktopState = (collapsed) => {
            body.classList.toggle('sidebar-collapsed', collapsed);
            btn.setAttribute('aria-expanded', String(!collapsed));
            localStorage.setItem(SIDEBAR_STORAGE_KEY, collapsed ? '1' : '0');
            setIcon();
        };

        const openMobile = () => {
            body.classList.add('sidebar-open-mobile');
            btn.setAttribute('aria-expanded', 'true');
            setIcon();
        };

        const closeMobile = () => {
            body.classList.remove('sidebar-open-mobile');
            btn.setAttribute('aria-expanded', 'false');
            setIcon();
        };

        // ====================================================================
        // Toggle Handler
        // ====================================================================
        
        const handleToggle = () => {
            if (media.matches) {
                // Mobile: abre/fecha overlay
                body.classList.contains('sidebar-open-mobile') 
                    ? closeMobile() 
                    : openMobile();
            } else {
                // Desktop: colapsa/expande
                const nextState = !body.classList.contains('sidebar-collapsed');
                setDesktopState(nextState);
            }
        };

        // ====================================================================
        // Event Listeners
        // ====================================================================
        
        btn.addEventListener('click', handleToggle);
        backdrop?.addEventListener('click', closeMobile);
        
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && body.classList.contains('sidebar-open-mobile')) {
                closeMobile();
            }
        });

        // ====================================================================
        // Sincronização de Estado
        // ====================================================================
        
        const syncState = () => {
            if (media.matches) {
                closeMobile();
            } else {
                setDesktopState(getSavedState());
            }
        };

        // Estado inicial
        syncState();
        setIcon();

        // Listener de mudança de breakpoint
        if (typeof media.addEventListener === 'function') {
            media.addEventListener('change', syncState);
        } else if (typeof media.addListener === 'function') {
            media.addListener(syncState);
        }
    }

    // ========================================================================
    // EXPORT
    // ========================================================================
    global.LK = global.LK || {};
    global.LK.initHeader = initHeader;

})(window);

// ============================================================================
// MODALS.JS - Sistema de Modais Genérico
// ============================================================================

(function (global) {
    'use strict';

    /**
     * Inicializa sistema de modais com data-attributes
     */
    function initModals() {
        const $ = (s, c = document) => c.querySelector(s);
        const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));

        // ====================================================================
        // Helpers
        // ====================================================================
        
        const open = (id) => {
            const modal = $('#' + id);
            if (!modal) return;
            
            modal.classList.add('active');
            document.body.classList.add('lk-modal-open');
        };

        const close = (id) => {
            const modal = $('#' + id);
            if (!modal) return;
            
            modal.classList.remove('active');
            if (!$('.lk-modal.active')) {
                document.body.classList.remove('lk-modal-open');
            }
        };

        // ====================================================================
        // Event Listeners
        // ====================================================================
        
        // Botões que abrem modais
        $$('[data-open-modal]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modalId = btn.getAttribute('data-open-modal');
                open(modalId);
            });
        });

        // Botões que fecham modais
        $$('[data-close-modal]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modalId = btn.getAttribute('data-close-modal');
                close(modalId);
            });
        });

        // Clique fora do modal fecha
        $$('.lk-modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    close(modal.id);
                }
            });

            // Botões com data-action="close"
            modal.querySelectorAll('[data-action="close"]').forEach(closeBtn => {
                closeBtn.addEventListener('click', () => close(modal.id));
            });
        });

        // ESC fecha modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const activeModal = $('.lk-modal.active');
                if (activeModal) {
                    close(activeModal.id);
                }
            }
        });
    }

    global.LK = global.LK || {};
    global.LK.initModals = initModals;

})(window);

// ============================================================================
// FAB (Floating Action Button) - Sistema de Menu Flutuante
// ============================================================================

(() => {
    'use strict';

    const container = document.querySelector('.fab-container');
    const fabBtn = document.getElementById('fabButton');
    const menu = document.getElementById('fabMenu');
    
    if (!container || !fabBtn || !menu) return;

    const bs = window.bootstrap;

    // ========================================================================
    // Helpers
    // ========================================================================
    
    function openMenu() {
        container.classList.add('open');
        fabBtn.classList.add('active');
        fabBtn.setAttribute('aria-expanded', 'true');
        document.addEventListener('click', handleOutside, { once: true });
        document.addEventListener('keydown', handleEsc, { once: true });
    }

    function closeMenu() {
        container.classList.remove('open');
        fabBtn.classList.remove('active');
        fabBtn.setAttribute('aria-expanded', 'false');
    }

    function toggleMenu(e) {
        e.stopPropagation();
        container.classList.contains('open') ? closeMenu() : openMenu();
    }

    function handleOutside(e) {
        if (!container.contains(e.target)) {
            closeMenu();
        }
    }

    function handleEsc(e) {
        if (e.key === 'Escape') {
            closeMenu();
        }
    }

    // ========================================================================
    // Event Listeners
    // ========================================================================
    
    fabBtn.addEventListener('click', toggleMenu);

    // Abrir modais Bootstrap
    container.querySelectorAll('[data-open-modal]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const kind = btn.getAttribute('data-open-modal');

            if (kind === 'receita' || kind === 'despesa') {
                const modalEl = document.querySelector('#modalLancamento');
                const tipoSelect = document.querySelector('#lanTipo');
                const title = document.querySelector('#modalLancamentoTitle');

                if (tipoSelect) tipoSelect.value = kind;
                if (title) title.textContent = `Novo ${kind.charAt(0).toUpperCase() + kind.slice(1)}`;
                
                bs?.Modal?.getOrCreateInstance(modalEl)?.show();
            } else if (kind === 'agendamento') {
                const modalEl = document.querySelector('#modalAgendamento');
                bs?.Modal?.getOrCreateInstance(modalEl)?.show();
            }

            closeMenu();
        });
    });

    // ========================================================================
    // Garantir z-index e pointer-events
    // ========================================================================
    
    fabBtn.style.pointerEvents = 'auto';
    menu.style.pointerEvents = 'auto';
    container.style.zIndex = container.style.zIndex || '9999';

})();