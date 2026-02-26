<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/financas-modern.css?v=<?= time() ?>">

<section class="fin-page">

    <!-- ==================== SELETOR DE MÊS (compartilhado) ==================== -->
    <?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>

    <!-- ==================== CARDS RESUMO: ORÇAMENTOS ==================== -->
    <div class="fin-summary-grid" id="summaryOrcamentos" data-aos="fade-up">
        <!-- Saúde Financeira -->
        <div class="summary-card saude-card">
            <div class="saude-content" id="saudeContent">
                <div class="summary-icon">
                    <div class="saude-ring" id="saudeRing">
                        <svg viewBox="0 0 36 36">
                            <path class="ring-bg"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="ring-fill" id="saudeRingFill" stroke-dasharray="100, 100"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <span class="ring-text" id="saudeScore">--</span>
                    </div>
                </div>
                <div class="summary-info">
                    <span class="summary-label">Saúde Financeira</span>
                    <span class="summary-status" id="saudeLabel">Carregando...</span>
                </div>
            </div>
            <div class="saude-cta" id="saudeCta" style="display:none">
                <div class="saude-cta-icon">
                    <i data-lucide="heart-pulse"></i>
                </div>
                <div class="saude-cta-text">
                    <span class="summary-label">Saúde Financeira</span>
                    <span class="saude-cta-msg">Defina orçamentos nas categorias para acompanhar sua saúde
                        financeira</span>
                </div>
            </div>
        </div>

        <!-- Total Orçado -->
        <div class="summary-card">
            <div class="summary-icon blue">
                <i data-lucide="wallet"></i>
            </div>
            <div class="summary-info">
                <span class="summary-label">Orçado</span>
                <span class="summary-value" id="totalOrcado">R$ --</span>
            </div>
        </div>

        <!-- Total Gasto -->
        <div class="summary-card">
            <div class="summary-icon orange">
                <i data-lucide="receipt"></i>
            </div>
            <div class="summary-info">
                <span class="summary-label">Gasto</span>
                <span class="summary-value" id="totalGasto">R$ --</span>
            </div>
        </div>

        <!-- Disponível -->
        <div class="summary-card">
            <div class="summary-icon green">
                <i data-lucide="piggy-bank" style="color: white"></i>
            </div>
            <div class="summary-info">
                <span class="summary-label">Disponível</span>
                <span class="summary-value" id="totalDisponivel">R$ --</span>
            </div>
        </div>
    </div>

    <!-- ==================== CARDS RESUMO: METAS ==================== -->
    <div class="fin-summary-grid" id="summaryMetas" data-aos="fade-up" style="display:none;">
        <!-- Metas Ativas -->
        <div class="summary-card">
            <div class="summary-icon purple">
                <i data-lucide="target"></i>
            </div>
            <div class="summary-info">
                <span class="summary-label">Metas Ativas</span>
                <span class="summary-value" id="metasAtivas">--</span>
            </div>
        </div>

        <!-- Total Acumulado -->
        <div class="summary-card">
            <div class="summary-icon green">
                <i data-lucide="coins"></i>
            </div>
            <div class="summary-info">
                <span class="summary-label">Acumulado</span>
                <span class="summary-value" id="metasTotalAtual">R$ --</span>
            </div>
        </div>

        <!-- Objetivo Total -->
        <div class="summary-card">
            <div class="summary-icon blue">
                <i data-lucide="flag" style="color: var(--color-primary)"></i>
            </div>
            <div class="summary-info">
                <span class="summary-label">Objetivo Total</span>
                <span class="summary-value" id="metasTotalAlvo">R$ --</span>
            </div>
        </div>

        <!-- Progresso Geral -->
        <div class="summary-card">
            <div class="summary-icon">
                <div class="saude-ring" id="metasProgressRing">
                    <svg viewBox="0 0 36 36">
                        <path class="ring-bg"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="ring-fill score-good" id="metasProgressRingFill" stroke-dasharray="0, 100"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="ring-text" id="metasProgressScore">0%</span>
                </div>
            </div>
            <div class="summary-info">
                <span class="summary-label">Progresso Geral</span>
                <span class="summary-status status-good" id="metasProgressLabel">--</span>
            </div>
        </div>
    </div>

    <!-- ==================== TABS ==================== -->
    <div class="fin-tabs" data-aos="fade-up" data-aos-delay="100" role="tablist" aria-label="Seções de finanças">
        <button class="fin-tab active" data-tab="orcamentos" role="tab" aria-selected="true"
            aria-controls="tab-orcamentos" id="fin-tab-orcamentos">
            <i data-lucide="pie-chart"></i>
            <span>Orçamentos</span>
        </button>
        <button class="fin-tab" data-tab="metas" role="tab" aria-selected="false" aria-controls="tab-metas"
            id="fin-tab-metas">
            <i data-lucide="target"></i>
            <span>Metas</span>
        </button>
    </div>

    <!-- ==================== TAB: ORÇAMENTOS ==================== -->
    <div class="fin-tab-content active" id="tab-orcamentos" role="tabpanel" aria-labelledby="fin-tab-orcamentos">

        <!-- Ações rápidas -->
        <div class="fin-actions-bar" data-aos="fade-up" data-aos-delay="150">
            <div class="actions-left">
                <button class="fin-action-btn primary" id="btnAutoSugerir"
                    title="A IA analisa seus últimos 3 meses e sugere orçamentos automaticamente">
                    <i data-lucide="wand-2"></i>
                    <span>Sugestão Inteligente</span>
                </button>
                <button class="fin-action-btn" id="btnCopiarMes" title="Copiar orçamentos do mês anterior">
                    <i data-lucide="copy"></i>
                    <span>Copiar Mês Anterior</span>
                </button>
            </div>
            <button class="fin-action-btn success" id="btnNovoOrcamento">
                <i data-lucide="plus"></i>
                <span>Novo Orçamento</span>
            </button>
        </div>

        <!-- Grid de orçamentos -->
        <div class="orcamentos-grid" id="orcamentosGrid">
            <div class="loading-state">
                <i data-lucide="loader-2" class="animate-spin"></i>
                <p>Carregando orçamentos...</p>
            </div>
        </div>

        <!-- Estado vazio -->
        <div class="fin-empty-state" id="orcamentosEmpty" style="display: none;">
            <div class="empty-icon">
                <i data-lucide="pie-chart"></i>
            </div>
            <h3>Nenhum orçamento configurado</h3>
            <p>Configure orçamentos por categoria para controlar seus gastos.<br>
                Clique em <strong>"Sugestão Inteligente"</strong> para configurar automaticamente!</p>
            <button class="fin-action-btn primary" id="btnAutoSugerirEmpty">
                <i data-lucide="wand-2"></i>
                <span>Configurar Automaticamente</span>
            </button>
        </div>

        <!-- Insights (dentro da tab orçamentos) -->
        <div class="fin-insights-section" id="insightsSection" style="display:none;">
            <div class="fin-section-label">
                <i data-lucide="lightbulb"></i>
                <span>Insights</span>
            </div>
            <div class="insights-grid" id="insightsGrid"></div>
        </div>
    </div>

    <!-- ==================== TAB: METAS ==================== -->
    <div class="fin-tab-content" id="tab-metas" role="tabpanel" aria-labelledby="fin-tab-metas">

        <!-- Ações -->
        <div class="fin-actions-bar" data-aos="fade-up">
            <div class="actions-left">
                <button class="fin-action-btn" id="btnTemplates">
                    <i data-lucide="wand-sparkles"></i>
                    <span>Usar Template</span>
                </button>
            </div>
            <button class="fin-action-btn success" id="btnNovaMeta">
                <i data-lucide="plus"></i>
                <span>Nova Meta</span>
            </button>
        </div>

        <!-- Grid de metas -->
        <div class="metas-grid" id="metasGrid">
            <div class="loading-state">
                <i data-lucide="loader-2" class="animate-spin"></i>
                <p>Carregando metas...</p>
            </div>
        </div>

        <!-- Estado vazio -->
        <div class="fin-empty-state" id="metasEmpty" style="display: none;">
            <div class="empty-icon">
                <i data-lucide="target" style="color: var(--color-primary)"></i>
            </div>
            <h3>Nenhuma meta financeira</h3>
            <p>Crie metas para acompanhar seus objetivos financeiros.<br>
                Use um <strong>template pronto</strong> para começar rapidamente!</p>
            <button class="fin-action-btn primary" id="btnTemplatesEmpty">
                <i data-lucide="wand-sparkles"></i>
                <span>Escolher Template</span>
            </button>
        </div>
    </div>

