/**
 * Sistema de Onboarding - Lukrato
 * Guia interativo para novos usuários
 * 
 * FONTE DE VERDADE: SERVIDOR (banco de dados)
 * 
 * Estados do onboarding:
 * - Não iniciado: completed = false
 * - Tour guiado em andamento: completed = true, mode = 'guided', tour_skipped = false
 * - Tour guiado pulado: completed = true, mode = 'guided', tour_skipped = true
 * - Explorar por conta própria: completed = true, mode = 'self'
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

        // Estado do onboarding (vindo do servidor)
        this.status = {
            completed: false,
            completed_at: null,
            mode: null,
            tour_skipped: false,
            tour_skipped_at: null,
            should_show_tour: false
        };

        this.isLoading = true;
        this.currentStep = 0;
        this.totalSteps = 2;

        this.init();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Escuta eventos de mudança de dados para atualizar onboarding automaticamente
        window.addEventListener('lukrato:data-changed', () => {
            setTimeout(() => this.checkProgress(), 500);
        });

        // Escutar criação de lançamentos
        window.addEventListener('lancamento-created', () => {
            setTimeout(() => this.checkProgress(), 300);
        });

        // Escutar criação de contas
        window.addEventListener('conta-created', () => {
            setTimeout(() => this.checkProgress(), 300);
        });
    }

    async init() {
        try {
            // Carregar status do servidor (única fonte de verdade)
            await this.loadStatusFromServer();

            // Se onboarding não foi iniciado (usuário novo), mostrar modal de boas-vindas
            if (!this.status.completed) {
                window.gamificationPaused = true;
                this.showWelcomeModal();
                return;
            }

            // Se usuário escolheu explorar por conta própria, não fazer nada
            if (this.status.mode === 'self') {
                window.gamificationPaused = false;
                return;
            }

            // Se o tour foi pulado, não mostrar mais nada
            if (this.status.tour_skipped) {
                window.gamificationPaused = false;
                return;
            }

            // Se deve mostrar tour guiado, verificar progresso
            if (this.status.should_show_tour) {
                window.gamificationPaused = true;
                await this.checkProgress();
            } else {
                window.gamificationPaused = false;
            }
        } catch (error) {
            console.error('[Onboarding] Erro na inicialização:', error);
            // Em caso de erro, não mostrar nada para não bloquear o usuário
            window.gamificationPaused = false;
        }
    }

    /**
     * Carrega o status do onboarding do servidor
     */
    async loadStatusFromServer() {
        try {
            const response = await fetch(`${this.baseUrl}api/onboarding/status`, {
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (data.success && data.data) {
                this.status = data.data;
            }
        } catch (error) {
            console.warn('[Onboarding] Erro ao carregar status do servidor:', error);
            throw error;
        } finally {
            this.isLoading = false;
        }
    }

    /**
     * Marca o onboarding como completo com o modo escolhido
     * @param {string} mode - 'guided' ou 'self'
     */
    async markComplete(mode) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            const response = await fetch(`${this.baseUrl}api/onboarding/complete`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ mode })
            });

            const data = await response.json();

            if (data.success && data.data) {
                this.status = data.data;
                return true;
            }

            console.error('[Onboarding] Erro ao marcar completo:', data);
            return false;
        } catch (error) {
            console.error('[Onboarding] Erro ao marcar completo:', error);
            return false;
        }
    }

    /**
     * Marca o tour como pulado
     */
    async skipTour() {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            const response = await fetch(`${this.baseUrl}api/onboarding/skip-tour`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success && data.data) {
                this.status = data.data;
                return true;
            }

            console.error('[Onboarding] Erro ao pular tour:', data);
            return false;
        } catch (error) {
            console.error('[Onboarding] Erro ao pular tour:', error);
            return false;
        }
    }

    /**
     * Verifica o progresso do usuário (contas e lançamentos)
     */
    async checkProgress() {
        try {
            // Se não deve mostrar tour, não verificar
            if (!this.status.should_show_tour) {
                return;
            }

            // Verificar se há contas
            const contasResponse = await fetch(`${this.baseUrl}api/contas`);
            const contas = await contasResponse.json();
            const hasContas = Array.isArray(contas) ? contas.length > 0 : (contas.data?.length > 0 || false);

            // Verificar se há lançamentos
            const lancamentosResponse = await fetch(`${this.baseUrl}api/lancamentos?limit=10`);
            const lancamentos = await lancamentosResponse.json();
            const hasLancamentos = Array.isArray(lancamentos) ? lancamentos.length > 0 : (lancamentos.data?.length > 0 || false);

            const progress = { hasContas, hasLancamentos };

            // Se não tem nada, mostrar empty state cards
            if (!hasContas && !hasLancamentos) {
                this.showEmptyStateCards(progress);
            }
            // Se tem conta mas não tem lançamento
            else if (hasContas && !hasLancamentos) {
                this.showNextStepGuide('lancamento', progress);
            }
            // Se completou tudo, mostrar celebração e finalizar
            else if (hasContas && hasLancamentos) {
                await this.showCompletionCelebration();
            }
        } catch (error) {
            console.error('[Onboarding] Erro ao verificar progresso:', error);
        }
    }

    getCurrentPage() {
        const path = window.location.pathname.toLowerCase();
        if (path.includes('/contas')) return 'contas';
        if (path.includes('/categorias')) return 'categorias';
        if (path.includes('/lancamentos')) return 'lancamentos';
        if (path.includes('/dashboard')) return 'dashboard';
        return 'other';
    }

    showNextStepGuide(nextStep, progress) {
        // Verificar página atual
        const currentPage = this.getCurrentPage();

        // NÃO mostrar banner se já estiver na página de destino
        if (nextStep === 'lancamento' && currentPage === 'lancamentos') {
            return;
        }

        // Buscar container
        let container = document.querySelector('.lk-main') ||
            document.querySelector('.content-wrapper') ||
            document.querySelector('main');

        if (!container) {
            return;
        }

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
        document.querySelector('.onboarding-welcome')?.remove();

        // Remover banner anterior se existir
        document.querySelector('.next-step-banner')?.remove();

        // Inserir no topo do container
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
        // Marcar tour como pulado (já completou tudo)
        await this.skipTour();

        // BLOQUEAR conquistas temporariamente
        window.gamificationPaused = true;

        // FECHAR QUALQUER MODAL EXISTENTE DO SWEETALERT2
        if (typeof Swal !== 'undefined' && Swal.isVisible()) {
            Swal.close();
        }

        // Aguardar um pouco para processar
        await new Promise(resolve => setTimeout(resolve, 300));

        // Tocar som
        try {
            const baseUrl = window.BASE_URL || '/lukrato/public/';
            const audio = new Audio(baseUrl + 'assets/audio/success-fanfare-trumpets-6185.mp3');
            audio.volume = 0.5;
            audio.play().catch(() => {});
        } catch (err) {
            // Ignorar erro de áudio
        }

        // Confetes
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
            }
        } catch (error) {
            // Ignorar erro de confetti
        }

        // Criar modal de celebração
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

                    <button class="cc-close-btn" onclick="window.onboardingManager.closeCelebration()">
                        Começar a usar!
                    </button>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', celebrationHTML);

        // Remover os cards de onboarding
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

    closeCelebration() {
        document.querySelector('.completion-celebration-overlay')?.remove();
        document.querySelector('.onboarding-welcome')?.remove();
        document.querySelector('.next-step-banner')?.remove();
        window.gamificationPaused = false;
        if (typeof window.showPendingAchievements === 'function') {
            window.showPendingAchievements();
        }
    }

    showEmptyStateCards(progress = { hasContas: false, hasLancamentos: false }) {
        // Verificar página atual - NÃO mostrar cards se já estiver em página específica
        const currentPage = this.getCurrentPage();
        if (currentPage !== 'dashboard' && currentPage !== 'other') {
            return;
        }

        // Buscar container
        let container = document.querySelector('.lk-main');
        if (!container) {
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
                    <button class="skip-onboarding" onclick="window.onboardingManager.skipTutorial()">
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
        document.querySelector('.next-step-banner')?.remove();
        document.querySelector('.onboarding-welcome')?.remove();

        // Inserir antes do conteúdo
        const firstSection = container.querySelector('section') || container.firstElementChild;
        if (firstSection) {
            firstSection.insertAdjacentHTML('beforebegin', quickStartHTML);
        } else {
            container.insertAdjacentHTML('afterbegin', quickStartHTML);
        }

        this.attachQuickStartEvents();
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

    showWelcomeModal() {
        // Verificar se modal já existe
        if (document.getElementById('onboardingModalOverlay')) {
            return;
        }

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
                        <button class="btn-secondary" onclick="window.onboardingManager.exploreSelf()">
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

    /**
     * Usuário escolheu "Começar Tour Guiado"
     */
    async startGuide() {
        // Fechar modal de boas-vindas
        document.getElementById('onboardingModalOverlay')?.remove();

        // Marcar como completo com modo 'guided'
        const success = await this.markComplete('guided');
        if (!success) {
            if (typeof window.showNotification === 'function') {
                window.showNotification('Erro ao iniciar o tour. Tente novamente.', 'error');
            }
            return;
        }

        // Mostrar cards de ação
        await this.checkProgress();
    }

    /**
     * Usuário escolheu "Explorar por conta própria"
     */
    async exploreSelf() {
        // Fechar modal de boas-vindas
        document.getElementById('onboardingModalOverlay')?.remove();

        // Marcar como completo com modo 'self'
        const success = await this.markComplete('self');
        if (!success) {
            if (typeof window.showNotification === 'function') {
                window.showNotification('Erro ao salvar preferência. Tente novamente.', 'error');
            }
            return;
        }

        // Despausar gamificação
        window.gamificationPaused = false;

        // Mostrar mensagem de incentivo
        if (typeof window.showNotification === 'function') {
            window.showNotification('Explore à vontade! Estamos aqui se precisar de ajuda. 👋', 'info');
        }
    }

    /**
     * Usuário clicou em "Pular tutorial"
     */
    async skipTutorial() {
        // Marcar tour como pulado no servidor
        const success = await this.skipTour();
        if (!success) {
            if (typeof window.showNotification === 'function') {
                window.showNotification('Erro ao pular tutorial. Tente novamente.', 'error');
            }
            return;
        }

        // Remover elementos visuais
        document.getElementById('onboardingModalOverlay')?.remove();
        document.querySelector('.onboarding-welcome')?.remove();
        document.querySelector('.next-step-banner')?.remove();

        // Despausar gamificação
        window.gamificationPaused = false;

        // Mostrar mensagem
        if (typeof window.showNotification === 'function') {
            window.showNotification('Tutorial pulado. Você pode acessar a ajuda a qualquer momento! 👋', 'info');
        }
    }

    // Método legado para compatibilidade
    skip() {
        this.skipTutorial();
    }
}

// Inicializar automaticamente
window.onboardingManager = new OnboardingManager();
