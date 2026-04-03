<!-- ====== STEP 4: Data + Hora + Pago ====== -->
<div class="lk-wizard-step" data-step="4" id="globalStep4">
    <div class="lk-wizard-question" id="globalStep4Question">
        <h3>
            <i data-lucide="calendar-clock"></i>
            <span id="globalStep4Title">Quando aconteceu?</span>
        </h3>
        <p id="globalStep4Subtitle">Informe a data e horário</p>
    </div>

    <!-- Data e Hora -->
    <div class="lk-form-group">
        <label class="lk-label required">
            <i data-lucide="calendar-clock"></i>
            Quando?
        </label>
        <div class="lk-datetime-inline">
            <div class="lk-datetime-date">
                <i data-lucide="calendar" class="lk-datetime-icon"></i>
                <input type="date" id="globalLancamentoData" name="data" class="lk-input lk-input-date" required>
            </div>
            <div class="lk-datetime-sep">
                <span>às</span>
            </div>
            <div class="lk-datetime-time">
                <i data-lucide="clock" class="lk-datetime-icon"></i>
                <input type="time" id="globalLancamentoHora" name="hora_lancamento" class="lk-input lk-input-time"
                    placeholder="--:--">
            </div>
        </div>
        <small class="lk-helper-text">Horário é opcional — útil para organizar e lembretes.</small>
    </div>

    <!-- Status de Pagamento -->
    <div class="lk-form-group" id="globalPagoGroup" style="display: none;">
        <div class="lk-checkbox-wrapper">
            <label class="lk-checkbox-label">
                <input type="checkbox" id="globalLancamentoPago" name="pago" value="1" class="lk-checkbox" checked>
                <span class="lk-checkbox-custom"></span>
                <span class="lk-checkbox-text">
                    <i data-lucide="circle-check"></i>
                    <span id="globalPagoLabel">Já foi pago</span>
                </span>
            </label>
        </div>
        <small class="lk-helper-text" id="globalPagoHelperText">Pendentes não alteram o saldo até serem
            confirmados.</small>
    </div>

    <!-- Nav -->
    <div class="lk-wizard-nav">
        <div class="lk-wizard-nav-left">
            <button type="button" class="lk-btn-voltar" onclick="lancamentoGlobalManager.prevStep()">
                <i data-lucide="arrow-left"></i>
                Voltar
            </button>
        </div>
        <div class="lk-wizard-nav-right" id="globalStep4NavRight">
            <button type="button" class="lk-btn lk-btn-primary" onclick="lancamentoGlobalManager.nextStep()">
                Próximo
                <i data-lucide="arrow-right"></i>
            </button>
        </div>
    </div>
</div>