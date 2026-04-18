/**
 * ============================================================================
 * LUKRATO — Card Detail Modals (Vite Module)
 * ============================================================================
 * Combinação dos renderers e do modal de detalhes de cartão para relatórios.
 *
 * Substitui:
 *   - public/assets/js/card-modal-renderers.js   (CardModalRenderers)
 *   - public/assets/js/card-detail-modal-refactored.js (LK_CardDetail)
 * ============================================================================
 */

import '../../../css/admin/relatorios/_modal-cartao.css';
import '../../../css/admin/relatorios/_modal-responsive.css';
import { apiFetch, buildUrl, getErrorMessage } from '../shared/api.js';
import { resolveReportCardDetailsEndpoint } from '../api/endpoints/reports.js';
import { escapeHtml } from '../shared/utils.js';

// ─── Formatação local (BRL) ────────────────────────────────────────────────
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value) || 0);
}

function getCssVar(name, fallback = '') {
    try {
        const value = getComputedStyle(document.documentElement).getPropertyValue(name);
        return (value || '').trim() || fallback;
    } catch { return fallback; }
}

// ─── Icon mapping (FA → Lucide) ────────────────────────────────────────────
const FA_LUCIDE = {
    'check-circle': 'circle-check', 'exclamation-triangle': 'triangle-alert',
    'arrow-trend-up': 'trending-up', 'arrow-trend-down': 'trending-down',
    'info-circle': 'info', 'times-circle': 'x-circle',
    'lightbulb': 'lightbulb', 'chart-line': 'line-chart'
};
const lucideIcon = (faName) => { const clean = faName.replace(/^fa-/, ''); return FA_LUCIDE[clean] || clean; };

// ═══════════════════════════════════════════════════════════════════════════
//  RENDERERS (ex CardModalRenderers)
// ═══════════════════════════════════════════════════════════════════════════

function renderLancamentos(lancamentos) {
    if (!lancamentos || lancamentos.length === 0) {
        return '<div class="empty-message"><i data-lucide="inbox"></i><p>Nenhum lançamento neste mês</p></div>';
    }
    return lancamentos.map(lanc => `
        <div class="lancamento-row surface-card">
            <div class="lancamento-left">
                <div class="lancamento-category" style="background: ${lanc.categoria_cor}20; color: ${lanc.categoria_cor};">
                    ${escapeHtml(lanc.categoria)}
                </div>
                <div class="lancamento-description">
                    ${escapeHtml(lanc.descricao)}
                    ${lanc.eh_parcelado ? `<span class="parcela-tag">${lanc.parcela_info}</span>` : ''}
                </div>
                <div class="lancamento-date">${new Date(lanc.data.split(' ')[0] + 'T00:00:00').toLocaleDateString('pt-BR')}</div>
            </div>
            <div class="lancamento-amount">${formatCurrency(lanc.valor)}</div>
        </div>
    `).join('');
}

function renderComparison(diferenca) {
    if (Math.abs(diferenca.absoluta) <= 1) return '';
    return `
        <span class="comparison-label">vs mês anterior</span>
        <span class="comparison-value ${diferenca.absoluta > 0 ? 'negative' : 'positive'}">
            ${diferenca.absoluta > 0 ? '↑' : '↓'} 
            ${formatCurrency(Math.abs(diferenca.absoluta))} 
            (${diferenca.percentual > 0 ? '+' : ''}${diferenca.percentual.toFixed(1)}%)
        </span>
    `;
}

