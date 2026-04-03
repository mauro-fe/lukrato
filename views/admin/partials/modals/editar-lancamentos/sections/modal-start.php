<!-- Modal de Edição de Lançamento (usado pela tabela) -->
<div class="modal fade lk-edit-lanc-modal" id="modalEditarLancamento" tabindex="-1"
    aria-labelledby="modalEditarLancamentoLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable lk-edit-lanc-modal__dialog">
        <div class="modal-content lk-edit-lanc-modal__content">
            <div class="modal-header lk-edit-lanc-modal__header">
                <div class="lk-edit-lanc-modal__header-main">
                    <div class="modal-icon lk-edit-lanc-modal__icon">
                        <i data-lucide="pen-square"></i>
                    </div>
                    <div class="lk-edit-lanc-modal__header-copy">
                        <span class="lk-edit-lanc-modal__eyebrow">Ajuste rápido</span>
                        <h5 class="modal-title" id="modalEditarLancamentoLabel">Editar lançamento</h5>
                        <p class="modal-subtitle">Revise os dados principais sem perder o contexto da sua lista.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar modal"></button>
            </div>
            <div class="modal-body lk-edit-lanc-modal__body">
                <div id="editLancAlert" class="alert alert-danger d-none" role="alert"></div>
                <form id="formLancamento" class="lk-edit-lanc-form" novalidate>
