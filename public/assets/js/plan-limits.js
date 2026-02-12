/**
 * ============================================
 * PLAN LIMITS MANAGER
 * ============================================
 * Gerencia avisos e bloqueios de limites do plano
 * para usuários do plano gratuito.
 */

(function () {
    'use strict';

    // ============================================
    // CONFIGURAÇÃO
    // ============================================

    const CONFIG = {
        apiBase: (function () {
            // Tentar várias formas de obter a URL base
            if (typeof window.getBaseUrl === 'function') return window.getBaseUrl();
            if (typeof LK !== 'undefined' && typeof LK.getBase === 'function') return LK.getBase();
            const meta = document.querySelector('meta[name="base-url"]');
            if (meta?.content) return meta.content.replace(/\/?$/, '/');
            // Fallback para ambiente de desenvolvimento
            return window.BASE_URL || '/lukrato/public/';
        })(),
        cacheKey: 'lukrato_plan_limits',
        cacheTTL: 5 * 60 * 1000, // 5 minutos
        upgradeUrl: '/billing',
        // Mensagens contextuais por página
        contextualMessages: {
            relatorios: '📊 Análises completas e exportação com o Pro',
            cartoes: '💳 Gerencie todos os seus cartões de crédito',
            contas: '🏦 Organize todas as suas contas bancárias',
            agendamentos: '⏰ Lembretes automáticos por email',
            metas: '🎯 Crie metas ilimitadas',
            categorias: '🏷️ Personalize sem limites',
            lancamentos: '💰 Registre sem preocupações',
            dashboard: '📈 Dashboard avançado com insights',
            faturas: '📄 Visualize todo o histórico de faturas',
            default: '🚀 Desbloqueie todo o potencial do Lukrato',
        },
    };

    // ============================================
    // ESTADO
    // ============================================

    let limitsData = null;
    let lastFetch = 0;

    // ============================================
    // API
    // ============================================

    async function fetchLimits(force = false) {
        const now = Date.now();

        // Usar cache se não forçado e ainda válido
        if (!force && limitsData && (now - lastFetch) < CONFIG.cacheTTL) {
            return limitsData;
        }

        try {
            const response = await fetch(`${CONFIG.apiBase}api/plan/limits`, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Erro ao buscar limites');
            }

            const result = await response.json();

            if (result.success) {
                limitsData = result.data;
                lastFetch = now;

                // Salvar no localStorage para acesso rápido
                try {
                    localStorage.setItem(CONFIG.cacheKey, JSON.stringify({
                        data: limitsData,
                        timestamp: now,
                    }));
                } catch (e) { /* ignore */ }

                return limitsData;
            }
        } catch (error) {
            console.warn('[PlanLimits] Erro ao buscar limites:', error);

            // Tentar usar cache do localStorage
            try {
                const cached = localStorage.getItem(CONFIG.cacheKey);
                if (cached) {
                    const parsed = JSON.parse(cached);
                    if ((now - parsed.timestamp) < CONFIG.cacheTTL * 2) {
                        limitsData = parsed.data;
                        return limitsData;
                    }
                }
            } catch (e) { /* ignore */ }
        }

        return null;
    }

    // ============================================
    // VERIFICAÇÕES
    // ============================================

    function isPro() {
        return limitsData?.is_pro === true;
    }

    function canCreate(resource) {
        if (!limitsData) return { allowed: true };
        if (limitsData.is_pro) return { allowed: true };

        const resourceMap = {
            'conta': 'contas',
            'contas': 'contas',
            'account': 'contas',
            'cartao': 'cartoes',
            'cartoes': 'cartoes',
            'card': 'cartoes',
            'categoria': 'categorias',
            'categorias': 'categorias',
            'category': 'categorias',
            'meta': 'metas',
            'metas': 'metas',
            'goal': 'metas',
        };

        const key = resourceMap[resource.toLowerCase()] || resource;
        return limitsData[key] || { allowed: true };
    }

    function getHistoryRestriction() {
        if (!limitsData) return { restricted: false };
        return limitsData.historico || { restricted: false };
    }

    function hasFeature(featureName) {
        if (!limitsData) return true;
        if (limitsData.is_pro) return true;
        return limitsData.features?.[featureName] === true;
    }

    // ============================================
    // UI - ALERTAS
    // ============================================

    function showLimitAlert(options = {}) {
        const {
            message = 'Você atingiu o limite do plano gratuito.',
            type = 'warning', // 'warning' | 'error' | 'info'
            showUpgrade = true,
            container = null,
            persistent = false,
        } = options;

        // Remover alertas anteriores
        if (!persistent) {
            document.querySelectorAll('.plan-limit-alert').forEach(el => el.remove());
        }

        const alert = document.createElement('div');
        alert.className = `plan-limit-alert alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
        alert.style.cssText = 'margin: 1rem 0; animation: slideDown 0.3s ease-out;';

        let html = `
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'exclamation-triangle'} me-2"></i>
                    <span>${message}</span>
                </div>
        `;

        if (showUpgrade) {
            html += `
                <a href="${CONFIG.upgradeUrl}" class="btn btn-sm btn-${type === 'error' ? 'light' : 'primary'} ms-3">
                    <i class="fas fa-rocket me-1"></i> Fazer Upgrade
                </a>
            `;
        }

        html += `
                <button type="button" class="btn-close ms-2" data-bs-dismiss="alert"></button>
            </div>
        `;

        alert.innerHTML = html;

        // Inserir no container ou no topo da página
        if (container) {
            container.prepend(alert);
        } else {
            const mainContent = document.querySelector('.main-content, main, .container-fluid');
            if (mainContent) {
                mainContent.prepend(alert);
            }
        }

        // Auto-dismiss após 10 segundos se não for erro
        if (type !== 'error') {
            setTimeout(() => {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 300);
            }, 10000);
        }

        return alert;
    }

    function showUpgradeModal(options = {}) {
        const {
            title = '🚀 Faça Upgrade para o Lukrato Pro',
            message = 'Você atingiu o limite do plano gratuito.',
            features = [
                'Lançamentos ilimitados',
                'Contas bancárias ilimitadas',
                'Cartões de crédito ilimitados',
                'Relatórios avançados',
                'Exportação PDF e Excel',
                'Histórico completo',
            ],
            context = null,
        } = options;

        // Detectar contexto automaticamente se não fornecido
        const detectedContext = context || getCurrentPageContext();
        const contextMsg = CONFIG.contextualMessages[detectedContext] || CONFIG.contextualMessages.default;

        // Verificar se já existe modal
        let modal = document.getElementById('planUpgradeModal');
        if (modal) {
            modal.remove();
        }

        const modalHtml = `
            <div class="modal fade" id="planUpgradeModal" tabindex="-1" role="dialog" aria-labelledby="upgradeModalTitle" aria-modal="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-gradient-primary text-white">
                            <h5 class="modal-title" id="upgradeModalTitle">${title}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-crown fa-3x text-warning mb-3" aria-hidden="true"></i>
                                <p class="upgrade-context-message">${contextMsg}</p>
                                <p class="text-muted">${message}</p>
                            </div>
                            <div class="text-start mb-4">
                                <h6 class="text-muted mb-3">Com o Pro você tem:</h6>
                                <ul class="list-unstyled upgrade-features-list">
                                    ${features.map(f => `<li class="mb-2"><i class="fas fa-check text-success me-2" aria-hidden="true"></i>${f}</li>`).join('')}
                                </ul>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-center">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Agora não</button>
                            <a href="${CONFIG.upgradeUrl}" class="btn btn-primary btn-lg">
                                <i class="fas fa-rocket me-2" aria-hidden="true"></i> Quero ser Pro!
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        modal = document.getElementById('planUpgradeModal');

        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });

        return modal;
    }

    // Helper para detectar contexto da página atual
    function getCurrentPageContext() {
        const path = window.location.pathname.toLowerCase();
        const contexts = ['relatorios', 'cartoes', 'contas', 'agendamentos', 'metas', 'categorias', 'lancamentos', 'dashboard', 'faturas'];

        for (const ctx of contexts) {
            if (path.includes(ctx)) {
                return ctx;
            }
        }
        return 'default';
    }

    // ============================================
    // UI - BADGES DE LIMITE
    // ============================================

    function renderLimitBadge(resource) {
        const limit = canCreate(resource);

        if (limit.allowed) {
            if (limit.remaining !== undefined && limit.remaining <= 2 && limit.remaining > 0) {
                return `<span class="badge bg-warning text-dark ms-2" title="Restam ${limit.remaining}">
                    <i class="fas fa-exclamation-triangle me-1"></i>${limit.remaining} restante${limit.remaining > 1 ? 's' : ''}
                </span>`;
            }
            return '';
        }

        return `<span class="badge bg-danger ms-2" title="Limite atingido">
            <i class="fas fa-lock me-1"></i>Limite atingido
        </span>`;
    }

    function updateAddButtons() {
        // Atualizar botões de adicionar com badges de limite
        const buttonMap = {
            '[data-action="add-conta"], #btnNovaConta': 'contas',
            '[data-action="add-cartao"], #btnNovoCartao': 'cartoes',
            '[data-action="add-categoria"], #btnNovaCategoria': 'categorias',
            '[data-action="add-meta"], #btnNovaMeta': 'metas',
        };

        Object.entries(buttonMap).forEach(([selector, resource]) => {
            const buttons = document.querySelectorAll(selector);
            buttons.forEach(btn => {
                const limit = canCreate(resource);
                const isBlocked = !limit.allowed;
                const alreadyBlocked = btn.hasAttribute('data-limit-blocked');
                const isWarning = limit.allowed && limit.remaining !== undefined && limit.remaining <= 2;
                const alreadyWarning = btn.hasAttribute('data-limit-warning');

                // Skip if state hasn't changed (avoid DOM mutations that re-trigger MutationObserver)
                if (isBlocked && alreadyBlocked) return;
                if (isWarning && alreadyWarning) return;
                if (!isBlocked && !isWarning && !alreadyBlocked && !alreadyWarning) return;

                // Remove old badges
                btn.querySelectorAll('.limit-badge').forEach(b => b.remove());
                btn.removeAttribute('data-limit-blocked');
                btn.removeAttribute('data-limit-warning');
                btn.classList.remove('disabled');
                btn.removeEventListener('click', handleBlockedClick, { capture: true });

                if (isBlocked) {
                    // Desabilitar botão
                    btn.classList.add('disabled');
                    btn.setAttribute('data-limit-blocked', 'true');

                    // Adicionar badge
                    const badge = document.createElement('span');
                    badge.className = 'limit-badge badge bg-danger ms-2';
                    badge.innerHTML = '<i class="fas fa-lock"></i>';
                    btn.appendChild(badge);

                    // Interceptar clique
                    btn.addEventListener('click', handleBlockedClick, { capture: true });
                } else if (isWarning) {
                    // Aviso de quase no limite
                    btn.setAttribute('data-limit-warning', 'true');
                    const badge = document.createElement('span');
                    badge.className = 'limit-badge badge bg-warning text-dark ms-2';
                    badge.textContent = limit.remaining;
                    btn.appendChild(badge);
                }
            });
        });
    }

    function handleBlockedClick(e) {
        if (e.currentTarget.hasAttribute('data-limit-blocked')) {
            e.preventDefault();
            e.stopPropagation();

            const resource = e.currentTarget.dataset.resource || 'recurso';
            showUpgradeModal({
                message: `Você atingiu o limite de ${resource} do plano gratuito.`,
            });
        }
    }

    // ============================================
    // INTERCEPTAR RESPOSTAS DA API
    // ============================================

    function setupApiInterceptor() {
        const originalFetch = window.fetch;

        window.fetch = async function (...args) {
            const response = await originalFetch.apply(this, args);

            // Clone para não consumir o body
            const clone = response.clone();

            try {
                // Verificar se é resposta 403 com limit_reached
                if (response.status === 403) {
                    const data = await clone.json();
                    if (data.limit_reached) {
                        showUpgradeModal({
                            message: data.message || 'Você atingiu o limite do plano gratuito.',
                        });
                    }
                }
            } catch (e) { /* ignore */ }

            return response;
        };
    }

    // ============================================
    // HISTÓRICO - APLICAR RESTRIÇÃO
    // ============================================

    function applyHistoryRestriction() {
        const restriction = getHistoryRestriction();

        if (!restriction.restricted) return;

        // Encontrar seletores de período
        const periodSelectors = document.querySelectorAll('[data-period-selector], .period-selector, #periodoSelect');

        periodSelectors.forEach(selector => {
            const options = selector.querySelectorAll('option');
            const minDate = new Date(restriction.min_date);

            options.forEach(option => {
                const value = option.value;
                // Se for uma data no formato YYYY-MM, verificar se é anterior ao permitido
                if (/^\d{4}-\d{2}$/.test(value)) {
                    const optionDate = new Date(value + '-01');
                    if (optionDate < minDate) {
                        option.disabled = true;
                        option.textContent += ' 🔒';
                        option.title = restriction.message;
                    }
                }
            });
        });

        // Adicionar aviso se necessário
        if (restriction.message) {
            const container = document.querySelector('.period-controls, .filter-controls');
            if (container && !container.querySelector('.history-restriction-info')) {
                const info = document.createElement('small');
                info.className = 'history-restriction-info text-muted d-block mt-1';
                info.innerHTML = `<i class="fas fa-info-circle me-1"></i>${restriction.message}`;
                container.appendChild(info);
            }
        }
    }

    // ============================================
    // INICIALIZAÇÃO
    // ============================================

    async function init() {
        // Buscar limites
        await fetchLimits();

        if (!limitsData) return;

        // Se for Pro, não precisa fazer nada
        if (limitsData.is_pro) {
            return;
        }

        // Configurar interceptor de API
        setupApiInterceptor();

        // Atualizar botões
        updateAddButtons();

        // Aplicar restrição de histórico
        applyHistoryRestriction();

        // Observar mudanças no DOM para novos botões (debounced para evitar loop infinito)
        let updateTimeout = null;
        const observer = new MutationObserver(() => {
            if (updateTimeout) return;
            updateTimeout = setTimeout(() => {
                updateTimeout = null;
                updateAddButtons();
            }, 300);
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }

    // ============================================
    // EXPORTAR API PÚBLICA
    // ============================================

    window.PlanLimits = {
        init,
        fetchLimits,
        isPro,
        canCreate,
        hasFeature,
        getHistoryRestriction,
        showLimitAlert,
        showUpgradeModal,
        renderLimitBadge,
        updateAddButtons,
        getData: () => limitsData,
    };

    // Auto-inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