function renderParcelamentosTable(parcelamentos) {
    return `
        <table class="parcelamentos-table surface-card">
            <thead><tr><th>Compra</th><th>Categoria</th><th>Progresso</th><th>Valor/Mês</th><th>Restante</th><th>Término</th></tr></thead>
            <tbody>
                ${parcelamentos.map(parc => {
        const progress = ((parc.total_parcelas - parc.parcelas_restantes) / parc.total_parcelas) * 100;
        return `<tr>
                        <td><strong>${escapeHtml(parc.descricao)}</strong></td>
                        <td><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:${parc.categoria_cor};margin-right:.5rem;"></span>${escapeHtml(parc.categoria)}</td>
                        <td><div class="parcela-progress"><span style="font-size:.75rem;color:var(--color-text-muted);">${parc.total_parcelas - parc.parcelas_restantes}/${parc.total_parcelas}</span><div class="parcela-bar"><div class="parcela-bar-fill" style="width:${progress}%;background:${parc.categoria_cor};"></div></div></div></td>
                        <td>${formatCurrency(parc.valor_parcela)}</td>
                        <td><strong>${formatCurrency(parc.valor_total_restante)}</strong></td>
                        <td style="font-size:.875rem;color:var(--color-text-muted);">${parc.data_final}</td>
                    </tr>`;
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
                        <span class="categoria-dot" style="background:${parc.categoria_cor};"></span>
                        <strong>${escapeHtml(parc.descricao)}</strong>
                    </div>
                    <button class="btn-ver-detalhes" onclick="this.closest('.parcelamento-card-mobile').classList.toggle('expanded')">
                        <i data-lucide="chevron-down"></i><span>Detalhes</span>
                    </button>
                </div>
                <div class="parcelamento-card-summary">
                    <span class="valor-mensal">${formatCurrency(parc.valor_parcela)}/mês</span>
                    <span class="parcelas-info">${parc.total_parcelas - parc.parcelas_restantes}/${parc.total_parcelas} parcelas</span>
                </div>
                <div class="parcelamento-card-progress">
                    <div class="parcela-bar"><div class="parcela-bar-fill" style="width:${progress}%;background:${parc.categoria_cor};"></div></div>
                </div>
                <div class="parcelamento-card-details">
                    <div class="detail-row"><span class="detail-label">Categoria</span><span class="detail-value"><span class="categoria-dot" style="background:${parc.categoria_cor};"></span>${escapeHtml(parc.categoria)}</span></div>
                    <div class="detail-row"><span class="detail-label">Valor por Parcela</span><span class="detail-value">${formatCurrency(parc.valor_parcela)}</span></div>
                    <div class="detail-row"><span class="detail-label">Total Restante</span><span class="detail-value highlight">${formatCurrency(parc.valor_total_restante)}</span></div>
                    <div class="detail-row"><span class="detail-label">Término Previsto</span><span class="detail-value">${parc.data_final}</span></div>
                </div>
            </div>
        `;
    }).join('');
}

function renderParcelamentos(data) {
    if (!data || data.quantidade === 0) {
        return '<div class="empty-message"><i data-lucide="circle-check"></i><p>Nenhum parcelamento ativo</p></div>';
    }
    return `
        <div class="parcelamentos-table-wrapper surface-card">${renderParcelamentosTable(data.ativos)}</div>
        <div class="parcelamentos-mobile-list">${renderParcelamentosMobile(data.ativos)}</div>
    `;
}

function renderInsights(insights) {
    if (!insights) return '';
    const cards = [];

    if (insights.tendencia) {
        cards.push(`<div class="insight-card surface-card insight-${insights.tendencia.type}"><div class="insight-icon"><i data-lucide="${lucideIcon(insights.tendencia.icon)}"></i></div><div class="insight-content"><div class="insight-header-row"><span class="insight-label">Tendência</span><span class="insight-badge">${insights.tendencia.variacao}</span></div><h4 class="insight-status">${insights.tendencia.status}</h4><p class="insight-desc">${insights.tendencia.descricao}</p><p class="insight-recommendation"><i data-lucide="star"></i> ${insights.tendencia.recomendacao}</p></div></div>`);
    }
    if (insights.parcelamentos) {
        cards.push(`<div class="insight-card surface-card insight-${insights.parcelamentos.type}"><div class="insight-icon"><i data-lucide="${lucideIcon(insights.parcelamentos.icon)}"></i></div><div class="insight-content"><div class="insight-header-row"><span class="insight-label">Parcelamentos</span><span class="insight-badge">${insights.parcelamentos.valor}</span></div><h4 class="insight-status">${insights.parcelamentos.status}</h4><p class="insight-desc">${insights.parcelamentos.descricao}</p><p class="insight-recommendation"><i data-lucide="star"></i> ${insights.parcelamentos.recomendacao}</p></div></div>`);
    }
    if (insights.limite) {
        cards.push(`<div class="insight-card surface-card insight-${insights.limite.type}"><div class="insight-icon"><i data-lucide="${lucideIcon(insights.limite.icon)}"></i></div><div class="insight-content"><div class="insight-header-row"><span class="insight-label">Uso do Limite</span><span class="insight-badge">${insights.limite.percentual}</span></div><h4 class="insight-status">${insights.limite.status}</h4><p class="insight-desc">${insights.limite.descricao}</p><p class="insight-recommendation"><i data-lucide="star"></i> ${insights.limite.recomendacao}</p></div></div>`);
    }

    if (cards.length === 0) return '';
    return `
        <div class="insights-header"><i data-lucide="lightbulb"></i><h3>Análise Inteligente</h3></div>
        <div class="insights-grid">${cards.join('')}</div>
    `;
}

