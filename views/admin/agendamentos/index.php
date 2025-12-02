<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/css/tabulator.min.css">

<div data-aos="fade-up">
    <button type="button" class="btn btn-primary mt-5" data-bs-toggle="modal" data-bs-target="#modalAgendamento"
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
        <section class="ag-cards-wrapper">
            <section class="ag-cards-container" id="agCards"></section>

            <nav class="ag-cards-pager" id="agCardsPager" aria-label="Paginação de agendamentos">
                <button type="button" id="agPagerFirst" class="ag-pager-btn" disabled aria-label="Primeira página">
                    «
                </button>
                <button type="button" id="agPagerPrev" class="ag-pager-btn" disabled aria-label="Página anterior">
                    <
                </button>

                <span id="agPagerInfo" class="ag-pager-info">
                    Nenhum agendamento
                </span>

                <button type="button" id="agPagerNext" class="ag-pager-btn" disabled aria-label="Próxima página">
                    >
                </button>
                <button type="button" id="agPagerLast" class="ag-pager-btn" disabled aria-label="Última página">
                    »
                </button>
            </nav>
        </section>
    </div>

    <div id="agPaywall" class="empty-state paywall-state d-none" role="alert" aria-live="polite" hidden>
        <i class="fas fa-lock"></i>
        <h3>Agendamentos exclusivos do plano Pro</h3>
        <p id="agPaywallMessage">Agendamentos sǜo exclusivos do plano Pro.</p>
        <button type="button" class="lk-btn btn btn-primary" id="agPaywallCta">
            <i class="fas fa-crown"></i>
            Assinar plano Pro
        </button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
