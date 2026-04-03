<!-- Modal de Orçamento / Limite Mensal -->
<div class="modal fade" id="modalOrcamento" tabindex="-1" aria-labelledby="modalOrcamentoLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
        <div class="modal-content border-0 rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="modalOrcamentoLabel">
                    <i data-lucide="wallet"></i> Limite Mensal
                </h5>
                <button type="button" class="btn-close btn-close-custom" data-bs-dismiss="modal"
                    aria-label="Fechar"></button>
            </div>

            <div class="modal-body">
                <div class="orc-modal-description">
                    <i data-lucide="info"></i>
                    <span>Defina o valor máximo que deseja gastar por mês nesta categoria. Você será alertado quando
                        estiver próximo ou ultrapassar o limite.</span>
                </div>

                <p class="orc-modal-cat-name">Categoria: <strong id="orcCategoriaNome">—</strong></p>
                <p class="orc-modal-gasto d-none" id="orcGastoAtual">Gasto atual: <strong id="orcGastoValor">R$
                        0,00</strong></p>

                <div id="orcAlertError" class="alert alert-danger d-none py-2 px-3" style="font-size:0.85rem"
                    role="alert"></div>

                <form id="formOrcamento" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="orcValorLimite">Orçamento mensal (R$)</label>
                        <div class="orc-input-wrapper">
                            <span class="orc-input-prefix">R$</span>
                            <input type="text" class="form-control orc-input-currency" id="orcValorLimite"
                                name="valor_limite" placeholder="0,00" required inputmode="decimal" autocomplete="off">
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger btn-sm d-none" id="btnRemoverOrcamento">
                    <i data-lucide="trash-2"></i> Remover limite
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm" form="formOrcamento" id="btnSalvarOrcamento">
                    <i data-lucide="check"></i> <span id="btnOrcText">Definir</span>
                </button>
            </div>
        </div>
    </div>
</div>
