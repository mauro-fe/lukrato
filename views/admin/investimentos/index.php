<?php
if (!isset($investments))      $investments = [];
if (!isset($totalInvested))    $totalInvested = 0.0;
if (!isset($currentValue))     $currentValue = 0.0;
if (!isset($profit))           $profit = ($currentValue - $totalInvested);
if (!isset($profitPercentage)) $profitPercentage = ($totalInvested > 0 ? (($profit / $totalInvested) * 100) : 0.0);
if (!isset($statsByCategory))  $statsByCategory = [];
if (!isset($categories))       $categories = [];
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-investimentos-index.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-partials-modals-modal_investimentos.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/css/tabulator.min.css">

<div class="main-content">
    <div class="page-header" data-aos="fade-up">
        <button type=" button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-investimentos"
            title="Adicionar investimento">
            <i class="fa-solid fa-plus"></i> Novo investimento
        </button>
    </div>

    <section class="stats-grid">
        <div class="stat-card" data-aos="flip-left">
            <div class="stat-ico blue"><i class="fa-solid fa-wallet"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Investido</span>
                <span class="stat-value">R$ <?= number_format((float)$totalInvested, 2, ',', '.') ?></span>
            </div>
        </div>

        <div class="stat-card" data-aos="flip-left">
            <div class="stat-ico green"><i class="fa-solid fa-arrow-trend-up"></i></div>
            <div class="stat-info">
                <span class="stat-label">Valor Atual</span>
                <span class="stat-value">R$ <?= number_format((float)$currentValue, 2, ',', '.') ?></span>
            </div>
        </div>

        <div class="stat-card" data-aos="flip-left">
            <div class="stat-ico orange"><i class="fa-solid fa-briefcase"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total de Ativos</span>
                <span class="stat-value"><?= (is_array($investments) ? count($investments) : 0) ?></span>
            </div>
        </div>
    </section>

    <section class="content-grid">
        <div class="card" data-aos="flip-left">
            <div class="card-header">
                <h3>Distribuição por Categoria</h3>
            </div>
            <div class="card-body">
                <div class="chart-wrap"><canvas id="categoryChart"></canvas></div>
            </div>
        </div>

        <div class="card" data-aos="flip-right">
            <div class="card-header">
                <h3>Resumo por Categoria</h3>
            </div>
            <div class="card-body">
                <div class="cat-list">
                    <?php foreach ($statsByCategory as $c): ?>
                    <div class="cat-row">
                        <div class="cat-left">
                            <span class="cat-dot"
                                style="background:<?= htmlspecialchars($c['color'] ?? '#64748b') ?>"></span>
                            <?= htmlspecialchars($c['category'] ?? '-') ?>
                        </div>
                        <div class="cat-val">
                            R$ <?= number_format((float)($c['value'] ?? 0), 2, ',', '.') ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($statsByCategory)): ?>
                    <div class="empty-state">Nenhuma categoria para exibir.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card full-width" data-aos="zoom-in">
            <div class="card-header">
                <h3>Meus Investimentos</h3>
            </div>
            <div class="card-body">
                <?php if (empty($investments)): ?>
                <div class="empty-state">
                    <div>Nenhum investimento cadastrado ainda.</div>
                    <button class="btn-invest" data-bs-toggle="modal" data-bs-target="#modal-investimentos">
                        <i class="fa-solid fa-plus"></i> Adicionar primeiro investimento
                    </button>
                </div>
                <?php else: ?>
                <div class="container-table">
                    <section class="table-container">
                        <div id="tabLancamentos"></div>
                    </section>

                    <section class="invest-cards" id="investCards"></section>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php if (defined('BASE_PATH')): ?>
<?php include BASE_PATH . '/views/admin/partials/modals/modal_investimentos.php'; ?>
<?php include BASE_PATH . '/views/admin/partials/modals/modal_transacao_investimento.php'; ?>
<?php endif; ?>

