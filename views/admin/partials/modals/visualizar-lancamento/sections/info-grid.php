<div class="row g-4">
    <!-- Coluna Esquerda -->
    <div class="col-md-6">
        <div class="info-card p-3 rounded-3"
            style="background: var(--color-bg); border: 1px solid var(--color-card-border);">
            <h6 class="mb-3" style="color: var(--color-primary);"><i data-lucide="info" class="me-2"></i>Informações
                Principais</h6>

            <div class="info-row d-flex justify-content-between mb-2">
                <span>Data:</span>
                <strong id="viewLancData"></strong>
            </div>

            <div class="info-row d-flex justify-content-between mb-2">
                <span>Tipo:</span>
                <span id="viewLancTipo"></span>
            </div>

            <div class="info-row d-flex justify-content-between mb-2">
                <span>Valor:</span>
                <strong id="viewLancValor" style="font-size: 1.1rem;"></strong>
            </div>

            <div class="info-row d-flex justify-content-between mb-2">
                <span>Status:</span>
                <span id="viewLancStatus"></span>
            </div>
        </div>
    </div>

    <!-- Coluna Direita -->
    <div class="col-md-6">
        <div class="info-card p-3 rounded-3"
            style="background: var(--color-bg); border: 1px solid var(--color-card-border);">
            <h6 class="mb-3" style="color: var(--color-primary);"><i data-lucide="tags" class="me-2"></i>Classificação
            </h6>

            <div class="info-row d-flex justify-content-between mb-2">
                <span>Categoria:</span>
                <strong id="viewLancCategoria"></strong>
            </div>

            <div class="info-row d-flex justify-content-between mb-2">
                <span>Conta:</span>
                <strong id="viewLancConta"></strong>
            </div>

            <div class="info-row d-flex justify-content-between mb-2" id="viewLancCartaoItem"
                style="display: none !important;">
                <span>Cartão:</span>
                <strong id="viewLancCartao"></strong>
            </div>

            <div class="info-row d-flex justify-content-between mb-2" id="viewLancFormaPgtoItem">
                <span>Forma Pagamento:</span>
                <strong id="viewLancFormaPgto"></strong>
            </div>
            <div class="info-row d-flex justify-content-between mb-2" id="viewLancMetaItem"
                style="display: none !important;">
                <span>Meta:</span>
                <strong id="viewLancMeta"></strong>
            </div>
        </div>
    </div>
</div>