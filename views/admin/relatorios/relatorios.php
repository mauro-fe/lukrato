<style>
.month-nav-btn {
    background: transparent;
    border: none;
    color: var(--color-text);
    cursor: pointer;
    padding: var(--spacing-2);
    border-radius: var(--radius-sm);
    transition: all var(--transition-fast);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
}

.month-nav-btn:hover {
    background: var(--color-primary);
    color: white;
    transform: scale(1.1);
}

.month-label {
    font-weight: 600;
    min-width: 200px;
    text-align: center;
    font-size: var(--font-size-base);
}

/* Export Card */
.export-card {
    background: var(--glass-bg);
    backdrop-filter: var(--glass-backdrop);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-lg);
    padding: var(--spacing-4);
    box-shadow: var(--shadow-md);
    margin-bottom: var(--spacing-6);
}

.export-label {
    font-size: var(--font-size-xs);
    font-weight: 600;
    text-transform: uppercase;
    color: var(--color-text-muted);
    letter-spacing: 0.05em;
    margin-bottom: var(--spacing-3);
    display: block;
}

.export-controls {
    display: flex;
    gap: var(--spacing-3);
    flex-wrap: wrap;
}

/* Controls Section */
.controls-section {
    display: flex;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-6);
    flex-wrap: wrap;
    align-items: center;
}

/* Tabs */
.tabs {
    display: flex;
    gap: var(--spacing-2);
    background: var(--glass-bg);
    backdrop-filter: var(--glass-backdrop);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-lg);
    padding: var(--spacing-2);
    box-shadow: var(--shadow-md);
    overflow-x: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--color-primary) transparent;
}

.tabs::-webkit-scrollbar {
    height: 4px;
}

.tabs::-webkit-scrollbar-thumb {
    background: var(--color-primary);
    border-radius: var(--radius-sm);
}

.tab-btn {
    position: relative;
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-4);
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--radius-md);
    color: var(--color-text);
    font-family: var(--font-primary);
    font-size: var(--font-size-sm);
    font-weight: 500;
    white-space: nowrap;
    cursor: pointer;
    transition: all var(--transition-normal);
    overflow: hidden;
}

.tab-btn:hover {
    background: rgba(230, 126, 34, 0.1);
    border-color: var(--glass-border);
    transform: translateY(-1px);
}

.tab-btn.active {
    background: linear-gradient(135deg, var(--color-primary), #D35400);
    border-color: var(--color-primary);
    color: white;
    font-weight: 600;
    box-shadow: var(--shadow-md);
}

.tab-btn:focus-visible {
    outline: none;
    box-shadow: var(--ring);
}

/* Select */
.select-wrapper {
    position: relative;
    min-width: 220px;
}

.select {
    width: 100%;
    appearance: none;
    background: var(--glass-bg);
    backdrop-filter: var(--glass-backdrop);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-full);
    padding: var(--spacing-3) var(--spacing-6) var(--spacing-3) var(--spacing-4);
    color: var(--color-text);
    font-size: var(--font-size-sm);
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition-fast);
    box-shadow: var(--shadow-md);
}

.select:hover {
    border-color: var(--color-primary);
}

.select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: var(--ring);
}

/* Button */
.btn {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-4);
    background: var(--color-primary);
    border: 1px solid var(--color-primary);
    border-radius: var(--radius-full);
    color: white;
    font-size: var(--font-size-sm);
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition-fast);
    box-shadow: var(--shadow-md);
    white-space: nowrap;
}

.btn:hover {
    background: #D35400;
    border-color: #D35400;
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.btn:active {
    transform: translateY(0);
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.btn-primary {
    background: var(--color-secondary);
    border-radius: var(--radius-sm);

}

.btn-primary:hover {
    background: #1a252f;
}

/* Report Area */
.report-area {
    background: var(--glass-bg);
    backdrop-filter: var(--glass-backdrop);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-lg);
    padding: var(--spacing-6);
    box-shadow: var(--shadow-lg);
    min-height: 400px;
}

/* Chart Container */
.chart-container {
    padding: var(--spacing-4);
    min-height: 420px;
}

.chart-container canvas {
    min-height: 360px;
}

.chart-dual {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: var(--spacing-6);
    height: 300px;
}

.chart-wrapper {
    position: relative;
}


/* Loading */
.loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-4);
    padding: var(--spacing-8);
    color: var(--color-text-muted);
}

.spinner {
    width: 48px;
    height: 48px;
    border: 4px solid var(--glass-border);
    border-top-color: var(--color-primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-3);
    padding: var(--spacing-8);
    text-align: center;
}