<script>
(function() {
    const el = document.getElementById('categoryChart');
    if (!el) return;

    const cat = <?= json_encode($statsByCategory, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || [];
    const labels = cat.map(c => c.category || '-');
    const values = cat.map(c => Number(c.value || 0));
    const colors = cat.map(c => c.color || '#64748b');
    const total = values.reduce((a, b) => a + b, 0);
    let categoryChart = null;

    const renderCategoryChart = () => {
        if (categoryChart) {
            categoryChart.destroy();
            categoryChart = null;
        }

        const css = getComputedStyle(document.documentElement);
        const textColor = (css.getPropertyValue('--color-text') || '#e5e7eb').trim();
        const borderColor = (css.getPropertyValue('--color-surface') || '#0b1220').trim();

        categoryChart = new Chart(el.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor,
                    hoverOffset: 6,
                    cutout: '64%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: textColor || '#e5e7eb',
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                const v = ctx.parsed;
                                const p = total > 0 ? (v / total) * 100 : 0;
                                return `${ctx.label}: ${v.toLocaleString('pt-BR',{style:'currency',currency:'BRL'})} (${p.toFixed(2)}%)`;
                            }
                        }
                    }
                }
            }
        });
    };

    renderCategoryChart();
    document.addEventListener('lukrato:theme-changed', renderCategoryChart);
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    let raw = <?= json_encode($investments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || [];
    if (!Array.isArray(raw)) raw = [];

    const escapeHtml = (v) => String(v ?? '').replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[ch]);

    const mapInvest = (inv) => {
        const quantity = Number(inv.quantidade ?? inv.quantity ?? 0);
        const avgPrice = Number(inv.preco_medio ?? inv.avg_price ?? 0);
        const currentPrice = Number(inv.preco_atual ?? inv.current_price ?? avgPrice);
        const total = inv.valor_atual !== undefined ? Number(inv.valor_atual) : quantity * currentPrice;

        return {
            id: Number(inv.id ?? 0),
            nome: inv.nome ?? inv.name ?? '-',
            ticker: inv.ticker ?? '-',
            categoria: inv.categoria_nome ?? inv.category_name ?? '-',
            cor: inv.cor ?? inv.color ?? '#475569',
            quantidade: quantity,
            precoMedio: avgPrice,
            precoAtual: currentPrice,
            valorTotal: total
        };
    };

    async function fetchInvestimentos() {
        try {
            const res = await fetch(`${BASE_URL}api/investimentos`);
            const json = await res.json().catch(() => ({}));
            if (!res.ok || json.error) {
                throw new Error(json.message || 'Falha ao carregar investimentos');
            }

            // API padroniza como { status, message, data: { data: [...] } }
            const payload = Array.isArray(json.data?.data)
                ? json.data.data
                : Array.isArray(json.data)
                    ? json.data
                    : Array.isArray(json)
                        ? json
                        : null;

            if (!payload) {
                throw new Error('Dados de investimentos em formato inesperado');
            }

            return payload;
        } catch (err) {
            console.error(err);
            toast(err.message || 'Falha ao carregar investimentos', 'error');
            return [];
        }
    }

    if (raw.length === 0) {
        raw = await fetchInvestimentos();
    }

    const data = raw.map(mapInvest);
    const tabEl = document.getElementById('tabLancamentos');

    if (tabEl && window.innerWidth > 768 && window.Tabulator) {
        new Tabulator(tabEl, {
            data,
            layout: 'fitDataStretch',
            responsiveLayout: false,
            columnDefaults: {
                headerHozAlign: 'left',
                resizable: false
            },
            columns: [{
                    title: 'Nome',
                    field: 'nome',
                    minWidth: 100,
                    formatter: (cell) =>
                        `<strong>${escapeHtml(cell.getValue() ?? '-')}</strong>`
                },
                {
                    title: 'Categoria',
                    field: 'categoria',
                    minWidth: 140,
                    formatter: (cell) => {
                        const cor = escapeHtml(cell.getRow().getData().cor || '#475569');
                        const cat = escapeHtml(cell.getValue() || '-');
                        return `<span class="badge" style="background:${cor}">${cat}</span>`;
                    }
                },
                {
                    title: 'Ticker',
                    field: 'ticker',
                    minWidth: 110
                },
                {
                    title: 'Quantidade',
                    field: 'quantidade',
                    hozAlign: 'right',
                    formatter: (cell) => {
                        return Number(cell.getValue() || 0).toLocaleString('pt-BR', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    }
                },
                {
                    title: 'Preco medio',
                    field: 'precoMedio',
                    hozAlign: 'right',
                    formatter: (cell) => {
                        return `R$ ${Number(cell.getValue() || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    }
                },
                {
                    title: 'Preco atual',
                    field: 'precoAtual',
                    hozAlign: 'right',
                    formatter: (cell) => {
                        return `R$ ${Number(cell.getValue() || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    }
                },
                {
                    title: 'Valor total',
                    field: 'valorTotal',
                    hozAlign: 'right',
                    formatter: (cell) => {
                        return `<strong>R$ ${Number(cell.getValue() || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>`;
                    }
                },
                {
                    title: 'Ações',
                    field: 'id',
                    hozAlign: 'center',
                    width: 210,
                    formatter: (cell) => {
                        const row = cell.getRow().getData();
                        const id = cell.getValue();
                        const base = '<?= BASE_URL ?>';
                        const nome = escapeHtml(row.nome || '');
                        const ticker = escapeHtml(row.ticker || '');
                        return `
                        <div class="action-buttons">
                            <a class="btn-icon success" href="#" data-acao="compra" data-id="${id}" data-nome="${nome}" data-ticker="${ticker}" title="Comprar mais">
                                <i class="fa-solid fa-cart-plus"></i>
                            </a>
                            <a class="btn-icon danger" href="#" data-acao="venda" data-id="${id}" data-nome="${nome}" data-ticker="${ticker}" title="Vender">
                                <i class="fa-solid fa-hand-holding-dollar"></i>
                            </a>
                            <a class="btn-icon" href="${base}investimentos/edit/${id}" data-edit data-id="${id}" title="Editar">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            <a class="btn-icon neutral" href="${base}investimentos/delete/${id}" data-delete data-id="${id}" title="Excluir">
                                <i class="fa-regular fa-trash-can"></i>
                            </a>
                        </div>
                    `;
                    }
                }
            ]
        });
    }

    const cardsContainer = document.getElementById('investCards');
    if (cardsContainer) {
        const header = `
            <div class="invest-cards-header">
                <span>Nome</span>
                <span>Categoria</span>
                <span>Ações</span>
            </div>
        `;
        const cards = data.map((inv) => {
            const base = '<?= BASE_URL ?>';
            return `
                <article class="invest-card" data-id="${inv.id}" aria-expanded="false">
                    <div class="invest-card-main">
                        <div class="invest-card-name">${inv.nome}</div>
                        <div class="invest-card-cat">
                            <span class="badge" style="background:${inv.cor}">${inv.categoria}</span>
                        </div>
                        <div class="invest-card-actions">
                            <a class="btn-icon success" href="#" data-acao="compra" data-id="${inv.id}" data-nome="${inv.nome}" data-ticker="${inv.ticker}" title="Comprar mais">
                                <i class="fa-solid fa-cart-plus"></i>
                            </a>
                            <a class="btn-icon danger" href="#" data-acao="venda" data-id="${inv.id}" data-nome="${inv.nome}" data-ticker="${inv.ticker}" title="Vender">
                                <i class="fa-solid fa-hand-holding-dollar"></i>
                            </a>
                            <a class="btn-icon" href="${base}investimentos/edit/${inv.id}" data-edit data-id="${inv.id}" title="Editar">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            <a class="btn-icon neutral" href="${base}investimentos/delete/${inv.id}" data-delete data-id="${inv.id}" title="Excluir">
                                <i class="fa-regular fa-trash-can"></i>
                            </a>
                        </div>
                    </div>
                    <button type="button" class="invest-card-toggle" data-invest-toggle aria-expanded="false">
                        <span class="invest-toggle-icon"><i class="fa-solid fa-chevron-right"></i></span>
                        <span class="inv-toggle-text">Ver detalhes</span>
                    </button>
                    <div class="invest-card-details">
                        <div class="invest-card-row"><span class="label">Ticker</span><span class="value">${inv.ticker}</span></div>
                        <div class="invest-card-row"><span class="label">Quantidade</span><span class="value">${inv.quantidade.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span></div>
                        <div class="invest-card-row"><span class="label">Preco medio</span><span class="value">R$ ${inv.precoMedio.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span></div>
                        <div class="invest-card-row"><span class="label">Preco atual</span><span class="value">R$ ${inv.precoAtual.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span></div>
                        <div class="invest-card-row"><span class="label">Valor total</span><span class="value">R$ ${inv.valorTotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span></div>
                    </div>
                </article>
            `;
        }).join('');

        cardsContainer.innerHTML = header + cards;
    }
});

// Toggle de detalhes
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-invest-toggle]');
    if (!btn) return;

    const card = btn.closest('.invest-card');
    if (card) {
        const details = card.querySelector('.invest-card-details');
        const expanded = card.classList.toggle('is-expanded');
        btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        if (details) details.style.display = expanded ? 'grid' : 'none';
        const textEl = btn.querySelector('.inv-toggle-text');
        if (textEl) textEl.textContent = expanded ? 'Fechar detalhes' : 'Ver detalhes';
    }
});
</script>

