/**
 * Sistema de Onboarding - Lukrato
 * Guia interativo para novos usuários
 */

class OnboardingManager {
    constructor() {
        this.baseUrl = window.BASE_URL || '/lukrato/public/';
        this.storageKey = 'lukrato_onboarding_completed';
        this.currentStep = 0;
        this.totalSteps = 3;

        this.init();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Escuta eventos de mudança de dados para atualizar onboarding automaticamente
        window.addEventListener('lukrato:data-changed', () => {
            console.log('🎯 Dados mudaram, atualizando onboarding...');
            setTimeout(() => this.checkEmptyState(), 1500);
        });
    }

    init() {
        // Verificar se já completou o onboarding
        if (this.isCompleted()) {
            this.checkEmptyState();
            return;
        }

        // Aguardar carregamento do DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.start());
        } else {
            this.start();
        }
    }

    isCompleted() {
        return localStorage.getItem(this.storageKey) === 'true';
    }

    markCompleted() {
        localStorage.setItem(this.storageKey, 'true');
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

            // Verificar se há categorias
            const categoriasResponse = await fetch(`${this.baseUrl}api/categorias`);
            const categorias = await categoriasResponse.json();
            const hasCategorias = Array.isArray(categorias) ? categorias.length > 0 : (categorias.data?.length > 0 || false);

            // Salvar progresso
            this.updateProgress({
                hasContas,
                hasCategorias,
                hasLancamentos
            });

            // Se não tem nada, mostrar empty state melhorado
            if (!hasContas && !hasLancamentos && !hasCategorias) {
                this.showEmptyStateCards();
            }
            // Se tem conta mas não tem categoria
            else if (hasContas && !hasCategorias) {
                this.showNextStepGuide('categoria');
            }
            // Se tem conta e categoria mas não tem lançamento
            else if (hasContas && hasCategorias && !hasLancamentos) {
                this.showNextStepGuide('lancamento');
            }
            // Se completou tudo, mostrar celebração
            else if (hasContas && hasCategorias && hasLancamentos) {
                this.showCompletionCelebration();
            }
        } catch (error) {
            console.error('Erro ao verificar empty state:', error);
        }
    }

    updateProgress(progress) {
        localStorage.setItem('lukrato_onboarding_progress', JSON.stringify(progress));
    }

    getProgress() {
        const saved = localStorage.getItem('lukrato_onboarding_progress');
        return saved ? JSON.parse(saved) : { hasContas: false, hasCategorias: false, hasLancamentos: false };
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
        if (nextStep === 'categoria' && currentPage === 'categorias') {
            console.log('🎯 Onboarding: Já está na página de categorias, não mostrando banner');
            return;
        }
        if (nextStep === 'lancamento' && currentPage === 'lancamentos') {
            console.log('🎯 Onboarding: Já está na página de lançamentos, não mostrando banner');
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

        console.log('🎯 Onboarding: Mostrando banner de próximo passo:', nextStep, 'em', container.className);

        const progress = this.getProgress();

        let stepInfo = {};
        if (nextStep === 'categoria') {
            stepInfo = {
                icon: '🏷️',
                title: 'Parabéns! Conta criada! 🎉',
                subtitle: 'Agora vamos organizar suas finanças',
                description: 'Crie categorias para classificar seus gastos e receitas',
                actionText: 'Criar Categoria',
                actionUrl: `${this.baseUrl}categorias`,
                step: 2
            };
        } else if (nextStep === 'lancamento') {
            stepInfo = {
                icon: '💰',
                title: 'Ótimo! Categorias criadas! 🎊',
                subtitle: 'Hora de registrar suas movimentações',
                description: 'Adicione seus primeiros lançamentos para começar a controlar seu dinheiro',
                actionText: 'Adicionar Lançamento',
                actionCallback: () => this.openLancamentoModal(),
                step: 3
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
                    <div class="nsb-step ${progress.hasCategorias ? 'completed' : ''}">
                        <i class="fas ${progress.hasCategorias ? 'fa-check-circle' : 'fa-circle'}"></i>
                        <span>Categorias</span>
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

    showCompletionCelebration() {
        // Verificar se já mostrou celebração
        if (localStorage.getItem('lukrato_onboarding_celebration_shown') === 'true') {
            return;
        }

        localStorage.setItem('lukrato_onboarding_celebration_shown', 'true');

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
                            <span>Categorias organizadas</span>
                        </div>
                        <div class="cc-achievement">
                            <i class="fas fa-check-circle"></i>
                            <span>Primeiro lançamento</span>
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

                    <button class="cc-close-btn" onclick="document.querySelector('.completion-celebration-overlay').remove()">
                        Começar a usar!
                    </button>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', celebrationHTML);
    }

    showEmptyStateCards() {
        // Verificar página atual - NÃO mostrar cards se já estiver em página específica
        const currentPage = this.getCurrentPage();
        if (currentPage !== 'dashboard' && currentPage !== 'other') {
            console.log('🎯 Onboarding: Já está em página específica, não mostrando cards de boas-vindas');
            return;
        }

        // Buscar container - funciona em qualquer página
        let container = document.querySelector('.lk-main');

        if (!container) {
            console.warn('🎯 Onboarding: Container não encontrado para cards');
            return;
        }

        console.log('🎯 Onboarding: Mostrando cards de boas-vindas em', container.className);

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

                    <div class="quick-start-card" data-action="create-category">
                        <div class="qsc-icon" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa);">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="qsc-content">
                            <h3>2. Organize com categorias</h3>
                            <p>Crie categorias personalizadas para seus gastos e receitas</p>
                            <button class="qsc-btn">
                                <span>Criar Categoria</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                        <div class="qsc-badge">Passo 2</div>
                    </div>

                    <div class="quick-start-card" data-action="create-transaction">
                        <div class="qsc-icon" style="background: linear-gradient(135deg, #10b981, #34d399);">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div class="qsc-content">
                            <h3>3. Registre lançamentos</h3>
                            <p>Adicione suas receitas e despesas para controlar seu dinheiro</p>
                            <button class="qsc-btn">
                                <span>Adicionar Lançamento</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                        <div class="qsc-badge">Passo 3</div>
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

        // Criar Categoria
        const createCategoryCard = document.querySelector('[data-action="create-category"]');
        createCategoryCard?.querySelector('.qsc-btn')?.addEventListener('click', () => {
            window.location.href = `${this.baseUrl}categorias`;
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

    startGuide() {
        // Fechar modal de boas-vindas
        document.getElementById('onboardingModalOverlay')?.remove();

        // Marcar como completado e mostrar cards de ação
        this.markCompleted();
        this.showEmptyStateCards();
    }

    skip() {
        this.markCompleted();
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
