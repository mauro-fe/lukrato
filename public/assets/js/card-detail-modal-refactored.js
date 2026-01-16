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
            console.log('🔍 Carregando detalhes do cartão:', url);

            const response = await fetch(url, {
                credentials: 'include'
            });

            if (!response.ok) {
                console.error('❌ Erro na resposta:', response.status, response.statusText);
                throw new Error('Erro ao carregar detalhes do cartão');
            }

            const responseText = await response.text();
            let data;
            
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('❌ Erro ao fazer parse do JSON:', e);
                throw new Error('Resposta inválida do servidor');
            }

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
        // Remover modal existente
        const existingModal = document.getElementById('cardDetailModalOverlay');
        if (existingModal) {
            existingModal.remove();
        }

        // Buscar template
        const template = document.getElementById('cardDetailModalTemplate');
        if (!template) {
            console.error('Template do modal não encontrado');
            return;
        }

        // Criar overlay e clonar conteúdo do template
        const overlay = document.createElement('div');
        overlay.id = 'cardDetailModalOverlay';
        overlay.className = 'card-detail-modal-overlay';
        overlay.appendChild(template.content.cloneNode(true));

        // Preencher dados no template
        populateTemplate(overlay, data, cardColor);

        // Adicionar ao DOM
        document.body.appendChild(overlay);

        // Ativar com animação
        requestAnimationFrame(() => {
            overlay.classList.add('active');
        });

        // Renderizar gráficos após inserção
        setTimeout(() => {
            renderEvolutionChart(data.evolucao.meses);
            renderImpactChart(data.impacto_futuro.meses);
        }, 100);
    }

    function populateTemplate(overlay, data, cardColor) {
        const R = CardModalRenderers;

        // Header
        overlay.querySelector('[data-color]').style.background = `linear-gradient(135deg, ${cardColor}, ${cardColor}DD)`;
        overlay.querySelector('[data-cartao-nome]').textContent = data.cartao.nome;
        overlay.querySelector('[data-periodo]').textContent = `${data.fatura_mes.mes}/${data.fatura_mes.ano}`;

        // Stats
        overlay.querySelector('[data-fatura-total]').textContent = R.formatCurrency(data.fatura_mes.total);
        overlay.querySelector('[data-limite]').textContent = R.formatCurrency(data.cartao.limite);
        overlay.querySelector('[data-disponivel]').textContent = R.formatCurrency(data.cartao.limite_disponivel);
        overlay.querySelector('[data-utilizacao]').textContent = `${(data.cartao.percentual_utilizacao_geral || 0).toFixed(1)}%`;

        // Lançamentos
        const lancamentosCount = data.fatura_mes.lancamentos.length;
        overlay.querySelector('[data-lancamentos-count]').textContent = `${lancamentosCount} ${lancamentosCount === 1 ? 'lançamento' : 'lançamentos'}`;
        overlay.querySelector('[data-lancamentos-list]').innerHTML = R.renderLancamentos(data.fatura_mes.lancamentos);

        // Summary
        overlay.querySelector('[data-a-vista]').textContent = R.formatCurrency(data.fatura_mes.a_vista);
        overlay.querySelector('[data-parcelado]').textContent = R.formatCurrency(data.fatura_mes.parcelado);
        overlay.querySelector('[data-total]').textContent = R.formatCurrency(data.fatura_mes.total);

        // Comparison
        const comparisonEl = overlay.querySelector('[data-comparison]');
        if (Math.abs(data.fatura_mes.diferenca_absoluta) > 1) {
            comparisonEl.innerHTML = R.renderComparison({
                absoluta: data.fatura_mes.diferenca_absoluta,
                percentual: data.fatura_mes.diferenca_percentual
            });
            comparisonEl.style.display = 'block';
        }

        // Tendência
        const tendenciaEl = overlay.querySelector('[data-tendencia]');
        tendenciaEl.className = `tendencia-indicator ${data.evolucao.tendencia}`;
        tendenciaEl.innerHTML = `
            <i class="fas fa-arrow-${data.evolucao.tendencia === 'subindo' ? 'up' : data.evolucao.tendencia === 'caindo' ? 'down' : 'right'}"></i>
            ${data.evolucao.tendencia.charAt(0).toUpperCase() + data.evolucao.tendencia.slice(1)}
        `;
        overlay.querySelector('[data-media]').textContent = R.formatCurrency(data.evolucao.media);

        // Parcelamentos
        const comprometidoEl = overlay.querySelector('[data-comprometido]');
        if (data.parcelamentos.quantidade > 0) {
            comprometidoEl.textContent = `${R.formatCurrency(data.parcelamentos.total_comprometido)} comprometidos`;
            comprometidoEl.style.display = 'inline-block';
        }
        overlay.querySelector('[data-parcelamentos-content]').innerHTML = R.renderParcelamentos(data.parcelamentos);

        // Insights
        const insightsEl = overlay.querySelector('[data-insights]');
        if (data.insights) {
            insightsEl.innerHTML = R.renderInsights(data.insights);
            insightsEl.style.display = 'block';
        }
    }

    function closeCardDetailModal() {
        const modal = document.getElementById('cardDetailModalOverlay');
        if (!modal) return;

        modal.classList.remove('active');

        // Destruir gráficos
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
