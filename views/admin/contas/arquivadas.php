<div class="container" id="archivedAccountsPage">
    <div class="lk-acc-title d-flex align-items-center gap-3">
        <h3 class="mb-0">Contas arquivadas</h3>
        <a class="btn btn-light" href="<?= BASE_URL ?>contas">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- Estatísticas -->
    <div class="stats-grid pt-5">
        <div class="stat-card">
            <div class="stat-value" id="totalArquivadas">0</div>
            <div class="stat-label">Contas Arquivadas</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="saldoArquivado">R$ 0,00</div>
            <div class="stat-label">Saldo (arquivadas)</div>
        </div>
    </div>

    <div class="lk-card mt-4">
        <div class="acc-grid" id="archivedGrid" aria-live="polite">
            <div class="acc-skeleton"></div>
            <div class="acc-skeleton"></div>
            <div class="acc-skeleton"></div>
        </div>
    </div>
</div>

<script>
(function initArchivedAccountsPage() {
    const BASE = (document.querySelector('meta[name="base-url"]')?.content || location.origin + '/');
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // URLs com e sem index.php (fallback)
    const apiPretty = (p) => `${BASE}api/${p}`.replace(/\/{2,}/g, '/').replace(':/', '://');
    const apiIndex = (p) => `${BASE}index.php/api/${p}`.replace(/\/{2,}/g, '/').replace(':/', '://');
    async function fetchAPI(path, opts = {}) {
        let res = await fetch(apiPretty(path), opts);
        if (res.status === 404) res = await fetch(apiIndex(path), opts);
        return res;
    }

    // DOM
    const grid = document.getElementById('archivedGrid');
    const totalArquivadas = document.getElementById('totalArquivadas');
    const saldoArquivado = document.getElementById('saldoArquivado');

    // Helpers
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
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        } [m]));
    }

    // State
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
            grid.innerHTML = `<div class="lk-empty">Nenhuma conta arquivada.</div>`;
            updateStats([]);
            return;
        }
        updateStats(rows);

        for (const c of rows) {
            const saldo = (typeof c.saldoAtual === 'number') ? c.saldoAtual : (c.saldoInicial ?? 0);
            const card = document.createElement('div');
            card.className = 'acc-card';
            card.innerHTML = `
      <div>
        <div class="acc-head">
          <div class="acc-dot"></div>
          <div>
            <div class="acc-name">${escapeHTML(c.nome||'')}</div>
            <div class="acc-sub">${escapeHTML(c.instituicao||'—')}</div>
          </div>
          <span class="acc-badge inactive">Arquivada</span>
        </div>
        <div class="acc-balance">R$ ${formatMoneyBR(saldo)}</div>
      </div>
      <div class="acc-actions">
        <button class="btn btn-ghost btn-restore" data-id="${c.id}">
          <i class="fas fa-undo"></i> Restaurar
        </button>
        <button class="btn btn-ghost btn-hard-delete" data-id="${c.id}">
          <i class="fas fa-trash"></i> Excluir
        </button>
      </div>
    `;
            grid.appendChild(card);
        }
    }


    grid?.addEventListener('click', (e) => {
        const bRestore = e.target.closest('.btn-restore');
        const bDelete = e.target.closest('.btn-hard-delete');

        if (bRestore) {
            const id = Number(bRestore.dataset.id);
            handleRestore(id);
        }
        if (bDelete) {
            const id = Number(bDelete.dataset.id);
            handleHardDelete(id);
        }
    });

    async function handleRestore(id) {
        try {
            const res = await fetchAPI(`accounts/${id}/restore`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: CSRF ? {
                    'X-CSRF-TOKEN': CSRF
                } : {}
            });
            if (!res.ok) throw new Error('Falha ao restaurar');
            Swal.fire('Pronto!', 'Conta restaurada.', 'success');
            await load();
        } catch (err) {
            console.error(err);
            Swal.fire('Erro', err.message || 'Falha ao restaurar.', 'error');
        }
    }

    async function handleHardDelete(id) {
        const ok = await Swal.fire({
            title: 'Excluir permanentemente?',
            text: 'Esta ação não pode ser desfeita.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir',
            confirmButtonColor: '#e74c3c'
        });
        if (!ok.isConfirmed) return;

        try {
            const res = await fetchAPI(`accounts/${id}/delete`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    ...(CSRF ? {
                        'X-CSRF-TOKEN': CSRF
                    } : {})
                }
            });

            const ct = res.headers.get('content-type') || '';
            let payload = {};
            if (ct.includes('application/json')) {
                payload = await res.json().catch(() => ({}));
            }

            if (!res.ok || payload?.status === 'error') {
                throw new Error(payload?.message || `HTTP ${res.status}`);
            }

            Swal.fire('Excluída!', 'Conta removida definitivamente.', 'success');
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
})();
</script>