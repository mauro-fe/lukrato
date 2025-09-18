<section class="container">

    <h3>Lançamentos</h3>

    <header class="dash-lk-header">

        <div class="header-left">

            <div class="month-selector">

                <div class="lk-period">

                    <button class="month-nav-btn" id="prevMonth" type="button" aria-label="Mês anterior">

                        <i class="fas fa-chevron-left"></i>

                    </button>

                    <button class="month-dropdown-btn" id="monthDropdownBtn" type="button" data-bs-toggle="modal"

                        data-bs-target="#monthModal" aria-haspopup="true" aria-expanded="false">

                        <span id="currentMonthText">Carregando...</span>

                        <i class="fas fa-chevron-down"></i>

                    </button>

                    <div class="month-display">

                        <div class="month-dropdown" id="monthDropdown" role="menu"></div>

                    </div>

                    <button class="month-nav-btn" id="nextMonth" type="button" aria-label="Próximo mês">

                        <i class="fas fa-chevron-right"></i>

                    </button>

                </div>

            </div>

        </div>

    </header>

    <div class="header-right">

        <div class="type-filter" role="group" aria-label="Filtro por tipo">

            <label for="filtroTipo" class="sr-only">Tipo</label>

            <select id="filtroTipo" class="lk-select btn btn-primary">

                <option value="">Todos</option>

                <option value="receita">Receitas</option>

                <option value="despesa">Despesas</option>

            </select>

            <button id="btnFiltrar" type="button" class="lk-btn ghost btn">

                <i class="fas fa-filter"></i> Filtrar

            </button>

            <!-- NOVO: ações em massa -->

            <button id="btnExcluirSel" type="button" class="lk-btn danger btn ms-2" disabled>

                <i class="fas fa-trash"></i> Excluir selecionados

            </button>

            <small id="selInfo" class="text-muted ms-2 d-none">

                <span id="selCount">0</span> selecionado(s)

            </small>

        </div>

        <section class="table-container mt-5">

            <table class="lukrato-table" id="tabelaLancamentos">

                <thead>

                    <tr>

                        <!-- NOVO: seleção em massa -->

                        <th class="text-center" style="width:36px">

                            <input type="checkbox" id="chkAll" aria-label="Selecionar todos">

                        </th>

                        <th>Data</th>

                        <th>Tipo</th>

                        <th>Categoria</th>

                        <th>Conta</th>

                        <th>Descrição</th>

                        <th class="text-right">Valor</th>

                        <th style="width:82px">Ações</th>

                    </tr>

                </thead>

                <tbody id="tbodyLancamentos">

                    <tr>

                        <!-- ATUALIZE O COLSPAN PARA 8 -->

                        <td colspan="8" class="text-center">Carregando…</td>

                    </tr>

                </tbody>

            </table>

        </section>

    </div>

</section>

<!-- Modal: Selecionar mês -->

<div class="modal fade" id="monthModal" tabindex="-1" aria-labelledby="monthModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered" style="max-width:520px">

        <div class="modal-content bg-dark text-light border-0 rounded-3">

            <div class="modal-header border-0">

                <h5 class="modal-title" id="monthModalLabel">Selecionar mês</h5>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"

                    aria-label="Fechar"></button>

            </div>

            <div class="modal-body pt-0">

                <!-- Toolbar: Ano + Ações rápidas -->

                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">

                    <div class="btn-group" role="group" aria-label="Navegar entre anos">

                        <button type="button" class="btn btn-outline-light btn-sm" id="mpPrevYear"

                            title="Ano anterior">

                            <i class="fas fa-chevron-left"></i>

                        </button>

                        <span class="px-3 fw-semibold" id="mpYearLabel">2024</span>

                        <button type="button" class="btn btn-outline-light btn-sm" id="mpNextYear"

                            title="Próximo ano">

                            <i class="fas fa-chevron-right"></i>

                        </button>

                    </div>

                    <div class="d-flex gap-2">

                        <button type="button" class="btn btn-outline-light btn-sm" id="mpTodayBtn">Hoje</button>

                        <input type="month" class="form-control form-control-sm bg-dark text-light border-secondary"

                            id="mpInputMonth" style="width:165px">

                    </div>

                </div>

                <!-- Grade de meses -->

                <div id="mpGrid" class="row g-2"></div>

            </div>

            <div class="modal-footer border-0 pt-0">

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>

            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous">

