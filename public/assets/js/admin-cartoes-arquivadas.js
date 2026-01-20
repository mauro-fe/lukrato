
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
        }[m]));
    }

    let _rows = [];

    function getDefaultColor(bandeira) {
        const colors = {
            'visa': '#1a1f71',
            'mastercard': '#eb001b',
            'elo': '#ffcb05',
            'amex': '#006fcf',
            'hipercard': '#d9001b'
        };
        return colors[bandeira?.toLowerCase()] || '#e67e22';
    }

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
            const bandeira = (c.bandeira || 'Desconhecida').toLowerCase();
            const limite = formatMoneyBR(c.limite_total || 0);
            const disponivel = formatMoneyBR(c.limite_disponivel || 0);
            const ultimos = c.ultimos_digitos || '0000';

            // Obter cor da instituição financeira (mesma lógica da página principal)
            const cor = c.conta?.instituicao_financeira?.cor_primaria ||
                c.instituicao_cor ||
                c.cor_cartao ||
                getDefaultColor(bandeira);

            const id = c.id;

            // Mapear bandeira para logos
            const bandeirasLogos = {
                'visa': `${BASE}assets/img/bandeiras/visa.png`,
                'mastercard': `${BASE}assets/img/bandeiras/mastercard.png`,
                'elo': `${BASE}assets/img/bandeiras/elo.png`,
                'amex': `${BASE}assets/img/bandeiras/amex.png`,
                'hipercard': `${BASE}assets/img/bandeiras/hipercard.png`,
            };

            const logoSrc = bandeirasLogos[bandeira] || '';
            const brandHTML = logoSrc ?
                `<img src="${logoSrc}" alt="${bandeira}" class="brand-logo">` :
                `<i class="fas fa-credit-card brand-icon-fallback"></i>`;

            return `
            <div class="credit-card" data-brand="${bandeira}" data-id="${id}" style="background: ${cor}">
                <div class="card-header">
                    <div class="card-brand">
                        ${brandHTML}
                        <span class="card-name">${nome}</span>
                    </div>
                    <div class="card-actions">
                        <button class="card-action-btn" onclick="handleRestore(${id})" title="Restaurar">
                            <i class="fas fa-undo"></i>
                        </button>
                        <button class="card-action-btn" onclick="handleHardDelete(${id}, '${nome.replace(/'/g, "\\'")}')" title="Excluir permanentemente">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>

            <div class="card-number">
                **** **** **** ${ultimos}
            </div>

            <div class="card-footer">
                <div class="card-holder">
                    <div class="card-label">Limite Disponível</div>
                    <div class="card-value">R$ ${disponivel}</div>
                </div>
                <div class="card-limit">
                    <div class="card-label">Limite Total</div>
                    <div class="card-value">R$ ${limite}</div>
                </div>
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

    window.handleRestore = async function (id) {
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

    window.handleHardDelete = async function (id, nome = '') {
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

            const data = await safeJson(res);

            if (res.status === 422) {

                if (data?.status === 'confirm_delete') {
                    const totalLancamentos = data?.total_lancamentos || 0;
                    const totalFaturas = data?.total_faturas || 0;
                    const totalItens = data?.total_itens || 0;


                    let detalhes = '';
                    let totalGeral = totalLancamentos + totalFaturas + totalItens;

                    if (totalGeral > 0) {
                        detalhes = '<ul style="text-align:left; margin-top: 1rem; margin-bottom: 1rem;">';
                        if (totalLancamentos > 0) detalhes +=
                            `<li><b>${totalLancamentos}</b> lançamento(s)</li>`;
                        if (totalFaturas > 0) detalhes += `<li><b>${totalFaturas}</b> fatura(s)</li>`;
                        if (totalItens > 0) detalhes += `<li><b>${totalItens}</b> item(ns) de fatura</li>`;
                        detalhes += '</ul>';
                    } else {
                        // Fallback: usar a mensagem do servidor
                        detalhes =
                            `<p style="margin: 1rem 0; white-space: pre-line;">${data.message || 'Nenhum dado vinculado encontrado'}</p>`;
                    }


                    const confirm = await Swal.fire({
                        title: 'Excluir cartão e TODOS os dados vinculados?',
                        html: `
        <div style="text-align:left; padding: 1rem;">
            <p style="margin-bottom: 1rem;">O cartão <b>${nomeCartao.replace(/</g, '&lt;')}</b> possui os seguintes dados vinculados:</p>
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
                        return;
                    }


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

                    if (!delRes.ok || !delData.success) {
                        throw new Error(delData?.message || 'Erro ao excluir');
                    }

                    const totalExcluido = (delData.deleted_lancamentos || 0) + (delData.deleted_faturas ||
                        0) + (delData.deleted_itens || 0);

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