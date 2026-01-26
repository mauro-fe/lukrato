/**
 * Sistema de Onboarding - Lukrato
 * Guia interativo para novos usuários
 * 
 * O status do onboarding é sincronizado com o servidor para
 * funcionar corretamente em múltiplos dispositivos.
 */

class OnboardingManager {
    constructor() {
        // Usar a função global LK.getBase() se disponível
        if (window.LK && typeof window.LK.getBase === 'function') {
            this.baseUrl = window.LK.getBase();
        } else {
            // Fallback para meta tag
            const meta = document.querySelector('meta[name="base-url"]');
            this.baseUrl = meta?.content || window.BASE_URL || '/lukrato/public/';
        }
        this.storageKey = 'lukrato_onboarding_completed';
        this.currentStep = 0;
        this.totalSteps = 2;
        this.serverStatusLoaded = false;

        this.init();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Escuta eventos de mudança de dados para atualizar onboarding automaticamente
        window.addEventListener('lukrato:data-changed', () => {
            setTimeout(() => this.checkEmptyState(), 500);
        });

        // Escutar criação de lançamentos diretamente - verificar IMEDIATAMENTE
        window.addEventListener('lancamento-created', () => {
            setTimeout(() => this.checkEmptyState(), 300);
        });

        // Escutar criação de contas
        window.addEventListener('conta-created', () => {
            setTimeout(() => this.checkEmptyState(), 300);
        });
    }

