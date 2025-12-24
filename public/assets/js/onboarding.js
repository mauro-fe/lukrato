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
        let container = document.querySelector('.modern-dashboard') ||
            document.querySelector('.content-wrapper') ||
            document.querySelector('.lk-main') ||
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

        // Inserir no topo do container
        const firstSection = container.querySelector('section') || container.firstElementChild;
        if (firstSection) {
            firstSection.insertAdjacentHTML('beforebegin', nextStepHTML);
        } else {
            container.insertAdjacentHTML('afterbegin', nextStepHTML);
        }

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
        let container = document.querySelector('.modern-dashboard') ||
            document.querySelector('.content-wrapper') ||
            document.querySelector('.lk-main') ||
            document.querySelector('main');

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

// Estilos inline para o onboarding
const onboardingStyles = document.createElement('style');
onboardingStyles.textContent = `
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.onboarding-welcome {
    background: var(--glass-bg);
    border: 2px solid var(--glass-border);
    border-radius: 24px;
    padding: 40px;
    margin-bottom: 32px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.welcome-header {
    text-align: center;
    margin-bottom: 40px;
}

.welcome-icon {
    font-size: 64px;
    margin-bottom: 16px;
    animation: bounce 2s ease-in-out infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.welcome-header h2 {
    font-size: 32px;
    font-weight: 800;
    color: var(--color-text);
    margin-bottom: 12px;
}

.welcome-header p {
    font-size: 16px;
    color: var(--color-text-muted);
}

.quick-start-grid {
    display: grid;
    gap: 24px;
    margin-bottom: 32px;
}

@media (min-width: 768px) {
    .quick-start-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.quick-start-card {
    background: var(--color-surface);
    border: 2px solid var(--glass-border);
    border-radius: 16px;
    padding: 24px;
    position: relative;
    transition: all 0.3s ease;
    cursor: pointer;
}

.quick-start-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    border-color: var(--color-primary);
}

.qsc-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: white;
    margin-bottom: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.qsc-content h3 {
    font-size: 18px;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: 8px;
}

.qsc-content p {
    font-size: 14px;
    color: var(--color-text-muted);
    line-height: 1.6;
    margin-bottom: 16px;
}

.qsc-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 12px 16px;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark, #d35400));
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.qsc-btn:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
}

.qsc-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    background: var(--color-primary);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}

.welcome-footer {
    display: flex;
    flex-direction: column;
    gap: 20px;
    align-items: center;
}

.skip-onboarding {
    background: none;
    border: none;
    color: var(--color-text-muted);
    font-size: 14px;
    cursor: pointer;
    text-decoration: underline;
    transition: color 0.2s;
}

.skip-onboarding:hover {
    color: var(--color-text);
}

.welcome-features {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
    justify-content: center;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: var(--color-text-muted);
}

.feature-item i {
    color: var(--color-primary);
}

/* Quick Actions Bar */
.quick-actions-bar {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    border-radius: 12px;
    padding: 16px 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);
}

.qab-content {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.qab-icon {
    font-size: 28px;
}

.qab-text {
    flex: 1;
    color: #78350f;
    font-size: 14px;
    min-width: 200px;
}

.qab-text strong {
    color: #78350f;
    font-weight: 700;
}

.qab-btn {
    padding: 10px 20px;
    background: white;
    color: #f59e0b;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.qab-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.qab-close {
    background: rgba(255, 255, 255, 0.3);
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    color: #78350f;
    cursor: pointer;
    transition: all 0.2s;
}

.qab-close:hover {
    background: rgba(255, 255, 255, 0.5);
}

/* Modal de Boas-vindas */
.onboarding-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.onboarding-modal {
    background: var(--color-surface);
    border-radius: 24px;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.4s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.onboarding-modal-header {
    text-align: center;
    padding: 40px 40px 32px;
    border-bottom: 1px solid var(--glass-border);
}

.modal-icon {
    font-size: 72px;
    margin-bottom: 16px;
}

.onboarding-modal-header h2 {
    font-size: 28px;
    font-weight: 800;
    color: var(--color-text);
    margin-bottom: 12px;
}

.onboarding-modal-header p {
    font-size: 16px;
    color: var(--color-text-muted);
}

.onboarding-modal-body {
    padding: 32px 40px;
}

.benefit-list {
    display: grid;
    gap: 20px;
}

.benefit-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.benefit-item i {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark, #d35400));
    color: white;
    border-radius: 12px;
    font-size: 20px;
    flex-shrink: 0;
}

.benefit-item strong {
    display: block;
    font-size: 16px;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: 4px;
}

.benefit-item p {
    font-size: 14px;
    color: var(--color-text-muted);
    line-height: 1.6;
}

.onboarding-modal-footer {
    padding: 24px 40px;
    border-top: 1px solid var(--glass-border);
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.btn-secondary, .btn-primary {
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    font-size: 14px;
}

.btn-secondary {
    background: var(--color-surface);
    color: var(--color-text);
    border: 2px solid var(--glass-border);
}

.btn-secondary:hover {
    background: var(--color-bg);
}

.btn-primary {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark, #d35400));
    color: white;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
}

@media (max-width: 640px) {
    .onboarding-modal {
        border-radius: 16px;
    }

    .onboarding-modal-header,
    .onboarding-modal-body,
    .onboarding-modal-footer {
        padding-left: 24px;
        padding-right: 24px;
    }

    .onboarding-modal-footer {
        flex-direction: column-reverse;
    }

    .btn-secondary, .btn-primary {
        width: 100%;
        justify-content: center;
    }
}

/* Next Step Banner */
.next-step-banner {
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 32px;
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 20px;
    align-items: center;
    position: relative;
    overflow: hidden;
}

.next-step-banner::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40" fill="rgba(255,255,255,0.05)"/></svg>');
    opacity: 0.5;
    pointer-events: none;
}

.nsb-icon {
    font-size: 56px;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
}

.nsb-content {
    color: white;
    position: relative;
    z-index: 1;
}

.nsb-content h3 {
    font-size: 24px;
    font-weight: 800;
    margin-bottom: 8px;
    color: white;
}

.nsb-subtitle {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 6px;
    opacity: 0.95;
}

.nsb-description {
    font-size: 14px;
    opacity: 0.85;
    margin: 0;
}

.nsb-action-btn {
    padding: 14px 28px;
    background: white;
    color: #059669;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.nsb-action-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 16px rgba(0,0,0,0.25);
}

.nsb-progress {
    position: absolute;
    bottom: 12px;
    right: 24px;
    display: flex;
    gap: 12px;
}

.nsb-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    color: rgba(255,255,255,0.6);
    font-size: 11px;
    transition: all 0.3s ease;
}

.nsb-step i {
    font-size: 16px;
}

.nsb-step.completed {
    color: white;
}

.nsb-step.completed i {
    animation: checkPulse 0.5s ease;
}

@keyframes checkPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.3); }
}

/* Completion Celebration */
.completion-celebration-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.85);
    backdrop-filter: blur(8px);
    z-index: 10001;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: fadeIn 0.4s ease;
}

.completion-celebration {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border-radius: 24px;
    max-width: 600px;
    width: 100%;
    padding: 48px;
    text-align: center;
    position: relative;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(0,0,0,0.5);
    animation: celebrationPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes celebrationPop {
    0% {
        opacity: 0;
        transform: scale(0.5) rotate(-10deg);
    }
    100% {
        opacity: 1;
        transform: scale(1) rotate(0);
    }
}

.cc-confetti {
    font-size: 32px;
    margin-bottom: 16px;
    animation: confettiFall 3s ease-in-out infinite;
}

@keyframes confettiFall {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.cc-icon {
    font-size: 80px;
    margin-bottom: 24px;
    animation: trophySpin 2s ease-in-out infinite;
}

@keyframes trophySpin {
    0%, 100% { transform: rotate(-5deg); }
    50% { transform: rotate(5deg); }
}

.completion-celebration h2 {
    font-size: 32px;
    font-weight: 900;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 16px;
}

.completion-celebration > p {
    font-size: 16px;
    color: rgba(255,255,255,0.8);
    margin-bottom: 32px;
}

.cc-achievements {
    display: flex;
    justify-content: center;
    gap: 24px;
    margin-bottom: 32px;
    flex-wrap: wrap;
}

.cc-achievement {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: rgba(16, 185, 129, 0.2);
    border: 2px solid #10b981;
    border-radius: 12px;
    color: #10b981;
    font-weight: 600;
}

.cc-achievement i {
    font-size: 20px;
}

.cc-rewards {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 32px;
}

.cc-reward {
    padding: 20px;
    background: rgba(59, 130, 246, 0.1);
    border: 2px solid rgba(59, 130, 246, 0.3);
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.cc-reward i {
    font-size: 32px;
    color: #3b82f6;
}

.cc-reward strong {
    color: white;
    font-size: 16px;
}

.cc-reward small {
    color: rgba(255,255,255,0.6);
    font-size: 12px;
}

.cc-next-steps {
    text-align: left;
    background: rgba(255,255,255,0.05);
    padding: 24px;
    border-radius: 16px;
    margin-bottom: 32px;
}

.cc-next-steps h3 {
    color: white;
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 16px;
}

.cc-next-steps ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.cc-next-steps li {
    color: rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
}

.cc-next-steps li i {
    color: #10b981;
    font-size: 18px;
}

.cc-close-btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
}

.cc-close-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
}

@media (max-width: 640px) {
    .next-step-banner {
        grid-template-columns: 1fr;
        text-align: center;
    }

    .nsb-progress {
        position: static;
        justify-content: center;
        margin-top: 16px;
    }

    .cc-rewards {
        grid-template-columns: 1fr;
    }

    .completion-celebration {
        padding: 32px 24px;
    }
}
`;

document.head.appendChild(onboardingStyles);

// Inicializar automaticamente
window.onboardingManager = new OnboardingManager();
