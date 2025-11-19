<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/css/tabulator.min.css">

<div data-aos="fade-up">
    <button type="button" class="btn btn-primary mt-5" data-bs-toggle="modal" data-bs-target="#modalAgendamento"
        title="Adicionar agendamento">
        <i class="fa-solid fa-plus"></i> Novo Agendamento
    </button>
</div>

<div class="container-table mt-5" data-aos="fade-up">
    <div id="agList" class="table-container">
        <div id="agendamentosTable"></div>
    </div>

    <div id="agPaywall" class="empty-state paywall-state d-none" role="alert" aria-live="polite" hidden>
        <i class="fas fa-lock"></i>
        <h3>Agendamentos exclusivos do plano Pro</h3>
        <p id="agPaywallMessage">Agendamentos são exclusivos do plano Pro.</p>
        <button type="button" class="lk-btn btn btn-primary" id="agPaywallCta">
            <i class="fas fa-crown"></i>
            Assinar plano Pro
        </button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
