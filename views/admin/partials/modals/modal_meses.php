<style>
/* Modal Month Picker - Estilos aprimorados */
#monthModal .modal-content {
    background: var(--color-surface) !important;
    color: var(--color-text);
    border-radius: var(--radius-xl);
    border: 1px solid var(--glass-border);
    box-shadow: var(--shadow-xl);
    font-family: var(--font-primary);
    backdrop-filter: var(--glass-backdrop);
    overflow: hidden;
}

#monthModal .modal-header {
    border: 0;
    background: var(--glass-bg);
    color: var(--color-text);
    padding: var(--spacing-5) var(--spacing-6);
    position: relative;
}

#monthModal .modal-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: var(--spacing-6);
    right: var(--spacing-6);
    height: 2px;
    background: linear-gradient(90deg,
            var(--color-primary),
            var(--color-secondary),
            var(--color-primary));
    opacity: 0.3;
}

#monthModal .modal-footer {
    border: 0;
    background: transparent;
    color: var(--color-text);
    padding: var(--spacing-4) var(--spacing-6) var(--spacing-5);
}

#monthModal .modal-title {
    font-size: var(--font-size-xl);
    font-weight: 700;
    color: var(--color-primary);
    letter-spacing: -0.02em;
}

#monthModal .btn-close {
    background: var(--glass-bg);
    border-radius: var(--radius-md);
    opacity: 0.7;
    transition: all var(--transition-normal);
    width: 32px;
    height: 32px;
}

#monthModal .btn-close:hover {
    opacity: 1;
    background: var(--color-danger);
    transform: rotate(90deg);
}

#monthModal .modal-body {
    padding: var(--spacing-5) var(--spacing-6);
}

/* Controles de navegação do ano */
.modal-body .d-flex.align-items-center.justify-content-between {
    background: var(--glass-bg);
    padding: var(--spacing-3);
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-4);
}

#monthModal .btn-group {
    background: var(--color-surface-muted);
    border-radius: var(--radius-md);
    padding: var(--spacing-1);
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

#monthModal #mpYearLabel {
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--color-primary);
    min-width: 80px;
    text-align: center;
    padding: 0 var(--spacing-3);
}

#monthModal .btn {
    border-radius: var(--radius-md);
    font-size: var(--font-size-sm);
    font-weight: 500;
    transition: all var(--transition-normal);
    position: relative;
    overflow: hidden;
}

#monthModal .btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--color-primary);
    opacity: 0;
    transition: opacity var(--transition-fast);
}

#monthModal .btn:hover::before {
    opacity: 0.1;
}

#monthModal .btn-outline-light,
#monthModal .btn-secondary,
#monthModal .btn-outline-secondary {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    color: var(--color-text);
    padding: var(--spacing-2) var(--spacing-3);
}

#monthModal .btn-outline-light:hover,
#monthModal .btn-secondary:hover,
#monthModal .btn-outline-secondary:hover {
    background: var(--color-primary);
    color: white !important;
    border-color: var(--color-primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

#monthModal .btn-outline-light:active,
#monthModal .btn-secondary:active,
#monthModal .btn-outline-secondary:active {
    transform: translateY(0);
}

#monthModal .btn-outline-light.btn-sm {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

#monthModal .btn-primary {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: white;
    padding: var(--spacing-3) var(--spacing-5);
    font-weight: 600;
}

#monthModal .btn-primary:hover {
    background: var(--color-secondary);
    border-color: var(--color-secondary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

#monthModal #mpTodayBtn {
    font-weight: 600;
    padding: 22px 40px;
}

/* Formulário */
#monthModal .form-label {
    color: var(--color-text);
    font-size: var(--font-size-sm);
    font-weight: 600;
    margin-bottom: var(--spacing-2);
}

#monthModal .form-control,
#monthModal .form-select {
    background: var(--color-surface-muted);
    color: var(--color-text);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-md);
    font-size: var(--font-size-sm);
    padding: var(--spacing-3);
    transition: all var(--transition-normal);
}

#monthModal .form-control::placeholder {
    color: var(--color-text-muted);
    opacity: 0.6;
}

#monthModal .form-control:focus,
#monthModal .form-select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 4px var(--ring);
    color: var(--color-text) !important;
    background: var(--color-surface);
    transform: translateY(-1px);
}

#monthModal #mpInputMonth {
    width: 165px;
    cursor: pointer;
}

/* Grid de meses */
#mpGrid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--spacing-2);
    margin-top: var(--spacing-4);
}

#mpGrid .col-4 {
    padding: 0;
}

#mpGrid .mp-month {
    width: 100%;
    padding: 15px 50px;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    color: var(--color-text);
    border-radius: var(--radius-md);
    font-weight: 500;
    font-size: var(--font-size-sm);
    transition: all var(--transition-normal);
    position: relative;
    overflow: hidden;
    cursor: pointer;
}

