<style>
    .lk-accounts-wrap {
        max-width: 1200px;
        margin: 0 auto;
        background: #2c3e50;
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }

    .lk-acc-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }

    .lk-acc-title {
        font-size: 32px;
        font-weight: 700;
        color: #2d3748;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .lk-acc-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .lk-card {
        background: #2c3e50;
        border: none;
        border-radius: 16px;
        padding: 0;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .lk-table {
        width: 100%;
        border-collapse: collapse;
    }

    .lk-table th,
    .lk-table td {
        padding: 16px 20px;
        border-bottom: 1px solid #f0f2f5;
        text-align: left;
    }

    .lk-table th {
        background: linear-gradient(135deg, #f8f9ff 0%, #eef2ff 100%);
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #4a5568;
    }

    .lk-table tbody tr {
        transition: all 0.2s ease;
    }

    .lk-table tbody tr:hover {
        background-color: #f8f9ff;
        transform: translateX(2px);
    }

    .lk-empty {
        padding: 40px;
        text-align: center;
        color: #a0aec0;
        font-style: italic;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
        border-radius: 12px;
        padding: 12px 18px;
        cursor: pointer;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        font-size: 14px;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
    }

    .btn-light {
        background: #f7fafc;
        color: #4a5568;
        border: 1px solid #e2e8f0;
    }

    .btn-light:hover {
        background: #edf2f7;
    }

    .btn-danger {
        background: linear-gradient(135deg, #fc8181 0%, #e53e3e 100%);
        color: #fff;
    }

    .btn-danger:hover {
        background: linear-gradient(135deg, #feb2b2 0%, #c53030 100%);
    }

    .btn-ghost {
        background: transparent;
        color: #718096;
        border: none;
    }

    .btn-ghost:hover {
        background: #f7fafc;
    }

    .btn-sm {
        padding: 8px 12px;
        font-size: 12px;
    }

    .tag {
        font-size: 11px;
        font-weight: 500;
        padding: 4px 12px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .tag.active {
        background: linear-gradient(135deg, #68d391 0%, #38a169 100%);
        color: #fff;
    }

    .tag.inactive {
        background: linear-gradient(135deg, #fed7d7 0%, #e53e3e 100%);
        color: #fff;
    }

    .lk-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .lk-form-grid .full {
        grid-column: 1/-1;
    }

    .lk-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .lk-field label {
        font-size: 13px;
        font-weight: 500;
        color: #4a5568;
    }

    .lk-field input,
    .lk-field select {
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 16px;
        font-size: 14px;
        transition: all 0.2s ease;
        background: #fff;
    }

    .lk-field input:focus,
    .lk-field select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .lk-modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(5px);
        background: rgba(0, 0, 0, 0.4);
        z-index: 1000;
        animation: fadeIn 0.3s ease;
    }

    .lk-modal.open {
        display: flex;
    }

    .lk-modal-card {
        width: 100%;
        max-width: 600px;
        background: #fff;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
        animation: slideUp 0.3s ease;
    }

    .lk-modal-h {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .lk-modal-t {
        font-size: 24px;
        font-weight: 700;
        color: #2d3748;
    }

    .lk-modal-f {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 24px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 12px;
        text-transform: uppercase;
        color: #718096;
        letter-spacing: 0.05em;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideUp {
        from {
            transform: translateY(30px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    @media (max-width: 768px) {
        .lk-accounts-wrap {
            padding: 20px;
            margin: 10px;
        }

        .lk-acc-header {
            flex-direction: column;
            align-items: stretch;
        }

        .lk-acc-title {
            text-align: center;
            font-size: 24px;
        }

        .lk-form-grid {
            grid-template-columns: 1fr;
        }

        .lk-table {
            font-size: 12px;
        }

        .lk-table th,
        .lk-table td {
            padding: 8px;
        }
    }
</style>
<section class="container py-4">
    <div class="lk-acc-title">
        <h3>Contas</h3>
    </div>

    <div class="lk-accounts-wrap">
        <div class="lk-acc-header">
            <div class="lk-acc-actions">
                <button class="btn btn-light" id="btnReload">
                    <i class="fas fa-sync-alt"></i> Atualizar
                </button>
                <button class="btn btn-primary" id="btnNovaConta">
                    <i class="fas fa-plus"></i> Nova Conta
                </button>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="stats-grid" id="statsContainer">
            <div class="stat-card">
                <div class="stat-value" id="totalContas">0</div>
                <div class="stat-label">Total de Contas</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="contasAtivas">0</div>
                <div class="stat-label">Contas Ativas</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="saldoTotal">R$ 0,00</div>
                <div class="stat-label">Saldo Total</div>
            </div>
        </div>

        <div class="lk-card">
            <table class="lk-table" id="accountsTable" aria-label="Tabela de contas">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Instituição</th>
                        <th>Moeda</th>
                        <th>Saldo Inicial</th>
                        <th>Status</th>
                        <th style="width:160px">Ações</th>
                    </tr>
                </thead>
                <tbody id="accountsTbody">
                    <tr>
                        <td class="lk-empty" colspan="6">Carregando...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Criar/Editar -->
    <div class="lk-modal" id="modalConta" role="dialog" aria-modal="true" aria-labelledby="modalContaTitle">
        <div class="lk-modal-card">
            <div class="lk-modal-h">
                <div class="lk-modal-t" id="modalContaTitle">Nova conta</div>
                <button class="btn btn-ghost" id="modalClose" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="lk-modal-b">
                <form id="formConta">
                    <input type="hidden" id="contaId" value="">
                    <div class="lk-form-grid">
                        <div class="lk-field full">
                            <label for="nome">Nome da conta *</label>
                            <input id="nome" name="nome" type="text" placeholder="Ex.: Nubank, Dinheiro, PicPay"
                                required>
                        </div>
                        <div class="lk-field">
                            <label for="instituicao">Instituição</label>
                            <input id="instituicao" name="instituicao" type="text" placeholder="Ex.: Nubank, Caixa">
                        </div>
                        <div class="lk-field">
                            <label for="moeda">Moeda</label>
                            <select id="moeda" name="moeda">
                                <option value="BRL">BRL (R$)</option>
                                <option value="USD">USD ($)</option>
                                <option value="EUR">EUR (€)</option>
                            </select>
                        </div>
                        <div class="lk-field">
                            <label for="saldo_inicial">Saldo inicial</label>
                            <input id="saldo_inicial" name="saldo_inicial" type="text" inputmode="decimal"
                                placeholder="0,00">
                        </div>
                        <div class="lk-field">
                            <label for="tipo_id">Tipo (opcional)</label>
                            <select id="tipo_id" name="tipo_id">
                                <option value="">—</option>
                                <option value="1">Conta Corrente</option>
                                <option value="2">Carteira</option>
                                <option value="3">Poupança</option>
                                <option value="4">Cartão Pré-pago</option>
                            </select>
                        </div>
                    </div>
                    <div class="lk-modal-f">
                        <button type="button" class="btn btn-light" id="btnCancel">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnSave">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
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

        const tbody = $('#accountsTbody');
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

        // Stats elements
        const totalContas = $('#totalContas');
        const contasAtivas = $('#contasAtivas');
        const saldoTotal = $('#saldoTotal');

        // Modal open/close
        function openModal(edit = false, data = null) {
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';

            if (edit && data) {
                modalTitle.textContent = 'Editar conta';
                inputId.value = data.id;
                fNome.value = data.nome || '';
                fInst.value = data.instituicao || '';
                fMoeda.value = data.moeda || 'BRL';
                fSaldo.value = formatMoneyBR(data.saldoInicial ?? 0);
                fTipo.value = data.tipo_id ?? '';
            } else {
                modalTitle.textContent = 'Nova conta';
                inputId.value = '';
                form.reset();
                fMoeda.value = 'BRL';
                fSaldo.value = '';
            }
            setTimeout(() => fNome.focus(), 40);
        }

        function closeModal() {
            modal.classList.remove('open');
            document.body.style.overflow = '';
        }

        // Event listeners
        modalClose?.addEventListener('click', closeModal);
        btnCancel?.addEventListener('click', closeModal);

        // Close modal on backdrop click
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('open')) {
                closeModal();
            }
        });

        btnNovaConta?.addEventListener('click', (e) => {
            console.log('Nova conta clicked!'); // Debug
            e.preventDefault();
            e.stopPropagation();
            openModal(false, null);
        });

        btnReload?.addEventListener('click', (e) => {
            e.preventDefault();
            load();
        });

        // Submit form
        form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                nome: (fNome.value || '').trim(),
                instituicao: (fInst.value || '').trim(),
                moeda: fMoeda.value || 'BRL',
                saldo_inicial: parseMoneyBR(fSaldo.value || '0'),
                tipo_id: fTipo.value ? Number(fTipo.value) : null
            };
            if (!payload.nome) return Swal.fire('Atenção', 'Nome obrigatório.', 'warning');

            try {
                const id = inputId.value ? Number(inputId.value) : null;
                const method = id ? 'PUT' : 'POST';
                const path = id ? `accounts/${id}` : 'accounts';

                const res = await fetchAPI(path, {
                    method,
                    credentials: 'same-origin', // << garante cookie/sessão
                    headers: {
                        'Content-Type': 'application/json',
                        ...(CSRF ? {
                            'X-CSRF-TOKEN': CSRF
                        } : {}) // << nome mais comum
                    },
                    body: JSON.stringify(payload)
                });

                const ct = res.headers.get('content-type') || '';

                let errorMsg = `HTTP ${res.status}`;
                if (!res.ok) {
                    if (ct.includes('application/json')) {
                        const errData = await res.json().catch(() => ({}));
                        if (errData?.message) errorMsg = errData.message;
                    } else {
                        const txt = await res.text();
                        errorMsg = txt.slice(0, 200); // prévia se não for JSON
                    }
                    throw new Error(errorMsg);
                }
                if (!ct.includes('application/json')) {
                    const txt = await res.text();
                    throw new Error('Resposta não é JSON. Prévia: ' + txt.slice(0, 120));
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

        // Update stats
        function updateStats(rows) {
            const total = rows ? rows.length : 0;
            const ativas = rows ? rows.filter(a => a.ativo).length : 0;
            const saldo = rows ? rows.reduce((sum, a) => sum + (a.saldoInicial || 0), 0) : 0;

            totalContas.textContent = total;
            contasAtivas.textContent = ativas;
            saldoTotal.textContent = `R$ ${formatMoneyBR(saldo)}`;
        }

        // Render table
        function renderRows(rows) {
            tbody.innerHTML = '';

            if (!rows || rows.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="lk-empty">Nenhuma conta cadastrada ainda.</td></tr>`;
                updateStats([]);
                return;
            }

            updateStats(rows);

            for (const c of rows) {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                        <td><strong>${escapeHTML(c.nome||'')}</strong></td>
                        <td>${escapeHTML(c.instituicao||'')}</td>
                        <td>${escapeHTML(c.moeda||'BRL')}</td>
                        <td>R$ ${formatMoneyBR(c.saldoInicial ?? 0)}</td>
                        <td><span class="tag ${c.ativo ? 'active' : 'inactive'}">${c.ativo ? 'Ativa' : 'Inativa'}</span></td>
                        <td>
                            <button class="btn btn-light btn-sm btn-edit" data-id="${c.id}">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-danger btn-sm btn-del" data-id="${c.id}">
                                <i class="fas fa-trash"></i> Inativar
                            </button>
                        </td>
                    `;
                tbody.appendChild(tr);
            }

            // Bind events to buttons
            $$('.btn-edit', tbody).forEach(b => {
                b.addEventListener('click', () => {
                    const id = Number(b.dataset.id);
                    const c = rows.find(r => r.id === id);
                    openModal(true, c);
                });
            });

            $$('.btn-del', tbody).forEach(b => {
                b.addEventListener('click', async () => {
                    const id = Number(b.dataset.id);
                    const ok = await Swal.fire({
                        title: 'Inativar conta?',
                        text: 'Você poderá reativá-la depois.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sim, inativar'
                    });
                    if (!ok.isConfirmed) return;

                    try {
                        const res = await fetchAPI(`accounts/${id}`, {
                            method: 'DELETE',
                            credentials: 'same-origin',
                            headers: {
                                ...(CSRF ? {
                                    'X-CSRF-TOKEN': CSRF
                                } : {})
                            }
                        });

                        const ct = res.headers.get('content-type') || '';

                        let errorMsg = `HTTP ${res.status}`;
                        if (!res.ok) {
                            if (ct.includes('application/json')) {
                                const errData = await res.json().catch(() => ({}));
                                if (errData?.message) errorMsg = errData.message;
                            } else {
                                const txt = await res.text();
                                errorMsg = txt.slice(0, 200); // prévia se não for JSON
                            }
                            throw new Error(errorMsg);
                        }
                        if (!ct.includes('application/json')) {
                            const txt = await res.text();
                            throw new Error('Resposta não é JSON. Prévia: ' + txt.slice(0,
                                120));
                        }
                        const data = await res.json();
                        if (data?.status === 'error') throw new Error(data?.message ||
                            'Falha ao inativar.');
                        Swal.fire('Pronto!', 'Conta inativada.', 'success');
                        await load();
                    } catch (err) {
                        console.error(err);
                        Swal.fire('Erro', err.message || 'Falha ao inativar conta.', 'error');
                    }
                });
            });
        }

        // Load accounts from API
        async function load() {
            try {
                tbody.innerHTML = `<tr><td class="lk-empty" colspan="6">Carregando...</td></tr>`;
                const res = await fetchAPI('accounts');
                const ct = res.headers.get('content-type') || '';

                let errorMsg = `HTTP ${res.status}`;
                if (!res.ok) {
                    if (ct.includes('application/json')) {
                        const errData = await res.json().catch(() => ({}));
                        if (errData?.message) errorMsg = errData.message;
                    } else {
                        const txt = await res.text();
                        errorMsg = txt.slice(0, 200); // prévia se não for JSON
                    }
                    throw new Error(errorMsg);
                }
                if (!ct.includes('application/json')) {
                    const txt = await res.text();
                    throw new Error('Resposta não é JSON. Prévia: ' + txt.slice(0, 120));
                }
                const data = await res.json();
                renderRows(Array.isArray(data) ? data : []);
            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td class="lk-empty" colspan="6">Erro ao carregar.</td></tr>`;
                updateStats([]);
                Swal.fire('Erro', err.message || 'Não foi possível carregar as contas.', 'error');
            }
        }

        // Helper functions
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

        // Format money input on blur
        fSaldo?.addEventListener('blur', () => {
            const v = parseMoneyBR(fSaldo.value);
            fSaldo.value = v ? formatMoneyBR(v) : '';
        });

        // Initialize
        load();
    })();
</script>