.empty-state i {
    font-size: 4rem;
    color: var(--color-text-muted);
    opacity: 0.5;
}

.empty-state h3 {
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--color-text);
}

.empty-state p {
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}

/* Responsive */
@media (max-width: 1024px) {
    .controls-section {
        flex-direction: column;
        align-items: stretch;
    }

    .tabs {
        width: 100%;
        justify-content: flex-start;
    }

    .select-wrapper {
        width: 100%;
    }
}

@media (max-width: 768px) {
    body {
        padding: var(--spacing-2);
    }

    .container {
        padding: var(--spacing-2);
    }

    .header {
        flex-direction: column;
        align-items: stretch;
    }


    .chart-dual {
        grid-template-columns: 1fr;
    }

    .report-area {
        padding: var(--spacing-4);
    }

    .tab-btn span {
        display: none;
    }

    .tab-btn {
        padding: var(--spacing-3);
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .export-controls {
        flex-direction: column;
    }

    .export-controls .select-wrapper,
    .export-controls .btn {
        width: 100%;
    }
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    animation: fadeIn 0.3s ease;
}

/* Utility Classes */
.hidden {
    display: none !important;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}
</style>

<!-- Export Card -->
<div class="export-card" data-aos="fade-up">
    <label class="export-label" for="exportType">
        <i class="fas fa-file-export"></i>
        Exportar Relatórios
    </label>

    <div class="export-controls">
        <div class="select-wrapper">
            <select id="exportType" class="lk-select" aria-label="Tipo de relatório">
                <option value="despesas_por_categoria">Despesas por categoria</option>
                <option value="receitas_por_categoria">Receitas por categoria</option>
                <option value="saldo_mensal">Saldo diário</option>
                <option value="receitas_despesas_diario">Receitas x Despesas diário</option>
                <option value="evolucao_12m">Evolução 12 meses</option>
                <option value="receitas_despesas_por_conta">Receitas x Despesas por conta</option>
                <option value="resumo_anual">Resumo anual</option>
                <option value="despesas_anuais_por_categoria">Despesas anuais por categoria</option>
                <option value="receitas_anuais_por_categoria">Receitas anuais por categoria</option>
            </select>
        </div>
        <div class="select-wrapper">
            <select id="exportFormat" class="lk-select" aria-label="Formato de exportação">
                <option value="pdf">PDF</option>
                <option value="excel">Excel (.xlsx)</option>
            </select>
        </div>
        <button id="exportBtn" class="lk-select btn btn-primary">
            <i class="fas fa-download"></i>
            <span>Exportar</span>
        </button>
    </div>
</div>
<?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>
<!-- Controls -->
<section class="controls-section" data-aos="fade-up">
    <div class="tabs" role="tablist">
        <button class="tab-btn active" data-view="category" role="tab" aria-selected="true">
            <i class="fas fa-chart-pie"></i>
            <span>Por Categoria</span>
        </button>
        <button class="tab-btn" data-view="balance" role="tab" aria-selected="false">
            <i class="fas fa-chart-line"></i>
            <span>Saldo Diário</span>
        </button>
        <button class="tab-btn" data-view="comparison" role="tab" aria-selected="false">
            <i class="fas fa-chart-column"></i>
            <span>Receitas x Despesas</span>
        </button>
        <button class="tab-btn" data-view="accounts" role="tab" aria-selected="false">
            <i class="fas fa-wallet"></i>
            <span>Por Conta</span>
        </button>
        <button class="tab-btn" data-view="evolution" role="tab" aria-selected="false">
            <i class="fas fa-timeline"></i>
            <span>Evolução 12m</span>
        </button>
        <button class="tab-btn" data-view="annual_summary" role="tab" aria-selected="false">
            <i class="fas fa-calendar-alt"></i>
            <span>Resumo Anual</span>
        </button>
        <button class="tab-btn" data-view="annual_category" role="tab" aria-selected="false">
            <i class="fas fa-chart-pie"></i>
            <span>Categoria Anual</span>
        </button>
    </div>

    <div class="select-wrapper" id="typeSelectWrapper">
        <select id="reportType" class="lk-select" aria-label="Tipo de relatório">
            <option value="despesas_por_categoria">Despesas por categoria</option>
            <option value="receitas_por_categoria">Receitas por categoria</option>
        </select>
    </div>

    <div class="select-wrapper hidden" id="accountSelectWrapper">
        <select id="accountFilter" class="lk-select" aria-label="Filtrar por conta">
            <option value="">Todas as contas</option>
        </select>
    </div>


</section>

<!-- Report Area -->
<div data-aos="zoom-in">

    <section class="report-area fade-in" id="reportArea">
        <div class="loading">
            <div class="spinner"></div>
            <p>Carregando relatório...</p>
        </div>
    </section>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
// ============================================================================
// CONFIGURATION & CONSTANTS
// ============================================================================
const CONFIG = {
    BASE_URL: <?= json_encode(rtrim(BASE_URL ?? '/', '/') . '/') ?>,
    CHART_COLORS: [
        '#E67E22', '#2C3E50', '#2ECC71', '#F39C12',
        '#9B59B6', '#1ABC9C', '#E74C3C', '#3498DB'
    ],
    VIEWS: {
        CATEGORY: 'category',
        BALANCE: 'balance',
        COMPARISON: 'comparison',
        ACCOUNTS: 'accounts',
        EVOLUTION: 'evolution',
        ANNUAL_SUMMARY: 'annual_summary',
        ANNUAL_CATEGORY: 'annual_category'
    }
};

const YEARLY_VIEWS = new Set([
    CONFIG.VIEWS.ANNUAL_SUMMARY,
    CONFIG.VIEWS.ANNUAL_CATEGORY
]);

const TYPE_OPTIONS = {
    [CONFIG.VIEWS.CATEGORY]: [{
            value: 'despesas_por_categoria',
            label: 'Despesas por categoria'
        },
        {
            value: 'receitas_por_categoria',
            label: 'Receitas por categoria'
        }
    ],
    [CONFIG.VIEWS.ANNUAL_CATEGORY]: [{
            value: 'despesas_anuais_por_categoria',
            label: 'Despesas anuais por categoria'
        },
        {
            value: 'receitas_anuais_por_categoria',
            label: 'Receitas anuais por categoria'
        }
    ]
};

// ============================================================================
// STATE MANAGEMENT
// ============================================================================
const state = {
    currentView: CONFIG.VIEWS.CATEGORY,
    categoryType: 'despesas_por_categoria',
    annualCategoryType: 'despesas_anuais_por_categoria',
    currentMonth: getCurrentMonth(),
    currentAccount: null,
    chart: null,
    accounts: []
};

function isYearlyView(view = state.currentView) {
    return YEARLY_VIEWS.has(view);
}

function syncPickerMode() {
    const showYear = isYearlyView();
    window.LukratoHeader?.setPickerMode?.(showYear ? 'year' : 'month');
    if (showYear) {
        const headerYear = window.LukratoHeader?.getYear?.();
        if (headerYear) {
            const [, monthPart = '01'] = state.currentMonth.split('-');
            const normalizedMonth = String(monthPart).padStart(2, '0');
            state.currentMonth = `${headerYear}-${normalizedMonth}`;
        }
    }
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================
function getCurrentMonth() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(Number(value) || 0);
}

function formatMonthLabel(yearMonth) {
    const [year, month] = yearMonth.split('-');
    const date = new Date(year, month - 1);
    return date.toLocaleDateString('pt-BR', {
        month: 'long',
        year: 'numeric'
    });
}

function addMonths(yearMonth, delta) {
    const [year, month] = yearMonth.split('-').map(Number);
    const date = new Date(year, month - 1 + delta);
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

function hexToRgba(hex, alpha = 0.25) {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

// ============================================================================
// API FUNCTIONS
// ============================================================================
async function fetchReportData() {
    const params = new URLSearchParams({
        type: getReportType(),
        year: state.currentMonth.split('-')[0],
        month: state.currentMonth.split('-')[1]
    });

    if (state.currentAccount) {
        params.set('account_id', state.currentAccount);
    }

    try {
        const response = await fetch(`${CONFIG.BASE_URL}api/reports?${params}`, {
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok) throw new Error('API request failed');

        const json = await response.json();
        return json.data || json;
    } catch (error) {
        console.error('Error fetching report data:', error);
        return {
            labels: [],
            values: []
        };
    }
}

async function fetchAccounts() {
    try {
        const response = await fetch(`${CONFIG.BASE_URL}api/accounts`, {
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok) throw new Error('Failed to fetch accounts');

        const json = await response.json();
        return (json.items || json || []).map(acc => ({
            id: Number(acc.id),
            name: acc.nome || acc.apelido || acc.instituicao || `Conta #${acc.id}`
        }));
    } catch (error) {
        console.error('Error fetching accounts:', error);
        return [];
    }
}

function getReportType() {
    const typeMap = {
        [CONFIG.VIEWS.CATEGORY]: state.categoryType,
        [CONFIG.VIEWS.ANNUAL_CATEGORY]: state.annualCategoryType,
        [CONFIG.VIEWS.BALANCE]: 'saldo_mensal',
        [CONFIG.VIEWS.COMPARISON]: 'receitas_despesas_diario',
        [CONFIG.VIEWS.ACCOUNTS]: 'receitas_despesas_por_conta',
        [CONFIG.VIEWS.EVOLUTION]: 'evolucao_12m',
        [CONFIG.VIEWS.ANNUAL_SUMMARY]: 'resumo_anual'
    };
    return typeMap[state.currentView] ?? state.categoryType;
}

function getActiveCategoryType() {
    return state.currentView === CONFIG.VIEWS.ANNUAL_CATEGORY ?
        state.annualCategoryType :
        state.categoryType;
}

// ============================================================================
// CHART FUNCTIONS
// ============================================================================
function destroyChart() {
    if (state.chart) {
        if (Array.isArray(state.chart)) {
            state.chart.forEach(c => c?.destroy());
        } else {
            state.chart.destroy();
        }
        state.chart = null;
    }
}

function setupChartDefaults() {
    const textColor = getComputedStyle(document.documentElement)
        .getPropertyValue('--color-text').trim();

    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
}

function renderPieChart(data) {
    const {
        labels = [], values = []
    } = data;

    if (!labels.length || !values.some(v => v > 0)) {
        return showEmptyState();
    }

    const entries = labels
        .map((label, idx) => ({
            label,
            value: Number(values[idx]) || 0,
            color: CONFIG.CHART_COLORS[idx % CONFIG.CHART_COLORS.length]
        }))
        .filter(e => e.value > 0)
        .sort((a, b) => b.value - a.value);

    // Split entries into two balanced charts when we have enough categories
    const shouldSplit = entries.length > 2;
    const chunkSize = shouldSplit ? Math.ceil(entries.length / 2) : entries.length;
    const chunks = shouldSplit ? [entries.slice(0, chunkSize), entries.slice(chunkSize)].filter(chunk => chunk.length) :
        [entries];

    const html = `
                <div class="chart-dual">
                    ${chunks.map((_, idx) => `
                        <div class="chart-wrapper">
                            <canvas id="chart${idx}"></canvas>
                        </div>
                    `).join('')}
                </div>
            `;

    setReportContent(html);
    destroyChart();

    const type = getActiveCategoryType();
    const titleMap = {
        'receitas_por_categoria': 'Receitas por Categoria',
        'despesas_por_categoria': 'Despesas por Categoria',
        'receitas_anuais_por_categoria': 'Receitas anuais por Categoria',
        'despesas_anuais_por_categoria': 'Despesas anuais por Categoria'
    };
    const title = titleMap[type] || 'Distribuição por Categoria';

    state.chart = chunks.map((chunk, idx) => {
        const canvas = document.getElementById(`chart${idx}`);
        return new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: chunk.map(e => e.label),
                datasets: [{
                    data: chunk.map(e => e.value),
                    backgroundColor: chunk.map(e => e.color),
                    borderWidth: 2,
                    borderColor: getComputedStyle(document.documentElement)
                        .getPropertyValue('--color-surface').trim()
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: chunks.length > 1 ? `${title} - Parte ${idx + 1}` : title
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const label = context.label || '';
                                const value = formatCurrency(context.parsed);
                                return `${label}: ${value}`;
                            }
                        }
                    }
                }
            }
        });
    });
}