// ═══════════════════════════════════════════════════════════════════════════
//  CARD DETAIL MODAL (ex LK_CardDetail)
// ═══════════════════════════════════════════════════════════════════════════

let evolutionChart = null;
let impactChart = null;

function resetCharts() {
    if (evolutionChart) {
        evolutionChart.destroy();
        evolutionChart = null;
    }

    if (impactChart) {
        impactChart.destroy();
        impactChart = null;
    }
}

function getCurrentMonthKey() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}

function parseMonthKey(monthKey) {
    const [yearRaw, monthRaw] = String(monthKey || '').split('-');
    const year = Number.parseInt(yearRaw, 10);
    const month = Number.parseInt(monthRaw, 10);

    if (!Number.isInteger(year) || !Number.isInteger(month) || month < 1 || month > 12) {
        return null;
    }

    return { year, month };
}

async function fetchCardDetailData(cardId, monthKey) {
    const parsed = parseMonthKey(monthKey || getCurrentMonthKey());
    if (!parsed) {
        throw new Error('Mês de referência inválido');
    }

    const url = buildUrl(resolveReportCardDetailsEndpoint(cardId), {
        mes: parsed.month,
        ano: parsed.year,
    });

    const data = await apiFetch(url, { credentials: 'include' }, { timeout: 15000 });

    if (!data.success || !data.data) throw new Error(data.message || 'Dados inválidos retornados');

    return data.data;
}

async function openCardDetailModal(cardId, cardName, cardColor, currentMonth) {
    if (!cardId) { console.error('ID do cartão não fornecido'); return; }

    // Find the clicked button and show inline loading
    const btn = document.querySelector(`[data-action="open-card-detail"][data-card-id="${cardId}"].card-action-btn`);
    const originalBtnHtml = btn?.innerHTML;
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader-2" class="icon-spin"></i> <span>Carregando...</span>';
        if (window.lucide) window.lucide.createIcons({ nodes: [btn] });
    }

    try {
        const detailData = await fetchCardDetailData(cardId, currentMonth);
        renderCardDetailModal(detailData, cardColor);
    } catch (error) {
        console.error('Erro ao abrir detalhes do cartão:', error);
        document.body.style.overflow = '';
        const msg = getErrorMessage(error, 'Não foi possível carregar os detalhes do cartão. Tente novamente.');
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Erro ao carregar', text: msg, confirmButtonColor: '#e67e22' });
        }
    } finally {
        // Restore button
        if (btn) {
            btn.disabled = false;
            if (originalBtnHtml) btn.innerHTML = originalBtnHtml;
            if (window.lucide) window.lucide.createIcons({ nodes: [btn] });
        }
    }
}

