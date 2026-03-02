/**
 * ============================================================================
 * LUKRATO — Contas Arquivadas Page (Vite Module)
 * ============================================================================
 * Extraído de views/admin/contas/arquivadas.php (inline IIFE)
 *
 * Carrega contas arquivadas, renderiza cards, restaura e exclui contas.
 * ============================================================================
 */

const BASE = (() => {
    const meta = document.querySelector('meta[name="base-url"]')?.content || '';
    return meta.replace(/\/?$/, '/');
})();

const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

// URLs com e sem index.php (fallback)
const apiPretty = (p) => `${BASE}api/${p}`.replace(/\/{2,}/g, '/').replace(':/', '://');
const apiIndex = (p) => `${BASE}index.php/api/${p}`.replace(/\/{2,}/g, '/').replace(':/', '://');

async function fetchAPI(path, opts = {}) {
    let res = await fetch(apiPretty(path), opts);
    if (res.status === 404) res = await fetch(apiIndex(path), opts);
    return res;
}

const grid = document.getElementById('archivedGrid');
const totalArquivadas = document.getElementById('totalArquivadas');
const saldoArquivado = document.getElementById('saldoArquivado');

async function safeJson(res) {
    try { return await res.json(); }
    catch { return null; }
}

function formatMoneyBR(v) {
    try {
        return Number(v).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    } catch {
        return (Math.round((+v || 0) * 100) / 100).toFixed(2).replace('.', ',');
    }
}

function escapeHTML(s = '') {
    return String(s).replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;',
        '"': '&quot;', "'": '&#39;'
    }[m]));
}

let _rows = [];

function updateStats(rows) {
    const total = rows?.length || 0;
    const saldo = (rows || []).reduce((sum, a) => {
        const val = (typeof a.saldoAtual === 'number') ? a.saldoAtual : (a.saldoInicial || 0);
        return sum + val;
    }, 0);
    totalArquivadas.textContent = total;
    saldoArquivado.textContent = `R$ ${formatMoneyBR(saldo)}`;
}

function renderCards(rows) {
    grid.innerHTML = '';
    if (!rows || !rows.length) {
        grid.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1;">
                <div class="empty-icon">
                    <i data-lucide="archive"></i>
                </div>
                <h3>Nenhuma conta arquivada</h3>
                <p>Quando você arquivar uma conta, ela aparecerá aqui</p>
            </div>
        `;
        updateStats([]);
        return;
    }
    updateStats(rows);

    for (const c of rows) {
        const saldo = (typeof c.saldoAtual === 'number') ? c.saldoAtual : (c.saldoInicial ?? 0);
        const saldoClass = saldo >= 0 ? 'positive' : 'negative';

        const instituicao = c.instituicao_financeira || {};
        const instituicaoNome = instituicao.nome || c.instituicao || 'Sem instituição';
        const logoUrl = instituicao.logo_url || `${BASE}assets/img/banks/default.svg`;
        const corPrimaria = instituicao.cor_primaria || '#95a5a6';

        const card = document.createElement('div');
        card.setAttribute('data-aos', 'flip-left');
        card.className = 'account-card archived-card';
        card.innerHTML = `
            <div class="account-header" style="background: ${corPrimaria};">
                <div class="account-logo">
                    <img src="${logoUrl}" alt="${escapeHTML(c.nome || '')}" />
                </div>
            </div>
            <div class="account-body" style="position: relative;">
                <span class="acc-badge inactive" style="position: absolute; top: 1rem; right: 1rem; background: rgba(0,0,0,0.6); color: white; border: 1px solid rgba(255,255,255,0.2); z-index: 10;">
                    <i data-lucide="archive"></i>
                    Arquivada
                </span>
                <h3 class="account-name">${escapeHTML(c.nome || '')}</h3>
                <div class="account-institution">${escapeHTML(instituicaoNome)}</div>
                <div class="account-balance ${saldoClass}">
                    R$ ${formatMoneyBR(saldo)}
                </div>
                <div class="acc-actions">
                    <button class="btn-action btn-restore" data-id="${c.id}" title="Restaurar conta">
                        <i data-lucide="undo-2"></i>
                        <span>Restaurar</span>
                    </button>
                    <button class="btn-action btn-delete" data-id="${c.id}" title="Excluir permanentemente">
                        <i data-lucide="trash-2"></i>
                        <span>Excluir</span>
                    </button>
                </div>
            </div>
        `;
        grid.appendChild(card);
    }
}

grid?.addEventListener('click', (e) => {
    const bRestore = e.target.closest('.btn-restore');
    const bDelete = e.target.closest('.btn-delete');

    if (bRestore) handleRestore(Number(bRestore.dataset.id));
    if (bDelete) handleHardDelete(Number(bDelete.dataset.id));
});

async function handleRestore(id) {
    const conta = _rows.find(c => c.id === id);
    const nomeConta = conta ? conta.nome : 'esta conta';

    const result = await Swal.fire({
        title: 'Restaurar conta?',
        html: `Deseja realmente restaurar <strong>${nomeConta}</strong>?<br><small class="text-muted">A conta voltará a aparecer na lista ativa.</small>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#e67e22',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i data-lucide="undo-2"></i> Sim, restaurar',
        cancelButtonText: '<i data-lucide="x"></i> Cancelar',
        reverseButtons: true,
        buttonsStyling: true
    });

    if (!result.isConfirmed) return;

    try {
        const res = await fetchAPI(`accounts/${id}/restore`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: CSRF ? { 'X-CSRF-TOKEN': CSRF } : {}
        });
        if (!res.ok) throw new Error('Falha ao restaurar');

        Swal.fire({
            title: 'Restaurada!',
            text: 'A conta foi restaurada com sucesso.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
        await load();
    } catch (err) {
        console.error(err);
        Swal.fire({
            title: 'Erro!',
            text: err.message || 'Falha ao restaurar.',
            icon: 'error',
            confirmButtonColor: '#e67e22'
        });
    }
}

