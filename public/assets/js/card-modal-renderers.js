/**
 * ============================================================================
 * RENDERIZADORES DE COMPONENTES DO MODAL DE CARTÃO
 * ============================================================================
 * Funções auxiliares para renderizar partes específicas do modal
 * ============================================================================
 */

const CardModalRenderers = (() => {
    'use strict';

    const Utils = {
        formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(Number(value) || 0);
        },

        escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, function (match) {
                const replacements = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                };
                return replacements[match] ?? match;
            });
        }
    };

    function renderLancamentos(lancamentos) {
        if (!lancamentos || lancamentos.length === 0) {
            return '<div class="empty-message"><i class="fas fa-inbox"></i><p>Nenhum lançamento neste mês</p></div>';
        }

        return lancamentos.map(lanc => `
            <div class="lancamento-row">
                <div class="lancamento-left">
                    <div class="lancamento-category" style="background: ${lanc.categoria_cor}20; color: ${lanc.categoria_cor};">
                        ${Utils.escapeHtml(lanc.categoria)}
                    </div>
                    <div class="lancamento-description">
                        ${Utils.escapeHtml(lanc.descricao)}
                        ${lanc.eh_parcelado ? `<span class="parcela-tag">${lanc.parcela_info}</span>` : ''}
                    </div>
                    <div class="lancamento-date">${new Date(lanc.data).toLocaleDateString('pt-BR')}</div>
                </div>
                <div class="lancamento-amount">${Utils.formatCurrency(lanc.valor)}</div>
            </div>
        `).join('');
    }

    function renderComparison(diferenca) {
        if (Math.abs(diferenca.absoluta) <= 1) return '';

        return `
            <span class="comparison-label">vs mês anterior</span>
            <span class="comparison-value ${diferenca.absoluta > 0 ? 'negative' : 'positive'}">
                ${diferenca.absoluta > 0 ? '↑' : '↓'} 
                ${Utils.formatCurrency(Math.abs(diferenca.absoluta))} 
                (${diferenca.percentual > 0 ? '+' : ''}${diferenca.percentual.toFixed(1)}%)
            </span>
        `;
    }

    function renderParcelamentosTable(parcelamentos) {
        return `
            <table class="parcelamentos-table">
                <thead>
                    <tr>
                        <th>Compra</th>
                        <th>Categoria</th>
                        <th>Progresso</th>
                        <th>Valor/Mês</th>
                        <th>Restante</th>
                        <th>Término</th>
                    </tr>
                </thead>
                <tbody>
                    ${parcelamentos.map(parc => {
            const progress = ((parc.total_parcelas - parc.parcelas_restantes) / parc.total_parcelas) * 100;
            return `
                            <tr>
                                <td><strong>${Utils.escapeHtml(parc.descricao)}</strong></td>
                                <td>
                                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: ${parc.categoria_cor}; margin-right: 0.5rem;"></span>
                                    ${Utils.escapeHtml(parc.categoria)}
                                </td>
                                <td>
                                    <div class="parcela-progress">
                                        <span style="font-size: 0.75rem; color: var(--color-text-muted);">${parc.total_parcelas - parc.parcelas_restantes}/${parc.total_parcelas}</span>
                                        <div class="parcela-bar">
                                            <div class="parcela-bar-fill" style="width: ${progress}%; background: ${parc.categoria_cor};"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>${Utils.formatCurrency(parc.valor_parcela)}</td>
                                <td><strong>${Utils.formatCurrency(parc.valor_total_restante)}</strong></td>
                                <td style="font-size: 0.875rem; color: var(--color-text-muted);">${parc.data_final}</td>
                            </tr>
                        `;
        }).join('')}
                </tbody>
            </table>
        `;
    }

    function renderParcelamentosMobile(parcelamentos) {
        return parcelamentos.map(parc => {
            const progress = ((parc.total_parcelas - parc.parcelas_restantes) / parc.total_parcelas) * 100;
            return `
                <div class="parcelamento-card-mobile">
                    <div class="parcelamento-card-header">
                        <div class="parcelamento-card-title">
                            <span class="categoria-dot" style="background: ${parc.categoria_cor};"></span>
                            <strong>${Utils.escapeHtml(parc.descricao)}</strong>
                        </div>
                        <button class="btn-ver-detalhes" onclick="this.closest('.parcelamento-card-mobile').classList.toggle('expanded')">
                            <i class="fas fa-chevron-down"></i>
                            <span>Detalhes</span>
                        </button>
                    </div>
                    <div class="parcelamento-card-summary">
                        <span class="valor-mensal">${Utils.formatCurrency(parc.valor_parcela)}/mês</span>
                        <span class="parcelas-info">${parc.total_parcelas - parc.parcelas_restantes}/${parc.total_parcelas} parcelas</span>
                    </div>
                    <div class="parcelamento-card-progress">
                        <div class="parcela-bar">
                            <div class="parcela-bar-fill" style="width: ${progress}%; background: ${parc.categoria_cor};"></div>
                        </div>
                    </div>
                    <div class="parcelamento-card-details">
                        <div class="detail-row">
                            <span class="detail-label">Categoria</span>
                            <span class="detail-value">
                                <span class="categoria-dot" style="background: ${parc.categoria_cor};"></span>
                                ${Utils.escapeHtml(parc.categoria)}
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Valor por Parcela</span>
                            <span class="detail-value">${Utils.formatCurrency(parc.valor_parcela)}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Total Restante</span>
                            <span class="detail-value highlight">${Utils.formatCurrency(parc.valor_total_restante)}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Término Previsto</span>
                            <span class="detail-value">${parc.data_final}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderParcelamentos(data) {
        if (!data || data.quantidade === 0) {
            return '<div class="empty-message"><i class="fas fa-check-circle"></i><p>Nenhum parcelamento ativo</p></div>';
        }

        return `
            <div class="parcelamentos-table-wrapper">
                ${renderParcelamentosTable(data.ativos)}
            </div>
            <div class="parcelamentos-mobile-list">
                ${renderParcelamentosMobile(data.ativos)}
            </div>
        `;
    }

    function renderInsights(insights) {
        if (!insights) return '';

        const cards = [];

        if (insights.tendencia) {
            cards.push(`
                <div class="insight-card insight-${insights.tendencia.type}">
                    <div class="insight-icon">
                        <i class="fas ${insights.tendencia.icon}"></i>
                    </div>
                    <div class="insight-content">
                        <div class="insight-header-row">
                            <span class="insight-label">Tendência</span>
                            <span class="insight-badge">${insights.tendencia.variacao}</span>
                        </div>
                        <h4 class="insight-status">${insights.tendencia.status}</h4>
                        <p class="insight-desc">${insights.tendencia.descricao}</p>
                        <p class="insight-recommendation">
                            <i class="fas fa-star"></i> ${insights.tendencia.recomendacao}
                        </p>
                    </div>
                </div>
            `);
        }

        if (insights.parcelamentos) {
            cards.push(`
                <div class="insight-card insight-${insights.parcelamentos.type}">
                    <div class="insight-icon">
                        <i class="fas ${insights.parcelamentos.icon}"></i>
                    </div>
                    <div class="insight-content">
                        <div class="insight-header-row">
                            <span class="insight-label">Parcelamentos</span>
                            <span class="insight-badge">${insights.parcelamentos.valor}</span>
                        </div>
                        <h4 class="insight-status">${insights.parcelamentos.status}</h4>
                        <p class="insight-desc">${insights.parcelamentos.descricao}</p>
                        <p class="insight-recommendation">
                            <i class="fas fa-star"></i> ${insights.parcelamentos.recomendacao}
                        </p>
                    </div>
                </div>
            `);
        }

        if (insights.limite) {
            cards.push(`
                <div class="insight-card insight-${insights.limite.type}">
                    <div class="insight-icon">
                        <i class="fas ${insights.limite.icon}"></i>
                    </div>
                    <div class="insight-content">
                        <div class="insight-header-row">
                            <span class="insight-label">Uso do Limite</span>
                            <span class="insight-badge">${insights.limite.percentual}</span>
                        </div>
                        <h4 class="insight-status">${insights.limite.status}</h4>
                        <p class="insight-desc">${insights.limite.descricao}</p>
                        <p class="insight-recommendation">
                            <i class="fas fa-star"></i> ${insights.limite.recomendacao}
                        </p>
                    </div>
                </div>
            `);
        }

        if (cards.length === 0) return '';

        return `
            <div class="insights-header">
                <i class="fas fa-lightbulb"></i>
                <h3>Análise Inteligente</h3>
            </div>
            <div class="insights-grid">
                ${cards.join('')}
            </div>
        `;
    }

    return {
        renderLancamentos,
        renderComparison,
        renderParcelamentos,
        renderInsights,
        formatCurrency: Utils.formatCurrency,
        escapeHtml: Utils.escapeHtml
    };
})();
