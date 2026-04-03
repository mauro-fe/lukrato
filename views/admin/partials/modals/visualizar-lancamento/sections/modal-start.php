<!-- Modal de Visualização de Lançamento -->
<div class="modal fade" id="modalViewLancamento" tabindex="-1" aria-labelledby="modalViewLancamentoLabel"
    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4" style="background: var(--color-bg); overflow: hidden;">
            <!-- Header -->
            <div class="modal-header border-0 pb-0"
                style="background: linear-gradient(135deg, var(--color-primary) 0%, #d35400 100%); padding: 1.5rem;">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon-container"
                        style="width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="receipt" style="font-size: 1.5rem; color: white;"></i>
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
