<!-- Modal de Edição de Lançamento (usado pela tabela) -->
<div class="modal fade" id="modalEditarLancamento" tabindex="-1" aria-labelledby="modalEditarLancamentoLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:650px">
        <div class="modal-content border-0 rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarLancamentoLabel">Editar Lançamento</h5>
                <button type="button" class="btn-close btn-close-custom" data-bs-dismiss="modal"
                    aria-label="Fechar modal"></button>
            </div>
            <div class="modal-body pt-0">
                <div id="editLancAlert" class="alert alert-danger d-none" role="alert"></div>
                <form id="formLancamento" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editLancData" class="form-label">Data</label>
                            <input type="date" class="form-control form-control-sm" id="editLancData" required
                                aria-required="true">
                        </div>
                        <div class="col-md-6">
                            <label for="editLancTipo" class="form-label">Tipo</label>
                            <select class="form-select form-select-sm" id="editLancTipo" required aria-required="true">
                                <option value="receita">Receita</option>
                                <option value="despesa">Despesa</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label for="editLancConta" class="form-label">Conta</label>
                            <select class="form-select form-select-sm" id="editLancConta" required
                                aria-required="true"></select>
                        </div>
                        <div class="col-md-6">
                            <label for="editLancCategoria" class="form-label">Categoria</label>
                            <select class="form-select form-select-sm" id="editLancCategoria"></select>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-8">
                            <label for="editLancDescricao" class="form-label">Descrição</label>
                            <input type="text" class="form-control form-control-sm" id="editLancDescricao"
                                aria-label="Descrição do lançamento">
                        </div>
                        <div class="col-md-4">
                            <label for="editLancValor" class="form-label">Valor</label>
                            <input type="text" class="form-control form-control-sm money-mask" id="editLancValor"
                                inputmode="decimal" placeholder="R$ 0,00" required aria-required="true">
                        </div>
                    </div>

                    <!-- Forma de Pagamento -->
                    <div class="row g-3 mt-1">
                        <div class="col-12">
                            <label for="editLancFormaPagamento" class="form-label">Forma de Pagamento</label>
                            <select class="form-select form-select-sm" id="editLancFormaPagamento">
                                <option value="">-- Não informada --</option>
                                <option value="pix">📱 PIX</option>
                                <option value="cartao_credito">💳 Cartão de Crédito</option>
                                <option value="cartao_debito">💳 Cartão de Débito</option>
                                <option value="dinheiro">💵 Dinheiro</option>
                                <option value="boleto">📄 Boleto</option>
                                <option value="transferencia">🏦 Transferência</option>
                                <option value="deposito">🏦 Depósito</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary">Salvar alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>