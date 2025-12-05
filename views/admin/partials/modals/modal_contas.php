<style>
    /* Modal Container */
    .lk-modal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(12px) saturate(180%);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        padding: var(--spacing-4);
        animation: fadeIn 0.3s ease;
    }

    .lk-modal.active {
        display: flex;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    /* Modal Card */
    .lk-modal-card {
        background: var(--color-surface);
        border-radius: var(--radius-xl);
        border: 1px solid var(--glass-border);
        box-shadow: var(--shadow-xl), 0 0 0 1px rgba(230, 126, 34, 0.1);
        max-width: 600px;
        width: 100%;
        max-height: 90vh;
        overflow: hidden;
        position: relative;
        animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-30px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Barra decorativa superior */
    .lk-modal-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg,
                transparent,
                var(--color-primary) 30%,
                var(--color-primary) 70%,
                transparent);
        opacity: 0.8;
        animation: shimmer 3s ease-in-out infinite;
    }

    @keyframes shimmer {

        0%,
        100% {
            opacity: 0.6;
        }

        50% {
            opacity: 1;
        }
    }

    /* Header */
    .lk-modal-h {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--spacing-6) var(--spacing-6) var(--spacing-4);
        border-bottom: 1px solid var(--glass-border);
    }

    .lk-modal-t {
        font-size: var(--font-size-xl);
        font-weight: 700;
        color: var(--color-primary);
        letter-spacing: -0.02em;
    }

    /* Body */
    .lk-modal-b {
        padding: var(--spacing-6);
        max-height: calc(90vh - 140px);
        overflow-y: auto;
    }

    .lk-modal-b::-webkit-scrollbar {
        width: 8px;
    }

    .lk-modal-b::-webkit-scrollbar-track {
        background: var(--color-surface-muted);
        border-radius: var(--radius-sm);
    }

    .lk-modal-b::-webkit-scrollbar-thumb {
        background: var(--color-primary);
        border-radius: var(--radius-sm);
    }

    /* Form Grid */
    .lk-form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: var(--spacing-4);
    }

    .lk-field {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-2);
    }

    .lk-field.full {
        grid-column: 1 / -1;
    }

    .lk-field label {
        color: var(--color-text);
        font-size: var(--font-size-sm);
        font-weight: 600;
        letter-spacing: 0.01em;
    }

    .lk-field input,
    .lk-field select {
        background: var(--color-surface-muted);
        color: var(--color-text);
        border: 1px solid var(--color-primary) !important;
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
        padding: var(--spacing-3) var(--spacing-4);
        transition: var(--transition-normal);
        font-family: var(--font-primary);
        width: 100%;
    }

    .lk-field input::placeholder {
        color: var(--color-text-muted);
        opacity: 0.6;
    }

    .lk-field input:focus,
    .lk-field select:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 4px var(--ring);
        background: var(--color-surface);
        transform: translateY(-1px);
    }

    .lk-field input:hover:not(:focus),
    .lk-field select:hover:not(:focus) {
        border-color: rgba(230, 126, 34, 0.4);
    }

    .lk-field input[readonly] {
        background: var(--glass-bg);
        cursor: not-allowed;
        opacity: 0.7;
    }

    /* Select customizado */
    .lk-field select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23e67e22' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right var(--spacing-3) center;
        background-size: 16px;
        padding-right: var(--spacing-6);
    }

    /* Input de Data customizado */
    .lk-field input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(0.6) sepia(1) saturate(5) hue-rotate(360deg);
        cursor: pointer;
        opacity: 0.8;
        transition: var(--transition-fast);
    }

    .lk-field input[type="date"]::-webkit-calendar-picker-indicator:hover {
        opacity: 1;
        transform: scale(1.1);
    }

    /* Footer */
    .lk-modal-f {
        display: flex;
        gap: var(--spacing-3);
        justify-content: flex-end;
        padding-top: var(--spacing-5);
        margin-top: var(--spacing-5);
        border-top: 1px solid var(--glass-border);
    }

    /* Botões */
    .btn {
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
        font-weight: 600;
        padding: var(--spacing-3) var(--spacing-6);
        transition: var(--transition-normal);
        border: none;
        font-family: var(--font-primary);
        letter-spacing: 0.02em;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-2);
    }

    .btn-ghost:hover {
        background: var(--color-primary);
        color: var(--branco);
    }

    .btn-light {
        background: var(--glass-bg);
        border: 2px solid var(--glass-border);
        color: var(--color-text);
    }

    .btn-light:hover {
        background: var(--color-surface-muted);
        border-color: var(--color-text-muted);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--color-primary), #d35400);
        color: var(--branco);
        box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
        position: relative;
        overflow: hidden;
    }

    .btn-primary::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s ease;
    }

    .btn-primary:hover::before {
        left: 100%;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(230, 126, 34, 0.4);
    }

    .btn-primary:active {
        transform: translateY(0);
    }

    /* Demo buttons */
    .demo-container {
        display: flex;
        gap: var(--spacing-4);
        flex-wrap: wrap;
    }

    .demo-btn {
        background: linear-gradient(135deg, var(--color-primary), #d35400);
        color: white;
        border: none;
        padding: var(--spacing-4) var(--spacing-6);
        border-radius: var(--radius-md);
        font-size: var(--font-size-base);
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition-normal);
        box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
        font-family: var(--font-primary);
    }

    .demo-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(230, 126, 34, 0.4);
    }



    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }
    }

    @keyframes bounce {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-4px);
        }
    }

    @keyframes rotate {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    /* Responsivo */
    @media (max-width: 576px) {
        .lk-modal-card {
            border-radius: var(--radius-lg);
        }

        .lk-form-grid {
            grid-template-columns: 1fr;
        }

        .lk-field {
            grid-column: 1 / -1;
        }

        .lk-modal-h,
        .lk-modal-b {
            padding-left: var(--spacing-4);
            padding-right: var(--spacing-4);
        }



        .lk-modal-f {
            margin-right: 50px;
        }



        .btn {
            justify-content: center;
        }
    }



    /* Estados de validação */
    .lk-field input:invalid:not(:placeholder-shown),
    .lk-field select:invalid:not(:placeholder-shown) {
        border-color: var(--color-danger);
    }

    .lk-field input:valid:not(:placeholder-shown):not([readonly]),
    .lk-field select:valid:not(:placeholder-shown) {
        border-color: var(--color-success);
    }

    /* Loading state */
    .btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* Tags de campo obrigatório */
    .lk-field label::after {
        content: '';
    }

    .lk-field:has(input[required]) label::after,
    .lk-field:has(select[required]) label::after {
        content: ' *';
        color: var(--color-danger);
        font-weight: 700;
    }
</style>

<!-- Modal: Nova Conta -->
<div class="lk-modal" id="modalConta" role="dialog" aria-modal="true" aria-labelledby="modalContaTitle">
    <div class="lk-modal-card">
        <div class="lk-modal-h">
            <div class="lk-modal-t" id="modalContaTitle">Nova conta</div>
            <button class="btn btn-ghost" id="modalClose" type="button">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="lk-modal-b">
            <form id="formConta">
                <input type="hidden" id="contaId" value="">
                <div class="lk-form-grid">
                    <div class="lk-field full">
                        <label for="nome">Nome da conta</label>
                        <input id="nome" name="nome" type="text" placeholder="Ex.: Nubank, Dinheiro, PicPay" required>
                    </div>
                    <div class="lk-field">
                        <label for="instituicao">Institui��o</label>
                        <input id="instituicao" name="instituicao" type="text" placeholder="Ex.: Nubank, Caixa"
                            required>
                    </div>
                    <div class="lk-field">
                        <label for="saldo_inicial">Saldo inicial</label>
                        <input class="real" id="saldo_inicial" name="saldo_inicial" type="text" inputmode="decimal"
                            placeholder="R$ 0,00">
                    </div>
                </div>
                <div class="lk-modal-f">
                    <button type="button" class="btn btn-light" id="btnCancel">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnSave">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Novo Lançamento na Conta -->
<div class="lk-modal" id="modalLancConta" role="dialog" aria-modal="true" aria-labelledby="modalLancContaTitle">
    <div class="lk-modal-card">
        <div class="lk-modal-h">
            <div class="lk-modal-t" id="modalLancContaTitle">Novo lan�amento</div>
            <button class="btn btn-ghost" id="lancClose" type="button">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="lk-modal-b">
            <form id="formLancConta">
                <input type="hidden" id="lanContaId" name="lanContaId" value="">
                <div class="lk-form-grid">
                    <div class="lk-field full">
                        <label for="lanContaNome">Conta selecionada</label>
                        <input type="text" id="lanContaNome" placeholder="Escolha uma conta na lista" readonly>
                    </div>
                    <div class="lk-field">
                        <label for="lanTipo">Tipo</label>
                        <select id="lanTipo" name="lanTipo" required>
                            <option value="despesa"> Despesa</option>
                            <option value="receita"> Receita</option>
                        </select>
                    </div>
                    <div class="lk-field">
                        <label for="lanData">Data</label>
                        <input type="date" id="lanData" name="lanData" required>
                    </div>
                    <div class="lk-field full">
                        <label for="lanCategoria">Categoria</label>
                        <select id="lanCategoria" name="lanCategoria" required>
                            <option value="">Selecione uma categoria</option>
                            <option value="1">Alimenta��o</option>
                            <option value="2">Transporte</option>
                            <option value="3">Moradia</option>
                        </select>
                    </div>
                    <div class="lk-field full">
                        <label for="lanDescricao">Descri��o</label>
                        <input type="text" id="lanDescricao" name="lanDescricao" placeholder="Ex.: Mercado / Sal�rio">
                    </div>
                    <div class="lk-field full">
                        <label for="lanValor">Valor</label>
                        <input class="real" type="text" id="lanValor" name="lanValor" inputmode="decimal"
                            placeholder="R$ 0,00" required>
                    </div>
                </div>
                <div class="lk-modal-f">
                    <button type="button" class="btn btn-light" id="lancCancel">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Transferência -->
<div class="lk-modal" id="modalTransfer" role="dialog" aria-modal="true" aria-labelledby="modalTransferTitle">
    <div class="lk-modal-card">
        <div class="lk-modal-h">
            <div class="lk-modal-t" id="modalTransferTitle">Transfer�ncia</div>
            <button class="btn btn-ghost" id="trClose" type="button">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="lk-modal-b">
            <form id="formTransfer">
                <input type="hidden" id="trOrigemId" name="trOrigemId">
                <div class="lk-form-grid">
                    <div class="lk-field full">
                        <label>Origem</label>
                        <input id="trOrigemNome" type="text" readonly value="Conta Corrente">
                    </div>
                    <div class="lk-field full">
                        <label for="trDestinoId">Destino</label>
                        <select id="trDestinoId" name="trDestinoId" required>
                            <option value="">Selecione a conta de destino</option>
                            <option value="1">Poupan�a</option>
                            <option value="2">Investimentos</option>
                            <option value="3">Carteira</option>
                        </select>
                    </div>
                    <div class="lk-field">
                        <label for="trData">Data</label>
                        <input type="date" id="trData" name="trData" required>
                    </div>
                    <div class="lk-field">
                        <label for="trValor">Valor</label>
                        <input class="real" type="text" id="trValor" name="trValor" inputmode="decimal" placeholder="R$ 0,00" required>
                    </div>
                    <div class="lk-field full">
                        <label for="trDesc">Descrição (opcional)</label>
                        <input type="text" id="trDesc" name="trDesc" placeholder="Ex.: Transferência entre contas">
                    </div>
                </div>
                <div class="lk-modal-f">
                    <button type="button" class="btn btn-light" id="trCancel">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-exchange-alt"></i> Transferir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function formatarReal(valor) {
        valor = valor.replace(/\D/g, "");
        valor = (valor / 100).toFixed(2) + "";
        valor = valor.replace(".", ",");
        valor = valor.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        return valor;
    }

    document.querySelectorAll(".real").forEach(function(input) {
        input.addEventListener("input", function() {
            this.value = "R$ " + formatarReal(this.value);
        });
    });
</script>