    async init() {
        // SEMPRE sincronizar com servidor primeiro
        await this.syncWithServer();

        const completed = this.isCompleted();
        const inProgress = localStorage.getItem('lukrato_onboarding_in_progress') === 'true';


        // Se marcado como completo, FORÇAR despausar gamificação
        if (completed) {
            window.gamificationPaused = false;
            localStorage.removeItem('lukrato_onboarding_in_progress'); // Limpar flag de progresso
            // NÃO chamar checkEmptyState() aqui para evitar reset
            return;
        }

        // PAUSAR GAMIFICAÇÃO SE ESTIVER EM PROGRESSO (em qualquer página)
        if (inProgress) {
            window.gamificationPaused = true;
        }

        // Se está em progresso, mostrar cards mas não o modal
        if (inProgress) {
            setTimeout(() => this.checkEmptyState(), 1000);
            return;
        }


        // Aguardar carregamento do DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.start());
        } else {
            this.start();
        }
    }

    /**
     * Sincroniza o status do onboarding com o servidor
     * Isso garante que o onboarding não apareça em outros dispositivos
     */
    async syncWithServer() {
        try {
            const response = await fetch(`${this.baseUrl}api/onboarding/status`, {
                credentials: 'same-origin'
            });

            if (!response.ok) {
                console.warn('[Onboarding] Não foi possível verificar status no servidor');
                return;
            }

            const data = await response.json();

            if (data.success && data.data?.completed) {
                // Servidor diz que já completou - sincronizar localStorage
                localStorage.setItem(this.storageKey, 'true');
                this.serverStatusLoaded = true;
            } else if (data.success && !data.data?.completed) {
                // Servidor diz que NÃO completou
                // NÃO apagar localStorage - confiar no local como fonte primária
                // Isso evita loops onde o servidor não salvou mas o usuário já escolheu
                this.serverStatusLoaded = true;
            }
        } catch (error) {
            console.warn('[Onboarding] Erro ao sincronizar com servidor:', error);
            // Em caso de erro, usa o localStorage como fallback
        }
    }

    isCompleted() {
        return localStorage.getItem(this.storageKey) === 'true';
    }

    async markCompleted() {
        // Marcar localmente
        localStorage.setItem(this.storageKey, 'true');

        // Sincronizar com o servidor
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;



            const response = await fetch(`${this.baseUrl}api/onboarding/complete`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (!response.ok) {
                console.error('[Onboarding] Erro ao marcar completo no servidor:', {
                    status: response.status,
                    statusText: response.statusText,
                    data: data
                });
            } else {
            }
        } catch (error) {
            console.error('[Onboarding] Erro ao marcar completo no servidor:', error);
            // Não é crítico - o localStorage já foi atualizado
        }
    }

    async checkEmptyState() {
        try {

            // Verificar se há contas
            const contasResponse = await fetch(`${this.baseUrl}api/contas`);
            const contas = await contasResponse.json();
            const hasContas = Array.isArray(contas) ? contas.length > 0 : (contas.data?.length > 0 || false);

            // Verificar se há lançamentos
            const lancamentosResponse = await fetch(`${this.baseUrl}api/lancamentos?limit=10`);
            const lancamentos = await lancamentosResponse.json();
            const hasLancamentos = Array.isArray(lancamentos) ? lancamentos.length > 0 : (lancamentos.data?.length > 0 || false);

            // Salvar progresso
            this.updateProgress({
                hasContas,
                hasLancamentos
            });

            // NOVO USUÁRIO: Se não tem nada E onboarding está marcado como completo, 
            // verificar com SERVIDOR se realmente completou - localStorage pode estar sujo
            if (!hasContas && !hasLancamentos && this.isCompleted()) {
                // Verificar com servidor antes de resetar
                try {
                    const response = await fetch(`${this.baseUrl}api/onboarding/status`, {
                        credentials: 'same-origin'
                    });
                    const serverData = await response.json();

                    // Se servidor confirma que completou, NÃO resetar
                    if (serverData.success && serverData.data?.completed) {
                        return;
                    }

                    // Servidor diz que NÃO completou - aí sim resetar
                } catch (error) {
                    // Em caso de erro de rede, NÃO resetar (seguro)
                    console.warn('⚠️ [Onboarding] Erro ao verificar servidor, mantendo estado atual:', error);
                    return;
                }

                localStorage.removeItem(this.storageKey);
                localStorage.removeItem('lukrato_onboarding_celebration_shown');
                localStorage.removeItem('lukrato_onboarding_progress');
                localStorage.removeItem('lukrato_onboarding_in_progress');
                // Mostrar modal de boas-vindas
                this.showWelcomeModal();
                return;
            }

            // Se não tem nada, mostrar empty state melhorado
            if (!hasContas && !hasLancamentos) {
                this.showEmptyStateCards();
            }
            // Se tem conta mas não tem lançamento
            else if (hasContas && !hasLancamentos) {
                this.showNextStepGuide('lancamento');
            }
            // Se completou tudo, mostrar celebração
            else if (hasContas && hasLancamentos) {
                this.showCompletionCelebration();
            }
        } catch (error) {
            console.error('❌ [Onboarding] Erro ao verificar empty state:', error);
        }
    }

    updateProgress(progress) {
        localStorage.setItem('lukrato_onboarding_progress', JSON.stringify(progress));
    }

    getProgress() {
        const saved = localStorage.getItem('lukrato_onboarding_progress');
        return saved ? JSON.parse(saved) : { hasContas: false, hasLancamentos: false };
    }

    getCurrentPage() {
        const path = window.location.pathname.toLowerCase();
        if (path.includes('/contas')) return 'contas';
        if (path.includes('/categorias')) return 'categorias';
        if (path.includes('/lancamentos')) return 'lancamentos';
        if (path.includes('/dashboard')) return 'dashboard';
        return 'other';
    }

    showNextStepGuide(nextStep) {
        // Verificar página atual
        const currentPage = this.getCurrentPage();

        // NÃO mostrar banner se já estiver na página de destino
        if (nextStep === 'lancamento' && currentPage === 'lancamentos') {
            return;
        }

        // Buscar container - funciona em qualquer página
        let container = document.querySelector('.lk-main') ||
            document.querySelector('.content-wrapper') ||
            document.querySelector('main');

        if (!container) {
            console.warn('🎯 Onboarding: Container não encontrado para banner');
            return;
        }

        const progress = this.getProgress();

        let stepInfo = {};
        if (nextStep === 'lancamento') {
            stepInfo = {
                icon: '💰',
                title: 'Ótimo! Conta criada! 🎉',
                subtitle: 'Hora de registrar suas movimentações',
                description: 'Adicione seus primeiros lançamentos para começar a controlar seu dinheiro',
                actionText: 'Adicionar Lançamento',
                actionCallback: () => this.openLancamentoModal(),
                step: 2
            };
        }

        const nextStepHTML = `
            <div class="next-step-banner" style="animation: slideDown 0.5s ease;">
                <div class="nsb-icon">${stepInfo.icon}</div>
                <div class="nsb-content">
                    <h3>${stepInfo.title}</h3>
                    <p class="nsb-subtitle">${stepInfo.subtitle}</p>
                    <p class="nsb-description">${stepInfo.description}</p>
                </div>
                <button class="nsb-action-btn" data-step="${nextStep}">
                    ${stepInfo.actionText}
                    <i class="fas fa-arrow-right"></i>
                </button>
                <div class="nsb-progress">
                    <div class="nsb-step ${progress.hasContas ? 'completed' : ''}">
                        <i class="fas ${progress.hasContas ? 'fa-check-circle' : 'fa-circle'}"></i>
                        <span>Conta</span>
                    </div>
                    <div class="nsb-step ${progress.hasLancamentos ? 'completed' : ''}">
                        <i class="fas ${progress.hasLancamentos ? 'fa-check-circle' : 'fa-circle'}"></i>
                        <span>Lançamentos</span>
                    </div>
                </div>
            </div>
        `;

        // Remover cards de boas-vindas se existirem
        const existingCards = document.querySelector('.onboarding-welcome');
        if (existingCards) existingCards.remove();

        // Remover banner anterior se existir
        const existingBanner = document.querySelector('.next-step-banner');
        if (existingBanner) existingBanner.remove();

        // Inserir SEMPRE no topo do container (antes de qualquer coisa)
        container.insertAdjacentHTML('afterbegin', nextStepHTML);

        // Adicionar evento ao botão
        const actionBtn = document.querySelector('.nsb-action-btn');
        actionBtn?.addEventListener('click', () => {
            if (stepInfo.actionCallback) {
                stepInfo.actionCallback();
            } else if (stepInfo.actionUrl) {
                window.location.href = stepInfo.actionUrl;
            }
        });
    }

    async showCompletionCelebration() {
        // Verificar se já mostrou celebração
        if (localStorage.getItem('lukrato_onboarding_celebration_shown') === 'true') {
            // Marcar como completado mesmo se já mostrou antes
            await this.markCompleted();
            return;
        }

        localStorage.setItem('lukrato_onboarding_celebration_shown', 'true');

        // Marcar onboarding como completado
        await this.markCompleted();

        // BLOQUEAR conquistas temporariamente para não atropelarem o modal de setup
        window.gamificationPaused = true;

        // FECHAR QUALQUER MODAL EXISTENTE DO SWEETALERT2
        if (typeof Swal !== 'undefined' && Swal.isVisible()) {
            Swal.close();
        }

        // Aguardar um pouco para processar o lançamento
        await new Promise(resolve => setTimeout(resolve, 300));

        // Tocar som IMEDIATAMENTE
        try {
            const baseUrl = window.BASE_URL || '/lukrato/public/';
            const audio = new Audio(baseUrl + 'assets/audio/success-fanfare-trumpets-6185.mp3');
            audio.volume = 0.5;
            audio.play()
                .then(() => console.log('🔊 Som de celebração tocando!'))
                .catch(err => console.warn('⚠️ Não foi possível tocar o som:', err.message));
        } catch (err) {
            console.warn('⚠️ Erro ao criar áudio:', err);
        }

        // Confetes logo após o som
        try {
            if (typeof confetti === 'function') {
                setTimeout(() => {
                    const duration = 3 * 1000;
                    const animationEnd = Date.now() + duration;
                    const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 100000 };

                    const interval = setInterval(function () {
                        const timeLeft = animationEnd - Date.now();
                        if (timeLeft <= 0) return clearInterval(interval);

                        const particleCount = 50 * (timeLeft / duration);
                        confetti(Object.assign({}, defaults, {
                            particleCount,
                            origin: { x: Math.random(), y: Math.random() - 0.2 }
                        }));
                    }, 250);
                }, 100);
            } else {
                console.warn('⚠️ Biblioteca confetti não está carregada');
            }
        } catch (error) {
            console.error('❌ Erro ao executar confetes:', error);
        }

        // Criar modal de celebração de setup completo
        const celebrationHTML = `
            <div class="completion-celebration-overlay">
                <div class="completion-celebration">
                    <div class="cc-confetti">🎉🎊✨🎈🎁</div>
                    <div class="cc-icon">🏆</div>
                    <h2>Parabéns! Você completou o setup inicial!</h2>
                    <p>Agora você está pronto para controlar suas finanças como um profissional</p>
                    
                    <div class="cc-achievements">
                        <div class="cc-achievement">
                            <i class="fas fa-check-circle"></i>
                            <span>Conta criada</span>
                        </div>
                        <div class="cc-achievement">
                            <i class="fas fa-check-circle"></i>
                            <span>Categorias padrão já configuradas</span>
                        </div>
                        <div class="cc-achievement">
                            <i class="fas fa-check-circle"></i>
                            <span>Primeiro lançamento registrado</span>
                        </div>
                    </div>

                    <div class="cc-rewards">
                        <div class="cc-reward">
                            <i class="fas fa-star"></i>
                            <strong>+50 Pontos</strong>
                            <small>Bônus de início</small>
                        </div>
                        <div class="cc-reward">
                            <i class="fas fa-trophy"></i>
                            <strong>Conquista Desbloqueada</strong>
                            <small>Primeiro Passo</small>
                        </div>
                    </div>

                    <div class="cc-next-steps">
                        <h3>Próximos Passos:</h3>
                        <ul>
                            <li><i class="fas fa-chart-line"></i> Explore os relatórios financeiros</li>
                            <li><i class="fas fa-calendar-alt"></i> Configure lembretes de contas</li>
                            <li><i class="fas fa-target"></i> Defina suas metas financeiras</li>
                        </ul>
                    </div>

                    <button class="cc-close-btn" onclick="this.closest('.completion-celebration-overlay').remove(); document.querySelector('.onboarding-welcome')?.remove(); window.gamificationPaused = false; if(typeof window.showPendingAchievements === 'function') { window.showPendingAchievements(); }">
                        Começar a usar!
                    </button>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', celebrationHTML);

        // Remover os cards de onboarding após 2 segundos do modal aparecer
        setTimeout(() => {
            const onboardingWelcome = document.querySelector('.onboarding-welcome');
            if (onboardingWelcome) {
                onboardingWelcome.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                onboardingWelcome.style.opacity = '0';
                onboardingWelcome.style.transform = 'translateY(-20px)';
                setTimeout(() => onboardingWelcome.remove(), 500);
            }
        }, 2000);
    }

    showEmptyStateCards() {
        // Verificar página atual - NÃO mostrar cards se já estiver em página específica
        const currentPage = this.getCurrentPage();
        if (currentPage !== 'dashboard' && currentPage !== 'other') {
            return;
        }

        // Buscar container - funciona em qualquer página
        let container = document.querySelector('.lk-main');

        if (!container) {
            console.warn('🎯 Onboarding: Container não encontrado para cards');
            return;
        }


        // Criar cards de ação rápida
        const quickStartHTML = `
            <div class="onboarding-welcome" style="animation: fadeInUp 0.6s ease;">
                <div class="welcome-header">
                    <div class="welcome-icon">🎯</div>
                    <h2>Bem-vindo ao Lukrato!</h2>
                    <p>Vamos começar sua jornada para organizar suas finanças</p>
                </div>

                <div class="quick-start-grid">
                    <div class="quick-start-card" data-action="create-account">
                        <div class="qsc-icon" style="background: linear-gradient(135deg, #3b82f6, #60a5fa);">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="qsc-content">
                            <h3>1. Crie sua primeira conta</h3>
                            <p>Adicione sua conta bancária, carteira ou cartão de crédito</p>
                            <button class="qsc-btn">
                                <span>Criar Conta</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                        <div class="qsc-badge">Passo 1</div>
                    </div>

                    <div class="quick-start-card" data-action="create-transaction">
                        <div class="qsc-icon" style="background: linear-gradient(135deg, #10b981, #34d399);">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div class="qsc-content">
                            <h3>2. Registre lançamentos</h3>
                            <p>Adicione suas receitas e despesas para controlar seu dinheiro</p>
                            <p class="qsc-detail">✨ Categorias já estão configuradas para você!</p>
                            <button class="qsc-btn">
                                <span>Adicionar Lançamento</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                        <div class="qsc-badge">Passo 2</div>
                    </div>
                </div>

                <div class="welcome-footer">
                    <button class="skip-onboarding" onclick="window.onboardingManager.skip()">
                        Pular tutorial
                    </button>
                    <div class="welcome-features">
                        <div class="feature-item">
                            <i class="fas fa-gamepad"></i>
                            <span>Sistema de Pontos</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-trophy"></i>
                            <span>Conquistas</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-chart-line"></i>
                            <span>Relatórios</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remover banner de próximo passo se existir
        const existingBanner = document.querySelector('.next-step-banner');
        if (existingBanner) existingBanner.remove();

        // Inserir antes do conteúdo
        const firstSection = container.querySelector('section') || container.firstElementChild;
        if (firstSection) {
            firstSection.insertAdjacentHTML('beforebegin', quickStartHTML);
            this.attachQuickStartEvents();
        } else {
            container.insertAdjacentHTML('afterbegin', quickStartHTML);
            this.attachQuickStartEvents();
        }
    }

    showQuickActionsBar() {
        const header = document.querySelector('.page-header') || document.querySelector('.modern-dashboard');
        if (!header) return;

        const quickActionsHTML = `
            <div class="quick-actions-bar" style="animation: slideDown 0.5s ease;">
                <div class="qab-content">
                    <div class="qab-icon">💡</div>
                    <div class="qab-text">
                        <strong>Dica rápida:</strong> 
                        Adicione seus primeiros lançamentos para ver suas finanças ganharem vida!
                    </div>
                    <button class="qab-btn" onclick="window.onboardingManager.openLancamentoModal()">
                        <i class="fas fa-plus"></i>
                        Adicionar Lançamento
                    </button>
                    <button class="qab-close" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;

        header.insertAdjacentHTML('afterend', quickActionsHTML);
    }

    attachQuickStartEvents() {
        // Criar Conta
        const createAccountCard = document.querySelector('[data-action="create-account"]');
        createAccountCard?.querySelector('.qsc-btn')?.addEventListener('click', () => {
            window.location.href = `${this.baseUrl}contas`;
        });

        // Criar Lançamento
        const createTransactionCard = document.querySelector('[data-action="create-transaction"]');
        createTransactionCard?.querySelector('.qsc-btn')?.addEventListener('click', () => {
            this.openLancamentoModal();
        });
    }

    openLancamentoModal() {
        // Tentar abrir modal de lançamento se existir
        const btnNovoLancamento = document.querySelector('[data-action="novo-lancamento"]') ||
            document.querySelector('.btn-add-lancamento') ||
            document.getElementById('btnNovoLancamento');

        if (btnNovoLancamento) {
            btnNovoLancamento.click();
        } else {
            // Redirecionar para página de lançamentos
            window.location.href = `${this.baseUrl}lancamentos`;
        }
    }

    start() {
        // Mostrar modal de boas-vindas
        this.showWelcomeModal();
    }

    showWelcomeModal() {
        const modalHTML = `
            <div class="onboarding-modal-overlay" id="onboardingModalOverlay">
                <div class="onboarding-modal">
                    <div class="onboarding-modal-header">
                        <div class="modal-icon">🎉</div>
                        <h2>Bem-vindo ao Lukrato!</h2>
                        <p>Sua jornada para uma vida financeira organizada começa aqui</p>
                    </div>

                    <div class="onboarding-modal-body">
                        <div class="benefit-list">
                            <div class="benefit-item">
                                <i class="fas fa-chart-line"></i>
                                <div>
                                    <strong>Controle Total</strong>
                                    <p>Veja para onde seu dinheiro está indo</p>
                                </div>
                            </div>
                            <div class="benefit-item">
                                <i class="fas fa-trophy"></i>
                                <div>
                                    <strong>Gamificação</strong>
                                    <p>Ganhe pontos e conquistas organizando suas finanças</p>
                                </div>
                            </div>
                            <div class="benefit-item">
                                <i class="fas fa-mobile-alt"></i>
                                <div>
                                    <strong>Acesso em Qualquer Lugar</strong>
                                    <p>Use no celular, tablet ou computador</p>
                                </div>
                            </div>
                            <div class="benefit-item">
                                <i class="fas fa-shield-alt"></i>
                                <div>
                                    <strong>Seguro e Privado</strong>
                                    <p>Seus dados protegidos com criptografia</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="onboarding-modal-footer">
                        <button class="btn-secondary" onclick="window.onboardingManager.skip()">
                            Explorar por conta própria
                        </button>
                        <button class="btn-primary" onclick="window.onboardingManager.startGuide()">
                            <span>Começar Tour Guiado</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    async startGuide() {
        // Fechar modal de boas-vindas
        document.getElementById('onboardingModalOverlay')?.remove();

        // Marcar como completo no servidor para não aparecer novamente
        await this.markCompleted();

        // Marcar como EM PROGRESSO para mostrar os cards de ação
        localStorage.setItem('lukrato_onboarding_in_progress', 'true');

        // Mostrar cards de ação
        this.showEmptyStateCards();
    }

    skip() {
        this.markCompleted();
        localStorage.setItem('lukrato_onboarding_in_progress', 'true'); // Também marcar como em progresso
        document.getElementById('onboardingModalOverlay')?.remove();
        document.querySelector('.onboarding-welcome')?.remove();

        // Mostrar mensagem de incentivo
        if (typeof window.showNotification === 'function') {
            window.showNotification('Você pode acessar o tutorial a qualquer momento! 👋', 'info');
        }
    }
}

// Inicializar automaticamente
window.onboardingManager = new OnboardingManager();