async function renderCardDetailPage({
    cardId,
    currentMonth = getCurrentMonthKey(),
    cardColor = getCssVar('--color-primary', '#e67e22'),
    mountId = 'cardDetailPageContent',
    loadingId = 'cardDetailPageLoading',
    errorId = 'cardDetailPageError',
    titleId = 'cardDetailPageTitle',
    subtitleId = 'cardDetailPageSubtitle',
} = {}) {
    const mountEl = document.getElementById(mountId);
    const loadingEl = document.getElementById(loadingId);
    const errorEl = document.getElementById(errorId);
    const titleEl = document.getElementById(titleId);
    const subtitleEl = document.getElementById(subtitleId);

    if (!mountEl || !Number.isInteger(Number(cardId)) || Number(cardId) <= 0) {
        return;
    }

    if (errorEl) {
        errorEl.hidden = true;
        errorEl.innerHTML = '';
    }

    if (loadingEl) {
        loadingEl.hidden = false;
        loadingEl.style.display = 'flex';
    }

    mountEl.hidden = true;
    mountEl.innerHTML = '';

    try {
        const detailData = await fetchCardDetailData(Number(cardId), currentMonth);
        const template = document.getElementById('cardDetailModalTemplate');

        if (!template) {
            throw new Error('Template de detalhes não encontrado');
        }

        const fragment = template.content.cloneNode(true);
        const detailRoot = fragment.querySelector('.card-detail-modal');
        const closeBtn = fragment.querySelector('.card-detail-close');

        if (detailRoot) {
            detailRoot.classList.add('card-detail-modal--page');
        }

        if (closeBtn) {
            closeBtn.remove();
        }

        mountEl.appendChild(fragment);
        populateTemplate(mountEl, detailData, cardColor);

        if (titleEl) {
            titleEl.textContent = `${detailData?.cartao?.nome || 'Cartão'} - ${detailData?.fatura_mes?.mes || ''}/${detailData?.fatura_mes?.ano || ''}`;
        }

        if (subtitleEl) {
            subtitleEl.textContent = 'Fatura do mês, evolução mensal e impacto dos parcelamentos.';
        }

        if (loadingEl) {
            loadingEl.hidden = true;
            loadingEl.style.display = 'none';
        }

        mountEl.hidden = false;
        if (window.lucide) window.lucide.createIcons({ nodes: [mountEl] });

        resetCharts();
        renderEvolutionChart(detailData.evolucao?.meses, mountEl);
        renderImpactChart(detailData.impacto_futuro?.meses, mountEl);
    } catch (error) {
        if (loadingEl) {
            loadingEl.hidden = true;
            loadingEl.style.display = 'none';
        }

        const message = getErrorMessage(error, 'Não foi possível carregar os detalhes deste cartão.');

        if (titleEl) {
            titleEl.textContent = 'Detalhes indisponíveis';
        }

        if (subtitleEl) {
            subtitleEl.textContent = message;
        }

        if (errorEl) {
            errorEl.hidden = false;
            errorEl.innerHTML = `<p>${escapeHtml(message)}</p>`;
        }
    }
}

function renderCardDetailModal(data, cardColor) {
    const modalSystem = window.LK?.modalSystem;

    // Clean up any existing modal first
    const existingModal = document.getElementById('cardDetailModalOverlay');
    if (existingModal) {
        existingModal.remove();
        if (!modalSystem) {
            document.body.style.overflow = '';
        }
    }

    const template = document.getElementById('cardDetailModalTemplate');
    if (!template) { console.error('Template do modal não encontrado'); return; }

    const overlay = document.createElement('div');
    overlay.id = 'cardDetailModalOverlay';
    overlay.className = 'card-detail-modal-overlay';
    overlay.appendChild(template.content.cloneNode(true));

    try {
        populateTemplate(overlay, data, cardColor);
    } catch (err) {
        console.error('Erro ao popular o modal:', err);
        return;
    }

    // Prevent background scroll while modal is open
    if (!modalSystem) {
        document.body.style.overflow = 'hidden';
    }

    if (modalSystem) {
        modalSystem.prepareOverlay(overlay, { scope: 'page' });
    } else {
        document.body.appendChild(overlay);
    }
    overlay.classList.add('active');

    // Scroll overlay to top
    overlay.scrollTop = 0;
    resetCharts();

    // Close on overlay background click (not on the modal itself)
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closeCardDetailModal();
    });

    // Close on Escape key
    const escHandler = (e) => {
        if (e.key === 'Escape') {
            closeCardDetailModal();
            document.removeEventListener('keydown', escHandler);
        }
    };
    document.addEventListener('keydown', escHandler);

    setTimeout(() => {
        try {
            // Render lucide icons inside modal
            if (window.lucide) window.lucide.createIcons({ nodes: [overlay] });
            renderEvolutionChart(data.evolucao?.meses, overlay);
            renderImpactChart(data.impacto_futuro?.meses, overlay);
        } catch (err) {
            console.error('Erro ao renderizar gráficos do modal:', err);
        }
    }, 100);
}