</section>

<!-- ==================== MODAL: NOVO/EDITAR ORÇAMENTO ==================== -->
<div class="fin-modal-overlay" id="modalOrcamento">
    <div class="fin-modal">
        <div class="fin-modal-header">
            <h3 id="modalOrcamentoTitle">Novo Orçamento</h3>
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
                        <span class="toggle-label">Acumular sobra do mês anterior</span>
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

<!-- ==================== MODAL: SUGESTÕES INTELIGENTES ==================== -->
<div class="fin-modal-overlay" id="modalSugestoes">
    <div class="fin-modal large">
        <div class="fin-modal-header">
            <h3><i data-lucide="wand-2" style="color: var(--color-primary)"></i> Sugestão Inteligente</h3>
            <button class="fin-modal-close" data-close-modal="modalSugestoes">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="fin-modal-body">
            <p class="fin-modal-desc">
                Analisamos seus gastos dos últimos 3 meses e sugerimos limites <strong>abaixo da sua média</strong> para
                ajudar você a economizar em cada categoria.
                Você pode ajustar os valores antes de aplicar.
            </p>
            <div class="sugestoes-list" id="sugestoesList">
                <div class="loading-state">
                    <i data-lucide="loader-2" class="animate-spin"></i>
                    <p>Analisando seu histórico...</p>
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
                    <label class="fin-label"><i data-lucide="pencil"></i> Título</label>
                    <input type="text" id="metaTitulo" class="fin-input" placeholder="Ex: Reserva de emergência"
                        required maxlength="150">
                </div>
                <div class="fin-form-group">
                    <label class="fin-label"><i data-lucide="landmark"></i> Vincular a uma conta <span
                            class="fin-badge-optional">opcional</span></label>
                    <select id="metaContaId" class="fin-select">
                        <option value="">— Sem vínculo (aporte manual) —</option>
                    </select>
                    <span class="fin-hint" id="metaContaHint" style="display:none"><i data-lucide="info"></i> O
                        progresso será atualizado automaticamente com o saldo da conta.</span>
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
                            <option value="economia">💰 Economia</option>
                            <option value="compra">🛒 Compra</option>
                            <option value="quitacao">💳 Quitar Dívida</option>
                            <option value="emergencia">🛡️ Emergência</option>
                            <option value="investimento">📈 Investimento</option>
                            <option value="viagem">✈️ Viagem</option>
                            <option value="educacao">🎓 Educação</option>
                            <option value="moradia">🏠 Moradia</option>
                            <option value="veiculo">🚗 Veículo</option>
                            <option value="saude">🏥 Saúde</option>
                            <option value="negocio">🏪 Negócio</option>
                            <option value="aposentadoria">🏖️ Aposentadoria</option>
                            <option value="outro">🎯 Outro</option>
                        </select>
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-label"><i data-lucide="flag"></i> Prioridade</label>
                        <select id="metaPrioridade" class="fin-select">
                            <option value="baixa">🟢 Baixa</option>
                            <option value="media" selected>🟡 Média</option>
                            <option value="alta">🔴 Alta</option>
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
                <div class="loading-state">
                    <i data-lucide="loader-2" class="animate-spin"></i>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= BASE_URL ?>assets/js/admin-financas-index.js?v=<?= time() ?>"></script>