<!-- ============================================================
         PREVISÃO + GAMIFICAÇÃO — lado a lado
         ============================================================ -->
<div class="dash-duo-row dash-duo-row--flexible dash-duo-row--decision">

    <!-- PREVISÃO FINANCEIRA — toggled via personalização -->
    <section class="provisao-section surface-card surface-card--interactive surface-card--clip" id="sectionPrevisao"
        style="display:none;">
        <h2 class="provisao-title" id="provisaoTitle">Fechamento previsto</h2>
        <p class="provisao-headline" id="provisaoHeadline"></p>

        <!-- Alertas de vencidos -->
        <div class="provisao-alerts-container">
            <div class="provisao-alert despesas" id="provisaoAlertDespesas" style="display:none;">
                <div class="provisao-alert-icon"><i data-lucide="alert-triangle"></i></div>
                <span class="provisao-alert-text">
                    <strong id="provisaoAlertDespesasCount">0</strong> despesa(s) vencida(s) totalizando
                    <strong id="provisaoAlertDespesasTotal">R$ 0,00</strong>
                </span>
                <a href="<?= BASE_URL ?>lancamentos?status=vencido" class="provisao-alert-link">
                    Ver <i data-lucide="arrow-right"></i>
                </a>
            </div>
            <div class="provisao-alert receitas" id="provisaoAlertReceitas" style="display:none;">
                <div class="provisao-alert-icon"><i data-lucide="alert-circle"></i></div>
                <span class="provisao-alert-text">
                    <strong id="provisaoAlertReceitasCount">0</strong> receita(s) não recebida(s) totalizando
                    <strong id="provisaoAlertReceitasTotal">R$ 0,00</strong>
                </span>
                <a href="<?= BASE_URL ?>lancamentos?tipo=receita&status=vencido" class="provisao-alert-link">
                    Ver <i data-lucide="arrow-right"></i>
                </a>
            </div>
            <div class="provisao-alert faturas" id="provisaoAlertFaturas" style="display:none;">
                <div class="provisao-alert-icon"><i data-lucide="credit-card"></i></div>
                <span class="provisao-alert-text">
                    <strong id="provisaoAlertFaturasCount">0</strong> fatura(s) vencida(s) totalizando
                    <strong id="provisaoAlertFaturasTotal">R$ 0,00</strong>
                </span>
                <a href="<?= BASE_URL ?>faturas" class="provisao-alert-link">
                    Ver <i data-lucide="arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- Cards de resumo -->
        <div class="provisao-grid">
            <div class="provisao-card pagar">
                <div class="provisao-card-icon"><i data-lucide="arrow-up-right"></i></div>
                <div class="provisao-card-body">
                    <span class="provisao-card-label">Restante a pagar</span>
                    <span class="provisao-card-value" id="provisaoPagar">R$ 0,00</span>
                    <span class="provisao-card-count" id="provisaoPagarCount">0 pendentes</span>
                </div>
            </div>
            <div class="provisao-card receber">
                <div class="provisao-card-icon"><i data-lucide="arrow-down-left"></i></div>
                <div class="provisao-card-body">
                    <span class="provisao-card-label">Restante a receber</span>
                    <span class="provisao-card-value" id="provisaoReceber">R$ 0,00</span>
                    <span class="provisao-card-count" id="provisaoReceberCount">0 pendentes</span>
                </div>
            </div>
            <div class="provisao-card projetado">
                <div class="provisao-card-icon"><i data-lucide="trending-up"></i></div>
                <div class="provisao-card-body">
                    <span class="provisao-card-label">Saldo no fim do mês</span>
                    <span class="provisao-card-value" id="provisaoProjetado">R$ 0,00</span>
                    <span class="provisao-card-count" id="provisaoProjetadoLabel">entra no próximo mês com R$ 0,00</span>
                </div>
            </div>
            <div class="provisao-card previsto" id="provisaoPrevistoCard">
                <div class="provisao-card-icon"><i data-lucide="wallet"></i></div>
                <div class="provisao-card-body">
                    <span class="provisao-card-label">Previsto do mês</span>
                    <span class="provisao-card-value" id="provisaoPrevistoMes">R$ 0,00</span>
                    <span class="provisao-card-count" id="provisaoPrevistoMesLabel">impacto líquido do que ainda entra e sai</span>
                </div>
            </div>
        </div>

        <!-- Próximos vencimentos -->
        <div class="provisao-proximos">
            <div class="provisao-proximos-header">
                <span class="provisao-proximos-title" id="provisaoProximosTitle">
                    <i data-lucide="clock"></i> Próximos compromissos
                </span>
                <a href="<?= BASE_URL ?>lancamentos" class="provisao-ver-todos" id="provisaoVerTodos">
                    Ver todos <i data-lucide="arrow-right"></i>
                </a>
            </div>
            <div class="provisao-proximos-list" id="provisaoProximosList">
                <div class="provisao-empty" id="provisaoEmpty" style="display:none;">
                    <i data-lucide="check-circle"></i>
                    <span>Nenhum vencimento pendente</span>
                </div>
            </div>
        </div>

        <!-- Parcelas ativas -->
        <div class="provisao-parcelas" id="provisaoParcelas" style="display:none;">
            <div class="provisao-parcelas-icon"><i data-lucide="layers"></i></div>
            <span class="provisao-parcelas-text" id="provisaoParcelasText">0 parcelamentos ativos</span>
            <span class="provisao-parcelas-valor" id="provisaoParcelasValor">R$ 0,00/mês</span>
        </div>

        <!-- PRO overlay -->
        <div class="provisao-pro-overlay" id="provisaoProOverlay" style="display:none;">
            <div class="provisao-pro-content">
                <div class="provisao-pro-icon"><i data-lucide="crown"></i></div>
                <h3>Previsão completa</h3>
                <p>Veja entradas, saídas e o fechamento previsto do mês com o plano Pro.</p>
                <a href="<?= BASE_URL ?>billing" class="provisao-pro-btn">
                    <i data-lucide="gem"></i> Assinar Pro
                </a>
            </div>
        </div>
    </section>

    <!-- ============================================================
         GAMIFICAÇÃO — toggled via personalização
         ============================================================ -->
    <section class="gamification-section surface-card surface-card--interactive surface-card--clip"
        id="sectionGamificacao" style="display:none;">
        <div class="gamification-header">
            <h2 class="gamification-title"><i data-lucide="trophy"></i> Progresso</h2>
            <div class="level-badge" id="userLevel"><i data-lucide="star"></i> <span>Nível 1</span></div>
        </div>

        <div class="gamification-grid">
            <div class="streak-card">
                <div class="streak-icon"><i data-lucide="flame" aria-hidden="true"></i></div>
                <div class="streak-number" id="streakDays">0</div>
                <div class="streak-label">Dias seguidos</div>
                <div class="streak-protection" id="streakProtection" style="display:none;">
                    <i data-lucide="shield"></i> Proteção ativa
                </div>
            </div>

            <div class="level-progress-card progress-card">
                <div class="progress-header">
                    <span class="progress-title"><i data-lucide="bar-chart-3"></i> Progresso</span>
                    <span class="progress-percentage" id="levelProgressText"></span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="levelProgressBar" style="width:0%;"></div>
                </div>
                <div class="progress-text" id="levelProgressPoints">0 / 300 pontos</div>
            </div>

            <div class="badges-card">
                <h3 class="badges-title"><i data-lucide="medal"></i> Conquistas</h3>
                <div class="badges-grid" id="badgesGrid"></div>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-mini">
                <div class="stat-mini-value" id="totalLancamentos">0</div>
                <div class="stat-mini-label">Lançamentos</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value" id="totalCategorias">0</div>
                <div class="stat-mini-label">Categorias</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value" id="mesesAtivos">0</div>
                <div class="stat-mini-label">Meses ativos</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value" id="pontosTotal">0</div>
                <div class="stat-mini-label">Pontos</div>
            </div>
        </div>

        <div id="proCTA" style="display:none;">
            <button class="btn-pro-upgrade">
                <i data-lucide="gem"></i> Desbloqueie mais com o Pro
            </button>
        </div>
        <span id="proBadge" style="display:none;" class="pro-badge-inline">PRO</span>
    </section>

</div><!-- /dash-duo-row--flexible -->