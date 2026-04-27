<div class="lk-account-context-grid">
    <div class="lk-form-group lk-account-context-field">
        <label for="globalContaSelect" class="lk-label required">
            <i data-lucide="wallet"></i>
            <span id="globalContaSelectLabelText">Conta</span>
        </label>
        <div class="lk-select-wrapper">
            <select id="globalContaSelect" class="lk-select surface-card" required data-lk-custom-select="modal"
                data-lk-select-search="true" data-lk-select-sort="alpha" data-lk-select-search-placeholder="Buscar conta..."
                onchange="lancamentoGlobalManager.onContaChange()">
                <option value="">Escolha uma conta...</option>
            </select>
            <i data-lucide="chevron-down" class="lk-select-icon"></i>
        </div>
    </div>

    <div class="lk-conta-info surface-card" id="globalContaInfo" style="display: none;">
        <div class="lk-conta-badge">
            <i data-lucide="wallet"></i>
            <span id="globalContaNome">Conta</span>
        </div>
        <div class="lk-conta-saldo">
            Saldo atual: <strong id="globalContaSaldo">R$ 0,00</strong>
        </div>
    </div>
</div>

<div class="lk-planning-alerts" id="globalContaPlanningAlerts" hidden></div>

<div class="lk-wizard-progress" id="globalWizardProgress" style="display: none;">
</div>