<script>
// Handlers em fase de captura para sobrescrever listeners antigos
const BASE_URL = '<?= BASE_URL ?>';
const ui = {};

function refreshUiColors() {
    const cssVars = getComputedStyle(document.documentElement);
    ui.bg = (cssVars.getPropertyValue('--color-surface') || '#1c2c3c').trim();
    ui.fg = (cssVars.getPropertyValue('--color-text') || '#ffffff').trim();
    ui.ring = (cssVars.getPropertyValue('--ring') || 'rgba(230,126,34,.22)').trim();
    ui.danger = (cssVars.getPropertyValue('--color-danger') || '#e74c3c').trim();
}
refreshUiColors();
document.addEventListener('lukrato:theme-changed', refreshUiColors);

function toast(title, type = 'success') {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: type,
        title,
        showConfirmButton: false,
        timer: 2200,
        timerProgressBar: true,
        background: ui.bg,
        color: ui.fg,
        didOpen: (t) => {
            t.addEventListener('mouseenter', Swal.stopTimer);
            t.addEventListener('mouseleave', Swal.resumeTimer);
            t.style.boxShadow = `0 0 0 2px ${ui.ring}`;
            t.style.borderRadius = '12px';
        }
    });
}

const transacaoModalEl = document.getElementById('modal-transacao-investimento');
const transacaoForm = document.getElementById('form-transacao-investimento');
const transacaoInvestId = document.getElementById('transacao_investimento_id');
const transacaoTitle = document.getElementById('modalTransacaoLabel');
const transacaoInfo = document.getElementById('modalTransacaoInvestLabel');

