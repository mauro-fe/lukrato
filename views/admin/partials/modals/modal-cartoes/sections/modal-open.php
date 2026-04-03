<!-- Modal de Cartao de Credito -->
<div class="lk-modal-overlay" id="modalCartaoOverlay">
    <div class="modal-container" id="modalCartao" role="dialog" aria-modal="true" aria-labelledby="modalCartaoTitulo"
        onclick="event.stopPropagation()">
        <div class="modal-header">
            <div class="modal-header-content">
                <div class="modal-icon">
                    <i data-lucide="credit-card" style="color: white"></i>
                </div>
                <div>
                    <h2 class="modal-title" id="modalCartaoTitulo">Novo Cartao de Credito</h2>
                    <p class="modal-subtitle" id="modalCartaoSubtitle">Preencha os dados do seu cartao.</p>
                </div>
            </div>
            <button class="modal-close" type="button" aria-label="Fechar modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="modal-body">
            <form id="formCartao" autocomplete="off">
                <input type="hidden" id="cartaoId" name="cartao_id">