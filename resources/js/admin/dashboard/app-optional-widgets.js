/**
 * ============================================================================
 * LUKRATO - Dashboard / Optional Widgets
 * ============================================================================
 * Section widgets extracted from app.js to keep dashboard app orchestration lean.
 * ============================================================================
 */

export function createOptionalWidgets({
    API,
    CONFIG,
    Utils,
    escapeHtml,
    logClientError,
}) {
    const OptionalWidgets = {
        getContainer: (sectionId, bodyId) => {
            const existing = document.getElementById(bodyId);
            if (existing) return existing;

            const section = document.getElementById(sectionId);
            if (!section) return null;

            const legacyBody = section.querySelector('.dash-optional-body');
            if (legacyBody) {
                if (!legacyBody.id) legacyBody.id = bodyId;
                return legacyBody;
            }

            const body = document.createElement('div');
            body.className = 'dash-optional-body';
            body.id = bodyId;

            const header = section.querySelector('.dash-section-header');
            const orphanPlaceholders = Array.from(section.children).filter((child) =>
                child.classList?.contains('dash-placeholder')
            );

            if (header?.nextSibling) {
                section.insertBefore(body, header.nextSibling);
            } else {
                section.appendChild(body);
            }

            orphanPlaceholders.forEach((node) => body.appendChild(node));

            return body;
        },

        renderLoading: (container) => {
            if (!container) return;

            container.innerHTML = `
                <div class="dash-widget dash-widget--loading" aria-hidden="true">
                    <div class="dash-widget-skeleton dash-widget-skeleton--title"></div>
                    <div class="dash-widget-skeleton dash-widget-skeleton--value"></div>
                    <div class="dash-widget-skeleton dash-widget-skeleton--text"></div>
                    <div class="dash-widget-skeleton dash-widget-skeleton--bar"></div>
                </div>
            `;
        },

        renderEmpty: (container, message, href, cta) => {
            if (!container) return;

            container.innerHTML = `
                <div class="dash-widget-empty">
                    <p>${message}</p>
                    ${href && cta ? `<a href="${href}" class="dash-widget-link">${cta}</a>` : ''}
                </div>
            `;
        },

        getUsageColor: (percent) => {
            if (percent >= 85) return '#ef4444';
            if (percent >= 60) return '#f59e0b';
            return '#10b981';
        },

        getAccountBalance: (account) => {
            const candidates = [
                account?.saldoAtual,
                account?.saldo_atual,
                account?.saldo,
                account?.saldoInicial,
                account?.saldo_inicial,
            ];

            const value = candidates.find((item) => Number.isFinite(Number(item)));
            return Number(value || 0);
        },

        renderMetas: async (month) => {
            const container = OptionalWidgets.getContainer('sectionMetas', 'sectionMetasBody');
            if (!container) return;

            OptionalWidgets.renderLoading(container);

            try {
                const summary = await API.getFinanceSummary(month);
                const metas = summary?.metas ?? null;

                if (!metas || Number(metas.total_metas || 0) === 0) {
                    OptionalWidgets.renderEmpty(
                        container,
                        'Você ainda não tem metas ativas neste momento.',
                        `${CONFIG.BASE_URL}financas#metas`,
                        'Criar meta'
                    );
                    return;
                }

                const proxima = metas.proxima_concluir || null;
                const pctGeral = Math.round(Number(metas.progresso_geral || 0));

                if (!proxima) {
                    container.innerHTML = `
                        <div class="dash-widget">
                            <span class="dash-widget-label">Metas ativas</span>
                            <strong class="dash-widget-value">${Number(metas.total_metas || 0)}</strong>
                            <p class="dash-widget-caption">Você tem metas em andamento, mas nenhuma está próxima de conclusão.</p>
                            <div class="dash-widget-meta">
                                <span>Progresso geral</span>
                                <strong>${pctGeral}%</strong>
                            </div>
                            <div class="dash-widget-progress">
                                <span style="width:${Math.min(pctGeral, 100)}%; background:var(--color-primary);"></span>
                            </div>
                            <a href="${CONFIG.BASE_URL}financas#metas" class="dash-widget-link">Criar metas</a>
                        </div>
                    `;
                    return;
                }

                const titulo = escapeHtml(String(proxima.titulo || 'Sua meta principal'));
                const valorAtual = Number(proxima.valor_atual || 0);
                const valorAlvo = Number(proxima.valor_alvo || 0);
                const faltam = Math.max(valorAlvo - valorAtual, 0);
                const pct = Math.round(Number(proxima.progresso || 0));
                const cor = proxima.cor || 'var(--color-primary)';

                container.innerHTML = `
                    <div class="dash-widget">
                        <span class="dash-widget-label">Próxima meta</span>
                        <strong class="dash-widget-value">${titulo}</strong>
                        <p class="dash-widget-caption">Faltam ${Utils.money(faltam)} para concluir.</p>
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(pct, 100)}%; background:${cor};"></span>
                        </div>
                        <div class="dash-widget-meta">
                            <span>${Utils.money(valorAtual)} de ${Utils.money(valorAlvo)}</span>
                            <strong style="color:${cor};">${pct}%</strong>
                        </div>
                        <a href="${CONFIG.BASE_URL}financas#metas" class="dash-widget-link">Criar metas</a>
                    </div>
                `;
            } catch (error) {
                logClientError('Erro ao carregar widget de metas', error, 'Falha ao carregar metas');
                OptionalWidgets.renderEmpty(container, 'Não foi possível carregar suas metas agora.', `${CONFIG.BASE_URL}financas#metas`, 'Tentar nas finanças');
            }
        },

        renderCartoes: async () => {
            const container = OptionalWidgets.getContainer('sectionCartoes', 'sectionCartoesBody');
            if (!container) return;

            OptionalWidgets.renderLoading(container);

            try {
                const summary = await API.getCardsSummary();
                const totalCartoes = Number(summary?.total_cartoes || 0);

                if (!summary || totalCartoes === 0) {
                    OptionalWidgets.renderEmpty(
                        container,
                        'Você ainda não tem cartões ativos no dashboard.',
                        `${CONFIG.BASE_URL}cartoes`,
                        'Cadastrar cartão'
                    );
                    return;
                }

                const limiteDisponivel = Number(summary.limite_disponivel || 0);
                const limiteTotal = Number(summary.limite_total || 0);
                const percentualUso = Math.round(Number(summary.percentual_uso || 0));
                const usoColor = OptionalWidgets.getUsageColor(percentualUso);

                container.innerHTML = `
                    <div class="dash-widget">
                        <span class="dash-widget-label">Limite disponível</span>
                        <strong class="dash-widget-value">${Utils.money(limiteDisponivel)}</strong>
                        <p class="dash-widget-caption">${totalCartoes} cartão(ões) ativo(s) com ${percentualUso}% de uso consolidado.</p>
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(percentualUso, 100)}%; background:${usoColor};"></span>
                        </div>
                        <div class="dash-widget-meta">
                            <span>Limite total ${Utils.money(limiteTotal)}</span>
                            <strong style="color:${usoColor};">${percentualUso}% usado</strong>
                        </div>
                        <a href="${CONFIG.BASE_URL}cartoes" class="dash-widget-link">Criar cartões</a>
                    </div>
                `;
            } catch (error) {
                logClientError('Erro ao carregar widget de cartões', error, 'Falha ao carregar cartões');
                OptionalWidgets.renderEmpty(container, 'Não foi possível carregar seus cartões agora.', `${CONFIG.BASE_URL}cartoes`, 'Criar cartões');
            }
        },

        renderContas: async (month) => {
            const container = OptionalWidgets.getContainer('sectionContas', 'sectionContasBody');
            if (!container) return;

            OptionalWidgets.renderLoading(container);

            try {
                const accounts = await API.getAccountsBalances(month);
                const contas = Array.isArray(accounts) ? accounts : [];

                if (contas.length === 0) {
                    OptionalWidgets.renderEmpty(
                        container,
                        'Você ainda não tem contas ativas conectadas.',
                        `${CONFIG.BASE_URL}contas`,
                        'Adicionar conta'
                    );
                    return;
                }

                const sorted = contas
                    .map((account) => ({
                        ...account,
                        __saldo: OptionalWidgets.getAccountBalance(account),
                    }))
                    .sort((left, right) => right.__saldo - left.__saldo);

                const totalSaldo = sorted.reduce((sum, account) => sum + account.__saldo, 0);
                const principal = sorted[0] || null;
                const principalNome = escapeHtml(String(
                    principal?.nome ||
                    principal?.nome_conta ||
                    principal?.instituicao ||
                    principal?.banco_nome ||
                    'Conta principal'
                ));
                const principalSaldo = principal ? Utils.money(principal.__saldo) : Utils.money(0);

                container.innerHTML = `
                    <div class="dash-widget">
                        <span class="dash-widget-label">Saldo consolidado</span>
                        <strong class="dash-widget-value">${Utils.money(totalSaldo)}</strong>
                        <p class="dash-widget-caption">${sorted.length} conta(s) ativa(s) no painel.</p>
                        <div class="dash-widget-list">
                            ${sorted.slice(0, 3).map((account) => {
                    const label = escapeHtml(String(
                        account.nome ||
                        account.nome_conta ||
                        account.instituicao ||
                        account.banco_nome ||
                        'Conta'
                    ));
                    return `
                                    <div class="dash-widget-list-item">
                                        <span>${label}</span>
                                        <strong>${Utils.money(account.__saldo)}</strong>
                                    </div>
                                `;
                }).join('')}
                        </div>
                        <div class="dash-widget-meta">
                            <span>Maior saldo em ${principalNome}</span>
                            <strong>${principalSaldo}</strong>
                        </div>
                        <a href="${CONFIG.BASE_URL}contas" class="dash-widget-link">Criar contas +</a>
                    </div>
                `;
            } catch (error) {
                logClientError('Erro ao carregar widget de contas', error, 'Falha ao carregar contas');
                OptionalWidgets.renderEmpty(container, 'Não foi possível carregar suas contas agora.', `${CONFIG.BASE_URL}contas`, 'Criar contas +');
            }
        },

        renderOrcamentos: async (month) => {
            const container = OptionalWidgets.getContainer('sectionOrcamentos', 'sectionOrcamentosBody');
            if (!container) return;

            OptionalWidgets.renderLoading(container);

            try {
                const summary = await API.getFinanceSummary(month);
                const orcamento = summary?.orcamento ?? null;

                if (!orcamento || Number(orcamento.total_categorias || 0) === 0) {
                    OptionalWidgets.renderEmpty(
                        container,
                        'Você ainda não definiu limites para categorias.',
                        `${CONFIG.BASE_URL}financas#orcamentos`,
                        'Definir limite'
                    );
                    return;
                }

                const pctGeral = Math.round(Number(orcamento.percentual_geral || 0));
                const usageColor = OptionalWidgets.getUsageColor(pctGeral);
                const top3 = (orcamento.orcamentos || [])
                    .slice()
                    .sort((a, b) => Number(b.percentual || 0) - Number(a.percentual || 0))
                    .slice(0, 3);

                const itemsHtml = top3.map(orc => {
                    const color = OptionalWidgets.getUsageColor(orc.percentual);
                    return `
                        <div class="dash-widget-list-item">
                            <span>${escapeHtml(orc.categoria_nome || 'Categoria')}</span>
                            <strong style="color:${color};">${Math.round(orc.percentual || 0)}%</strong>
                        </div>
                    `;
                }).join('');

                container.innerHTML = `
                    <div class="dash-widget">
                        <span class="dash-widget-label">Uso geral dos limites</span>
                        <strong class="dash-widget-value" style="color:${usageColor};">${pctGeral}%</strong>
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(pctGeral, 100)}%; background:${usageColor};"></span>
                        </div>
                        <p class="dash-widget-caption">${Utils.money(orcamento.total_gasto || 0)} de ${Utils.money(orcamento.total_limite || 0)}</p>
                        ${itemsHtml ? `<div class="dash-widget-list">${itemsHtml}</div>` : ''}
                        <a href="${CONFIG.BASE_URL}financas#orcamentos" class="dash-widget-link">Ver orçamentos</a>
                    </div>
                `;
            } catch (error) {
                logClientError('Erro ao carregar widget de orçamentos', error, 'Falha ao carregar orçamentos');
                OptionalWidgets.renderEmpty(container, 'Não foi possível carregar seus orçamentos.', `${CONFIG.BASE_URL}financas#orcamentos`, 'Abrir orçamentos');
            }
        },

        renderFaturas: async () => {
            const container = OptionalWidgets.getContainer('sectionFaturas', 'sectionFaturasBody');
            if (!container) return;

            OptionalWidgets.renderLoading(container);

            try {
                const summary = await API.getCardsSummary();
                const totalCartoes = Number(summary?.total_cartoes || 0);

                if (!summary || totalCartoes === 0) {
                    OptionalWidgets.renderEmpty(
                        container,
                        'Você não tem cartões com faturas abertas.',
                        `${CONFIG.BASE_URL}faturas`,
                        'Criar faturas +'
                    );
                    return;
                }

                const faturaAberta = Number(summary.fatura_aberta ?? summary.limite_utilizado ?? 0);
                const limiteTotal = Number(summary.limite_total || 0);
                const pctUso = limiteTotal > 0
                    ? Math.round((faturaAberta / limiteTotal) * 100)
                    : Number(summary.percentual_uso || 0);
                const usageColor = OptionalWidgets.getUsageColor(pctUso);

                container.innerHTML = `
                    <div class="dash-widget">
                        <span class="dash-widget-label">Fatura atual</span>
                        <strong class="dash-widget-value">${Utils.money(faturaAberta)}</strong>
                        ${limiteTotal > 0 ? `
                            <div class="dash-widget-progress">
                                <span style="width:${Math.min(pctUso, 100)}%; background:${usageColor};"></span>
                            </div>
                            <p class="dash-widget-caption">${pctUso}% do limite utilizado</p>
                        ` : `
                            <p class="dash-widget-caption">${totalCartoes} cartão(ões) ativo(s)</p>
                        `}
                        <a href="${CONFIG.BASE_URL}faturas" class="dash-widget-link">Abrir faturas</a>
                    </div>
                `;
            } catch (error) {
                logClientError('Erro ao carregar widget de faturas', error, 'Falha ao carregar faturas');
                OptionalWidgets.renderEmpty(container, 'Não foi possível carregar suas faturas.', `${CONFIG.BASE_URL}faturas`, 'Ver faturas');
            }
        },

        render: async (month) => {
            await Promise.allSettled([
                OptionalWidgets.renderMetas(month),
                OptionalWidgets.renderCartoes(),
                OptionalWidgets.renderContas(month),
                OptionalWidgets.renderOrcamentos(month),
                OptionalWidgets.renderFaturas(),
            ]);
        },
    };

    return OptionalWidgets;
}
