<section class="orc-page">

    <!-- ==================== HEADER ==================== -->
    <header class="orc-page-header" data-aos="fade-up">
        <div class="orc-page-header__text">
            <h1 class="orc-page-header__title">Orcamento</h1>
            <p class="orc-page-header__desc">Quanto voce pode gastar e quanto ja gastou neste mes</p>
        </div>
    </header>

    <!-- ==================== CARDS RESUMO ==================== -->
    <div class="orc-summary-grid" id="summaryOrcamentos" data-aos="fade-up">
        <div class="orc-summary-card orc-summary-card--saude surface-card surface-card--interactive">
            <div class="orc-saude-content" id="saudeContent">
                <div class="orc-summary-card__icon">
                    <div class="orc-saude-ring" id="saudeRing">
                        <svg viewBox="0 0 36 36">
                            <path class="orc-ring-bg"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="orc-ring-fill" id="saudeRingFill" stroke-dasharray="100, 100"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <span class="orc-ring-text" id="saudeScore">--</span>
                    </div>
                </div>
                <div class="orc-summary-card__info">
                    <span class="orc-summary-card__label">Saude Financeira</span>
                    <span class="orc-summary-card__status" id="saudeLabel">Carregando...</span>
                </div>
            </div>
            <div class="orc-saude-cta" id="saudeCta" style="display:none">
                <div class="orc-saude-cta__icon">
                    <i data-lucide="heart-pulse"></i>
                </div>
                <div class="orc-saude-cta__text">
                    <span class="orc-summary-card__label">Saude Financeira</span>
                    <span class="orc-saude-cta__msg">Defina orcamentos nas categorias para acompanhar sua saude financeira</span>
                </div>
            </div>
        </div>

        <div class="orc-summary-card surface-card surface-card--interactive">
            <div class="orc-summary-card__icon orc-summary-card__icon--blue">
                <i data-lucide="wallet"></i>
            </div>
            <div class="orc-summary-card__info">
                <span class="orc-summary-card__label">Orcado</span>
                <span class="orc-summary-card__value" id="totalOrcado">R$ --</span>
            </div>
        </div>

        <div class="orc-summary-card surface-card surface-card--interactive">
            <div class="orc-summary-card__icon orc-summary-card__icon--orange">
                <i data-lucide="receipt"></i>
            </div>
            <div class="orc-summary-card__info">
                <span class="orc-summary-card__label">Gasto</span>
                <span class="orc-summary-card__value" id="totalGasto">R$ --</span>
            </div>
        </div>

        <div class="orc-summary-card surface-card surface-card--interactive">
            <div class="orc-summary-card__icon orc-summary-card__icon--green">
                <i data-lucide="piggy-bank"></i>
            </div>
            <div class="orc-summary-card__info">
                <span class="orc-summary-card__label">Disponivel</span>
                <span class="orc-summary-card__value" id="totalDisponivel">R$ --</span>
            </div>
        </div>
    </div>

    <!-- ==================== FOCO DO PERIODO ==================== -->
    <section class="orc-focus-panel surface-card surface-card--interactive" id="orcFocusPanel" data-aos="fade-up"
        data-aos-delay="80">
        <div class="orc-focus-panel__main">
            <div class="orc-focus-panel__eyebrow">
                <i data-lucide="sparkles"></i>
                <span>Onde agir agora</span>
            </div>
            <div class="orc-focus-panel__content" id="orcFocusContent">
                <div class="lk-loading-state">
                    <i data-lucide="loader-2"></i>
                    <p>Mapeando seus pontos de atencao...</p>
                </div>
            </div>
        </div>
        <div class="orc-focus-panel__stats" id="orcFocusStats">
            <div class="orc-focus-stat">
                <span class="orc-focus-stat__label">Em alerta</span>
                <strong class="orc-focus-stat__value">--</strong>
            </div>
            <div class="orc-focus-stat">
                <span class="orc-focus-stat__label">Estourados</span>
                <strong class="orc-focus-stat__value">--</strong>
            </div>
            <div class="orc-focus-stat">
                <span class="orc-focus-stat__label">Uso geral</span>
                <strong class="orc-focus-stat__value">--</strong>
            </div>
        </div>
    </section>

    <!-- ==================== ACOES RAPIDAS ==================== -->
    <div class="orc-actions-bar" data-aos="fade-up" data-aos-delay="100">
        <div class="orc-actions-bar__left">
            <button class="orc-action-btn orc-action-btn--primary" id="btnAutoSugerir"
                title="A IA analisa seus ultimos 3 meses e sugere orcamentos automaticamente">
                <i data-lucide="wand-2"></i>
                <span>Sugestao Inteligente</span>
            </button>
            <button class="orc-action-btn" id="btnCopiarMes" title="Copiar orcamentos do mes anterior">
                <i data-lucide="copy"></i>
                <span>Copiar Mes Anterior</span>
            </button>
        </div>
        <button class="orc-action-btn orc-action-btn--success" id="btnNovoOrcamento">
            <i data-lucide="plus"></i>
            <span>Novo Orcamento</span>
        </button>
    </div>

    <!-- ==================== FILTROS ==================== -->
    <section class="orc-toolbar surface-card" data-aos="fade-up" data-aos-delay="120">
        <label class="orc-toolbar__search">
            <i data-lucide="search"></i>
            <input type="search" id="orcSearchInput" placeholder="Buscar categoria">
        </label>
        <div class="orc-toolbar__chips" id="orcFilterChips">
            <button type="button" class="orc-chip is-active" data-filter="all">Todos</button>
            <button type="button" class="orc-chip" data-filter="over">Estourados</button>
            <button type="button" class="orc-chip" data-filter="warn">Em alerta</button>
            <button type="button" class="orc-chip" data-filter="ok">Com folga</button>
            <button type="button" class="orc-chip" data-filter="rollover">Com rollover</button>
        </div>
        <label class="orc-toolbar__sort">
            <span>Ordenar</span>
            <select id="orcSortSelect" class="fin-select">
                <option value="usage">Maior uso</option>
                <option value="exceeded">Maior excedente</option>
                <option value="remaining">Maior folga</option>
                <option value="alpha">Nome</option>
            </select>
        </label>
    </section>

    <!-- ==================== GRID DE ORCAMENTOS ==================== -->
    <div class="orc-grid" id="orcamentosGrid" data-aos="fade-up" data-aos-delay="150">
        <div class="lk-loading-state">
            <i data-lucide="loader-2"></i>
            <p>Carregando orcamentos...</p>
        </div>
    </div>

    <!-- ==================== ESTADO VAZIO ==================== -->
    <div class="orc-empty-state" id="orcamentosEmpty" style="display: none;">
        <div class="orc-empty-state__icon">
            <i data-lucide="pie-chart"></i>
        </div>
        <h3 class="orc-empty-state__title">Nenhum orcamento configurado</h3>
        <p class="orc-empty-state__text">Configure orcamentos por categoria para controlar seus gastos.<br>
            Clique em <strong>"Sugestao Inteligente"</strong> para configurar automaticamente.</p>
        <button class="orc-action-btn orc-action-btn--primary" id="btnAutoSugerirEmpty">
            <i data-lucide="wand-2"></i>
            <span>Configurar Automaticamente</span>
        </button>
    </div>

    <!-- ==================== INSIGHTS ==================== -->
    <div class="orc-insights-section" id="insightsSection" style="display:none;" data-aos="fade-up">
        <div class="orc-section-label">
            <i data-lucide="lightbulb"></i>
            <span>Insights</span>
        </div>
        <div class="orc-insights-grid" id="insightsGrid"></div>
    </div>

