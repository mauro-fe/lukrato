import { apiGetCached } from '../shared/api-store.js';
import { buildAppUrl } from '../shared/api.js';
import { resolvePlanLimitsEndpoint } from '../api/endpoints/billing.js';

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
        cacheKey: 'lukrato_plan_limits',
        cacheTTL: 5 * 60 * 1000, // 5 minutos
        upgradeUrl: buildAppUrl('billing'),
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
            financas: '📊 Metas e orçamento sem limites para planejar melhor',
            orcamento: '📈 Orçamentos inteligentes e ilimitados',
            perfil: '👤 Recursos avançados para personalizar sua conta',
            gamification: '🏆 Acelere seu progresso e desbloqueie vantagens exclusivas',
            default: '🚀 Desbloqueie todo o potencial do Lukrato',
        },
        contextualFeatures: {
            relatorios: [
                'Relatórios avançados e comparativos',
                'Exportação em PDF e Excel',
                'Histórico financeiro completo',
            ],
            contas: [
                'Contas bancárias ilimitadas',
                'Gestão financeira sem bloqueios',
                'Histórico completo de movimentações',
            ],
            categorias: [
                'Categorias e subcategorias ilimitadas',
                'Organização financeira avançada',
                'Mais precisão nos relatórios',
            ],
            metas: [
                'Criação de metas ilimitadas',
                'Acompanhamento completo de progresso',
                'Planejamento financeiro avançado',
            ],
            orcamento: [
                'Orçamentos ilimitados',
                'Insights inteligentes por categoria',
                'Controle mensal sem limites',
            ],
            financas: [
                'Metas e orçamentos sem limite',
                'Visão financeira completa',
                'Recomendações avançadas',
            ],
            lancamentos: [
                'Lançamentos ilimitados',
                'Controle total do fluxo financeiro',
                'Mais automações para produtividade',
            ],
            gamification: [
                'Conquistas e vantagens exclusivas',
                'Progressão acelerada',
                'Experiência completa de gamificação',
            ],
            default: [
                'Lançamentos ilimitados',
                'Relatórios avançados',
                'Exportação PDF/Excel',
                'Histórico completo',
            ],
        },
    };

    // ============================================
    // ESTADO
    // ============================================

    let limitsData = null;
    let lastFetch = 0;
    let initPromise = null;
    let activeUpgradePrompt = null;
    let lastUpgradePromptKey = '';
    let lastUpgradePromptAt = 0;

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
            const result = await apiGetCached(resolvePlanLimitsEndpoint(), {}, {
                cacheKey: 'global:plan-limits',
                ttlMs: CONFIG.cacheTTL,
                force,
            });

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

    function resolveUpgradeUrl(upgradeUrl) {
        const raw = typeof upgradeUrl === 'string' && upgradeUrl.trim()
            ? upgradeUrl.trim()
            : CONFIG.upgradeUrl;

        if (/^https?:\/\//i.test(raw)) {
            return raw;
        }

        return buildAppUrl(raw);
    }

    function getUpgradeFeatures(context) {
        return CONFIG.contextualFeatures[context] || CONFIG.contextualFeatures.default;
    }

    function buildUpgradePromptKey({ title, message, context, upgradeUrl }) {
        return [title, message, context, upgradeUrl].map((value) => String(value || '').trim()).join('::');
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
                    <i data-lucide="${type === 'error' ? 'circle-alert' : 'triangle-alert'}" style="width:16px;height:16px;display:inline-block;"></i>
                    <span>${message}</span>
                </div>
        `;

        if (showUpgrade) {
            html += `
                <a href="${resolveUpgradeUrl(CONFIG.upgradeUrl)}" class="btn btn-sm btn-${type === 'error' ? 'light' : 'primary'} ms-3">
                    <i data-lucide="rocket" style="width:16px;height:16px;display:inline-block;"></i> Fazer Upgrade
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
            features = null,
            context = null,
            upgradeUrl = CONFIG.upgradeUrl,
        } = options;

        // Detectar contexto automaticamente se não fornecido
        const detectedContext = context || getCurrentPageContext();
        const contextMsg = CONFIG.contextualMessages[detectedContext] || CONFIG.contextualMessages.default;
        const upgradeHref = resolveUpgradeUrl(upgradeUrl);
        const featureList = Array.isArray(features) && features.length
            ? features
            : getUpgradeFeatures(detectedContext);

        // Verificar se já existe modal
        let modal = document.getElementById('planUpgradeModal');
        if (modal) {
            modal.remove();
        }

        const modalHtml = `
            <div class="modal fade" id="planUpgradeModal" tabindex="-1" role="dialog" aria-labelledby="upgradeModalTitle" aria-modal="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="upgradeModalTitle">${title}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body text-center py-4">
                            <div class="mb-3">
                                <i data-lucide="crown" style="width:48px;height:48px;" class="text-warning mb-3" aria-hidden="true"></i>
                                <p class="upgrade-context-message">${contextMsg}</p>
                                <p class="text-muted">${message}</p>
                            </div>
                            <div class="text-start mb-4">
                                <h6 class="text-muted mb-3">Com o Pro você tem:</h6>
                                <ul class="list-unstyled upgrade-features-list">
                                    ${featureList.map(f => `<li class="mb-2"><i data-lucide="check" style="width:16px;height:16px;display:inline-block;" class="text-success me-2" aria-hidden="true"></i>${f}</li>`).join('')}
                                </ul>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-center">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Agora não</button>
                            <a href="${upgradeHref}" class="btn btn-primary btn-lg">
                                <i data-lucide="rocket" style="width:16px;height:16px;display:inline-block;" aria-hidden="true"></i> Quero ser Pro!
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        modal = document.getElementById('planUpgradeModal');

        window.LK?.modalSystem?.prepareBootstrapModal(modal, { scope: 'app' });

        const bsModal = bootstrap.Modal.getOrCreateInstance(modal, {
            backdrop: true,
            keyboard: true,
            focus: true,
        });
        bsModal.show();

        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });

        return modal;
    }

    // Helper para detectar contexto da página atual
    function getCurrentPageContext() {
        const path = window.location.pathname.toLowerCase();
        const contexts = [
            'relatorios',
            'cartoes',
            'contas',
            'agendamentos',
            'metas',
            'categorias',
            'lancamentos',
            'dashboard',
            'faturas',
            'financas',
            'orcamento',
            'perfil',
            'gamification',
        ];

        for (const ctx of contexts) {
            if (path.includes(ctx)) {
                return ctx;
            }
        }
        return 'default';
    }

    async function promptUpgrade(options = {}) {
        const {
            title = '🚀 Desbloqueie com o Pro',
            message = 'Este recurso está disponível no plano Pro.',
            context = null,
            features = null,
            upgradeUrl = null,
        } = options;

        const detectedContext = context || getCurrentPageContext();
        const resolvedUpgradeUrl = resolveUpgradeUrl(upgradeUrl || CONFIG.upgradeUrl);
        const featureList = Array.isArray(features) && features.length
            ? features
            : getUpgradeFeatures(detectedContext);

        const promptKey = buildUpgradePromptKey({
            title,
            message,
            context: detectedContext,
            upgradeUrl: resolvedUpgradeUrl,
        });
        const now = Date.now();

        if (activeUpgradePrompt) {
            return activeUpgradePrompt;
        }

        if (lastUpgradePromptKey === promptKey && (now - lastUpgradePromptAt) < 1500) {
            return { isConfirmed: false, isDismissed: true, skipped: true };
        }

        lastUpgradePromptKey = promptKey;
        lastUpgradePromptAt = now;

        activeUpgradePrompt = (async () => {
            if (window.LKFeedback?.upgradePrompt) {
                return window.LKFeedback.upgradePrompt({
                    title,
                    message,
                    context: detectedContext,
                    features: featureList,
                    upgradeUrl: resolvedUpgradeUrl,
                });
            }

            showUpgradeModal({
                title,
                message,
                context: detectedContext,
                features: featureList,
                upgradeUrl: resolvedUpgradeUrl,
            });

            return { isConfirmed: false, isDismissed: true };
        })();

        return activeUpgradePrompt.finally(() => {
            activeUpgradePrompt = null;
            lastUpgradePromptAt = Date.now();
        });
    }

    function handleApiLimitReached(payload = {}) {
        const limitReached = payload?.limit_reached === true || payload?.errors?.limit_reached === true;
        if (!limitReached) {
            return false;
        }

        return promptUpgrade({
            context: getCurrentPageContext(),
            message: payload?.message || 'Você atingiu o limite do plano gratuito.',
            upgradeUrl: payload?.errors?.upgrade_url || payload?.upgrade_url || CONFIG.upgradeUrl,
        });
    }

    // ============================================
    // UI - BADGES DE LIMITE
    // ============================================

    function renderLimitBadge(resource) {
        const limit = canCreate(resource);

        if (limit.allowed) {
            if (limit.remaining !== undefined && limit.remaining <= 2 && limit.remaining > 0) {
                return `<span class="badge bg-warning text-dark ms-2" title="Restam ${limit.remaining}">
                    <i data-lucide="triangle-alert" style="width:14px;height:14px;display:inline-block;"></i>${limit.remaining} restante${limit.remaining > 1 ? 's' : ''}
                </span>`;
            }
            return '';
        }

        return `<span class="badge bg-danger ms-2" title="Limite atingido">
            <i data-lucide="lock" style="width:14px;height:14px;display:inline-block;"></i>Limite atingido
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
                    badge.innerHTML = '<i data-lucide="lock" style="width:14px;height:14px;"></i>';
                    if (window.lucide) lucide.createIcons({ nodes: [badge] });
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
            promptUpgrade({
                context: getCurrentPageContext(),
                message: `Você atingiu o limite de ${resource} do plano gratuito.`,
            }).catch(() => { /* ignore */ });
        }
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
                        option.textContent += ' (Pro)';
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
                info.innerHTML = `<i data-lucide="info" style="width:14px;height:14px;display:inline-block;"></i>${restriction.message}`;
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

    async function initOnce(force = false) {
        if (!force && initPromise) {
            return initPromise;
        }

        if (!force) {
            initPromise = init();
            return initPromise;
        }

        limitsData = null;
        lastFetch = 0;

        initPromise = (async () => {
            await fetchLimits(true);

            if (!limitsData || limitsData.is_pro) {
                return;
            }

            updateAddButtons();
            applyHistoryRestriction();
        })();

        return initPromise;
    }

    async function refresh() {
        return initOnce(true);
    }

    document.addEventListener('lukrato:data-changed', () => {
        if (!limitsData?.is_pro) {
            refresh().catch(() => { /* ignore */ });
        }
    });

    // ============================================
    // EXPORTAR API PÚBLICA
    // ============================================

    window.PlanLimits = {
        init: initOnce,
        refresh,
        fetchLimits,
        isPro,
        canCreate,
        hasFeature,
        getHistoryRestriction,
        showLimitAlert,
        showUpgradeModal,
        promptUpgrade,
        handleApiLimitReached,
        renderLimitBadge,
        updateAddButtons,
        getData: () => limitsData,
    };

    // Auto-inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initOnce());
    } else {
        initOnce();
    }

})();
