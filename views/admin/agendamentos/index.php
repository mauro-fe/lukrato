<!-- Tabulator -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/css/tabulator.min.css">

<!-- CSS REFATORADO -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-tables-shared.css">

<div data-aos="fade-up">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgendamento"
        title="Adicionar agendamento">
        <i class="fa-solid fa-plus"></i> Novo Agendamento
    </button>
</div>

<div class="container-table mt-5" data-aos="fade-up">
    <div id="agList" class="table-container">
        <div class="ag-table-desktop">
            <div id="agendamentosTable"></div>
        </div>

        <!-- MOBILE: Cards + paginação -->
        <section class="ag-cards-wrapper cards-wrapper">
            <section class="ag-cards-container cards-container" id="agCards"></section>

            <nav class="lan-cards-pager cards-pager" id="lanCardsPager" aria-label="Paginação de lançamentos">
                <button type="button" id="lanPagerFirst" class="lan-pager-btn pager-btn" disabled
                    aria-label="Primeira página">
                    <i class="fas fa-angle-double-left"></i>
                </button>

                <button type="button" id="lanPagerPrev" class="lan-pager-btn pager-btn" disabled
                    aria-label="Página anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <span id="lanPagerInfo" class="lan-pager-info pager-info">Nenhum lançamento</span>

                <button type="button" id="lanPagerNext" class="lan-pager-btn pager-btn" disabled
                    aria-label="Próxima página">
                    <i class="fas fa-chevron-right"></i>
                </button>

                <button type="button" id="lanPagerLast" class="lan-pager-btn pager-btn" disabled
                    aria-label="Última página">
                    <i class="fas fa-angle-double-right"></i>
                </button>
            </nav>
        </section>
    </div>

    <div id="agPaywall" class="empty-state paywall-state d-none" role="alert" aria-live="polite" hidden>
        <i class="fas fa-lock"></i>
        <h3>Agendamentos exclusivos do plano Pro</h3>
        <p id="agPaywallMessage">Agendamentos são exclusivos do plano Pro.</p>
        <button type="button" class="lk-btn btn btn-primary" id="agPaywallCta">
            <i class="fas fa-crown"></i> Assinar plano Pro
        </button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/luxon@3.4.4/build/global/luxon.min.js"></script>