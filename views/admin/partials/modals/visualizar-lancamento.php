<!-- Modal de Visualização de Lançamento -->
<div class="modal fade" id="modalViewLancamento" tabindex="-1" aria-labelledby="modalViewLancamentoLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4" style="background: var(--color-bg); overflow: hidden;">
            <!-- Header -->
            <div class="modal-header border-0 pb-0"
                style="background: linear-gradient(135deg, var(--color-primary) 0%, #d35400 100%); padding: 1.5rem;">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-container"
                        style="width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-receipt" style="font-size: 1.5rem; color: white;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="modalViewLancamentoLabel"
                            style="color: white; font-weight: 700;"></h5>
                        <small id="viewLancamentoId" style="color: rgba(255,255,255,0.7);"></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Fechar"></button>
            </div>

            <!-- Body -->
            <div class="modal-body p-4">
                <div class="row g-4">
                    <!-- Coluna Esquerda -->
                    <div class="col-md-6">
                        <div class="info-card p-3 rounded-3"
                            style="background: var(--color-bg); border: 1px solid var(--color-card-border);">
                            <h6 class="mb-3" style="color: var(--color-primary);"><i
                                    class="fas fa-info-circle me-2"></i>Informações Principais</h6>

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
                            <h6 class="mb-3" style="color: var(--color-primary);"><i
                                    class="fas fa-tags me-2"></i>Classificação</h6>

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
                        </div>
                    </div>
                </div>

                <!-- Descrição -->
                <div class="info-card p-3 rounded-3 mt-3"
                    style="background: var(--color-bg); border: 1px solid var(--color-card-border);"
                    id="viewLancDescricaoCard">
                    <h6 class="mb-2" style="color: var(--color-primary);"><i
                            class="fas fa-align-left me-2"></i>Descrição</h6>
                    <p id="viewLancDescricao" class="mb-0"></p>
                </div>

                <!-- Parcelamento -->
                <div class="info-card p-3 rounded-3 mt-3"
                    style="background: var(--color-surface-muted); border: 1px solid var(--color-card-border); display: none;"
                    id="viewLancParcelamentoCard">
                    <h6 class="mb-2" style="color: var(--color-primary);"><i
                            class="fas fa-layer-group me-2"></i>Parcelamento</h6>
                    <div class="d-flex justify-content-between">
                        <span class="">Parcela:</span>
                        <strong id="viewLancParcela"></strong>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Fechar
                </button>
                <button type="button" class="btn btn-primary" id="btnEditFromView"
                    style="background: linear-gradient(135deg, var(--color-primary), #d35400); border: none;">
                    <i class="fas fa-edit me-1"></i> Editar
                </button>
            </div>
        </div>
    </div>
</div>