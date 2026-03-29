<section class="met-page">

    <!-- ==================== HEADER ==================== -->
    <header class="met-page-header" data-aos="fade-up">
        <div class="met-page-header__text">
            <h1 class="met-page-header__title">Metas</h1>
            <p class="met-page-header__desc">Quanto voce ja juntou e quanto falta para atingir seus objetivos</p>
        </div>
        <button class="met-action-btn met-action-btn--success met-header-cta" id="btnNovaMetaHeader">
            <i data-lucide="plus"></i>
            <span>Criar Nova Meta</span>
        </button>
    </header>

    <!-- ==================== CARDS RESUMO ==================== -->
    <div class="met-summary-grid" id="summaryMetas" data-aos="fade-up">
        <div class="met-summary-card surface-card surface-card--interactive">
            <div class="met-summary-card__icon met-summary-card__icon--purple">
                <i data-lucide="target"></i>
            </div>
            <div class="met-summary-card__info">
                <span class="met-summary-card__label">Metas Ativas</span>
                <span class="met-summary-card__value" id="metasAtivas">--</span>
            </div>
        </div>

        <div class="met-summary-card surface-card surface-card--interactive">
            <div class="met-summary-card__icon met-summary-card__icon--green">
                <i data-lucide="coins"></i>
            </div>
            <div class="met-summary-card__info">
                <span class="met-summary-card__label">Acumulado</span>
                <span class="met-summary-card__value" id="metasTotalAtual">R$ --</span>
            </div>
        </div>

        <div class="met-summary-card surface-card surface-card--interactive">
            <div class="met-summary-card__icon met-summary-card__icon--blue">
                <i data-lucide="flag"></i>
            </div>
            <div class="met-summary-card__info">
                <span class="met-summary-card__label">Objetivo Total</span>
                <span class="met-summary-card__value" id="metasTotalAlvo">R$ --</span>
            </div>
        </div>

        <div class="met-summary-card surface-card surface-card--interactive">
            <div class="met-summary-card__icon">
                <div class="met-progress-ring" id="metasProgressRing">
                    <svg viewBox="0 0 36 36">
                        <path class="met-ring-bg"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="met-ring-fill met-ring-fill--good" id="metasProgressRingFill" stroke-dasharray="0, 100"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="met-ring-text" id="metasProgressScore">0%</span>
                </div>
            </div>
            <div class="met-summary-card__info">
                <span class="met-summary-card__label">Progresso Geral</span>
                <span class="met-summary-card__status met-status--good" id="metasProgressLabel">--</span>
            </div>
        </div>
    </div>

    <!-- ==================== FOCO DO MOMENTO ==================== -->
    <section class="met-focus-panel surface-card surface-card--interactive" id="metFocusPanel" data-aos="fade-up"
        data-aos-delay="80">
        <div class="met-focus-panel__main">
            <div class="met-focus-panel__eyebrow">
                <i data-lucide="sparkles"></i>
                <span>Seu proximo passo</span>
            </div>
            <div class="met-focus-panel__content" id="metFocusContent">
                <div class="lk-loading-state">
                    <i data-lucide="loader-2"></i>
                    <p>Analisando suas metas...</p>
                </div>
            </div>
        </div>
        <div class="met-focus-panel__stats" id="metFocusStats">
            <div class="met-focus-stat">
                <span class="met-focus-stat__label">Em risco</span>
                <strong class="met-focus-stat__value">--</strong>
            </div>
            <div class="met-focus-stat">
                <span class="met-focus-stat__label">Aporte sugerido</span>
                <strong class="met-focus-stat__value">--</strong>
            </div>
            <div class="met-focus-stat">
                <span class="met-focus-stat__label">Concluidas</span>
                <strong class="met-focus-stat__value">--</strong>
            </div>
        </div>
    </section>

    <!-- ==================== ACOES ==================== -->
    <div class="met-actions-bar" data-aos="fade-up" data-aos-delay="100">
        <div class="met-actions-bar__left">
            <button class="met-action-btn" id="btnTemplates">
                <i data-lucide="wand-sparkles"></i>
                <span>Usar Template</span>
            </button>
        </div>
        <button class="met-action-btn met-action-btn--success" id="btnNovaMeta">
            <i data-lucide="plus"></i>
            <span>Nova Meta</span>
        </button>
    </div>

    <!-- ==================== FILTROS ==================== -->
    <section class="met-toolbar surface-card" data-aos="fade-up" data-aos-delay="120">
        <label class="met-toolbar__search">
            <i data-lucide="search"></i>
            <input type="search" id="metSearchInput" placeholder="Buscar meta por nome">
        </label>
        <div class="met-toolbar__chips" id="metFilterChips">
            <button type="button" class="met-chip is-active" data-filter="all">Todas</button>
            <button type="button" class="met-chip" data-filter="ativa">Ativas</button>
            <button type="button" class="met-chip" data-filter="atrasada">Atrasadas</button>
            <button type="button" class="met-chip" data-filter="concluida">Concluidas</button>
        </div>
        <label class="met-toolbar__sort">
            <span>Ordenar</span>
            <select id="metSortSelect" class="fin-select">
                <option value="deadline">Prazo mais proximo</option>
                <option value="progress">Maior progresso</option>
                <option value="remaining">Maior valor restante</option>
                <option value="priority">Prioridade</option>
                <option value="title">Nome</option>
            </select>
        </label>
    </section>

    <!-- ==================== GRID DE METAS ==================== -->
    <div class="met-grid" id="metasGrid" data-aos="fade-up" data-aos-delay="150">
        <div class="lk-loading-state">
            <i data-lucide="loader-2"></i>
            <p>Carregando metas...</p>
        </div>
    </div>

    <!-- ==================== INSIGHTS ==================== -->
    <div class="met-insights-section" id="metInsightsSection" style="display:none;" data-aos="fade-up">
        <div class="met-section-label">
            <i data-lucide="lightbulb"></i>
            <span>Insights</span>
        </div>
        <div class="met-insights-grid" id="metInsightsGrid"></div>
    </div>

    <!-- ==================== ESTADO VAZIO ==================== -->
    <div class="met-empty-state" id="metasEmpty" style="display: none;">
        <div class="met-empty-state__illustration">
            <div class="met-empty-state__icon">
                <i data-lucide="target"></i>
            </div>
            <div class="met-empty-state__rings">
                <div class="met-empty-ring met-empty-ring--1"></div>
                <div class="met-empty-ring met-empty-ring--2"></div>
                <div class="met-empty-ring met-empty-ring--3"></div>
            </div>
        </div>
        <h3 class="met-empty-state__title">Comece a planejar seu futuro!</h3>
        <p class="met-empty-state__text">Crie sua primeira meta financeira e acompanhe seu progresso.<br>
            Seja economizar para uma viagem, quitar uma divida ou construir sua reserva de emergencia.</p>
        <div class="met-empty-state__actions">
            <button class="met-action-btn met-action-btn--success" id="btnNovaMetaEmpty">
                <i data-lucide="plus"></i>
                <span>Criar Primeira Meta</span>
            </button>
            <button class="met-action-btn" id="btnTemplatesEmpty">
                <i data-lucide="wand-sparkles"></i>
                <span>Escolher Template</span>
            </button>
        </div>
    </div>

