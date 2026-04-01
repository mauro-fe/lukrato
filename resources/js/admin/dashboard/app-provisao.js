/**
 * ============================================================================
 * LUKRATO - Dashboard / Provisao
 * ============================================================================
 * Financial forecasting and upcoming due dates extracted from app.js.
 * ============================================================================
 */

export function createProvisao({
    API,
    Utils,
    escapeHtml,
    logClientError,
}) {
    const Provisao = {
        isProUser: null,

        checkProStatus: async () => {
            try {
                const overview = await API.getOverview(Utils.getCurrentMonth());
                Provisao.isProUser = overview?.plan?.is_pro === true;
            } catch {
                Provisao.isProUser = false;
            }

            return Provisao.isProUser;
        },

        render: async (month) => {
            const section = document.getElementById('sectionPrevisao');
            if (!section) return;

            // Always re-check Pro status to avoid stale cache
            await Provisao.checkProStatus();

            const overlay = document.getElementById('provisaoProOverlay');
            const isPro = Provisao.isProUser;

            // Sempre carrega dados reais (Free mostra só faturas, Pro mostra tudo)
            section.classList.remove('is-locked');
            if (overlay) overlay.style.display = 'none';

            try {
                const overview = await API.getOverview(month);
                Provisao.renderData(overview.provisao || null, isPro);
            } catch (err) {
                logClientError('Erro ao carregar provisão', err, 'Falha ao carregar previsão');
            }
        },

        renderData: (data, isPro = true) => {
            if (!data) return;

            const p = data.provisao || {};
            const money = Utils.money;
            const titleSummaryEl = document.getElementById('provisaoTitle');
            const headlineEl = document.getElementById('provisaoHeadline');

            if (titleSummaryEl) {
                titleSummaryEl.textContent = `Se continuar assim, você termina o mês com ${money(p.saldo_projetado || 0)}`;
            }

            if (headlineEl) {
                headlineEl.textContent = (p.saldo_projetado || 0) >= 0
                    ? 'A previsão abaixo considera seu saldo atual, o que ainda vai entrar e o que ainda vai sair.'
                    : 'A previsao indica aperto no fim do mes se o ritmo atual continuar.';
            }

            // Atualizar título e link conforme plano
            const titleEl = document.getElementById('provisaoProximosTitle');
            const verTodosEl = document.getElementById('provisaoVerTodos');
            if (titleEl) {
                titleEl.innerHTML = isPro
                    ? '<i data-lucide="clock"></i> Próximos Vencimentos'
                    : '<i data-lucide="credit-card"></i> Próximas Faturas';
            }
            if (verTodosEl) {
                verTodosEl.href = isPro
                    ? `${window.BASE_URL || '/'}lancamentos`
                    : `${window.BASE_URL || '/'}faturas`;
            }

            // Cards
            const pagar = document.getElementById('provisaoPagar');
            const receber = document.getElementById('provisaoReceber');
            const projetado = document.getElementById('provisaoProjetado');
            const pagarCount = document.getElementById('provisaoPagarCount');
            const receberCount = document.getElementById('provisaoReceberCount');
            const projetadoLabel = document.getElementById('provisaoProjetadoLabel');

            // Card A Receber - só mostra dados para Pro
            const receberCard = receber?.closest('.provisao-card');

            if (pagar) pagar.textContent = money(p.a_pagar || 0);

            if (isPro) {
                if (receber) receber.textContent = money(p.a_receber || 0);
                if (receberCard) receberCard.style.opacity = '1';
            } else {
                // Free: esconde o card A Receber ou mostra como bloqueado
                if (receber) receber.textContent = 'R$ --';
                if (receberCard) receberCard.style.opacity = '0.5';
            }

            if (projetado) {
                projetado.textContent = money(p.saldo_projetado || 0);
                projetado.style.color = (p.saldo_projetado || 0) >= 0 ? '' : 'var(--color-danger)';
            }

            // Contador de A Pagar com faturas de cartão
            if (pagarCount) {
                const countAgend = p.count_pagar || 0;
                const countFat = p.count_faturas || 0;

                if (isPro) {
                    let pagarText = `${countAgend} pendente${countAgend !== 1 ? 's' : ''}`;
                    if (countFat > 0) {
                        pagarText += ` • ${countFat} fatura${countFat !== 1 ? 's' : ''}`;
                    }
                    pagarCount.textContent = pagarText;
                } else {
                    // Free: mostra apenas faturas
                    pagarCount.textContent = `${countFat} fatura${countFat !== 1 ? 's' : ''}`;
                }
            }

            if (isPro) {
                if (receberCount) receberCount.textContent = `${p.count_receber || 0} pendente${(p.count_receber || 0) !== 1 ? 's' : ''}`;
            } else {
                if (receberCount) receberCount.textContent = 'Pro';
            }

            if (projetadoLabel) projetadoLabel.textContent = `saldo atual: ${money(p.saldo_atual || 0)}`;

            // Alertas de vencidos (separados por tipo)
            const vencidos = data.vencidos || {};

            // Alerta de despesas vencidas (só Pro)
            const alertDespesas = document.getElementById('provisaoAlertDespesas');
            if (alertDespesas) {
                const despesas = vencidos.despesas || {};
                if (isPro && (despesas.count || 0) > 0) {
                    alertDespesas.style.display = 'flex';
                    const countEl = document.getElementById('provisaoAlertDespesasCount');
                    const totalEl = document.getElementById('provisaoAlertDespesasTotal');
                    if (countEl) countEl.textContent = despesas.count;
                    if (totalEl) totalEl.textContent = money(despesas.total || 0);
                } else {
                    alertDespesas.style.display = 'none';
                }
            }

            // Alerta de receitas vencidas (não recebidas) - só Pro
            const alertReceitas = document.getElementById('provisaoAlertReceitas');
            if (alertReceitas) {
                const receitas = vencidos.receitas || {};
                if (isPro && (receitas.count || 0) > 0) {
                    alertReceitas.style.display = 'flex';
                    const countEl = document.getElementById('provisaoAlertReceitasCount');
                    const totalEl = document.getElementById('provisaoAlertReceitasTotal');
                    if (countEl) countEl.textContent = receitas.count;
                    if (totalEl) totalEl.textContent = money(receitas.total || 0);
                } else {
                    alertReceitas.style.display = 'none';
                }
            }

            // Alerta de faturas vencidas
            const alertFaturas = document.getElementById('provisaoAlertFaturas');
            if (alertFaturas) {
                const countFat = vencidos.count_faturas || 0;
                if (countFat > 0) {
                    alertFaturas.style.display = 'flex';
                    const countEl = document.getElementById('provisaoAlertFaturasCount');
                    const totalEl = document.getElementById('provisaoAlertFaturasTotal');
                    if (countEl) countEl.textContent = countFat;
                    if (totalEl) totalEl.textContent = money(vencidos.total_faturas || 0);
                } else {
                    alertFaturas.style.display = 'none';
                }
            }

            // Próximos vencimentos
            const list = document.getElementById('provisaoProximosList');
            const emptyEl = document.getElementById('provisaoEmpty');
            let proximos = data.proximos || [];

            // Free: filtra para mostrar apenas faturas
            if (!isPro) {
                proximos = proximos.filter(item => item.is_fatura === true);
            }

            if (list) {
                if (proximos.length === 0) {
                    list.innerHTML = '';
                    if (emptyEl) {
                        // Ajusta mensagem conforme plano
                        const emptyText = emptyEl.querySelector('span');
                        if (emptyText) {
                            emptyText.textContent = isPro ? 'Nenhum vencimento pendente' : 'Nenhuma fatura pendente';
                        }
                        list.appendChild(emptyEl);
                        emptyEl.style.display = 'flex';
                    }
                } else {
                    list.innerHTML = '';
                    const today = new Date().toISOString().slice(0, 10);

                    proximos.forEach(item => {
                        const tipo = (item.tipo || '').toLowerCase();
                        const isFatura = item.is_fatura === true;
                        const dataParts = (item.data_pagamento || '').split(/[T\s]/)[0];
                        const isHoje = dataParts === today;
                        const dateDisplay = Provisao.formatDateShort(dataParts);

                        let badges = '';
                        if (isHoje) badges += '<span class="provisao-item-badge vence-hoje">Hoje</span>';

                        if (isFatura) {
                            // Badge especial para fatura de cartão
                            badges += '<span class="provisao-item-badge fatura"><i data-lucide="credit-card"></i> Fatura</span>';
                            if (item.cartao_ultimos_digitos) {
                                badges += `<span>****${item.cartao_ultimos_digitos}</span>`;
                            }
                        } else {
                            if (item.eh_parcelado && item.numero_parcelas > 1) {
                                badges += `<span class="provisao-item-badge parcela">${item.parcela_atual}/${item.numero_parcelas}</span>`;
                            }
                            if (item.recorrente) {
                                badges += '<span class="provisao-item-badge recorrente">Recorrente</span>';
                            }
                            if (item.categoria) {
                                badges += `<span>${escapeHtml(item.categoria)}</span>`;
                            }
                        }

                        const tipoClass = isFatura ? 'fatura' : tipo;
                        const el = document.createElement('div');
                        el.className = 'provisao-item' + (isFatura ? ' is-fatura' : '');
                        el.innerHTML = `
                                <div class="provisao-item-dot ${tipoClass}"></div>
                                <div class="provisao-item-info">
                                    <div class="provisao-item-titulo">${escapeHtml(item.titulo || 'Sem título')}</div>
                                    <div class="provisao-item-meta">${badges}</div>
                                </div>
                                <span class="provisao-item-valor ${tipoClass}">${money(item.valor || 0)}</span>
                                <span class="provisao-item-data">${dateDisplay}</span>
                            `;

                        // Adicionar link para faturas
                        if (isFatura && item.cartao_id) {
                            el.style.cursor = 'pointer';
                            el.addEventListener('click', () => {
                                const dataVenc = (item.data_pagamento || '').split(/[T\s]/)[0];
                                const [ano, mes] = dataVenc.split('-');
                                window.location.href = `${window.BASE_URL || '/'}faturas?cartao_id=${item.cartao_id}&mes=${parseInt(mes, 10)}&ano=${ano}`;
                            });
                        }

                        list.appendChild(el);
                    });
                }
            }

            // Parcelas ativas (só Pro)
            const parcelasEl = document.getElementById('provisaoParcelas');
            const parcelas = data.parcelas || {};
            if (parcelasEl) {
                if (isPro && (parcelas.ativas || 0) > 0) {
                    parcelasEl.style.display = 'flex';
                    const textEl = document.getElementById('provisaoParcelasText');
                    const valorEl = document.getElementById('provisaoParcelasValor');
                    if (textEl) textEl.textContent = `${parcelas.ativas} parcelamento${parcelas.ativas !== 1 ? 's' : ''} ativo${parcelas.ativas !== 1 ? 's' : ''}`;
                    if (valorEl) valorEl.textContent = `${money(parcelas.total_mensal || 0)}/mês`;
                } else {
                    parcelasEl.style.display = 'none';
                }
            }
        },

        formatDateShort: (dateStr) => {
            if (!dateStr) return '-';
            try {
                const m = dateStr.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                return m ? `${m[3]}/${m[2]}` : '-';
            } catch {
                return '-';
            }
        },
    };

    return Provisao;
}