</script>

<script id="lk-lancamentos-loader">
    (() => {
        'use strict';
        if (window.__LK_LANCAMENTOS_LOADER__) return; // evita duplicar
        window.__LK_LANCAMENTOS_LOADER__ = true;

        // ===== Endpoint
        const rawBase = (window.BASE_URL || (location.pathname.includes('/public/') ?
            location.pathname.split('/public/')[0] + '/public/' :
            '/')).replace(/\/?$/, '/');
        const ENDPOINT = `${rawBase}api/lancamentos`; // GET, DELETE; opcional: POST /api/lancamentos/batch-delete

        // ===== DOM
        const $ = (s) => document.querySelector(s);
        const tbody = $('#tbodyLancamentos');
        const selectTipo = $('#filtroTipo');
        const btnFiltrar = $('#btnFiltrar');
        const btnExcluirSel = $('#btnExcluirSel');
        const chkAll = $('#chkAll');
        const selInfo = $('#selInfo');
        const selCountSpan = $('#selCount');

        // ===== Utils
        const fmtMoney = (n) => new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(Number(n || 0));
        const fmtDate = (iso) => {
            if (!iso) return '-';
            const d = new Date(iso);
            return isNaN(d) ? '-' : d.toLocaleDateString('pt-BR');
        };

        const hasSwal = !!window.Swal;
        const ask = async (title, text = '') => {
            if (hasSwal) {
                const r = await Swal.fire({
                    title,
                    text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, excluir',
                    cancelButtonText: 'Cancelar'
                });
                return r.isConfirmed;
            }
            return confirm(title || 'Confirmar exclusão?');
        };
        const toast = (msg, icon = 'success') => {
            if (hasSwal) Swal.fire({
                toast: true,
                position: 'top-end',
                timer: 1800,
                showConfirmButton: false,
                icon,
                title: msg
            });
        };

        // ===== Render
        function renderEmpty() {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">Sem lançamentos para o período</td></tr>`;
            if (btnExcluirSel) btnExcluirSel.disabled = true;
            if (selInfo) selInfo.classList.add('d-none');
            if (chkAll) chkAll.checked = false;
        }

        function updateSelectionInfo() {
            const checks = tbody.querySelectorAll('.row-check:checked');
            const count = checks.length;
            if (selCountSpan) selCountSpan.textContent = String(count);
            if (btnExcluirSel) btnExcluirSel.disabled = count === 0;
            if (selInfo) selInfo.classList.toggle('d-none', count === 0);
            if (chkAll) chkAll.checked = count > 0 && count === tbody.querySelectorAll('.row-check').length;
        }

        function renderRows(items) {
            if (!Array.isArray(items) || items.length === 0) return renderEmpty();
            const rows = items.map(it => `
      <tr data-id="${it.id ?? ''}">
        <td class="text-center" style="width:36px"><input type="checkbox" class="row-check" aria-label="Selecionar"></td>
        <td>${fmtDate(it.data || it.created_at)}</td>
        <td>${(it.tipo||'-').toString().replace(/^./, m=>m.toUpperCase())}</td>
        <td>${it.categoria_nome || it.categoria || '-'}</td>
        <td>${it.conta_nome || it.conta || '-'}</td>
        <td>${it.descricao || '-'}</td>
        <td class="text-end">${fmtMoney(it.valor)}</td>
        <td class="text-nowrap" style="width:120px">
          <button class="btn btn-sm btn-outline-primary me-1" data-action="edit" title="Editar">Editar</button>
          
            <button class="lk-btn danger btn-del" title="Excluir" data-action="delete">
                    <i class="fas fa-trash"></i>
                    </button>
        </td>
      </tr>
    `).join('');
            tbody.innerHTML = rows;
            wireRowEvents();
            updateSelectionInfo();
        }

        // ===== Eventos de linha e seleção
        function wireRowEvents() {
            // delete único (delegação)
            tbody.addEventListener('click', async (ev) => {
                const btn = ev.target.closest('button[data-action="delete"]');
                if (!btn) return;
                const tr = btn.closest('tr');
                const id = tr?.getAttribute('data-id');
                if (!id) return;

                const ok = await ask('Excluir lançamento?', 'Essa ação não pode ser desfeita.');
                if (!ok) return;

                btn.disabled = true;
                const okDel = await apiDeleteOne(id);
                btn.disabled = false;

                if (okDel) {
                    tr.remove();
                    toast('Excluído.');
                    if (!tbody.querySelector('tr')) renderEmpty();
                    updateSelectionInfo();
                } else {
                    toast('Falha ao excluir.', 'error');
                }
            });

            // seleção em massa
            tbody.querySelectorAll('.row-check').forEach(cb => cb.addEventListener('change', updateSelectionInfo));
        }

        chkAll && chkAll.addEventListener('change', () => {
            tbody.querySelectorAll('.row-check').forEach(cb => cb.checked = chkAll.checked);
            updateSelectionInfo();
        });

        // ===== API
        async function fetchLancamentos({
            month,
            tipo = '',
            limit = 500
        }) {
            const qs = new URLSearchParams();
            if (month) qs.set('month', month); // YYYY-MM
            if (tipo) qs.set('tipo', tipo); // receita|despesa
            qs.set('limit', String(limit));
            try {
                const res = await fetch(`${ENDPOINT}?${qs.toString()}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (res.status === 204 || res.status === 404 || !res.ok) return [];
                const data = await res.json().catch(() => null);
                if (Array.isArray(data)) return data;
                if (data && Array.isArray(data.data)) return data.data;
                return [];
            } catch {
                return [];
            }
        }

        async function apiDeleteOne(id) {
            try {
                const res = await fetch(`${ENDPOINT}/${encodeURIComponent(id)}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                return res.ok;
            } catch {
                return false;
            }
        }

        async function apiBulkDelete(ids) {
            // tenta endpoint em lote, se existir
            try {
                const res = await fetch(`${ENDPOINT}delete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        ids
                    })
                });
                if (res.ok) return true;
            } catch {}
            // fallback: um-a-um
            const results = await Promise.all(ids.map(apiDeleteOne));
            return results.every(Boolean);
        }

        // ===== Botão Excluir selecionados
        btnExcluirSel && btnExcluirSel.addEventListener('click', async () => {
            const checks = [...tbody.querySelectorAll('.row-check:checked')];
            if (checks.length === 0) return;

            const ids = checks.map(cb => cb.closest('tr')?.getAttribute('data-id')).filter(Boolean);
            const ok = await ask(`Excluir ${ids.length} lançamento(s)?`, 'Essa ação não pode ser desfeita.');
            if (!ok) return;

            btnExcluirSel.disabled = true;
            const done = await apiBulkDelete(ids);
            btnExcluirSel.disabled = false;

            if (done) {
                ids.forEach(id => tbody.querySelector(`tr[data-id="${CSS.escape(id)}"]`)?.remove());
                toast('Itens excluídos.');
                if (!tbody.querySelector('tr')) renderEmpty();
                updateSelectionInfo();
            } else {
                toast('Alguns itens não foram excluídos.', 'error');
            }
        });

        // ===== Carregar na troca de mês e no filtro
        let timer = null;
        async function load() {
            clearTimeout(timer);
            timer = setTimeout(async () => {
                const month = (window.LukratoHeader?.getMonth?.()) || (new Date()).toISOString().slice(0, 7);
                const tipo = selectTipo?.value || '';
                tbody.innerHTML = `<tr><td colspan="8" class="text-center">Carregando…</td></tr>`;
                const items = await fetchLancamentos({
                    month,
                    tipo,
                    limit: 500
                });
                renderRows(items);
            }, 10);
        }

        document.addEventListener('lukrato:month-changed', load);
        btnFiltrar && btnFiltrar.addEventListener('click', load);

        // primeira carga
        load();
    })();
</script>