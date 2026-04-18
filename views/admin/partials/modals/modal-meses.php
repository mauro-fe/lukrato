<div class="modal fade" id="monthModal" tabindex="-1" aria-labelledby="monthModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered month-modal-dialog">
        <div class="modal-content border-0 rounded-3">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="monthModalLabel">Selecionar mês</h5>
                <button type="button" class="month-modal-close" data-bs-dismiss="modal" aria-label="Fechar">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="modal-body pt-0">
                <div class="month-modal-toolbar">
                    <div class="month-modal-year-nav" role="group" aria-label="Navegar entre anos">
                        <button type="button" class="btn btn-outline-light btn-sm month-modal-icon-btn" id="mpPrevYear" title="Ano anterior">
                            <i data-lucide="chevron-left"></i>
                        </button>
                        <span class="px-3 fw-semibold" id="mpYearLabel">2024</span>
                        <button type="button" class="btn btn-outline-light btn-sm month-modal-icon-btn" id="mpNextYear" title="Próximo ano">
                            <i data-lucide="chevron-right"></i>
                        </button>
                    </div>
                    <div class="month-modal-quick-actions">
                        <button type="button" class="btn btn-outline-light btn-sm" id="mpTodayBtn">Hoje</button>
                        <div class="month-modal-input-wrap">
                            <label class="month-modal-input-label" for="mpInputMonth">Ir direto para</label>
                            <input type="month" class="form-control form-control-sm border-secondary" id="mpInputMonth">
                        </div>
                    </div>
                </div>
                <div id="mpGrid" class="row g-2"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary month-modal-dismiss" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="yearModal" tabindex="-1" aria-labelledby="yearModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered year-modal-dialog">
        <div class="modal-content border-0 rounded-3">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="yearModalLabel">Selecionar ano</h5>
                <button type="button" class="month-modal-close year-modal-close" data-bs-dismiss="modal" aria-label="Fechar">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="lk-year-grid mb-3" id="yearGrid"></div>
                <div class="month-modal-form-row">
                    <input type="number" class="form-control form-control-sm border-secondary" id="yearInput" min="2000"
                        max="2100" placeholder="Digite o ano">
                    <button type="button" class="btn btn-primary btn-sm year-modal-apply" id="yearApplyBtn">Aplicar</button>
                </div>
            </div>
        </div>
    </div>
</div>