</section>

<!-- ==================== MODAL: NOVA/EDITAR META ==================== -->
<div class="fin-modal-overlay" id="modalMeta">
    <div class="fin-modal">
        <div class="fin-modal-header">
            <h3 id="modalMetaTitle">Nova Meta</h3>
            <button class="fin-modal-close" data-close-modal="modalMeta">
                <i data-lucide="x"></i>
            </button>
        </div>
        <form id="formMeta">
            <?= csrf_input('default') ?>
            <div class="fin-modal-body">
                <div class="fin-form-group">
                    <label class="fin-label"><i data-lucide="pencil"></i> Titulo</label>
                    <input type="text" id="metaTitulo" class="fin-input" placeholder="Ex: Reserva de emergencia"
                        required maxlength="150">
                </div>
                <div class="fin-form-group">
                    <label class="fin-label"><i data-lucide="landmark"></i> Vincular a uma conta <span
                            class="fin-badge-optional">opcional</span></label>
                    <select id="metaContaId" class="fin-select">
                        <option value="">- Sem vinculo (aporte manual) -</option>
                    </select>
                    <span class="fin-hint" id="metaContaHint" style="display:none"><i data-lucide="info"></i> O
                        progresso sera atualizado automaticamente com o saldo da conta.</span>
                </div>
                <div class="fin-form-row-2">
                    <div class="fin-form-group">
                        <label class="fin-label"><i data-lucide="target"></i> Valor da Meta</label>
                        <input type="text" id="metaValorAlvo" class="fin-input" placeholder="R$ 0,00" required>
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-label"><i data-lucide="coins"></i> Valor Atual</label>
                        <input type="text" id="metaValorAtual" class="fin-input" placeholder="R$ 0,00" value="0">
                    </div>
                </div>
                <div class="fin-form-row-2">
                    <div class="fin-form-group">
                        <label class="fin-label"><i data-lucide="tag"></i> Tipo</label>
                        <select id="metaTipo" class="fin-select">
                            <option value="economia">Economia</option>
                            <option value="compra">Compra</option>
                            <option value="quitacao">Quitar Divida</option>
                            <option value="emergencia">Emergencia</option>
                            <option value="viagem">Viagem</option>
                            <option value="educacao">Educacao</option>
                            <option value="moradia">Moradia</option>
                            <option value="veiculo">Veiculo</option>
                            <option value="saude">Saude</option>
                            <option value="negocio">Negocio</option>
                            <option value="aposentadoria">Aposentadoria</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-label"><i data-lucide="flag"></i> Prioridade</label>
                        <select id="metaPrioridade" class="fin-select">
                            <option value="baixa">Baixa</option>
                            <option value="media" selected>Media</option>
                            <option value="alta">Alta</option>
                        </select>
                    </div>
                </div>
                <div class="fin-form-group">
                    <label class="fin-label"><i data-lucide="calendar"></i> Prazo (opcional)</label>
                    <input type="date" id="metaPrazo" class="fin-input">
                    <span class="fin-hint" id="metaAporteSugerido"></span>
                </div>
                <div class="fin-form-group">
                    <label class="fin-label"><i data-lucide="palette"></i> Cor</label>
                    <div class="color-picker-grid" id="metaCorPicker">
                        <button type="button" class="color-dot active" data-color="#6366f1"
                            style="background:#6366f1"></button>
                        <button type="button" class="color-dot" data-color="#3b82f6"
                            style="background:#3b82f6"></button>
                        <button type="button" class="color-dot" data-color="#10b981"
                            style="background:#10b981"></button>
                        <button type="button" class="color-dot" data-color="#f59e0b"
                            style="background:#f59e0b"></button>
                        <button type="button" class="color-dot" data-color="#ef4444"
                            style="background:#ef4444"></button>
                        <button type="button" class="color-dot" data-color="#8b5cf6"
                            style="background:#8b5cf6"></button>
                        <button type="button" class="color-dot" data-color="#ec4899"
                            style="background:#ec4899"></button>
                        <button type="button" class="color-dot" data-color="#14b8a6"
                            style="background:#14b8a6"></button>
                    </div>
                </div>
                <input type="hidden" id="metaCor" value="#6366f1">
                <input type="hidden" id="metaId" value="">
            </div>
            <div class="fin-modal-footer">
                <button type="button" class="fin-btn secondary" data-close-modal="modalMeta">Cancelar</button>
                <button type="submit" class="fin-btn primary">
                    <i data-lucide="check"></i> Salvar Meta
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== MODAL: TEMPLATES DE METAS ==================== -->
<div class="fin-modal-overlay" id="modalTemplates">
    <div class="fin-modal large">
        <div class="fin-modal-header">
            <h3><i data-lucide="wand-sparkles" style="color: var(--color-primary)"></i> Templates de Metas</h3>
            <button class="fin-modal-close" data-close-modal="modalTemplates">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="fin-modal-body">
            <p class="fin-modal-desc">Escolha um template para criar sua meta rapidamente.</p>
            <div class="templates-grid" id="templatesGrid">
                <div class="lk-loading-state">
                    <i data-lucide="loader-2"></i>
                    <p>Carregando templates...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAL: APORTE ==================== -->
<div class="fin-modal-overlay" id="modalAporte">
    <div class="fin-modal small">
        <div class="fin-modal-header">
            <h3><i data-lucide="plus-circle"></i> Registrar Aporte</h3>
            <button class="fin-modal-close" data-close-modal="modalAporte">
                <i data-lucide="x"></i>
            </button>
        </div>
        <form id="formAporte">
            <?= csrf_input('default') ?>
            <div class="fin-modal-body">
                <p class="aporte-meta-info" id="aporteMetaInfo"></p>
                <div class="fin-form-group">
                    <label class="fin-label"><i data-lucide="dollar-sign"></i> Valor do Aporte</label>
                    <input type="text" id="aporteValor" class="fin-input" placeholder="R$ 0,00" required>
                </div>
                <input type="hidden" id="aporteMetaId" value="">
            </div>
            <div class="fin-modal-footer">
                <button type="button" class="fin-btn secondary" data-close-modal="modalAporte">Cancelar</button>
                <button type="submit" class="fin-btn primary">
                    <i data-lucide="plus"></i> Adicionar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Page JS carregado automaticamente via loadPageJs() + Vite -->