</section>

<!-- ==================== MODAL: NOVO/EDITAR ORCAMENTO ==================== -->
<div class="fin-modal-overlay" id="modalOrcamento">
    <div class="fin-modal">
        <div class="fin-modal-header">
            <h3 id="modalOrcamentoTitle">Novo Orcamento</h3>
            <button class="fin-modal-close" data-close-modal="modalOrcamento">
                <i data-lucide="x"></i>
            </button>
        </div>
        <form id="formOrcamento">
            <?= csrf_input('default') ?>
            <div class="fin-modal-body">
                <div class="fin-form-group">
                    <label class="fin-label">
                        <i data-lucide="tag"></i> Categoria
                    </label>
                    <select id="orcCategoria" class="fin-select" required>
                        <option value="">Selecione uma categoria</option>
                    </select>
                </div>
                <div class="fin-form-group">
                    <label class="fin-label">
                        <i data-lucide="dollar-sign"></i> Limite Mensal
                    </label>
                    <input type="text" id="orcValor" class="fin-input" placeholder="R$ 0,00" required>
                    <span class="fin-hint" id="orcSugestao"></span>
                </div>
                <div class="fin-form-row">
                    <label class="fin-toggle">
                        <input type="checkbox" id="orcRollover">
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">Acumular sobra do mes anterior</span>
                    </label>
                </div>
                <div class="fin-form-row">
                    <label class="fin-toggle">
                        <input type="checkbox" id="orcAlerta80" checked>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">Alertar ao atingir 80%</span>
                    </label>
                </div>
                <div class="fin-form-row">
                    <label class="fin-toggle">
                        <input type="checkbox" id="orcAlerta100" checked>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">Alertar ao estourar</span>
                    </label>
                </div>
            </div>
            <div class="fin-modal-footer">
                <button type="button" class="fin-btn secondary" data-close-modal="modalOrcamento">Cancelar</button>
                <button type="submit" class="fin-btn primary">
                    <i data-lucide="check"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== MODAL: SUGESTOES INTELIGENTES ==================== -->
<div class="fin-modal-overlay" id="modalSugestoes">
    <div class="fin-modal large">
        <div class="fin-modal-header">
            <h3><i data-lucide="wand-2" style="color: var(--color-primary)"></i> Sugestao Inteligente</h3>
            <button class="fin-modal-close" data-close-modal="modalSugestoes">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="fin-modal-body">
            <p class="fin-modal-desc">
                Analisamos seus gastos dos ultimos 3 meses e sugerimos limites <strong>abaixo da sua media</strong> para
                ajudar voce a economizar em cada categoria.
                Voce pode ajustar os valores antes de aplicar.
            </p>
            <div class="sugestoes-list" id="sugestoesList">
                <div class="lk-loading-state">
                    <i data-lucide="loader-2"></i>
                    <p>Analisando seu historico...</p>
                </div>
            </div>
        </div>
        <div class="fin-modal-footer">
            <button type="button" class="fin-btn secondary" data-close-modal="modalSugestoes">Cancelar</button>
            <button type="button" class="fin-btn primary" id="btnAplicarSugestoes">
                <i data-lucide="check-check"></i> Aplicar Todas
            </button>
        </div>
    </div>
</div>

<!-- Page JS carregado automaticamente via loadPageJs() + Vite -->
