<div class="modal fade" id="monthModal" tabindex="-1" aria-labelledby="monthModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:600px;">
        <div class="modal-content border-0 rounded-3">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="monthModalLabel">Selecionar mês</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div class="btn-group" role="group" aria-label="Navegar entre anos">
                        <button type="button" class="btn btn-outline-light btn-sm" id="mpPrevYear" title="Ano anterior">
                            <i data-lucide="chevron-left"></i>
                        </button>
                        <span class="px-3 fw-semibold" id="mpYearLabel">2024</span>
                        <button type="button" class="btn btn-outline-light btn-sm" id="mpNextYear" title="Proximo ano">
                            <i data-lucide="chevron-right"></i>
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-light btn-sm" id="mpTodayBtn">Hoje</button>
                        <input type="month" class="form-control form-control-sm  border-secondary" id="mpInputMonth"
                            style="width:165px">
                    </div>
                </div>
                <div id="mpGrid" class="row g-2"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="yearModal" tabindex="-1" aria-labelledby="yearModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content  border-0 rounded-3">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="yearModalLabel">Selecionar ano</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="lk-year-grid mb-3" id="yearGrid"></div>
                <div class="d-flex gap-2">
                    <input type="number" class="form-control form-control-sm border-secondary" id="yearInput" min="2000"
                        max="2100" placeholder="Digite o ano">
                    <button type="button" class="btn btn-primary btn-sm" id="yearApplyBtn">Aplicar</button>
                </div>
            </div>

        </div>
    </div>
</div>