function openTransacaoModal(id, tipo = 'compra', nome = '', ticker = '') {
    if (!transacaoForm || !transacaoModalEl || !id) return;

    transacaoForm.dataset.investimentoId = id;
    if (transacaoInvestId) transacaoInvestId.value = id;

    const isVenda = tipo === 'venda';
    const radioCompra = document.getElementById('tipo_compra');
    const radioVenda = document.getElementById('tipo_venda');
    if (radioCompra && radioVenda) {
        radioCompra.checked = !isVenda;
        radioVenda.checked = isVenda;
    }

    if (transacaoTitle) transacaoTitle.textContent = isVenda ? 'Registrar venda' : 'Registrar compra';
    if (transacaoInfo) {
        const ident = [nome, ticker ? `(${ticker})` : ''].filter(Boolean).join(' ');
        transacaoInfo.textContent = ident || 'Investimento selecionado';
    }

    const modal = bootstrap.Modal.getOrCreateInstance(transacaoModalEl);
    modal.show();
}

document.addEventListener('click', (e) => {
    const actionBtn = e.target.closest('[data-acao]');
    if (!actionBtn) return;
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    const tipo = actionBtn.dataset.acao === 'venda' ? 'venda' : 'compra';
    const id = actionBtn.dataset.id;
    openTransacaoModal(id, tipo, actionBtn.dataset.nome || '', actionBtn.dataset.ticker || '');
}, true);

transacaoModalEl?.addEventListener('hidden.bs.modal', () => {
    if (!transacaoForm) return;
    transacaoForm.reset();
    transacaoForm.dataset.investimentoId = '';
    const dataInput = document.getElementById('data_transacao');
    if (dataInput) dataInput.value = new Date().toISOString().slice(0, 10);
});

transacaoForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const id = form.dataset.investimentoId;
    if (!id) return;

    const body = new URLSearchParams();
    const fd = new FormData(form);
    fd.forEach((v, k) => body.append(k, v));

    try {
        const res = await fetch(`${BASE_URL}api/investimentos/${id}/transacoes`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body
        });
        const json = await res.json().catch(() => ({}));

        if (!res.ok || json.error) {
            let msg = 'Erro ao registrar transação';

            // 1º: tenta pegar uma mensagem específica de erro de campo
            if (json.errors && typeof json.errors === 'object') {
                if (json.errors.quantidade) {
                    msg = json.errors.quantidade;
                } else {
                    const keys = Object.keys(json.errors);
                    if (keys.length > 0 && json.errors[keys[0]]) {
                        msg = json.errors[keys[0]];
                    }
                }
            }
            // 2º: se não tiver errors “bonitos”, usa a mensagem geral
            else if (json.message) {
                msg = json.message;
            }

            throw new Error(msg);
        }

        toast('Transação salva!');
        setTimeout(() => window.location.reload(), 900);
    } catch (err) {
        console.error(err);
        toast(err.message || 'Falha ao registrar transação', 'error');
    }
});

let editingId = null;

