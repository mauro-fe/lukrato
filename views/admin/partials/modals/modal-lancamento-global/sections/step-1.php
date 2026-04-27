<div class="lk-wizard-step active" data-step="1" id="globalStep1">
    <div class="lk-step-choice-main">
        <div class="lk-wizard-question">
            <h3>O que você deseja registrar?</h3>
        </div>

        <div class="lk-tipo-grid lk-tipo-grid-3">
            <button type="button" class="lk-tipo-card surface-card lk-tipo-receita" data-tipo="receita" data-requires-account="0"
                onclick="lancamentoGlobalManager.mostrarFormulario('receita')">
                <div class="lk-tipo-content">
                    <div class="lk-tipo-icon">
                        <i data-lucide="arrow-down"></i>
                    </div>
                    <span class="lk-tipo-copy">
                        <h4>Receita</h4>
                        <p>Dinheiro que entra</p>
                    </span>
                </div>
                <div class="lk-tipo-badge">+ Entrada</div>
            </button>

            <button type="button" class="lk-tipo-card surface-card lk-tipo-despesa" data-tipo="despesa" data-requires-account="0"
                onclick="lancamentoGlobalManager.mostrarFormulario('despesa')">
                <div class="lk-tipo-content">
                    <div class="lk-tipo-icon">
                        <i data-lucide="arrow-up"></i>
                    </div>
                    <span class="lk-tipo-copy">
                        <h4>Despesa</h4>
                        <p>Dinheiro que sai</p>
                    </span>
                </div>
                <div class="lk-tipo-badge">- Saída</div>
            </button>

            <button type="button" class="lk-tipo-card surface-card lk-tipo-transferencia" data-tipo="transferencia" data-requires-account="0"
                onclick="lancamentoGlobalManager.mostrarFormulario('transferencia')">
                <div class="lk-tipo-content">
                    <div class="lk-tipo-icon">
                        <i data-lucide="arrow-left-right"></i>
                    </div>
                    <span class="lk-tipo-copy">
                        <h4>Transferência</h4>
                        <p>Entre contas</p>
                    </span>
                </div>
                <div class="lk-tipo-badge">Mover</div>
            </button>
        </div>
    </div>
</div>