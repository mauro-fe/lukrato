<style>
    #monthModal .modal-content {
        background: var(--color-surface) !important;
        color: var(--color-text);
        border-radius: var(--radius-lg);
        border: 1px solid var(--glass-border);
        box-shadow: var(--shadow-lg);
        font-family: var(--font-primary);
    }

    #monthModal .modal-header,
    #monthModal .modal-footer {
        border: 0;
        background: transparent;
        color: var(--color-text);
    }

    #monthModal .modal-title {
        font-size: var(--font-size-lg);
        font-weight: 600;
        color: var(--color-primary);
    }

    #monthModal .btn {
        border-radius: var(--radius-sm);
        font-size: var(--font-size-sm);
        transition: var(--transition-fast);
    }

    #monthModal .btn-outline-light,
    #monthModal .btn-secondary,
    #monthModal .btn-outline-secondary {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        color: var(--color-text);
    }

    #monthModal .btn-outline-light:hover,
    #monthModal .btn-secondary:hover,
    #monthModal .btn-outline-secondary:hover {
        background: color-mix(in srgb, var(--glass-bg) 80%, var(--color-primary) 20%);
        color: var(--branco) !important;
        border-color: var(--color-primary);
    }

    #monthModal .btn-primary {
        background: var(--color-primary);
        border-color: var(--color-primary);
        color: var(--branco);
    }

    #monthModal .btn-primary:hover {
        filter: brightness(1.1);
    }

    #monthModal .form-label {
        color: var(--color-text);
        font-size: var(--font-size-sm);
        margin-bottom: 0.25rem;
    }

    #monthModal .form-control,
    #monthModal .form-select {
        background: var(--color-surface-muted);
        color: var(--color-text);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-sm);
        font-size: var(--font-size-sm);
        padding: var(--spacing-3);
    }

    #monthModal .form-control::placeholder {
        color: color-mix(in srgb, var(--color-text) 60%, transparent);
    }

    #monthModal .form-control:focus,
    #monthModal .form-select:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px var(--ring);
        color: var(--color-text) !important;
    }
</style>

<div class="modal fade" id="monthModal" tabindex="-1" aria-labelledby="monthModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:600px;">
        <div class="modal-content bg-dark text-light border-0 rounded-3">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="monthModalLabel">Selecionar mes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div class="btn-group" role="group" aria-label="Navegar entre anos">
                        <button type="button" class="btn btn-outline-light btn-sm" id="mpPrevYear" title="Ano anterior">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span class="px-3 fw-semibold" id="mpYearLabel">2024</span>
                        <button type="button" class="btn btn-outline-light btn-sm" id="mpNextYear" title="Proximo ano">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-light btn-sm" id="mpTodayBtn">Hoje</button>
                        <input type="month" class="form-control form-control-sm bg-dark text-light border-secondary"
                            id="mpInputMonth" style="width:165px">
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