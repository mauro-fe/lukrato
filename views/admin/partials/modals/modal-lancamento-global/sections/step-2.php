<!-- ====== STEP 2: Descrição + Valor ====== -->
<div class="lk-wizard-step" data-step="2" id="globalStep2">
    <div class="lk-wizard-question" id="globalStep2Question">
        <h3>
            <i data-lucide="pencil-line"></i>
            <span id="globalStep2Title">Com o que você gastou?</span>
        </h3>
        <p id="globalStep2Subtitle">Descreva e informe o valor</p>
    </div>

    <!-- Descrição -->
    <div class="lk-form-group lk-page-step-panel lk-page-step-panel--description">
        <label for="globalLancamentoDescricao" class="lk-label required">
            <i data-lucide="align-left"></i>
            Descrição
        </label>
        <input type="text" id="globalLancamentoDescricao" name="descricao" class="lk-input"
            placeholder="Ex: Salário, Aluguel, Compras..." required maxlength="200">
    </div>

    <!-- Valor -->
    <div class="lk-form-group lk-page-step-panel lk-page-step-panel--value">
        <label for="globalLancamentoValor" class="lk-label required">
            <i data-lucide="dollar-sign"></i>
            Valor
        </label>
        <div class="lk-input-money">
            <span class="lk-currency-symbol">R$</span>
            <input type="text" id="globalLancamentoValor" name="valor" class="lk-input lk-input-with-prefix"
                value="0,00" placeholder="0,00" autocomplete="off" required>
        </div>
    </div>

    <div class="lk-form-group lk-page-step-panel lk-page-step-panel--meta" id="globalMetaGroup" style="display: none;">
        <label for="globalLancamentoMeta" class="lk-label">
            <i data-lucide="target"></i>
            Vincular a uma meta
        </label>
        <div class="lk-select-wrapper">
            <select id="globalLancamentoMeta" name="meta_id" class="lk-select" data-lk-custom-select="modal"
                data-lk-select-search="true" data-lk-select-sort="alpha"
                data-lk-select-search-placeholder="Buscar meta..." onchange="lancamentoGlobalManager.onMetaChange()">
                <option value="">Nenhuma meta</option>
            </select>
            <i data-lucide="chevron-down" class="lk-select-icon"></i>
        </div>
        <small class="lk-helper-text" id="globalMetaHelperText">
            Vincule para registrar aporte ou uso da meta com este lancamento.
        </small>

        <div class="lk-form-group" id="globalMetaValorGroup" style="display: none; margin-top: 0.75rem;">
            <label for="globalLancamentoMetaValor" class="lk-label">
                <i data-lucide="coins"></i>
                Quanto veio da meta?
            </label>
            <div class="lk-input-money">
                <span class="lk-currency-symbol">R$</span>
                <input type="text" id="globalLancamentoMetaValor" name="meta_valor"
                    class="lk-input lk-input-with-prefix" value="" placeholder="0,00" autocomplete="off"
                    inputmode="decimal">
            </div>
            <small class="lk-helper-text">
                O restante entra como gasto normal do mês.
            </small>
        </div>

        <div class="lk-form-group" id="globalMetaRealizacaoGroup" style="display: none; margin-top: 0.5rem;">
            <div class="lk-checkbox-wrapper">
                <label class="lk-checkbox-label">
                    <input type="checkbox" id="globalLancamentoMetaRealizacao" class="lk-checkbox">
                    <span class="lk-checkbox-custom"></span>
                    <span class="lk-checkbox-text">
                        <i data-lucide="flag"></i>
                        Este gasto realiza o objetivo da meta
                    </span>
                </label>
            </div>
            <small class="lk-helper-text">
                Marcado: a meta vai para realizada sem reduzir o valor reservado.
            </small>
        </div>
    </div>

    <!-- Nav -->
    <div class="lk-wizard-nav">
        <div class="lk-wizard-nav-left">
            <button type="button" class="lk-btn-voltar" onclick="lancamentoGlobalManager.prevStep()">
                <i data-lucide="arrow-left"></i>
                Voltar
            </button>
        </div>
        <div class="lk-wizard-nav-right">
            <button type="button" class="lk-btn-skip" id="globalBtnQuickSave"
                onclick="lancamentoGlobalManager.saveQuick()" style="display:none;">
                <i data-lucide="zap"></i>
                Salvar rápido
            </button>
            <button type="button" class="lk-btn lk-btn-primary" onclick="lancamentoGlobalManager.nextStep()">
                Próximo
                <i data-lucide="arrow-right"></i>
            </button>
        </div>
    </div>
</div>