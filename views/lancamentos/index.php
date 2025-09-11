<section class="container">
    <h3>Lançamentos</h3>

    <header class="dash-lk-header">
        <div class="header-left">
            <div class="month-selector">
                <button class="month-nav-btn" id="prevMonth" type="button" aria-label="Mês anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <div class="month-display">
                    <!-- label do mês (o JS mantém .textContent e data-month) -->
                    <button class="month-dropdown-btn" id="openMonthModal" type="button" data-bs-toggle="modal"
                        data-bs-target="#monthPickerModal">
                        <span id="currentMonthText" data-month="">Carregando...</span>
                        <i class="fas fa-calendar-alt"></i>
                    </button>
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
            <select id="filtroTipo" class="lk-select">
                <option value="">Todos</option>
                <option value="receita">Receitas</option>
                <option value="despesa">Despesas</option>
            </select>
            <button id="btnFiltrar" type="button" class="lk-btn ghost">
                <i class="fas fa-filter"></i> Filtrar
            </button>
        </div>
    </div>

    <section class="table-container mt-5">
        <table class="lukrato-table" id="tabelaLancamentos">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Categoria</th>
                    <th>Conta/Cartão</th>
                    <th>Descrição</th>
                    <th class="text-right">Valor</th>
                </tr>
            </thead>
            <tbody id="tbodyLancamentos">
                <tr>
                    <td colspan="6" class="text-center">Carregando…</td>
                </tr>
            </tbody>
        </table>
    </section>
</section>
<div class="modal fade" id="monthPickerModal" tabindex="-1" aria-labelledby="monthPickerLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content"
            style="background: var(--glass-bg); color: var(--branco); border:1px solid var(--glass-border);">
            <div class="modal-header">
                <h5 class="modal-title" id="monthPickerLabel">Selecionar mês</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <label for="modalMonthInput" class="form-label">Mês</label>
                <input type="month" class="form-control" id="modalMonthInput" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn lk-btn" id="btnApplyMonth">
                    <i class="fas fa-check"></i> Aplicar
                </button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous">
</script>
<script>
    (() => {
        const elText = document.getElementById('currentMonthText');
        const btnPrev = document.getElementById('prevMonth');
        const btnNext = document.getElementById('nextMonth');
        const modalEl = document.getElementById('monthPickerModal');
        const inputMon = document.getElementById('modalMonthInput');
        const btnApply = document.getElementById('btnApplyMonth');

        const toYM = (d) => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
        const meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro',
            'outubro', 'novembro', 'dezembro'
        ];
        const label = (ym) => {
            const [y, m] = ym.split('-').map(Number);
            const nome = (meses[m - 1] || '').replace(/^./, c => c.toUpperCase());
            return `${nome} ${y}`;
        };

        let state = elText?.getAttribute('data-month') || toYM(new Date());

        function renderLabel() {
            if (!elText) return;
            elText.textContent = label(state);
            elText.setAttribute('data-month', state);
        }

        function dispatchChange() {
            document.dispatchEvent(new CustomEvent('lukrato:month-changed', {
                detail: {
                    month: state
                }
            }));
        }

        function setMonth(ym, {
            silent = false
        } = {}) {
            state = ym;
            renderLabel();
            if (!silent) dispatchChange();
        }

        function shiftMonth(delta) {
            const [y, m] = state.split('-').map(Number);
            const d = new Date(y, (m - 1) + delta, 1);
            setMonth(toYM(d));
        }

        // Eventos de navegação ‹ / ›
        btnPrev?.addEventListener('click', () => shiftMonth(-1));
        btnNext?.addEventListener('click', () => shiftMonth(1));

        // Modal Bootstrap: preencher ao abrir
        modalEl?.addEventListener('show.bs.modal', () => {
            if (inputMon) inputMon.value = state; // preenche com o mês atual
        });

        // Aplicar do modal
        btnApply?.addEventListener('click', () => {
            const ym = inputMon?.value;
            if (ym && /^\d{4}-\d{2}$/.test(ym)) {
                setMonth(ym);
                // fechar modal
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.hide();
            }
        });

        // API pública opcional (usada pelo seu script de Lançamentos se quiser)
        window.LukratoHeader = {
            getMonth: () => state,
            setMonth: (ym) => setMonth(ym)
        };

        // Inicializa
        renderLabel();
        // dispara 1x no load para sincronizar a página
        dispatchChange();
    })();
</script>