function populateTemplate(overlay, data, cardColor) {
    // Header
    overlay.querySelector('[data-color]').style.background = `linear-gradient(135deg, ${cardColor}, ${cardColor}DD)`;
    overlay.querySelector('[data-cartao-nome]').textContent = data.cartao.nome;
    overlay.querySelector('[data-periodo]').textContent = `${data.fatura_mes.mes}/${data.fatura_mes.ano}`;

    // Stats
    overlay.querySelector('[data-fatura-total]').textContent = formatCurrency(data.fatura_mes.total);
    overlay.querySelector('[data-limite]').textContent = formatCurrency(data.cartao.limite);
    overlay.querySelector('[data-disponivel]').textContent = formatCurrency(data.cartao.limite_disponivel);
    overlay.querySelector('[data-utilizacao]').textContent = `${(data.cartao.percentual_utilizacao_geral || 0).toFixed(1)}%`;

    // Lançamentos
    const lancamentosCount = data.fatura_mes.lancamentos.length;
    overlay.querySelector('[data-lancamentos-count]').textContent = `${lancamentosCount} ${lancamentosCount === 1 ? 'lançamento' : 'lançamentos'}`;
    overlay.querySelector('[data-lancamentos-list]').innerHTML = renderLancamentos(data.fatura_mes.lancamentos);

    // Summary
    overlay.querySelector('[data-a-vista]').textContent = formatCurrency(data.fatura_mes.a_vista);
    overlay.querySelector('[data-parcelado]').textContent = formatCurrency(data.fatura_mes.parcelado);
    overlay.querySelector('[data-total]').textContent = formatCurrency(data.fatura_mes.total);

    // Comparison
    const comparisonEl = overlay.querySelector('[data-comparison]');
    if (Math.abs(data.fatura_mes.diferenca_absoluta) > 1) {
        comparisonEl.innerHTML = renderComparison({ absoluta: data.fatura_mes.diferenca_absoluta, percentual: data.fatura_mes.diferenca_percentual });
        comparisonEl.style.display = 'block';
    }

    // Tendência
    const tendenciaEl = overlay.querySelector('[data-tendencia]');
    tendenciaEl.className = `tendencia-indicator ${data.evolucao.tendencia}`;
    tendenciaEl.innerHTML = `
        <i data-lucide="${data.evolucao.tendencia === 'subindo' ? 'arrow-up' : data.evolucao.tendencia === 'caindo' ? 'arrow-down' : 'arrow-right'}"></i>
        ${data.evolucao.tendencia.charAt(0).toUpperCase() + data.evolucao.tendencia.slice(1)}
    `;
    if (window.lucide) window.lucide.createIcons({ nodes: [tendenciaEl] });
    overlay.querySelector('[data-media]').textContent = formatCurrency(data.evolucao.media);

    // Parcelamentos
    const comprometidoEl = overlay.querySelector('[data-comprometido]');
    if (data.parcelamentos.quantidade > 0) {
        comprometidoEl.textContent = `${formatCurrency(data.parcelamentos.total_comprometido)} comprometidos`;
        comprometidoEl.style.display = 'inline-block';
    }
    overlay.querySelector('[data-parcelamentos-content]').innerHTML = renderParcelamentos(data.parcelamentos);

    // Insights
    const insightsEl = overlay.querySelector('[data-insights]');
    if (data.insights) {
        insightsEl.innerHTML = renderInsights(data.insights);
        insightsEl.style.display = 'block';
    }
}

