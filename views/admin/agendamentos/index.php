<!-- Tabulator -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/css/tabulator.min.css">

<!-- CSS -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-tables-shared.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/agendamentos-modern.css">

<div class="ag-page">
    <!-- ==================== HEADER COM TÍTULO E BOTÃO ==================== -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-text">
                <h1 class="page-title">
                    <i class="fas fa-calendar-check"></i>
                    <span>Agendamentos</span>
                </h1>
                <p class="page-subtitle">Gerencie suas contas a pagar e receber agendadas</p>
            </div>
            <button type="button" class="btn-new" data-bs-toggle="modal" data-bs-target="#modalAgendamento">
                <i class="fas fa-plus"></i>
                <span>Novo Agendamento</span>
            </button>
        </div>
    </div>

    <!-- ==================== CONTAINER DA TABELA ==================== -->
    <div class="modern-card table-card" id="agList">
        <div class="card-header">
            <div class="header-left">
                <i class="fas fa-list"></i>
                <div class="header-text">
                    <h3>Seus Agendamentos</h3>
                    <p>Lista de contas a pagar e receber agendadas</p>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- DESKTOP: Tabulator -->
            <div class="ag-table-desktop">
                <div id="agendamentosTable"></div>
            </div>

            <!-- MOBILE: Cards + paginação -->
            <div class="ag-cards-wrapper">
                <div class="ag-cards-container" id="agCards"></div>

                <nav class="cards-pager" id="agCardsPager" aria-label="Paginação de agendamentos">
                    <button type="button" id="agPagerFirst" class="pager-btn" disabled aria-label="Primeira página">
                        <i class="fas fa-angle-double-left"></i>
                    </button>

                    <button type="button" id="agPagerPrev" class="pager-btn" disabled aria-label="Página anterior">
                        <i class="fas fa-chevron-left"></i>
                    </button>

                    <span id="agPagerInfo" class="pager-info">Nenhum agendamento</span>

                    <button type="button" id="agPagerNext" class="pager-btn" disabled aria-label="Próxima página">
                        <i class="fas fa-chevron-right"></i>
                    </button>

                    <button type="button" id="agPagerLast" class="pager-btn" disabled aria-label="Última página">
                        <i class="fas fa-angle-double-right"></i>
                    </button>
                </nav>
            </div>
        </div>
    </div>

    <!-- ==================== PAYWALL ==================== -->
    <div id="agPaywall" class="paywall-message d-none" role="alert" hidden>
        <i class="fas fa-crown"></i>
        <h3>Recurso Premium</h3>
        <p id="agPaywallMessage">Agendamentos são exclusivos do plano Pro.</p>
        <button type="button" class="btn-upgrade" id="agPaywallCta">
            <i class="fas fa-crown"></i>
            Fazer Upgrade para PRO
        </button>
    </div>
</div>

<!-- ==================== SCRIPTS ==================== -->
<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/luxon@3.4.4/build/global/luxon.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/admin-agendamentos-index.js"></script>
