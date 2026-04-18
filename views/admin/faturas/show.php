<section class="parc-page fat-detail-page" id="faturaDetalhePage" data-fatura-id="<?= (int) ($faturaId ?? 0) ?>">
    <div class="fat-detail-shell surface-card surface-card--clip" id="faturaDetalheShell">
        <div class="fat-detail-toolbar">
            <a class="fat-detail-back" href="<?= BASE_URL ?>faturas" data-no-transition="true">
                <i data-lucide="arrow-left"></i>
                <span>Voltar para faturas</span>
            </a>

            <span class="fat-detail-pill">
                <i data-lucide="receipt-text"></i>
                <span>Detalhe da fatura</span>
            </span>
        </div>

        <div class="fat-detail-heading">
            <div>
                <span class="fat-detail-eyebrow">Analise completa</span>
                <h2 class="fat-detail-title" id="faturaDetalheTitle">Carregando fatura...</h2>
                <p class="fat-detail-subtitle" id="faturaDetalheSubtitle">
                    Buscando itens, progresso de pagamento e acoes disponiveis.
                </p>
            </div>
        </div>

        <div class="fat-detail-frame">
            <div class="fat-detail-loading" id="faturaDetalheLoading">
                <i data-lucide="loader-2"></i>
                <p>Carregando detalhes da fatura...</p>
            </div>

            <div class="fat-detail-content" id="faturaDetalheContent" hidden></div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../partials/modals/faturas/sections/modal-pagar-fatura.php'; ?>
<?php include __DIR__ . '/../partials/modals/faturas/sections/modal-editar-item.php'; ?>
<?php include __DIR__ . '/../partials/modals/faturas/sections/modal-excluir-item-escopo.php'; ?>
