/**
 * ============================================================================
 * MODAL DE RELATÓRIO DETALHADO DE CARTÃO
 * ============================================================================
 * Gerencia a exibição do relatório detalhado de um cartão de crédito
 * ============================================================================
 */

(() => {
    'use strict';

    const BASE_URL = (() => {
        const meta = document.querySelector('meta[name="base-url"]');
        if (meta) return meta.content.replace(/\/?$/, '/');
        const base = document.querySelector('base[href]');
        if (base) return base.href.replace(/\/?$/, '/');
        return window.BASE_URL ? String(window.BASE_URL).replace(/\/?$/, '/') : '/';
    })();

    let detailChart = null;
    let evolutionChart = null;
    let impactChart = null;

    const Utils = {
        formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(Number(value) || 0);
        },

        hexToRgba(hex, alpha = 0.1) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        },

        getCssVar(name, fallback = '') {
            try {
                const value = getComputedStyle(document.documentElement).getPropertyValue(name);
                return (value || '').trim() || fallback;
            } catch {
                return fallback;
            }
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

    async function openCardDetailModal(cardId, cardName, cardColor, currentMonth) {
        if (!cardId) {
            console.error('ID do cartão não fornecido');
            return;
        }

        try {
            const [year, month] = currentMonth.split('-');
            const params = new URLSearchParams({
                mes: month,
                ano: year
            });

            const url = `${BASE_URL}api/reports/card-details/${cardId}?${params}`;

            const response = await fetch(url, {
                credentials: 'include'
            });

            if (!response.ok) {
                console.error('❌ Erro na resposta:', response.status, response.statusText);
                const text = await response.text();
                console.error('❌ Response body:', text.substring(0, 500));
                throw new Error('Erro ao carregar detalhes do cartão');
            }

            const responseText = await response.text();

            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('❌ Erro ao fazer parse do JSON:', e);
                console.error('❌ Response completa:', responseText.substring(0, 1000));
                throw new Error('Resposta inválida do servidor (não é JSON)');
            }



            // Response::success retorna {status: 'success', message: '...', data: {...}}
            if (data.status !== 'success' || !data.data) {
                console.error('❌ Validação falhou:', data);
                throw new Error(data.message || 'Dados inválidos retornados');
            }

            renderCardDetailModal(data.data, cardColor);
        } catch (error) {
            console.error('Erro ao abrir detalhes do cartão:', error);
            alert('Erro ao carregar relatório detalhado. Tente novamente.');
        }
    }

    function renderCardDetailModal(data, cardColor) {
        // Remover modal existente se houver
        const existingModal = document.getElementById('cardDetailModalOverlay');
        if (existingModal) {
            existingModal.remove();
        }

        const modal = document.createElement('div');
        modal.id = 'cardDetailModalOverlay';
        modal.className = 'card-detail-modal-overlay';

        const percentualLimiteGeral = data.cartao.percentual_utilizacao_geral || 0;
        const limiteDisponivel = data.cartao.limite_disponivel || 0;

        modal.innerHTML = `
            <div class="card-detail-modal">
                <div class="card-detail-header">
                    <div class="card-detail-header-content">
                        <div class="card-detail-title-area">
                            <div class="card-detail-icon" style="background: linear-gradient(135deg, ${cardColor}, ${cardColor}DD);">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="card-detail-info">
                                <h2>${Utils.escapeHtml(data.cartao.nome)}</h2>
                                <p>${data.fatura_mes.mes}/${data.fatura_mes.ano}</p>
                            </div>
                        </div>
                        <button class="card-detail-close" onclick="window.LK_CardDetail?.close?.()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="card-detail-stats-grid">
                        <div class="stat-box">
                            <span class="stat-box-label">FATURA</span>
                            <span class="stat-box-value">${Utils.formatCurrency(data.fatura_mes.total)}</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-box-label">LIMITE</span>
                            <span class="stat-box-value">${Utils.formatCurrency(data.cartao.limite)}</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-box-label">DISPONÍVEL</span>
                            <span class="stat-box-value">${Utils.formatCurrency(limiteDisponivel)}</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-box-label">UTILIZAÇÃO</span>
                            <span class="stat-box-value">${percentualLimiteGeral.toFixed(1)}%</span>
                        </div>
                    </div>
                </div>
                
                <div class="card-detail-body">
                    <!-- Fatura do Mês -->
                    <div class="detail-section">
                        <div class="detail-section-header">
                            <h3><i class="fas fa-list"></i> Lançamentos do Mês</h3>
                            <span class="section-badge">${data.fatura_mes.lancamentos.length} ${data.fatura_mes.lancamentos.length === 1 ? 'lançamento' : 'lançamentos'}</span>
                        </div>
                        
                        <div class="lancamentos-list-clean">
                            ${data.fatura_mes.lancamentos.length > 0 ? data.fatura_mes.lancamentos.map(lanc => `
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
                            `).join('') : '<div class="empty-message"><i class="fas fa-inbox"></i><p>Nenhum lançamento neste mês</p></div>'}
                        </div>
                        
                        <div class="summary-boxes">
                            <div class="summary-box">
                                <span class="summary-label">À Vista</span>
                                <span class="summary-value">${Utils.formatCurrency(data.fatura_mes.a_vista)}</span>
                            </div>
                            <div class="summary-box">
                                <span class="summary-label">Parcelado</span>
                                <span class="summary-value">${Utils.formatCurrency(data.fatura_mes.parcelado)}</span>
                            </div>
                            <div class="summary-box highlight">
                                <span class="summary-label">TOTAL</span>
                                <span class="summary-value">${Utils.formatCurrency(data.fatura_mes.total)}</span>
                            </div>
                        </div>
                        
                        ${Math.abs(data.fatura_mes.diferenca_absoluta) > 1 ? `
                        <div class="comparison-box">
                            <span class="comparison-label">vs mês anterior</span>
                            <span class="comparison-value ${data.fatura_mes.diferenca_absoluta > 0 ? 'negative' : 'positive'}">
                                ${data.fatura_mes.diferenca_absoluta > 0 ? '↑' : '↓'} 
                                ${Utils.formatCurrency(Math.abs(data.fatura_mes.diferenca_absoluta))} 
                                (${data.fatura_mes.diferenca_percentual > 0 ? '+' : ''}${data.fatura_mes.diferenca_percentual.toFixed(1)}%)
                            </span>
                        </div>
                        ` : ''}
                    </div>
                    
                    <!-- Evolução Mensal -->
                    <div class="detail-section">
                        <div class="detail-section-header">
                            <i class="fas fa-chart-line"></i>
                            <h3>Evolução Mensal</h3>
                            <span class="tendencia-indicator ${data.evolucao.tendencia}">
                                <i class="fas fa-arrow-${data.evolucao.tendencia === 'subindo' ? 'up' : data.evolucao.tendencia === 'caindo' ? 'down' : 'right'}"></i>
                                ${data.evolucao.tendencia.charAt(0).toUpperCase() + data.evolucao.tendencia.slice(1)}
                            </span>
                        </div>
                        
                        <div class="detail-chart-container"">
                            <canvas id="evolutionChart"></canvas>
                        </div>
                        
                        <p style="text-align: center; margin-top: 1rem; color: var(--color-text-muted);">
                            Média dos últimos 6 meses: <strong>${Utils.formatCurrency(data.evolucao.media)}</strong>
                        </p>
                    </div>
                    
                    <!-- Parcelamentos Ativos -->
                    <div class="detail-section">
                        <div class="detail-section-header">
                            <i class="fas fa-calendar-check"></i>
                            <h3>Parcelamentos Ativos</h3>
                            ${data.parcelamentos.quantidade > 0 ? `
                                <span class="badge">${Utils.formatCurrency(data.parcelamentos.total_comprometido)} comprometidos</span>
                            ` : ''}
                        </div>
                        
                        ${data.parcelamentos.quantidade > 0 ? `
                        <!-- Tabela Desktop -->
                        <div class="parcelamentos-table-wrapper">
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
                                    ${data.parcelamentos.ativos.map(parc => {
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
                        </div>
                        
                        <!-- Cards Mobile para Parcelamentos -->
                        <div class="parcelamentos-mobile-list">
                            ${data.parcelamentos.ativos.map(parc => {
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
        }).join('')}
                        </div>
                        ` : '<div class="empty-message"><i class="fas fa-check-circle"></i><p>Nenhum parcelamento ativo</p></div>'}
                    </div>
                    
                    <!-- Impacto Futuro -->
                    <div class="detail-section">
                        <div class="detail-section-header">
                            <i class="fas fa-crystal-ball"></i>
                            <h3>Impacto Futuro</h3>
                        </div>
                        
                        <div class="detail-chart-container"">
                            <canvas id="impactChart"></canvas>
                        </div>
                        
                        ${data.insights ? `
                        <div class="insights-section">
                            <div class="insights-header">
                                <i class="fas fa-lightbulb"></i>
                                <h3>Análise Inteligente</h3>
                            </div>
                            
                            <div class="insights-grid">
                                ${data.insights.tendencia ? `
                                <div class="insight-card insight-${data.insights.tendencia.type}">
                                    <div class="insight-icon">
                                        <i class="fas ${data.insights.tendencia.icon}"></i>
                                    </div>
                                    <div class="insight-content">
                                        <div class="insight-header-row">
                                            <span class="insight-label">Tendência</span>
                                            <span class="insight-badge">${data.insights.tendencia.variacao}</span>
                                        </div>
                                        <h4 class="insight-status">${data.insights.tendencia.status}</h4>
                                        <p class="insight-desc">${data.insights.tendencia.descricao}</p>
                                        <p class="insight-recommendation">
                                            <i class="fas fa-star"></i> ${data.insights.tendencia.recomendacao}
                                        </p>
                                    </div>
                                </div>
                                ` : ''}
                                
                                ${data.insights.parcelamentos ? `
                                <div class="insight-card insight-${data.insights.parcelamentos.type}">
                                    <div class="insight-icon">
                                        <i class="fas ${data.insights.parcelamentos.icon}"></i>
                                    </div>
                                    <div class="insight-content">
                                        <div class="insight-header-row">
                                            <span class="insight-label">Parcelamentos</span>
                                            <span class="insight-badge">${data.insights.parcelamentos.valor}</span>
                                        </div>
                                        <h4 class="insight-status">${data.insights.parcelamentos.status}</h4>
                                        <p class="insight-desc">${data.insights.parcelamentos.descricao}</p>
                                        <p class="insight-recommendation">
                                            <i class="fas fa-star"></i> ${data.insights.parcelamentos.recomendacao}
                                        </p>
                                    </div>
                                </div>
                                ` : ''}
                                
                                ${data.insights.limite ? `
                                <div class="insight-card insight-${data.insights.limite.type}">
                                    <div class="insight-icon">
                                        <i class="fas ${data.insights.limite.icon}"></i>
                                    </div>
                                    <div class="insight-content">
                                        <div class="insight-header-row">
                                            <span class="insight-label">Uso do Limite</span>
                                            <span class="insight-badge">${data.insights.limite.percentual}</span>
                                        </div>
                                        <h4 class="insight-status">${data.insights.limite.status}</h4>
                                        <p class="insight-desc">${data.insights.limite.descricao}</p>
                                        <p class="insight-recommendation">
                                            <i class="fas fa-star"></i> ${data.insights.limite.recomendacao}
                                        </p>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Ativar modal com animação
        requestAnimationFrame(() => {
            modal.classList.add('active');
        });

        // Renderizar gráficos
        setTimeout(() => {
            renderFaturaChart(data.fatura_mes.por_categoria);
            renderEvolutionChart(data.evolucao.meses);
            renderImpactChart(data.impacto_futuro.meses);
        }, 100);
    }

    function closeCardDetailModal() {
        const modal = document.getElementById('cardDetailModalOverlay');
        if (!modal) return;

        modal.classList.remove('active');

        // Destruir gráficos
        if (detailChart) {
            detailChart.destroy();
            detailChart = null;
        }
        if (evolutionChart) {
            evolutionChart.destroy();
            evolutionChart = null;
        }
        if (impactChart) {
            impactChart.destroy();
            impactChart = null;
        }

        setTimeout(() => modal.remove(), 300);
    }

    function renderFaturaChart(categorias) {
        const canvas = document.getElementById('faturaChart');
        if (!canvas || categorias.length === 0) {
            if (canvas) canvas.parentElement.innerHTML = '<div class="empty-state-detail"><i class="fas fa-chart-pie"></i><p>Sem dados para exibir</p></div>';
            return;
        }

        if (detailChart) {
            detailChart.destroy();
        }

        const ctx = canvas.getContext('2d');
        detailChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categorias.map(c => c.nome),
                datasets: [{
                    data: categorias.map(c => c.valor),
                    backgroundColor: categorias.map(c => c.cor),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: Utils.getCssVar('--color-text'),
                            padding: 10,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 13, weight: '600' },
                        bodyFont: { size: 12 },
                        callbacks: {
                            label: (context) => {
                                const label = context.label || '';
                                const value = Utils.formatCurrency(context.parsed);
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percent = ((context.parsed / total) * 100).toFixed(1);
                                return `${label}: ${value} (${percent}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    function renderEvolutionChart(meses) {
        const canvas = document.getElementById('evolutionChart');
        if (!canvas) return;

        if (evolutionChart) {
            evolutionChart.destroy();
        }

        const ctx = canvas.getContext('2d');
        evolutionChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: meses.map(m => m.mes),
                datasets: [{
                    label: 'Fatura',
                    data: meses.map(m => m.valor),
                    borderColor: '#E67E22',
                    backgroundColor: Utils.hexToRgba('#E67E22', 0.1),
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => Utils.formatCurrency(context.parsed.y)
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: Utils.getCssVar('--color-text-muted'),
                            callback: (value) => Utils.formatCurrency(value)
                        },
                        grid: {
                            color: Utils.getCssVar('--glass-border')
                        }
                    },
                    x: {
                        ticks: { color: Utils.getCssVar('--color-text-muted') },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    function renderImpactChart(meses) {
        const canvas = document.getElementById('impactChart');
        if (!canvas) return;

        if (impactChart) {
            impactChart.destroy();
        }

        const ctx = canvas.getContext('2d');
        impactChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: meses.map(m => m.mes),
                datasets: [{
                    label: 'Projeção',
                    data: meses.map(m => m.valor),
                    backgroundColor: '#3498DB',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => Utils.formatCurrency(context.parsed.y)
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: Utils.getCssVar('--color-text-muted'),
                            callback: (value) => Utils.formatCurrency(value)
                        },
                        grid: {
                            color: Utils.getCssVar('--glass-border')
                        }
                    },
                    x: {
                        ticks: { color: Utils.getCssVar('--color-text-muted') },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // Expor função globalmente
    window.LK_CardDetail = {
        open: openCardDetailModal,
        close: closeCardDetailModal
    };
})();
