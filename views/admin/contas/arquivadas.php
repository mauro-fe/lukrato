<div class="cont-page">
    <!-- ==================== HEADER ==================== -->
    <div class="lk-accounts-wrap" style="margin-bottom: 2rem;" data-aos="fade-down">
        <div class="lk-acc-header" style="margin-bottom: 0; display: flex; justify-content: space-between; align-items: center;">
            <h1 style="font-size: 1.75rem; margin: 0; color: var(--color-text); font-weight: 700;">
                <i class="fas fa-archive" style="color: var(--color-primary);"></i>
                Contas Arquivadas
            </h1>
            <a class="btn btn-light" href="<?= BASE_URL ?>contas" aria-label="Voltar para contas">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- ==================== ESTATÍSTICAS ==================== -->
    <div class="stats-grid" id="statsContainer" style="margin-bottom: 2rem;">
        <div class="stat-card" data-aos="flip-left">
            <div class="stat-icon">
                <i class="fas fa-archive"></i>
            </div>
            <div>
                <div class="stat-value" id="totalArquivadas" aria-live="polite">0</div>
                <div class="stat-label">Contas Arquivadas</div>
            </div>
        </div>

        <div class="stat-card" data-aos="flip-right">
            <div class="stat-icon">
                <i class="fas fa-coins"></i>
            </div>
            <div>
                <div class="stat-value" id="saldoArquivado" aria-live="polite">R$ 0,00</div>
                <div class="stat-label">Saldo Total (Arquivadas)</div>
            </div>
        </div>
    </div>

    <!-- ==================== LISTA DE CONTAS ARQUIVADAS ==================== -->
    <div class="lk-accounts-wrap" data-aos="fade-up">
        <div class="lk-card">
            <div class="acc-grid" id="archivedGrid" aria-live="polite" aria-busy="false">
                <!-- Skeleton loader inicial -->
                <div class="acc-skeleton" aria-hidden="true"></div>
                <div class="acc-skeleton" aria-hidden="true"></div>
                <div class="acc-skeleton" aria-hidden="true"></div>
            </div>
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

        const grid = document.getElementById('archivedGrid');
        const totalArquivadas = document.getElementById('totalArquivadas');
        const saldoArquivado = document.getElementById('saldoArquivado');

        async function safeJson(res) {
            try {
                return await res.json();
            } catch {
                return null;
            }
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
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [m]));
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
                            <i class="fas fa-archive"></i>
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

                // Obter informações da instituição financeira
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
                            <img src="${logoUrl}" alt="${escapeHTML(c.nome||'')}" />
                        </div>
                    </div>
                    <div class="account-body" style="position: relative;">
                        <span class="acc-badge inactive" style="position: absolute; top: 1rem; right: 1rem; background: rgba(0,0,0,0.6); color: white; border: 1px solid rgba(255,255,255,0.2); z-index: 10;">
                            <i class="fas fa-archive"></i>
                            Arquivada
                        </span>
                        <h3 class="account-name">${escapeHTML(c.nome||'')}</h3>
                        <div class="account-institution">${escapeHTML(instituicaoNome)}</div>
                        <div class="account-balance ${saldoClass}">
                            R$ ${formatMoneyBR(saldo)}
                        </div>
                        <div class="acc-actions">
                            <button class="btn-action btn-restore" data-id="${c.id}" title="Restaurar conta">
                                <i class="fas fa-undo"></i>
                                <span>Restaurar</span>
                            </button>
                            <button class="btn-action btn-delete" data-id="${c.id}" title="Excluir permanentemente">
                                <i class="fas fa-trash-alt"></i>
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
            const conta = _rows.find(c => c.id === id);
            const nomeConta = conta ? conta.nome : 'esta conta';

            const result = await Swal.fire({
                title: 'Restaurar conta?',
                html: `Deseja realmente restaurar <strong>${nomeConta}</strong>?<br><small class="text-muted">A conta voltará a aparecer na lista ativa.</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#e67e22',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-undo"></i> Sim, restaurar',
                cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                reverseButtons: true,
                buttonsStyling: true
            });

            if (!result.isConfirmed) return;

            try {
                const res = await fetchAPI(`accounts/${id}/restore`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: CSRF ? {
                        'X-CSRF-TOKEN': CSRF
                    } : {}
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

            // 1) Confirmação inicial do usuário (ação irrevergível)
            const ok = await Swal.fire({
                title: 'Excluir permanentemente?',
                html: `Tem certeza que deseja excluir <strong>${nomeConta}</strong>?<br><small class="text-muted" style="color: #dc3545;">Esta ação não pode ser desfeita!</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-trash"></i> Sim, excluir',
                cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                buttonsStyling: true
            });
            if (!ok.isConfirmed) return;

            try {
                // 2) Tenta excluir (sem force)
                const res = await fetchAPI(`accounts/${id}/delete`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        ...(CSRF ? {
                            'X-CSRF-TOKEN': CSRF
                        } : {})
                    },
                    body: JSON.stringify({
                        force: false
                    })
                });

                if (res.status === 422) {
                    const data = await safeJson(res);
                    // Backend pedindo confirmação específica
                    if (data?.status === 'confirm_delete') {
                        const origem = data?.counts?.origem ?? 0;
                        const destino = data?.counts?.destino ?? 0;
                        const total = data?.counts?.total ?? (origem + destino);

                        const confirm = await Swal.fire({
                            title: 'Excluir conta e TODOS os lançamentos?',
                            html: `
            <div style="text-align:left">
              <p>A conta <b>${(nome||'').toString().replace(/</g,'&lt;')}</b> possui lançamentos vinculados.</p>
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
                            // Usuário optou por NÃO excluir -> manter arquivada (não precisa chamar nada)
                            await Swal.fire({
                                icon: 'info',
                                title: 'Mantida',
                                text: 'A conta continuará arquivada.'
                            });
                            return;
                        }

                        // 3) Confirmado -> reenvia com force=1
                        const res2 = await fetchAPI(`accounts/${id}/delete?force=1`, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                ...(CSRF ? {
                                    'X-CSRF-TOKEN': CSRF
                                } : {})
                            },
                            body: JSON.stringify({
                                force: true
                            })
                        });
                        if (!res2.ok) {
                            const err2 = await safeJson(res2);
                            throw new Error(err2?.message || `HTTP ${res2.status}`);
                        }

                        await Swal.fire({
                            icon: 'success',
                            title: 'Excluída',
                            text: 'Conta e lançamentos removidos.'
                        });
                        await load();
                        return;
                    }

                    // 422 sem payload de confirmação -> trate como erro padrão
                    const err = await safeJson(res);
                    throw new Error(err?.message || 'Não foi possível excluir.');
                }

                if (!res.ok) {
                    const err = await safeJson(res);
                    throw new Error(err?.message || `HTTP ${res.status}`);
                }

                // Sem lançamentos vinculados: excluiu direto
                await Swal.fire({
                    icon: 'success',
                    title: 'Excluída',
                    text: 'Conta removida com sucesso.'
                });
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

<style>
    /* Estilos customizados para SweetAlert2 */
    .swal2-popup .swal2-actions {
        gap: 1rem;
    }

    .swal2-popup .swal2-confirm {
        background-color: #e67e22 !important;
        color: white !important;
        border: none !important;
        padding: 0.75rem 2rem !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
        font-size: 1rem !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3) !important;
    }

    .swal2-popup .swal2-confirm:hover {
        background-color: #d35400 !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(230, 126, 34, 0.4) !important;
    }

    .swal2-popup .swal2-cancel {
        background-color: #6c757d !important;
        color: white !important;
        border: none !important;
        padding: 0.75rem 2rem !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
        font-size: 1rem !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3) !important;
    }

    .swal2-popup .swal2-cancel:hover {
        background-color: #5a6268 !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(108, 117, 125, 0.4) !important;
    }

    .swal2-popup .swal2-styled i {
        color: white !important;
        margin-right: 0.5rem;
    }

    /* Estilo para o botão de exclusão (vermelho) */
    .swal2-popup .swal2-confirm[style*="background: rgb(220, 53, 69)"],
    .swal2-popup .swal2-confirm[style*="background-color: rgb(220, 53, 69)"] {
        background-color: #dc3545 !important;
    }

    .swal2-popup .swal2-confirm[style*="background: rgb(220, 53, 69)"]:hover,
    .swal2-popup .swal2-confirm[style*="background-color: rgb(220, 53, 69)"]:hover {
        background-color: #c82333 !important;
    }

    /* Layout da página */
    .cont-page {
        display: flex;
        flex-direction: column;
        margin-top: var(--spacing-5);
        padding: 0 var(--spacing-4);
    }

    /* Ícone do empty state - adapta ao tema */
    .empty-icon {
        color: white !important;
    }

    [data-theme="light"] .empty-icon {
        color: white !important;
    }

    [data-theme="dark"] .empty-icon {
        color: var(--color-text) !important;
    }

    /* Melhorias nos botões dos cards */
    .acc-card .btn-primary {
        background: linear-gradient(135deg, #e67e22, #d35400);
        border: none;
        color: white;
        font-weight: 600;
    }

    .acc-card .btn-primary:hover {
        background: linear-gradient(135deg, #d35400, #c0392b);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(230, 126, 34, 0.4);
    }

    .acc-card .btn-danger {
        background: linear-gradient(135deg, #dc3545, #c82333);
        border: none;
        color: white;
        font-weight: 600;
    }

    .acc-card .btn-danger:hover {
        background: linear-gradient(135deg, #c82333, #bd2130);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(220, 53, 69, 0.4);
    }

    .acc-card-body {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    /* Estilos para cards de contas arquivadas com logo */
    .account-card {
        border-radius: var(--radius-lg);
        background: var(--color-card-bg);
        box-shadow: 0 6px 22px rgba(0, 0, 0, 0.25);
        border: 1px solid var(--color-card-border);
        overflow: hidden;
        transition: all 0.5s ease !important;
        position: relative;
    }

    .account-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.35);
    }

    /* Efeito sutil de arquivado */
    .account-card.archived-card {
        opacity: 0.85;
    }

    .account-card.archived-card:hover {
        opacity: 1;
    }

    .account-header {
        position: relative;
        height: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }

    /* Mantém o logo colorido para contas arquivadas */
    .archived-card .account-header::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.15);
        pointer-events: none;
    }

    .account-logo {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 1;
    }

    .account-logo img {
        max-width: 80%;
        max-height: 80%;
        object-fit: contain;
        filter: brightness(0) invert(1);
    }

    /* Remove o filtro branco para cards arquivados mantendo o logo colorido */
    .archived-card .account-logo img {
        filter: none;
        opacity: 0.9;
    }

    .account-body {
        padding: 1.5rem;
        background: var(--color-card-bg);
    }

    .account-name {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--color-text);
        margin: 0 0 0.5rem 0;
        padding-right: 7rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .account-institution {
        font-size: 0.875rem;
        color: var(--color-text-muted);
        margin-bottom: 1rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .account-balance {
        font-size: 1.75rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        font-variant-numeric: tabular-nums;
    }

    .account-balance.positive {
        color: var(--color-success);
    }

    .account-balance.negative {
        color: var(--color-danger);
    }

    /* Ações do card */
    .acc-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--color-card-border);
    }

    .btn-action {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        border: none;
        border-radius: var(--radius-md);
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-action i {
        font-size: 1rem;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .btn-action:active {
        transform: translateY(0);
    }

    .btn-restore {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
    }

    .btn-restore:hover {
        background: linear-gradient(135deg, #2980b9 0%, #21618c 100%);
    }

    .btn-delete {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        color: white;
    }

    .btn-delete:hover {
        background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
    }

    /* Badge de arquivada */
    .acc-badge.inactive {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.375rem 0.75rem;
        border-radius: var(--radius-full);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
</style>