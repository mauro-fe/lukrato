<div class="cont-page">
    <!-- ==================== HEADER ==================== -->
    <div class="lk-accounts-wrap" style="margin-bottom: 2rem;" data-aos="fade-down">
        <div class="lk-acc-header" style="margin-bottom: 0; display: flex; justify-content: space-between; align-items: center;">
            <h1 style="font-size: 1.75rem; margin: 0; color: var(--color-text); font-weight: 700;">
                <i class="fas fa-archive" style="color: var(--color-primary);"></i>
                Cartões Arquivados
            </h1>
            <a class="btn btn-light" href="<?= BASE_URL ?>cartoes" aria-label="Voltar para cartões">
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
                <div class="stat-value" id="totalArquivados" aria-live="polite">0</div>
                <div class="stat-label">Cartões Arquivados</div>
            </div>
        </div>

        <div class="stat-card" data-aos="flip-right">
            <div class="stat-icon">
                <i class="fas fa-credit-card"></i>
            </div>
            <div>
                <div class="stat-value" id="limiteTotal" aria-live="polite">R$ 0,00</div>
                <div class="stat-label">Limite Total (Arquivados)</div>
            </div>
        </div>
    </div>

    <!-- ==================== LISTA DE CARTÕES ARQUIVADOS ==================== -->
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

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .stat-card {
        background: var(--color-surface);
        border: 1px solid var(--color-card-border);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: var(--shadow-sm);
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: var(--radius-md);
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        color: white;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--color-text);
        margin-bottom: 0.25rem;
    }

    .stat-label {
        font-size: 0.875rem;
        color: var(--color-text-muted);
        font-weight: 500;
    }

    .acc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.5rem;
        padding: 1rem;
    }

    .acc-skeleton {
        height: 140px;
        background: linear-gradient(90deg, var(--color-surface-muted) 0%, color-mix(in srgb, var(--color-surface-muted) 90%, white) 50%, var(--color-surface-muted) 100%);
        background-size: 200% 100%;
        animation: shimmer 1.5s ease-in-out infinite;
        border-radius: var(--radius-lg);
    }

    @keyframes shimmer {
        0% {
            background-position: -200% 0;
        }

        100% {
            background-position: 200% 0;
        }
    }

    .cartao-card {
        background: var(--color-surface);
        border: 1px solid var(--color-card-border);
        border-radius: var(--radius-lg);
        padding: 1.25rem;
        box-shadow: var(--shadow-sm);
        transition: all 0.3s ease;
        position: relative;
    }

    .cartao-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .cartao-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .cartao-info h3 {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--color-text);
        margin-bottom: 0.25rem;
    }

    .cartao-info .bandeira {
        font-size: 0.875rem;
        color: var(--color-text-muted);
    }

    .cartao-limite {
        text-align: right;
    }

    .limite-label {
        font-size: 0.75rem;
        color: var(--color-text-muted);
        margin-bottom: 0.25rem;
    }

    .limite-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--color-primary);
    }

    .cartao-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--color-card-border);
    }

    .btn-action {
        flex: 1;
        padding: 0.5rem 1rem;
        border-radius: var(--radius-md);
        font-size: 0.875rem;
        font-weight: 500;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-restore {
        background: var(--color-success);
        color: white;
    }

    .btn-restore:hover {
        background: color-mix(in srgb, var(--color-success) 85%, #000);
    }

    .btn-delete {
        background: var(--color-danger);
        color: white;
    }

    .btn-delete:hover {
        background: color-mix(in srgb, var(--color-danger) 85%, #000);
    }

    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3rem;
    }

    .empty-icon {
        width: 120px;
        height: 120px;
        margin: 0 auto 1.5rem;
        background: var(--color-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: white;
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    .empty-state h3 {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--color-text);
        margin-bottom: 0.75rem;
    }

    .empty-state p {
        font-size: 1rem;
        color: var(--color-text-muted);
        margin-bottom: 1.5rem;
    }

    @media (max-width: 768px) {
        .acc-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    (function initArchivedCartoesPage() {
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
        const totalArquivados = document.getElementById('totalArquivados');
        const limiteTotal = document.getElementById('limiteTotal');

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
            totalArquivados.textContent = rows.length;
            const total = rows.reduce((sum, c) => sum + parseFloat(c.limite_total || 0), 0);
            limiteTotal.textContent = 'R$ ' + formatMoneyBR(total);
        }

        function renderEmpty() {
            grid.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3>Nenhum cartão arquivado</h3>
                    <p>Você não possui cartões arquivados no momento</p>
                </div>
            `;
        }

        function renderCartoes(rows) {
            if (!rows.length) {
                renderEmpty();
                return;
            }

            grid.innerHTML = rows.map(c => {
                const nome = escapeHTML(c.nome_cartao || 'Sem nome');
                const bandeira = escapeHTML(c.bandeira || 'Desconhecida');
                const limite = formatMoneyBR(c.limite_total || 0);
                const id = c.id;

                return `
                    <div class="cartao-card" data-id="${id}">
                        <div class="cartao-header">
                            <div class="cartao-info">
                                <h3>${nome}</h3>
                                <div class="bandeira">${bandeira}</div>
                            </div>
                            <div class="cartao-limite">
                                <div class="limite-label">Limite Total</div>
                                <div class="limite-value">R$ ${limite}</div>
                            </div>
                        </div>
                        <div class="cartao-actions">
                            <button class="btn-action btn-restore" onclick="handleRestore(${id})">
                                <i class="fas fa-undo"></i> Restaurar
                            </button>
                            <button class="btn-action btn-delete" onclick="handleHardDelete(${id}, '${nome.replace(/'/g, "\\'")}')">
                                <i class="fas fa-trash"></i> Excluir
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        async function load() {
            try {
                grid.setAttribute('aria-busy', 'true');

                const res = await fetchAPI('cartoes?archived=1');
                if (!res.ok) throw new Error('Falha ao carregar cartões arquivados');

                const data = await safeJson(res);
                _rows = Array.isArray(data) ? data : (data?.data || []);

                updateStats(_rows);
                renderCartoes(_rows);
            } catch (err) {
                console.error(err);
                grid.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3>Erro ao carregar</h3>
                        <p>${err.message || 'Não foi possível carregar os cartões arquivados'}</p>
                    </div>
                `;
            } finally {
                grid.setAttribute('aria-busy', 'false');
            }
        }

        window.handleRestore = async function(id) {
            const cartao = _rows.find(c => c.id === id);
            const nome = cartao ? cartao.nome_cartao : 'este cartão';

            const result = await Swal.fire({
                title: 'Restaurar Cartão',
                html: `Deseja restaurar o cartão <strong>${nome}</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-undo"></i> Sim, restaurar',
                cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                confirmButtonColor: '#2ecc71',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                buttonsStyling: true
            });

            if (!result.isConfirmed) return;

            try {
                const res = await fetchAPI(`cartoes/${id}/restore`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: CSRF ? {
                        'X-CSRF-TOKEN': CSRF
                    } : {}
                });
                if (!res.ok) throw new Error('Falha ao restaurar');

                Swal.fire({
                    title: 'Restaurado!',
                    text: 'O cartão foi restaurado com sucesso.',
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
        };

        window.handleHardDelete = async function(id, nome = '') {
            const cartao = _rows.find(c => c.id === id);
            const nomeCartao = cartao ? cartao.nome_cartao : nome || 'este cartão';

            // Confirmação inicial
            const ok = await Swal.fire({
                title: 'Excluir permanentemente?',
                html: `Tem certeza que deseja excluir <strong>${nomeCartao}</strong>?<br><small class="text-muted" style="color: #dc3545;">Esta ação não pode ser desfeita!</small>`,
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
                // Tenta excluir sem force
                console.log('🔍 Tentando excluir cartão ID:', id);
                const res = await fetchAPI(`cartoes/${id}/delete`, {
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

                console.log('📡 Response status:', res.status);
                const data = await safeJson(res);
                console.log('📦 Response data:', data);

                if (res.status === 422) {
                    console.log('⚠️ Status 422 detectado - requer confirmação');

                    if (data?.status === 'confirm_delete') {
                        console.log('✅ Status confirm_delete confirmado');
                        const totalLancamentos = data?.total_lancamentos || 0;
                        const totalFaturas = data?.total_faturas || 0;
                        const totalItens = data?.total_itens || 0;
                        console.log('📊 Lançamentos:', totalLancamentos, 'Faturas:', totalFaturas, 'Itens:', totalItens);

                        let detalhes = '';
                        let totalGeral = totalLancamentos + totalFaturas + totalItens;

                        if (totalGeral > 0) {
                            detalhes = '<ul style="text-align:left; margin-top: 1rem; margin-bottom: 1rem;">';
                            if (totalLancamentos > 0) detalhes += `<li><b>${totalLancamentos}</b> lançamento(s)</li>`;
                            if (totalFaturas > 0) detalhes += `<li><b>${totalFaturas}</b> fatura(s)</li>`;
                            if (totalItens > 0) detalhes += `<li><b>${totalItens}</b> item(ns) de fatura</li>`;
                            detalhes += '</ul>';
                        } else {
                            // Fallback: usar a mensagem do servidor
                            detalhes = `<p style="margin: 1rem 0; white-space: pre-line;">${data.message || 'Nenhum dado vinculado encontrado'}</p>`;
                        }

                        console.log('📝 HTML detalhes:', detalhes);

                        const confirm = await Swal.fire({
                            title: 'Excluir cartão e TODOS os dados vinculados?',
                            html: `
                                <div style="text-align:left; padding: 1rem;">
                                    <p style="margin-bottom: 1rem;">O cartão <b>${nomeCartao.replace(/</g,'&lt;')}</b> possui os seguintes dados vinculados:</p>
                                    ${detalhes}
                                    <p style="margin-top: 1rem; color: #dc3545; font-weight: 600;">⚠️ Ao excluir o cartão, TODOS esses dados serão excluídos permanentemente!</p>
                                    <p style="margin-top: 0.5rem;">Esta ação não pode ser desfeita. Deseja continuar?</p>
                                </div>
                            `,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: '<i class="fas fa-trash"></i> Sim, excluir tudo',
                            cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                            confirmButtonColor: '#dc3545',
                            cancelButtonColor: '#6c757d',
                            reverseButtons: true
                        });

                        if (!confirm.isConfirmed) {
                            console.log('❌ Usuário cancelou a exclusão');
                            return;
                        }

                        console.log('✅ Usuário confirmou - excluindo com force=true');

                        // Excluir com force=true
                        const delRes = await fetchAPI(`cartoes/${id}/delete`, {
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

                        const delData = await safeJson(delRes);
                        console.log('📦 Resposta da exclusão com force:', delData);

                        if (!delRes.ok || !delData.success) {
                            throw new Error(delData?.message || 'Erro ao excluir');
                        }

                        const totalExcluido = (delData.deleted_lancamentos || 0) + (delData.deleted_faturas || 0) + (delData.deleted_itens || 0);

                        await Swal.fire({
                            icon: 'success',
                            title: 'Excluído!',
                            html: `<p><b>${nomeCartao}</b> e todos os dados vinculados foram excluídos permanentemente.</p>
                                   <p style="margin-top: 0.5rem; font-size: 0.9em; color: #6c757d;">Total de registros excluídos: ${totalExcluido}</p>`,
                            timer: 3000,
                            showConfirmButton: false
                        });

                        await load();
                        return;
                    }
                }

                if (!res.ok) {
                    const errData = await safeJson(res);
                    throw new Error(errData?.message || 'Erro ao excluir');
                }

                await Swal.fire({
                    icon: 'success',
                    title: 'Excluído!',
                    text: 'Cartão excluído com sucesso.',
                    timer: 2000,
                    showConfirmButton: false
                });

                await load();
            } catch (err) {
                console.error(err);
                Swal.fire({
                    title: 'Erro!',
                    text: err.message || 'Falha ao excluir.',
                    icon: 'error',
                    confirmButtonColor: '#e67e22'
                });
            }
        };

        // Carrega ao iniciar
        load();
    })();
</script>