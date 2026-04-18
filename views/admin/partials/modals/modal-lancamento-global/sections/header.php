<?php $lancamentoWizardTitle = $lancamentoWizardTitle ?? 'Nova Transação'; ?>

<div class="lk-modal-header-gradient">
    <div class="lk-modal-icon-wrapper">
        <i data-lucide="arrow-left-right" style="color: white"></i>
    </div>
    <h2 class="lk-modal-title" id="modalLancamentoGlobalTitulo"><?= htmlspecialchars((string) $lancamentoWizardTitle, ENT_QUOTES, 'UTF-8') ?></h2>
    <button class="lk-modal-close-btn" onclick="lancamentoGlobalManager.closeModal()" type="button"
        aria-label="Fechar modal">
        <i data-lucide="x"></i>
    </button>
</div>
