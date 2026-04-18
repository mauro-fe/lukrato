<section
    class="card-detail-page"
    id="cardDetailPage"
    data-card-id="<?= (int) ($cartaoId ?? 0) ?>"
    data-current-month="<?= date('Y-m') ?>">
    <div class="card-detail-shell surface-card surface-card--clip">
        <div class="card-detail-toolbar">
            <a class="card-detail-back" href="<?= BASE_URL ?>cartoes" data-no-transition="true">
                <i data-lucide="arrow-left"></i>
                <span>Voltar para cartões</span>
            </a>

            <span class="card-detail-pill">
                <i data-lucide="credit-card"></i>
                <span>Detalhes do cartão</span>
            </span>
        </div>

        <div class="card-detail-heading">
            <h2 class="card-detail-title" id="cardDetailPageTitle">Carregando detalhes do cartão...</h2>
            <p class="card-detail-subtitle" id="cardDetailPageSubtitle">
                Buscando fatura, histórico e parcelamentos ativos.
            </p>
        </div>

        <div class="card-detail-frame">
            <div class="card-detail-loading" id="cardDetailPageLoading">
                <i data-lucide="loader-2"></i>
                <p>Carregando painel do cartão...</p>
            </div>

            <div class="card-detail-error" id="cardDetailPageError" hidden></div>
            <div class="card-detail-content" id="cardDetailPageContent" hidden></div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../partials/modals/card-detail-modal/sections/template.php'; ?>
<?= vite_scripts('admin/card-modals/index.js') ?>
