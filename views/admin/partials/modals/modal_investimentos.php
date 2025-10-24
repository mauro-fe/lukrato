<?php

/** @var array $categories */ ?>

<!-- Modal: Novo Investimento (Bootstrap 5) -->
<div class="modal fade" id="modal-investimentos" tabindex="-1" aria-labelledby="modalInvestimentosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="modalInvestimentosLabel">Novo Investimento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <form action="<?= BASE_URL ?>api/investimentos" method="POST" class="investment-form" id="form-investimento">
                    <!-- Linha 1 -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Categoria <span class="text-danger">*</span></label>
                            <select name="categoria_id" id="category_id" class="form-select" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['id'] ?>">
                                        <?= htmlspecialchars($cat['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="name" class="form-label">Nome do Ativo <span class="text-danger">*</span></label>
                            <input type="text" name="nome" id="name" class="form-control" placeholder="Ex: Banco do Brasil" required>
                        </div>
                    </div>

                    <!-- Linha 2 -->
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label for="ticker" class="form-label">Código/Ticker</label>
                            <input type="text" name="ticker" id="ticker" class="form-control" placeholder="Ex: BBAS3">
                            <div class="form-text">Opcional</div>
                        </div>

                        <div class="col-md-6">
                            <label for="purchase_date" class="form-label">Data da Compra</label>
                            <input type="date" name="data_compra" id="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <!-- Linha 3 -->
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label for="quantity" class="form-label">Quantidade <span class="text-danger">*</span></label>
                            <input type="number" name="quantidade" id="quantity" class="form-control" step="0.0001" placeholder="0.00" required>
                        </div>

                        <div class="col-md-6">
                            <label for="avg_price" class="form-label">Preço Médio <span class="text-danger">*</span></label>
                            <input type="number" name="preco_medio" id="avg_price" class="form-control" step="0.01" placeholder="0.00" required>
                        </div>
                    </div>

                    <!-- Linha 4 -->
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label for="current_price" class="form-label">Preço Atual</label>
                            <input type="number" name="preco_atual" id="current_price" class="form-control" step="0.01" placeholder="0.00">
                            <div class="form-text">Deixe em branco para usar o preço médio</div>
                        </div>

                        <div class="col-md-6">
                            <label for="total_invested" class="form-label">Valor Total Investido</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" id="total_invested" class="form-control fw-semibold" readonly placeholder="0,00">
                            </div>
                        </div>
                    </div>

                    <!-- Observações -->
                    <div class="mt-3">
                        <label for="notes" class="form-label">Observações</label>
                        <textarea name="observacoes" id="notes" class="form-control" rows="4" placeholder="Adicione observações..."></textarea>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="form-investimento" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Salvar
                </button>
            </div>

        </div>
    </div>
</div>

<script>
    // --- Cálculo de total investido (Qtd * Preço Médio) ---
    (() => {
        const quantityInput = document.getElementById('quantity');
        const avgPriceInput = document.getElementById('avg_price');
        const totalInvestedInput = document.getElementById('total_invested');
        const form = document.getElementById('form-investimento');

        function calculateTotal() {
            const quantity = parseFloat(quantityInput.value) || 0;
            const avgPrice = parseFloat(avgPriceInput.value) || 0;
            const total = quantity * avgPrice;

            // Formata como 0,00 (pt-BR) sem prefixo (o prefixo "R$" está no input-group-text)
            totalInvestedInput.value = total.toFixed(2).replace('.', ',');
        }

        quantityInput.addEventListener('input', calculateTotal);
        avgPriceInput.addEventListener('input', calculateTotal);

        // Se preço atual vier vazio, usa preço médio
        form.addEventListener('submit', function() {
            const currentPrice = document.getElementById('current_price');
            if (!currentPrice.value) currentPrice.value = avgPriceInput.value || '';
        });

        // Opcional: recalcula quando o modal abrir (útil se valores vierem preenchidos)
        const modalEl = document.getElementById('modal-investimentos');
        modalEl.addEventListener('shown.bs.modal', calculateTotal);
    })();
</script>