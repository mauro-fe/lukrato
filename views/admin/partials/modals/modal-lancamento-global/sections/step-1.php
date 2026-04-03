<!-- ====== STEP 1: Tipo de Movimentação ====== -->
<div class="lk-wizard-step active" data-step="1" id="globalStep1">
    <div class="lk-historico-section">
        <h3 class="lk-section-title">
            <i data-lucide="history"></i>
            Ultimas movimentações
        </h3>
        <div class="lk-historico-list surface-card" id="globalLancamentoHistorico">
            <div class="lk-historico-empty">
                <i data-lucide="history"></i>
                <p>Selecione uma conta para ver as ultimas movimentações.</p>
            </div>
        </div>
    </div>
    <div class="lk-wizard-question">
        <h3>
            <i data-lucide="list-checks"></i>
            O que você quer registrar?
        </h3>
        <p>Escolha o tipo de movimentação</p>
        <small class="lk-helper-text" id="globalTipoContaHint" hidden>
            Selecione uma conta para liberar as opcoes de lancamento.
        </small>
    </div>

    <div class="lk-tipo-grid lk-tipo-grid-3">
        <button type="button" class="lk-tipo-card surface-card lk-tipo-receita" data-requires-account="1"
            onclick="lancamentoGlobalManager.mostrarFormulario('receita')">
            <div class="lk-tipo-icon">
                <i data-lucide="arrow-down"></i>
            </div>
            <h4>Receita</h4>
            <p>Dinheiro que entra</p>
            <div class="lk-tipo-badge">+ Entrada</div>
        </button>

        <button type="button" class="lk-tipo-card surface-card lk-tipo-despesa" data-requires-account="1"
            onclick="lancamentoGlobalManager.mostrarFormulario('despesa')">
            <div class="lk-tipo-icon">
                <i data-lucide="arrow-up"></i>
            </div>
            <h4>Despesa</h4>
            <p>Dinheiro que sai</p>
            <div class="lk-tipo-badge">- Saída</div>
        </button>

        <button type="button" class="lk-tipo-card surface-card lk-tipo-transferencia" data-requires-account="1"
            onclick="lancamentoGlobalManager.mostrarFormulario('transferencia')">
            <div class="lk-tipo-icon">
                <i data-lucide="arrow-left-right"></i>
            </div>
            <h4>Transferência</h4>
            <p>Entre contas</p>
            <div class="lk-tipo-badge">⇄ Mover</div>
        </button>
    </div>
</div>