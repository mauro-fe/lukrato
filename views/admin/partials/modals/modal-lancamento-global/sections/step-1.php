<div class="lk-wizard-step active" data-step="1" id="globalStep1">
    <div class="lk-wizard-question">
        <h3>
            <i data-lucide="list-checks"></i>
            O que você deseja registrar?
        </h3>
    </div>

    <div class="lk-tipo-grid lk-tipo-grid-3">
        <button type="button" class="lk-tipo-card surface-card lk-tipo-receita" data-requires-account="0"
            onclick="lancamentoGlobalManager.mostrarFormulario('receita')">
            <div class="lk-tipo-icon">
                <i data-lucide="arrow-down"></i>
            </div>
            <span class="lk-tipo-copy">
                <h4>Receita</h4>
                <p>Dinheiro que entra</p>
            </span>
            <div class="lk-tipo-badge">+ Entrada</div>
        </button>

        <button type="button" class="lk-tipo-card surface-card lk-tipo-despesa" data-requires-account="0"
            onclick="lancamentoGlobalManager.mostrarFormulario('despesa')">
            <div class="lk-tipo-icon">
                <i data-lucide="arrow-up"></i>
            </div>
            <span class="lk-tipo-copy">
                <h4>Despesa</h4>
                <p>Dinheiro que sai</p>
            </span>
            <div class="lk-tipo-badge">- Saída</div>
        </button>

        <button type="button" class="lk-tipo-card surface-card lk-tipo-transferencia" data-requires-account="0"
            onclick="lancamentoGlobalManager.mostrarFormulario('transferencia')">
            <div class="lk-tipo-icon">
                <i data-lucide="arrow-left-right"></i>
            </div>
            <span class="lk-tipo-copy">
                <h4>Transferência</h4>
                <p>Entre contas</p>
            </span>
            <div class="lk-tipo-badge">Mover</div>
        </button>
    </div>
</div>