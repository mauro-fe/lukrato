<section class="container">
    <h3>Lançamentos</h3>

    <header class="dash-lk-header">
        <div class="header-left">
            <div class="month-selector">
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
        </div>

        <section class="table-container mt-5">
            <table class="lukrato-table" id="tabelaLancamentos">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Categoria</th>
                        <th>Conta</th>
                        <th>Descrição</th>
                        <th class="text-right">Valor</th>
                        <th style="width:82px">Ações</th> <!-- NOVO -->
                    </tr>
                </thead>
                <tbody id="tbodyLancamentos">
                    <tr>
                        <td colspan="6" class="text-center">Carregando…</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>

</section>
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
                        <button type="button" class="btn btn-outline-light btn-sm" id="mpPrevYear" title="Ano anterior">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span class="px-3 fw-semibold" id="mpYearLabel">2024</span>
                        <button type="button" class="btn btn-outline-light btn-sm" id="mpNextYear" title="Próximo ano">
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
<script>
    (() => {
        'use strict';

        const elText = document.getElementById('currentMonthText');
        const btnPrev = document.getElementById('prevMonth');
        const btnNext = document.getElementById('nextMonth');
        const modalEl = document.getElementById('monthModal');

        // elementos do modal
        const mpYearLabel = document.getElementById('mpYearLabel');
        const mpPrevYear = document.getElementById('mpPrevYear');
        const mpNextYear = document.getElementById('mpNextYear');
        const mpGrid = document.getElementById('mpGrid');
        const mpTodayBtn = document.getElementById('mpTodayBtn');
        const mpInput = document.getElementById('mpInputMonth');

        const STORAGE_KEY = 'lukrato.month.dashboard';
        const SHORT = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        const toYM = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
        const monthLabel = ym => {
            const [y, m] = ym.split('-').map(Number);
            return new Date(y, m - 1, 1).toLocaleDateString('pt-BR', {
                month: 'long',
                year: 'numeric'
            });
        };

        // estado
        let state = (sessionStorage.getItem(STORAGE_KEY) || toYM(new Date()));
        let modalYear = Number(state.split('-')[0]) || (new Date()).getFullYear();

        // util
        const setState = (ym, {
            silent = false
        } = {}) => {
            if (!/^\d{4}-\d{2}$/.test(ym)) return;
            state = ym;
            sessionStorage.setItem(STORAGE_KEY, state);
            if (elText) {
                elText.textContent = monthLabel(state);
                elText.setAttribute('data-month', state);
            }
            if (!silent) document.dispatchEvent(new CustomEvent('lukrato:month-changed', {
                detail: {
                    month: state
                }
            }));
        };

        const shiftMonth = (delta) => {
            const [y, m] = state.split('-').map(Number);
            const d = new Date(y, (m - 1) + delta, 1);
            setState(toYM(d));
        };

        // monta grade 12 meses
        const buildGrid = () => {
            if (!mpYearLabel || !mpGrid) return;
            mpYearLabel.textContent = modalYear;
            let html = '';
            for (let i = 0; i < 12; i++) {
                const ym = `${modalYear}-${String(i+1).padStart(2,'0')}`;
                const active = ym === state ? 'btn-warning text-dark fw-bold' : 'btn-outline-light';
                html += `
        <div class="col-4">
          <button type="button" class="mp-month btn w-100 py-3 ${active}" data-val="${ym}">
            ${SHORT[i]}
          </button>
        </div>`;
            }
            mpGrid.innerHTML = html;
            mpGrid.querySelectorAll('.mp-month').forEach(btn => {
                btn.addEventListener('click', () => {
                    setState(btn.getAttribute('data-val'));
                    // fecha modal
                    const inst = bootstrap.Modal.getOrCreateInstance(modalEl);
                    inst?.hide();
                });
            });
        };

        // listeners header
        btnPrev?.addEventListener('click', e => {
            e.preventDefault();
            shiftMonth(-1);
        });
        btnNext?.addEventListener('click', e => {
            e.preventDefault();
            shiftMonth(+1);
        });

        // listeners modal
        modalEl?.addEventListener('show.bs.modal', () => {
            modalYear = Number(state.split('-')[0]) || (new Date()).getFullYear();
            if (mpInput) mpInput.value = state;
            buildGrid();
        });

        mpPrevYear?.addEventListener('click', () => {
            modalYear--;
            buildGrid();
        });
        mpNextYear?.addEventListener('click', () => {
            modalYear++;
            buildGrid();
        });
        mpTodayBtn?.addEventListener('click', () => {
            const now = new Date();
            const todayYM = toYM(new Date(now.getFullYear(), now.getMonth(), 1));
            setState(todayYM);
            bootstrap.Modal.getOrCreateInstance(modalEl)?.hide();
        });
        mpInput?.addEventListener('change', e => {
            const ym = e.target.value;
            if (/^\d{4}-\d{2}$/.test(ym)) {
                setState(ym);
                bootstrap.Modal.getOrCreateInstance(modalEl)?.hide();
            }
        });

        // sincroniza com outras partes
        document.addEventListener('lukrato:month-changed', (e) => {
            const m = e.detail?.month;
            if (!m || m === state) return;
            setState(m, {
                silent: true
            }); // atualiza label sem reemitir
        });

        // API pública para outras partes da página
        window.LukratoHeader = {
            getMonth: () => state,
            setMonth: (ym) => setState(ym)
        };

        // inicializa label + emite 1x
        if (elText) elText.textContent = monthLabel(state);
        document.dispatchEvent(new CustomEvent('lukrato:month-changed', {
            detail: {
                month: state
            }
        }));
    })();
</script>