function renderLineChart(data) {
    const {
        labels = [], values = []
    } = data;

    if (!labels.length) return showEmptyState();

    setReportContent(`
                <div class="chart-container">
                    <canvas id="chart0"></canvas>
                </div>
            `);

    destroyChart();

    const color = getComputedStyle(document.documentElement)
        .getPropertyValue('--color-primary').trim();

    state.chart = new Chart(document.getElementById('chart0'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Saldo Diário',
                data: values.map(Number),
                borderColor: color,
                backgroundColor: hexToRgba(color, 0.2),
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Evolução do Saldo Mensal'
                },
                tooltip: {
                    callbacks: {
                        label: (context) => formatCurrency(context.parsed.y)
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        callback: (value) => formatCurrency(value)
                    }
                }
            }
        }
    });
}

function renderBarChart(data) {
    const {
        labels = [], receitas = [], despesas = []
    } = data;

    if (!labels.length) return showEmptyState();

    setReportContent(`
                <div class="chart-container">
                    <canvas id="chart0"></canvas>
                </div>
            `);

    destroyChart();

    const colorSuccess = getComputedStyle(document.documentElement)
        .getPropertyValue('--color-success').trim();
    const colorDanger = getComputedStyle(document.documentElement)
        .getPropertyValue('--color-danger').trim();

    state.chart = new Chart(document.getElementById('chart0'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                    label: 'Receitas',
                    data: receitas.map(Number),
                    backgroundColor: hexToRgba(colorSuccess, 0.6),
                    borderColor: colorSuccess,
                    borderWidth: 2
                },
                {
                    label: 'Despesas',
                    data: despesas.map(Number),
                    backgroundColor: hexToRgba(colorDanger, 0.6),
                    borderColor: colorDanger,
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: state.currentView === CONFIG.VIEWS.ACCOUNTS ?
                        'Receitas x Despesas por Conta' : state.currentView === CONFIG.VIEWS.ANNUAL_SUMMARY ?
                        'Resumo Anual por Mês' : 'Receitas x Despesas'
                },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const label = context.dataset.label || '';
                            const value = formatCurrency(context.parsed.y);
                            return `${label}: ${value}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        callback: (value) => formatCurrency(value)
                    }
                }
            }
        }
    });
}

// ============================================================================
// UI FUNCTIONS
// ============================================================================
function setReportContent(html) {
    document.getElementById('reportArea').innerHTML = html;
}

function showLoading() {
    setReportContent(`
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Carregando relatório...</p>
                </div>
            `);
}

function showEmptyState() {
    setReportContent(`
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <h3>Nenhum dado encontrado</h3>
                    <p>Não há informações disponíveis para o período selecionado.</p>
                </div>
            `);
}

function updateMonthLabel() {
    const labelEl = document.getElementById('monthLabel');
    if (labelEl) {
        labelEl.textContent = formatMonthLabel(state.currentMonth);
    }
}

function updateControls() {
    // Show/hide type selector
    const typeWrapper = document.getElementById('typeSelectWrapper');
    const showTypeSelect = [CONFIG.VIEWS.CATEGORY, CONFIG.VIEWS.ANNUAL_CATEGORY]
        .includes(state.currentView);
    typeWrapper.classList.toggle('hidden', !showTypeSelect);
    if (showTypeSelect) {
        syncTypeSelect();
    }

    // Show/hide account filter
    const accountWrapper = document.getElementById('accountSelectWrapper');
    accountWrapper.classList.remove('hidden');

    // Update export type
    const exportType = document.getElementById('exportType');
    exportType.value = getReportType();
}

function syncTypeSelect() {
    const select = document.getElementById('reportType');
    if (!select) return;

    const options = TYPE_OPTIONS[state.currentView];
    if (!options) return;

    const needsUpdate = select.options.length !== options.length ||
        options.some((opt, idx) => select.options[idx]?.value !== opt.value);

    if (needsUpdate) {
        select.innerHTML = options
            .map(option => `<option value="${option.value}">${option.label}</option>`)
            .join('');
    }

    select.value = getActiveCategoryType();
}

function setActiveTab(view) {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        const isActive = btn.dataset.view === view;
        btn.classList.toggle('active', isActive);
        btn.setAttribute('aria-selected', isActive);
    });
}

// ============================================================================
// RENDER FUNCTIONS
// ============================================================================
async function renderReport() {
    showLoading();

    const data = await fetchReportData();

    if (!data || !data.labels || data.labels.length === 0) {
        return showEmptyState();
    }

    switch (state.currentView) {
        case CONFIG.VIEWS.CATEGORY:
        case CONFIG.VIEWS.ANNUAL_CATEGORY:
            renderPieChart(data);
            break;
        case CONFIG.VIEWS.BALANCE:
        case CONFIG.VIEWS.EVOLUTION:
            renderLineChart(data);
            break;
        case CONFIG.VIEWS.COMPARISON:
        case CONFIG.VIEWS.ACCOUNTS:
        case CONFIG.VIEWS.ANNUAL_SUMMARY:
            renderBarChart(data);
            break;
        default:
            showEmptyState();
    }
}

// ============================================================================
// EXPORT FUNCTIONS
// ============================================================================
async function handleExport() {
    const exportBtn = document.getElementById('exportBtn');
    const originalHTML = exportBtn.innerHTML;

    exportBtn.disabled = true;
    exportBtn.innerHTML = `
                <div class="spinner" style="width: 1rem; height: 1rem; border-width: 2px;"></div>
                <span>Exportando...</span>
            `;

    try {
        const type = document.getElementById('exportType').value;
        const format = document.getElementById('exportFormat').value;

        const params = new URLSearchParams({
            type,
            format,
            year: state.currentMonth.split('-')[0],
            month: state.currentMonth.split('-')[1]
        });

        if (state.currentAccount) {
            params.set('account_id', state.currentAccount);
        }

        const response = await fetch(`${CONFIG.BASE_URL}api/reports/export?${params}`, {
            credentials: 'include'
        });

        if (!response.ok) throw new Error('Export failed');

        const blob = await response.blob();
        const disposition = response.headers.get('Content-Disposition');
        const filename = extractFilename(disposition) ||
            (format === 'excel' ? 'relatorio.xlsx' : 'relatorio.pdf');

        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Export error:', error);
        alert('Erro ao exportar relatório. Tente novamente.');
    } finally {
        exportBtn.disabled = false;
        exportBtn.innerHTML = originalHTML;
    }
}

function extractFilename(disposition) {
    if (!disposition) return null;

    const utf8Match = /filename\*=UTF-8''([^;]+)/i.exec(disposition);
    if (utf8Match) {
        try {
            return decodeURIComponent(utf8Match[1]);
        } catch (e) {
            return utf8Match[1];
        }
    }

    const simpleMatch = /filename="?([^";]+)"?/i.exec(disposition);
    return simpleMatch ? simpleMatch[1] : null;
}

// ============================================================================
// EVENT HANDLERS
// ============================================================================
function handleTabChange(view) {
    state.currentView = view;
    setActiveTab(view);
    updateControls();
    syncPickerMode();
    renderReport();
}

function handleTypeChange(type) {
    if (state.currentView === CONFIG.VIEWS.ANNUAL_CATEGORY) {
        state.annualCategoryType = type;
    } else {
        state.categoryType = type;
    }
    renderReport();
}

function handleAccountChange(accountId) {
    state.currentAccount = accountId || null;
    renderReport();
}

function onExternalMonthChange(event) {
    if (!event?.detail?.month || isYearlyView()) return;
    if (state.currentMonth === event.detail.month) return;
    state.currentMonth = event.detail.month;
    updateMonthLabel();
    renderReport();
}

function onExternalYearChange(event) {
    if (!isYearlyView() || !event?.detail?.year) return;
    const [, monthPart = '01'] = state.currentMonth.split('-');
    const normalizedMonth = String(monthPart).padStart(2, '0');
    const newValue = `${event.detail.year}-${normalizedMonth}`;
    if (state.currentMonth === newValue) return;
    state.currentMonth = newValue;
    renderReport();
}

// ============================================================================
// INITIALIZATION
// ============================================================================
async function initialize() {
    setupChartDefaults();

    // Load accounts
    state.accounts = await fetchAccounts();
    const accountSelect = document.getElementById('accountFilter');
    state.accounts.forEach(acc => {
        const option = document.createElement('option');
        option.value = acc.id;
        option.textContent = acc.name;
        accountSelect.appendChild(option);
    });

    // Setup event listeners
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => handleTabChange(btn.dataset.view));
    });

    document.getElementById('reportType').addEventListener('change', (e) => {
        handleTypeChange(e.target.value);
    });

    document.getElementById('accountFilter').addEventListener('change', (e) => {
        handleAccountChange(e.target.value);
    });

    const headerMonth = window.LukratoHeader?.getMonth?.();
    if (headerMonth) {
        state.currentMonth = headerMonth;
    }

    document.addEventListener('lukrato:month-changed', onExternalMonthChange);
    document.addEventListener('lukrato:year-changed', onExternalYearChange);

    document.getElementById('exportBtn').addEventListener('click', handleExport);

    // Initial render
    syncPickerMode();
    updateMonthLabel();
    updateControls();
    renderReport();
}

// Start the app
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize);
} else {
    initialize();
}

// Global API for external control
window.ReportsAPI = {
    setMonth: (yearMonth) => {
        if (!/^\d{4}-\d{2}$/.test(yearMonth)) return;
        state.currentMonth = yearMonth;
        updateMonthLabel();
        renderReport();
    },
    setView: (view) => {
        if (Object.values(CONFIG.VIEWS).includes(view)) {
            handleTabChange(view);
        }
    },
    refresh: () => renderReport(),
    getState: () => ({
        ...state
    })
};
</script>