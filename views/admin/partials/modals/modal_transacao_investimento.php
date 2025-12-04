<?php
/**
 * Modal para registrar compras ou vendas de um investimento existente.
 */
?>

<div class="modal fade" id="modal-transacao-investimento" tabindex="-1" aria-labelledby="modalTransacaoLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <p class="text-muted mb-1 small" id="modalTransacaoInvestLabel"></p>
                    <h5 class="modal-title" id="modalTransacaoLabel">Registrar transação</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <form class="p-3" id="form-transacao-investimento" method="POST">
                <input type="hidden" name="investimento_id" id="transacao_investimento_id" value="">

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label d-flex align-items-center gap-2">
                            Tipo <span class="text-danger">*</span>
                        </label>
                        <div class="d-flex gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo" id="tipo_compra" value="compra"
                                    checked>
                                <label class="form-check-label" for="tipo_compra">Compra</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo" id="tipo_venda" value="venda">
                                <label class="form-check-label" for="tipo_venda">Venda</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <label for="data_transacao" class="form-label">Data <span class="text-danger">*</span></label>
                        <input type="date" id="data_transacao" name="data_transacao" class="form-control"
                            value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <label for="transacao_quantidade" class="form-label">Quantidade <span
                                class="text-danger">*</span></label>
                        <input type="number" step="0.0001" min="0" class="form-control" name="quantidade"
                            id="transacao_quantidade" placeholder="0,00" required>
                    </div>
                    <div class="col-md-4">
                        <label for="transacao_preco" class="form-label">Preço unitário <span
                                class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" name="preco" id="transacao_preco"
                            placeholder="0,00" required>
                    </div>
                    <div class="col-md-4">
                        <label for="transacao_taxas" class="form-label">Taxas</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="taxas" id="transacao_taxas"
                            placeholder="0,00">
                        <div class="form-text">Inclua corretagem/IR se desejar.</div>
                    </div>
                </div>

                <div class="mt-3">
                    <label for="transacao_observacoes" class="form-label">Observações</label>
                    <textarea name="observacoes" id="transacao_observacoes" class="form-control" rows="3"
                        placeholder="Anote detalhes da operação (opcional)"></textarea>
                </div>
            </form>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" form="form-transacao-investimento">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Salvar transação
                </button>
            </div>
        </div>
    </div>
</div>
