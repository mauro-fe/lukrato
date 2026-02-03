<!-- ==================== MODAL: DETALHES DA FATURA ==================== -->
<div class="modal fade" id="modalDetalhesParcelamento" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-list"></i>
                    <span>Detalhes da Fatura</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalhesParcelamentoContent">
                <!-- Conteúdo carregado dinamicamente -->
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAL: PAGAR FATURA ==================== -->
<div class="modal fade" id="modalPagarFatura" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modern-modal">
            <div class="modal-header" style="border-bottom: 1px solid var(--glass-border);">
                <h5 class="modal-title">
                    <i class="fas fa-credit-card" style="color: #10b981;"></i>
                    <span>Pagar Fatura</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pagarFaturaId" value="">
                <input type="hidden" id="pagarFaturaValorTotal" value="">
                
                <!-- Escolha do tipo de pagamento -->
                <div id="pagarFaturaEscolha">
                    <p style="color: var(--color-text-muted); margin-bottom: 1.25rem; text-align: center;">
                        Como deseja efetuar o pagamento?
                    </p>
                    
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <button type="button" id="btnPagarTotal" class="btn-opcao-pagamento" style="
                            display: flex; align-items: center; gap: 1rem; 
                            padding: 1rem 1.25rem; 
                            background: linear-gradient(135deg, #10b981 0%, #059669 100%); 
                            color: white; 
                            border: none; 
                            border-radius: 12px; 
                            cursor: pointer; 
                            transition: all 0.2s;
                            text-align: left;
                            width: 100%;
                        ">
                            <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fas fa-check-double" style="font-size: 1.25rem;"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 1rem;">Pagar Valor Total</div>
                                <div id="valorTotalDisplay" style="font-size: 1.25rem; font-weight: bold; margin-top: 0.25rem;">R$ 0,00</div>
                            </div>
                        </button>
                        
                        <button type="button" id="btnPagarParcial" class="btn-opcao-pagamento" style="
                            display: flex; align-items: center; gap: 1rem; 
                            padding: 1rem 1.25rem; 
                            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); 
                            color: white; 
                            border: none; 
                            border-radius: 12px; 
                            cursor: pointer; 
                            transition: all 0.2s;
                            text-align: left;
                            width: 100%;
                        ">
                            <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fas fa-hand-holding-usd" style="font-size: 1.25rem;"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 1rem;">Pagar Outro Valor</div>
                                <div style="font-size: 0.875rem; opacity: 0.9; margin-top: 0.25rem;">Escolher valor personalizado</div>
                            </div>
                        </button>
                    </div>
                </div>
                
                <!-- Formulário de valor parcial (inicialmente escondido) -->
                <div id="pagarFaturaFormParcial" style="display: none;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
                        <button type="button" id="btnVoltarEscolha" style="
                            background: transparent; 
                            border: none; 
                            color: var(--color-text-muted); 
                            cursor: pointer;
                            padding: 0.5rem;
                            border-radius: 8px;
                            transition: all 0.2s;
                        ">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <span style="color: var(--color-text); font-weight: 500;">Pagar valor personalizado</span>
                    </div>
                    
                    <div class="mb-3">
                        <label for="valorPagamentoParcial" class="form-label" style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-money-bill-wave" style="color: #10b981;"></i>
                            Valor do pagamento
                        </label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: var(--color-surface); border-color: var(--glass-border); color: var(--color-text);">R$</span>
                            <input type="text" class="form-control" id="valorPagamentoParcial" 
                                   placeholder="0,00"
                                   style="font-size: 1.25rem; font-weight: 600; text-align: right; background: var(--color-surface); border-color: var(--glass-border); color: var(--color-text);">
                        </div>
                        <small id="valorTotalInfo" style="display: block; text-align: right; color: var(--color-text-muted); margin-top: 0.5rem;">
                            Valor total da fatura: R$ 0,00
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contaPagamentoFatura" class="form-label" style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-university" style="color: #3b82f6;"></i>
                            Conta para débito
                        </label>
                        <select id="contaPagamentoFatura" class="form-select" style="background: var(--color-surface); border-color: var(--glass-border); color: var(--color-text);">
                            <option value="">Carregando contas...</option>
                        </select>
                    </div>
                    
                    <div style="margin: 1rem 0; padding: 0.75rem; background: rgba(59, 130, 246, 0.1); border-radius: 8px; border-left: 4px solid #3b82f6;">
                        <p style="margin: 0; color: #60a5fa; font-size: 0.875rem;">
                            <i class="fas fa-info-circle"></i> 
                            O valor informado será debitado da conta selecionada. Os itens serão marcados como pagos proporcionalmente.
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="pagarFaturaFooter" style="border-top: 1px solid var(--glass-border); display: none;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-success" id="btnConfirmarPagamento">
                    <i class="fas fa-check"></i> Confirmar Pagamento
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAL: EDITAR ITEM DA FATURA ==================== -->
<div class="modal fade" id="modalEditarItemFatura" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit text-warning"></i>
                    <span>Editar Item</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarItemFatura">
                    <input type="hidden" id="editItemFaturaId" value="">
                    <input type="hidden" id="editItemId" value="">

                    <div class="mb-3">
                        <label for="editItemDescricao" class="form-label">Descrição</label>
                        <input type="text" class="form-control" id="editItemDescricao"
                            placeholder="Digite a descrição do item" required>
                    </div>

                    <div class="mb-3">
                        <label for="editItemValor" class="form-label">Valor (R$)</label>
                        <input type="text" class="form-control" id="editItemValor"
                            placeholder="0,00" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-warning" id="btnSalvarItemFatura">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>