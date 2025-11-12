<div class="cont-page">


    <div class="stats-grid pt-5" id="statsContainer">
        <div class="stat-card" data-aos="flip-left">
            <div class="stat-value" id="totalContas">0</div>
            <div class="stat-label">Total de Contas</div>
        </div>

        <div class="stat-card" data-aos="flip-right">
            <div class="stat-value" id="saldoTotal">R$ 0,00</div>
            <div class="stat-label">Saldo Total</div>
        </div>
    </div>

    <div class="lk-accounts-wrap" data-aos="fade-up">
        <div class="lk-acc-header">
            <div class="lk-acc-actions">
                <button class="btn btn-primary" id="btnNovaConta">
                <i class="fas fa-plus"></i> Nova Conta
                </button>
            </div>
            <a class="btn btn-primary" href="<?= BASE_URL ?>contas/arquivadas">Arquivadas</a>

        </div>


        <div class="lk-card">
            <div class="acc-grid" id="accountsGrid" aria-live="polite">
                <div class="acc-skeleton"></div>
                <div class="acc-skeleton"></div>
                <div class="acc-skeleton"></div>
            </div>
        </div>

    </div>
</div>
<?php include __DIR__ . '/../partials/modals/modal_contas.php'; ?>
<script>
    (function initAccountsPage() {
        const BASE = (document.querySelector('meta[name="base-url"]')?.content || location.origin + '/');
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // URLs com e sem index.php
        const apiPretty = (p) => `${BASE}api/${p}`.replace(/\/{2,}/g, '/').replace(':/', '://');
        const apiIndex = (p) => `${BASE}index.php/api/${p}`.replace(/\/{2,}/g, '/').replace(':/', '://');

        async function fetchAPI(path, opts = {}) {
            let res = await fetch(apiPretty(path), opts);
            if (res.status === 404) res = await fetch(apiIndex(path), opts);
            return res;
        }

        // DOM
        const $ = (s, sc = document) => sc.querySelector(s);
        const $$ = (s, sc = document) => Array.from(sc.querySelectorAll(s));

        // GRID novo (se existir) e fallback TABELA antiga
        const grid = $('#accountsGrid'); // << cards
        const tbody = $('#accountsTbody'); // << tabela (fallback)

        const btnReload = $('#btnReload');
        const btnNovaConta = $('#btnNovaConta');

        const modal = $('#modalConta');
        const modalTitle = $('#modalContaTitle');
        const modalClose = $('#modalClose');
        const btnCancel = $('#btnCancel');
        const form = $('#formConta');

        const inputId = $('#contaId');
        const fNome = $('#nome');
        const fInst = $('#instituicao');
        const fMoeda = $('#moeda');
        const fSaldo = $('#saldo_inicial');
        const fTipo = $('#tipo_id');

        const totalContas = $('#totalContas');
        const saldoTotal = $('#saldoTotal');

        function openModal(edit = false, data = null) {
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';

            if (edit && data) {
                modalTitle.textContent = 'Editar conta';
                inputId.value = data.id;
                fNome.value = data.nome || '';
                fInst.value = data.instituicao || '';
                if (fSaldo) fSaldo.value = formatMoneyBR(data.saldoInicial ?? 0);
                if (fTipo) fTipo.value = data.tipo_id ?? '';
            } else {
                modalTitle.textContent = 'Nova conta';
                inputId.value = '';
                form.reset();
                if (fSaldo) fSaldo.value = '';
                if (fTipo) fTipo.value = '';
            }
            setTimeout(() => fNome.focus(), 40);
        }

        function closeModal() {
            modal.classList.remove('open');
            document.body.style.overflow = '';
        }

        modalClose?.addEventListener('click', closeModal);
        btnCancel?.addEventListener('click', closeModal);
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal?.classList.contains('open')) closeModal();
        });

        btnNovaConta?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            openModal(false, null);
        });

        btnReload?.addEventListener('click', (e) => {
            e.preventDefault();
            load();
        });

        form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                nome: (fNome.value || '').trim(),
                instituicao: (fInst.value || '').trim(),
                saldo_inicial: parseMoneyBR(fSaldo?.value || '0'),
            };
            if (!payload.nome) return Swal.fire('Atenção', 'Nome obrigatório.', 'warning');

            try {
                const id = inputId.value ? Number(inputId.value) : null;
                const method = id ? 'PUT' : 'POST';
                const path = id ? `accounts/${id}` : 'accounts';

                const res = await fetchAPI(path, {
                    method,
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        ...(CSRF ? {
                            'X-CSRF-TOKEN': CSRF
                        } : {})
                    },
                    body: JSON.stringify(payload)
                });

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
                if (data?.status === 'error') throw new Error(data?.message || 'Erro ao salvar conta.');

                Swal.fire('Pronto!', 'Conta salva com sucesso.', 'success');
                closeModal();
                await load();
            } catch (err) {
                console.error(err);
                Swal.fire('Erro', err.message || 'Falha ao salvar.', 'error');
            }
        });

        function getContaSaldo(conta) {
            if (!conta) return 0;
            const raw = (typeof conta.saldoAtual === 'number') ? conta.saldoAtual : conta.saldoInicial;
            const num = Number(raw);
            return Number.isFinite(num) ? num : 0;
        }

        function sortContasBySaldo(rows = []) {
            return rows.slice().sort((a, b) => {
                const diff = getContaSaldo(b) - getContaSaldo(a);
                if (diff !== 0) return diff;
                return (a.nome || '').localeCompare(b.nome || '', 'pt-BR', {
                    sensitivity: 'base',
                    ignorePunctuation: true
                });
            });
        }

        function updateStats(rows) {
            const total = rows ? rows.length : 0;
            const ativas = rows ? rows.filter(a => a.ativo).length : 0;
            const saldo = rows ?
                rows.reduce((sum, a) => sum + getContaSaldo(a), 0) :
                0;

            totalContas.textContent = total;
            // contasAtivas.textContent = ativas;
            saldoTotal.textContent = `R$ ${formatMoneyBR(saldo)}`;
        }


        async function handleArchiveAccount(id) {
            const {
                isConfirmed
            } = await Swal.fire({
                title: 'Arquivar conta?',
                text: 'Você poderá restaurá-la depois na página "Contas arquivadas".',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, arquivar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            });
            if (!isConfirmed) return;

            try {
                const res = await fetchAPI(`accounts/${id}/archive`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        ...(CSRF ? {
                            'X-CSRF-TOKEN': CSRF
                        } : {})
                    }
                });

                if (!res.ok) throw new Error('Falha ao arquivar');
                Swal.fire('Pronto!', 'Conta arquivada.', 'success');
                await load();
            } catch (err) {
                console.error(err);
                Swal.fire('Erro', err.message || 'Falha ao arquivar', 'error');
            }
        }

        async function handleRestoreAccount(id) {
            const {
                isConfirmed
            } = await Swal.fire({
                title: 'Restaurar conta?',
                text: 'A conta voltará para a lista de contas ativas.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim, restaurar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            });
            if (!isConfirmed) return;

            try {
                const res = await fetchAPI(`accounts/${id}/restore`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        ...(CSRF ? {
                            'X-CSRF-TOKEN': CSRF
                        } : {})
                    }
                });

                if (!res.ok) throw new Error('Falha ao restaurar');
                Swal.fire('Pronto!', 'Conta restaurada.', 'success');
                await load();
            } catch (err) {
                console.error(err);
                Swal.fire('Erro', err.message || 'Falha ao restaurar', 'error');
            }
        }



        grid?.addEventListener('click', (e) => {
            const bArch = e.target.closest('.btn-archive');
            const bRest = e.target.closest('.btn-restore');
            if (bArch) handleArchiveAccount(Number(bArch.dataset.id));
            if (bRest) handleRestoreAccount(Number(bRest.dataset.id));
        });


        function renderRows(rows) {
            if (!tbody) return;
            tbody.innerHTML = '';

            if (!rows || rows.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="lk-empty">Nenhuma conta cadastrada ainda.</td></tr>`;
                updateStats([]);
                return;
            }

            updateStats(rows);

            for (const c of rows) {
                const saldo = getContaSaldo(c);
                const isActive = !!c.ativo;
                const tr = document.createElement('tr');
                tr.innerHTML = `
      <td>${escapeHTML(c.nome||'')}</td>
      <td>${escapeHTML(c.instituicao||'')}</td>
      <td>R$ ${formatMoneyBR(saldo)}</td>
      <td>${isActive ? '<span class="tag active">Ativa</span>' : '<span class="tag inactive">Arquivada</span>'}</td>
      <td style="width:260px">
        ${isActive ? `
          <button class="btn btn-ghost btn-acc-receita" data-id="${c.id}"><i class="fas fa-arrow-up"></i></button>
          <button class="btn btn-ghost btn-acc-despesa" data-id="${c.id}"><i class="fas fa-arrow-down"></i></button>
          🔄<button class="btn btn-ghost btn-acc-transfer" data-id="${c.id}"><i class="fas fa-right-left"></i></button>
          <button class="btn btn-ghost btn-edit" data-id="${c.id}"><i class="fas fa-pen"></i></button>
          <button class="btn btn-ghost btn-archive" data-id="${c.id}"><i class="fas fa-box-archive"></i></button>
        ` : `
          <button class="btn btn-ghost btn-restore" data-id="${c.id}"><i class="fas fa-rotate-left"></i></button>
        `}
      </td>
    `;
                tbody.appendChild(tr);
            }
        }


        let _lastRows = [];

        function renderCards(rows) {
            if (!grid) return;
            grid.innerHTML = '';

            if (!rows || rows.length === 0) {
                grid.innerHTML = `<div class="lk-empty">Nenhuma conta cadastrada ainda.</div>`;
                updateStats([]);
                return;
            }

            updateStats(rows);

            for (const c of rows) {
                const isActive = !!c.ativo;
                const statusBadge = isActive ?
                    `<span class="acc-badge active">Ativa</span>` :
                    `<span class="acc-badge inactive">Arquivada</span>`;

                const saldo = getContaSaldo(c);

                // ações: se arquivada, só “Restaurar”
                const actions = isActive ?
                    `
        <button class="btn btn-primary btn-acc-receita" data-id="${c.id}">
          <i class="fas fa-arrow-up"></i> Receita
        </button>
        <button class="btn btn-ghost btn-acc-despesa" data-id="${c.id}">
          <i class="fas fa-arrow-down"></i> Despesa
        </button>
        <button class="btn btn-ghost btn-acc-transfer" data-id="${c.id}">
          <i class="fas fa-right-left"></i> Transferir
        </button>
        <button class="btn btn-ghost btn-edit" data-id="${c.id}">
          <i class="fas fa-pen"></i> Editar
        </button>
        <button class="btn btn-ghost btn-archive" data-id="${c.id}">
          <i class="fas fa-box-archive"></i> Arquivar
        </button>` :
                    `
        <button class="btn btn-ghost btn-restore" data-id="${c.id}">
          <i class="fas fa-rotate-left"></i> Restaurar
        </button>`;

                const card = document.createElement('div');
                card.setAttribute('data-aos', 'flip-left');
                card.className = 'acc-card';
                card.innerHTML = `
      <div>
        <div class="acc-head">
          <div class="acc-dot"></div>
          <div>
            <div class="acc-name">${escapeHTML(c.nome || '')}</div>
            <div class="acc-sub">${escapeHTML(c.instituicao || '—')}</div>
          </div>
          ${statusBadge}
        </div>
        <div class="acc-balance">R$ ${formatMoneyBR(saldo)}</div>
      </div>
      <div class="acc-actions">${actions}</div>
    `;
                grid.appendChild(card);
            }
        }


        grid?.addEventListener('click', (e) => {
            const btnRec = e.target.closest('.btn-acc-receita');
            const btnDes = e.target.closest('.btn-acc-despesa');
            const btnEd = e.target.closest('.btn-edit');
            const btnTr = e.target.closest('.btn-acc-transfer');
            const bArch = e.target.closest('.btn-archive');
            const bRest = e.target.closest('.btn-restore');

            if (btnRec) return openLancModal(btnRec.dataset.id, 'receita');
            if (btnDes) return openLancModal(btnDes.dataset.id, 'despesa');
            if (btnEd) {
                const id = +btnEd.dataset.id;
                const c = _lastRows.find(r => r.id === id);
                return openModal(true, c);
            }
            if (btnTr) return openTransferModal(btnTr.dataset.id);
            if (bArch) return handleArchiveAccount(+bArch.dataset.id);
            if (bRest) return handleRestoreAccount(+bRest.dataset.id);
        });



        async function load() {
            try {
                if (grid) {
                    grid.innerHTML =
                        `<div class="acc-skeleton"></div><div class="acc-skeleton"></div><div class="acc-skeleton"></div>`;
                }
                if (tbody) {
                    tbody.innerHTML = `<tr><td class="lk-empty" colspan="6">Carregando...</td></tr>`;
                }

                const ym = new Date().toISOString().slice(0, 7); // YYYY-MM do mês atual
                const res = await fetchAPI(`accounts?with_balances=1&month=${ym}`);
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
                _lastRows = Array.isArray(data) ? sortContasBySaldo(data) : [];

                if (grid) renderCards(_lastRows);
                else if (tbody) renderRows(_lastRows);
            } catch (err) {
                console.error(err);
                if (grid) grid.innerHTML = `<div class="lk-empty">Erro ao carregar.</div>`;
                if (tbody) tbody.innerHTML = `<tr><td class="lk-empty" colspan="6">Erro ao carregar.</td></tr>`;
                updateStats([]);
                Swal.fire('Erro', err.message || 'Não foi possível carregar as contas.', 'error');
            }
        }

        function parseMoneyBR(s) {
            if (!s) return 0;
            s = String(s).replace(/\./g, '').replace(',', '.').replace(/[^\d.-]/g, '').trim();
            const v = parseFloat(s);
            return isFinite(v) ? Math.round(v * 100) / 100 : 0;
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

        function escapeHTML(str = '') {
            return String(str).replace(/[&<>"']/g, m => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [m]));
        }

        fSaldo?.addEventListener('blur', () => {
            const v = parseMoneyBR(fSaldo.value);
            fSaldo.value = v ? formatMoneyBR(v) : '';
        });

        const modalLanc = document.querySelector('#modalLancConta');
        const modalLancTitle = document.querySelector('#modalLancContaTitle');
        const lanClose = document.querySelector('#lancClose');
        const lanCancel = document.querySelector('#lancCancel');
        const formLanc = document.querySelector('#formLancConta');

        const qM = (sel) => modalLanc ? modalLanc.querySelector(sel) : null;

        const fLanConta = qM('#lanContaId');
        const fLanContaNome = qM('#lanContaNome');
        const fLanTipo = qM('#lanTipo');
        const fLanData = qM('#lanData');
        const fLanCat = qM('#lanCategoria');
        const fLanDesc = qM('#lanDescricao');
        const fLanValor = qM('#lanValor');
        const fLanPago = qM('#lanPago');
        const lblPago = qM('#lanPagoLabel');

        let _optsCache = null;
        async function getOptions() {
            if (_optsCache) return _optsCache;
            const res = await fetchAPI('options', {
                credentials: 'same-origin'
            });
            if (!res.ok) throw new Error('Falha ao carregar opções');
            _optsCache = await res.json().catch(() => ({}));
            return _optsCache;
        }

        async function refreshCategoriasLanc() {
            if (!fLanTipo || !fLanCat) return;
            const opts = await getOptions();
            const tipo = (fLanTipo.value || 'despesa').toLowerCase();
            const list = (tipo === 'receita') ?
                (opts?.categorias?.receitas || []) :
                (opts?.categorias?.despesas || []);

            fLanCat.innerHTML = `<option value="">Selecione uma categoria</option>`;
            for (const it of list) {
                const op = document.createElement('option');
                op.value = it.id;
                op.textContent = it.nome;
                fLanCat.appendChild(op);
            }
            if (lblPago) lblPago.textContent = (tipo === 'receita') ? 'Foi recebido?' : 'Foi pago?';
        }

        async function openLancModal(contaId, tipo = 'despesa') {
            if (!modalLanc) return;
            const id = Number(contaId);
            const conta = _lastRows.find(r => r.id === id);
            fLanConta.value = String(id);
            fLanTipo.value = (tipo === 'receita') ? 'receita' : 'despesa';

            if (fLanContaNome) {
                fLanContaNome.value = conta ?
                    `${conta.nome || 'Conta sem nome'}${conta.instituicao ? ' - ' + conta.instituicao : ''}` :
                    'Conta não encontrada';
            }
            if (modalLancTitle) {
                const tituloBase = fLanTipo.value === 'receita' ? 'Nova receita' : 'Nova despesa';
                modalLancTitle.textContent = conta?.nome ?
                    `${tituloBase} - ${conta.nome}` :
                    tituloBase;
            }

            // Prefill de data compatível com <input type="date">
            if (fLanData && 'valueAsDate' in fLanData) fLanData.valueAsDate = new Date();
            else if (fLanData) fLanData.value = new Date().toISOString().slice(0, 10);

            if (fLanDesc) fLanDesc.value = '';
            if (fLanValor) fLanValor.value = '';
            if (fLanPago) fLanPago.checked = false;

            await refreshCategoriasLanc(); // garante que o select já esteja populado

            modalLanc.classList.add('open');
            document.body.style.overflow = 'hidden';
            setTimeout(() => fLanValor?.focus(), 40);
        }

        function closeLancModal() {
            if (!modalLanc) return;
            modalLanc.classList.remove('open');
            document.body.style.overflow = '';
        }
        lanClose?.addEventListener('click', closeLancModal);
        lanCancel?.addEventListener('click', closeLancModal);
        modalLanc
            ?.addEventListener('click', (e) => {
                if (e.target === modalLanc) closeLancModal();
            });

        fLanTipo?.addEventListener('change', refreshCategoriasLanc);

        fLanValor?.addEventListener('blur', () => {
            const v = parseMoneyBR(fLanValor.value);
            fLanValor.value = v ? formatMoneyBR(v) : '';
        });

        formLanc?.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {

                if (!fLanCat.value) {

                    return Swal.fire('Atenção', 'Selecione uma categoria.', 'warning');
                }

                const payload = {
                    tipo: fLanTipo.value,
                    data: fLanData.value,
                    valor: parseMoneyBR(fLanValor.value),
                    categoria_id: fLanCat.value ? Number(fLanCat.value) : null,
                    conta_id: Number(fLanConta.value), // amarra na conta do card
                    descricao: fLanDesc.value || null,
                    observacao: null
                };
                if (!payload.data || !payload.valor || payload.valor <= 0)
                    return Swal.fire('Atenção', 'Preencha data e valor válidos.', 'warning');

                const res = await fetchAPI('transactions', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        ...(CSRF ? {
                            'X-CSRF-TOKEN': CSRF
                        } : {})
                    },
                    body: JSON.stringify(payload)
                });

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
                Swal.fire({
                    icon: 'success',
                    title: 'Lançado!',
                    timer: 1300,
                    showConfirmButton: false
                });
                closeLancModal();
                await load();
                window.refreshDashboard && window.refreshDashboard();
            } catch (err) {
                console.error(err);
                Swal.fire('Erro', err.message || 'Falha ao salvar', 'error');
            }
        });
        const modalTr = document.querySelector('#modalTransfer');
        const modalTransferTitle = document.querySelector('#modalTransferTitle');
        const trClose = document.querySelector('#trClose');
        const trCancel = document.querySelector('#trCancel');
        const formTr = document.querySelector('#formTransfer');

        const trOrigemId = modalTr?.querySelector('#trOrigemId');
        const trOrigemNome = modalTr?.querySelector('#trOrigemNome');
        const trDestinoId = modalTr?.querySelector('#trDestinoId');
        const trData = modalTr?.querySelector('#trData');
        const trValor = modalTr?.querySelector('#trValor');
        const trDesc = modalTr?.querySelector('#trDesc');


        async function openTransferModal(origemId) {
            if (!modalTr) return;
            const id = Number(origemId);
            const conta = _lastRows.find(r => r.id === id);
            if (!conta) return;
            const saldoDisponivel = getContaSaldo(conta);
            const saldoNormalizado = Math.round((Number(saldoDisponivel) || 0) * 100) / 100;
            modalTr.dataset.saldoDisponivel = String(saldoNormalizado);
            modalTr.dataset.saldoCentavos = String(Math.round(saldoNormalizado * 100));
            trOrigemId.value = String(conta.id);
            trOrigemNome.value =
                `${conta.nome}${conta.instituicao ? ' - ' + conta.instituicao : ''} (Saldo: R$ ${formatMoneyBR(saldoNormalizado)})`;
            if (modalTransferTitle) {
                modalTransferTitle.textContent = conta.nome ?
                    `Transfer�ncia - ${conta.nome}` :
                    'Transfer�ncia';
            }

            if (trData && 'valueAsDate' in trData) trData.valueAsDate = new Date();
            else if (trData) trData.value = new Date().toISOString().slice(0, 10);

            trValor.value = '';
            trDesc.value = '';

            const opts = await getOptions();
            const contas = Array.isArray(opts?.contas) ? opts.contas : [];
            trDestinoId.innerHTML = `<option value="">Selecione a conta de destino</option>`;
            for (const c of contas) {
                if (c.id === id) continue;
                const op = document.createElement('option');
                op.value = c.id;
                op.textContent = c.nome;
                trDestinoId.appendChild(op);
            }

            modalTr.classList.add('open');
            document.body.style.overflow = 'hidden';
            setTimeout(() => trValor?.focus(), 40);
        }

        function closeTransferModal() {
            if (!modalTr) return;
            modalTr.classList.remove('open');
            document.body.style.overflow = '';

        }
        trClose?.addEventListener('click', closeTransferModal);
        trCancel?.addEventListener('click', closeTransferModal);
        modalTr?.addEventListener('click', (e) => {
            if (e.target === modalTr) closeTransferModal();
        });

        trValor?.addEventListener('blur', () => {
            const v = parseMoneyBR(trValor.value);
            trValor.value = v ? formatMoneyBR(v) : '';
        });

        formTr?.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const origemId = Number(trOrigemId.value);
                const destinoId = Number(trDestinoId.value);
                const valor = parseMoneyBR(trValor.value);

                if (!destinoId || !origemId || origemId === destinoId)
                    return Swal.fire('Atenção', 'Selecione contas de origem e destino diferentes.',
                        'warning');

                if (!trData.value || !valor || valor <= 0)
                    return Swal.fire('Atenção', 'Preencha data e valor válidos.', 'warning');

                // ⬇️ NOVO: validação de saldo insuficiente
                const saldoDisponivel = Number(modalTr?.dataset?.saldoDisponivel || 0);
                const saldoCentavosDataset = Number(modalTr?.dataset?.saldoCentavos || 0);
                const saldoCentavos = Number.isFinite(saldoCentavosDataset) && saldoCentavosDataset > 0
                    ? saldoCentavosDataset
                    : Math.round(saldoDisponivel * 100);
                const valorCentavos = Math.round(valor * 100);
                if (valorCentavos > saldoCentavos) {
                    return Swal.fire(
                        'Saldo insuficiente',
                        `O valor da transferência (R$ ${formatMoneyBR(valor)}) é maior que o saldo disponível na conta de origem (R$ ${formatMoneyBR(saldoDisponivel)}).`,
                        'error'
                    );
                }

                const payload = {
                    data: trData.value,
                    valor,
                    conta_id: origemId,
                    conta_id_destino: destinoId,
                    descricao: trDesc.value || null,
                    observacao: null
                };


                const res = await fetchAPI('transfers', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        ...(CSRF ? {
                            'X-CSRF-TOKEN': CSRF
                        } : {})
                    },
                    body: JSON.stringify(payload)
                });

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

                Swal.fire({
                    icon: 'success',
                    title: 'Transferência registrada!',
                    timer: 1300,
                    showConfirmButton: false
                });
                closeTransferModal();
                await load();
                window.refreshDashboard && window.refreshDashboard();
            } catch (err) {
                console.error(err);
                Swal.fire('Erro', err.message || 'Falha ao salvar transferência.', 'error');
            }
        });
        load();
    })();
</script>