#mpGrid .mp-month::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
    opacity: 0;
    transition: opacity var(--transition-fast);

}

#mpGrid .mp-month:hover::before {
    opacity: 0.15;
}

#mpGrid .mp-month:hover {
    border-color: var(--color-primary);
    transform: translateY(-3px) scale(1.02);
    box-shadow: var(--shadow-md);
    color: var(--color-text);
}

#mpGrid .mp-month:active {
    transform: translateY(-1px) scale(1);
}

#mpGrid .mp-month.btn-warning {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: white;
    font-weight: 700;
    box-shadow: var(--shadow-md);
}

#mpGrid .mp-month.btn-warning::before {
    display: none;
}

#mpGrid .mp-month.btn-warning:hover {
    background: var(--color-secondary);
    border-color: var(--color-secondary);
    transform: translateY(-3px) scale(1.05);
    box-shadow: var(--shadow-lg);
}

/* Animação de entrada do grid */
#mpGrid .mp-month {
    animation: fadeInScale 0.3s ease forwards;
    opacity: 0;
}

#mpGrid .mp-month:nth-child(1) {
    animation-delay: 0.03s;
}

#mpGrid .mp-month:nth-child(2) {
    animation-delay: 0.06s;
}

#mpGrid .mp-month:nth-child(3) {
    animation-delay: 0.09s;
}

#mpGrid .mp-month:nth-child(4) {
    animation-delay: 0.12s;
}

#mpGrid .mp-month:nth-child(5) {
    animation-delay: 0.15s;
}

#mpGrid .mp-month:nth-child(6) {
    animation-delay: 0.18s;
}

#mpGrid .mp-month:nth-child(7) {
    animation-delay: 0.21s;
}

#mpGrid .mp-month:nth-child(8) {
    animation-delay: 0.24s;
}

#mpGrid .mp-month:nth-child(9) {
    animation-delay: 0.27s;
}

#mpGrid .mp-month:nth-child(10) {
    animation-delay: 0.30s;
}

#mpGrid .mp-month:nth-child(11) {
    animation-delay: 0.33s;
}

#mpGrid .mp-month:nth-child(12) {
    animation-delay: 0.36s;
}

@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.8) translateY(10px);
    }

    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Animação do modal */
#monthModal.fade .modal-dialog {
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    transform: scale(0.8) translateY(-50px);
}

#monthModal.show .modal-dialog {
    transform: scale(1) translateY(0);
}

/* Backdrop customizado */
#monthModal.modal {
    backdrop-filter: blur(8px);
}

/* Responsividade */
@media (max-width: 640px) {
    #monthModal .modal-dialog {
        max-width: calc(100vw - 32px);
        margin: var(--spacing-4);
    }

    #monthModal .modal-header,
    #monthModal .modal-body,
    #monthModal .modal-footer {
        padding-left: var(--spacing-4);
        padding-right: var(--spacing-4);
    }

    #monthModal .modal-title {
        font-size: var(--font-size-lg);
    }

    #mpGrid {
        grid-template-columns: repeat(3, 1fr);
        gap: var(--spacing-2);
    }

    #mpGrid .mp-month {
        padding: var(--spacing-3) var(--spacing-2);
        font-size: var(--font-size-xs);
    }

    .modal-body .d-flex.align-items-center.justify-content-between {
        flex-direction: column;
        gap: var(--spacing-3);
    }

    #monthModal #mpInputMonth {
        width: 100%;
    }
}

/* Tema escuro - ajustes específicos */
:root[data-theme="dark"] #monthModal .modal-content {
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
}

:root[data-theme="dark"] #monthModal .btn-close {
    filter: invert(1);
}

/* Tema claro - ajustes específicos */
:root[data-theme="light"] #monthModal .modal-header::after {
    opacity: 0.2;
}

#yearModal .modal-content {
    background: var(--color-surface) !important;
    color: var(--color-text);
    border-radius: var(--radius-xl);
    border: 1px solid var(--glass-border);
    box-shadow: var(--shadow-xl);
}

#yearModal .modal-header {
    border: 0;
    background: var(--glass-bg);
}

#yearModal .modal-body {
    padding: var(--spacing-5) var(--spacing-6);
}

.lk-year-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
    gap: var(--spacing-2);
}

.lk-year-grid .btn.active {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: #fff;
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

<div class="modal fade" id="yearModal" tabindex="-1" aria-labelledby="yearModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content bg-dark text-light border-0 rounded-3">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="yearModalLabel">Selecionar ano</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="lk-year-grid mb-3" id="yearGrid"></div>
                <div class="d-flex gap-2">
                    <input type="number" class="form-control form-control-sm bg-dark text-light border-secondary"
                        id="yearInput" min="2000" max="2100" placeholder="Digite o ano">
                    <button type="button" class="btn btn-primary btn-sm" id="yearApplyBtn">Aplicar</button>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