// Editar: captura ações e impede handlers antigos
document.addEventListener('click', async (e) => {
    const a = e.target.closest('a[data-edit]');
    if (!a) return;
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    const id = a.dataset.id;
    if (!id) return;
    editingId = id;

    try {
        const res = await fetch(`${BASE_URL}api/investimentos/${id}`);
        const json = await res.json();
        if (!res.ok || json.error) throw new Error(json.message || 'Falha ao carregar investimento');
        const d = json.data ?? json;

        const form = document.getElementById('form-investimento');
        form.action = `${BASE_URL}api/investimentos/${id}/update`;
        form.method = 'POST';
        document.getElementById('modalInvestimentosLabel').textContent = 'Editar Investimento';

        document.getElementById('category_id').value = String(d.categoria_id || '');
        document.getElementById('name').value = d.nome || '';
        document.getElementById('ticker').value = d.ticker || '';
        document.getElementById('quantity').value = (d.quantidade ?? '').toString();
        document.getElementById('avg_price').value = (d.preco_medio ?? '').toString();
        document.getElementById('current_price').value = (d.preco_atual ?? '').toString();
        const notesEl = document.getElementById('notes');
        if (notesEl) notesEl.value = d.observacoes || '';

        const modalEl = document.getElementById('modal-investimentos');
        const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        bsModal.show();

        document.getElementById('avg_price').dispatchEvent(new Event('input'));
    } catch (err) {
        console.error(err);
        toast('Falha ao carregar dados do investimento', 'error');
    }
}, true);

// Reset ao fechar
document.getElementById('modal-investimentos')?.addEventListener('hidden.bs.modal', () => {
    const form = document.getElementById('form-investimento');
    form.reset();
    form.action = `${BASE_URL}api/investimentos`;
    form.method = 'POST';
    document.getElementById('modalInvestimentosLabel').textContent = 'Novo Investimento';
    editingId = null;
});

// Submit via fetch
document.getElementById('form-investimento')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const fd = new FormData(form);
    const body = new URLSearchParams();
    for (const [k, v] of fd.entries()) body.append(k, v);
    try {
        const res = await fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok || json.error) throw new Error(json.message || 'Erro ao salvar');
        toast(editingId ? 'Investimento atualizado!' : 'Investimento criado!');
        setTimeout(() => window.location.reload(), 900);
    } catch (err) {
        console.error(err);
        toast(err.message || 'Falha ao salvar', 'error');
    }
});

// Excluir via fetch com captura
document.addEventListener('click', (e) => {
    const a = e.target.closest('a[data-delete]');
    if (!a) return;
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    const id = a.dataset.id;
    Swal.fire({
        icon: 'warning',
        title: 'Excluir investimento?',
        text: 'Esta acao nao podera ser desfeita.',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: ui.danger,
        background: ui.bg,
        color: ui.fg
    }).then(async (r) => {
        if (!r.isConfirmed) return;
        try {
            const res = await fetch(`${BASE_URL}api/investimentos/${id}/delete`, {
                method: 'POST'
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok || json.error) throw new Error(json.message || 'Erro ao excluir');
            toast('Excluido com sucesso');
            setTimeout(() => window.location.reload(), 900);
        } catch (err) {
            console.error(err);
            toast(err.message || 'Falha ao excluir', 'error');
        }
    });
}, true);
</script>
<?php if (isset($_SESSION['message'])): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const iconMap = {
        success: 'success',
        error: 'error',
        danger: 'error',
        warning: 'warning',
        info: 'info'
    };
    const msg = <?= json_encode($_SESSION['message'], JSON_UNESCAPED_UNICODE) ?>;
    const type = <?= json_encode($_SESSION['message_type'] ?? 'info', JSON_UNESCAPED_UNICODE) ?>;

    const css = getComputedStyle(document.documentElement);
    const bg = (css.getPropertyValue('--color-surface') || '#1c2c3c').trim();
    const fg = (css.getPropertyValue('--color-text') || '#ffffff').trim();
    const ring = (css.getPropertyValue('--ring') || 'rgba(230,126,34,.22)').trim();

    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: iconMap[type] ?? 'info',
        title: msg,
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
        background: bg,
        color: fg,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
            toast.style.boxShadow = `0 0 0 2px ${ring}`;
            toast.style.borderRadius = '12px';
        }
    });
});
</script>
<?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
<?php endif; ?>

<script>
document.addEventListener('click', (e) => {
    const a = e.target.closest('a[data-delete]');
    if (!a) return;
    e.preventDefault();

    const css = getComputedStyle(document.documentElement);
    const bg = (css.getPropertyValue('--color-surface') || '#1c2c3c').trim();
    const fg = (css.getPropertyValue('--color-text') || '#ffffff').trim();
    const danger = (css.getPropertyValue('--color-danger') || '#e74c3c').trim();

    Swal.fire({
        icon: 'warning',
        title: 'Excluir investimento?',
        text: 'Esta acao nao podera ser desfeita.',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: danger,
        background: bg,
        color: fg
    }).then((r) => {
        if (r.isConfirmed) window.location.href = a.href;
    });
});
</script>