function closeCardDetailModal() {
    const modal = document.getElementById('cardDetailModalOverlay');
    if (!modal) return;

    const modalSystem = window.LK?.modalSystem;

    // Fade out animation
    modal.classList.remove('active');
    modal.style.opacity = '0';
    modal.style.transition = 'opacity 0.25s ease';

    // Restore background scroll
    if (!modalSystem) {
        document.body.style.overflow = '';
    }

    resetCharts();

    setTimeout(() => modal.remove(), 300);
}

function renderEvolutionChart(meses, root = document) {
    const el = root.querySelector('#evolutionChart');
    if (!el) return;
    if (evolutionChart) { evolutionChart.destroy(); evolutionChart = null; }
    const seriesMeses = Array.isArray(meses) ? meses : [];

    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textMuted = getCssVar('--color-text-muted', '#999');
    const gridColor = getCssVar('--glass-border', 'rgba(255,255,255,0.1)');

    evolutionChart = new ApexCharts(el, {
        chart: {
            type: 'area',
            height: 260,
            toolbar: { show: false },
            background: 'transparent',
            fontFamily: 'Inter, Arial, sans-serif',
        },
        series: [{ name: 'Fatura', data: seriesMeses.map(m => Number(m.valor) || 0) }],
        xaxis: {
            categories: seriesMeses.map(m => m.mes),
            labels: { style: { colors: textMuted } },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            min: 0,
            labels: {
                style: { colors: textMuted },
                formatter: v => formatCurrency(v),
            },
        },
        colors: ['#E67E22'],
        stroke: { curve: 'smooth', width: 2.5 },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] },
        },
        markers: { size: 4, hover: { size: 6 } },
        grid: { borderColor: gridColor, strokeDashArray: 4, xaxis: { lines: { show: false } } },
        tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: v => formatCurrency(v) } },
        legend: { show: false },
        dataLabels: { enabled: false },
        theme: { mode: isDark ? 'dark' : 'light' },
    });
    evolutionChart.render();
}

function renderImpactChart(meses, root = document) {
    const el = root.querySelector('#impactChart');
    if (!el) return;
    if (impactChart) { impactChart.destroy(); impactChart = null; }
    const seriesMeses = Array.isArray(meses) ? meses : [];

    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textMuted = getCssVar('--color-text-muted', '#999');
    const gridColor = getCssVar('--glass-border', 'rgba(255,255,255,0.1)');

    impactChart = new ApexCharts(el, {
        chart: {
            type: 'bar',
            height: 260,
            toolbar: { show: false },
            background: 'transparent',
            fontFamily: 'Inter, Arial, sans-serif',
        },
        series: [{ name: 'Projeção', data: seriesMeses.map(m => Number(m.valor) || 0) }],
        xaxis: {
            categories: seriesMeses.map(m => m.mes),
            labels: { style: { colors: textMuted } },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            min: 0,
            labels: {
                style: { colors: textMuted },
                formatter: v => formatCurrency(v),
            },
        },
        colors: ['#3498DB'],
        plotOptions: {
            bar: { borderRadius: 6, columnWidth: '55%' },
        },
        grid: { borderColor: gridColor, strokeDashArray: 4, xaxis: { lines: { show: false } } },
        tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: v => formatCurrency(v) } },
        legend: { show: false },
        dataLabels: { enabled: false },
        theme: { mode: isDark ? 'dark' : 'light' },
    });
    impactChart.render();
}

// ─── Expose globally (used by PHP onclick handlers) ─────────────────────────
window.LK_CardDetail = {
    open: openCardDetailModal,
    close: closeCardDetailModal,
    renderPage: renderCardDetailPage,
};
window.CardModalRenderers = { renderLancamentos, renderComparison, renderParcelamentos, renderInsights, formatCurrency, escapeHtml };