async function handleHardDelete(id, nome = '') {
    const conta = _rows.find(c => c.id === id);
    const nomeConta = conta ? conta.nome : nome || 'esta conta';

    const ok = await Swal.fire({
        title: 'Excluir permanentemente?',
        html: `Tem certeza que deseja excluir <strong>${nomeConta}</strong>?<br><small class="text-muted" style="color: #dc3545;">Esta ação não pode ser desfeita!</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i data-lucide="trash-2"></i> Sim, excluir',
        cancelButtonText: '<i data-lucide="x"></i> Cancelar',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        reverseButtons: true,
        buttonsStyling: true
    });
    if (!ok.isConfirmed) return;

    try {
        const res = await fetchAPI(`accounts/${id}/delete`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                ...(CSRF ? { 'X-CSRF-TOKEN': CSRF } : {})
            },
            body: JSON.stringify({ force: false })
        });

        if (res.status === 422) {
            const data = await safeJson(res);

            if (data?.status === 'confirm_delete') {
                const origem = data?.counts?.origem ?? 0;
                const destino = data?.counts?.destino ?? 0;
                const total = data?.counts?.total ?? (origem + destino);

                const confirm = await Swal.fire({
                    title: 'Excluir conta e TODOS os lançamentos?',
                    html: `
                        <div style="text-align:left">
                            <p>A conta <b>${escapeHTML(nomeConta)}</b> possui lançamentos vinculados.</p>
                            <ul style="margin:6px 0 0 18px">
                                <li>Como origem: <b>${origem}</b></li>
                                <li>Como destino: <b>${destino}</b></li>
                                <li>Total: <b>${total}</b></li>
                            </ul>
                            <p style="margin-top:10px">Deseja continuar e excluir <b>TUDO</b>?</p>
                        </div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Excluir tudo',
                    cancelButtonText: 'Manter arquivada',
                    reverseButtons: true
                });

                if (!confirm.isConfirmed) {
                    await Swal.fire({ icon: 'info', title: 'Mantida', text: 'A conta continuará arquivada.' });
                    return;
                }

                const res2 = await fetchAPI(`accounts/${id}/delete?force=1`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        ...(CSRF ? { 'X-CSRF-TOKEN': CSRF } : {})
                    },
                    body: JSON.stringify({ force: true })
                });
                if (!res2.ok) {
                    const err2 = await safeJson(res2);
                    throw new Error(err2?.message || `HTTP ${res2.status}`);
                }

                await Swal.fire({ icon: 'success', title: 'Excluída', text: 'Conta e lançamentos removidos.' });
                await load();
                return;
            }

            const err = await safeJson(res);
            throw new Error(err?.message || 'Não foi possível excluir.');
        }

        if (!res.ok) {
            const err = await safeJson(res);
            throw new Error(err?.message || `HTTP ${res.status}`);
        }

        await Swal.fire({ icon: 'success', title: 'Excluída', text: 'Conta removida com sucesso.' });
        await load();

    } catch (err) {
        console.error(err);
        Swal.fire('Erro', err.message || 'Falha ao excluir conta.', 'error');
    }
}

async function load() {
    try {
        grid.innerHTML = `
            <div class="acc-skeleton"></div>
            <div class="acc-skeleton"></div>
            <div class="acc-skeleton"></div>`;

        const ym = new Date().toISOString().slice(0, 7);
        const res = await fetchAPI(`accounts?archived=1&with_balances=1&month=${ym}`);
        const ct = res.headers.get('content-type') || '';

        if (!res.ok) {
            let msg = `HTTP ${res.status}`;
            if (ct.includes('application/json')) {
                const j = await res.json().catch(() => ({}));
                msg = j?.message || msg;
            } else {
                const t = await res.text();
                msg = t.slice(0, 200);
            }
            throw new Error(msg);
        }

        if (!ct.includes('application/json')) {
            const t = await res.text();
            throw new Error('Resposta não é JSON. Prévia: ' + t.slice(0, 120));
        }

        const data = await res.json();
        _rows = Array.isArray(data) ? data : [];
        renderCards(_rows);
    } catch (err) {
        console.error(err);
        grid.innerHTML = `<div class="lk-empty">Erro ao carregar.</div>`;
        updateStats([]);
        Swal.fire('Erro', err.message || 'Não foi possível carregar as contas arquivadas.', 'error');
    }
}

load();
