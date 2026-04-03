<!-- Descrição -->
<div class="info-card p-3 rounded-3 mt-3"
    style="background: var(--color-bg); border: 1px solid var(--color-card-border);" id="viewLancDescricaoCard">
    <h6 class="mb-2" style="color: var(--color-primary);"><i data-lucide="align-left" class="me-2"></i>Descrição</h6>
    <p id="viewLancDescricao" class="mb-0"></p>
</div>

<!-- Parcelamento -->
<div class="info-card p-3 rounded-3 mt-3"
    style="background: var(--color-surface-muted); border: 1px solid var(--color-card-border); display: none;"
    id="viewLancParcelamentoCard">
    <h6 class="mb-2" style="color: var(--color-primary);"><i data-lucide="layers" class="me-2"></i>Parcelamento</h6>
    <div class="d-flex justify-content-between">
        <span class="">Parcela:</span>
        <strong id="viewLancParcela"></strong>
    </div>
</div>

<!-- Lembrete -->
<div class="info-card p-3 rounded-3 mt-3"
    style="background: var(--color-surface-muted); border: 1px solid var(--color-card-border); display: none;"
    id="viewLancLembreteCard">
    <h6 class="mb-2" style="color: var(--color-primary);"><i data-lucide="bell" class="me-2"></i>Lembrete</h6>
    <div class="d-flex justify-content-between">
        <span>Antecedência:</span>
        <strong id="viewLancLembreteTempo"></strong>
    </div>
    <div class="d-flex justify-content-between mt-1">
        <span>Canais:</span>
        <strong id="viewLancLembreteCanais"></strong>
    </div>
</div>
</div>