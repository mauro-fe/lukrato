/**
 * Sprint 2 Dashboard Components
 * Carrega Health Score e Greeting dinamicamente
 */

// Injeta CSS dos componentes
function injectStyles() {
  const base = window.BASE_URL || window.__LK_CONFIG?.baseUrl || '/';
  const styles = [
    'assets/css/pages/admin-dashboard/health-score.css',
    'assets/css/pages/admin-dashboard/greeting.css',
    'assets/css/pages/admin-dashboard/health-score-insights.css'
  ];

  styles.forEach(path => {
    const href = base + path;
    if (!document.querySelector(`link[href="${href}"]`)) {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = href;
      link.type = 'text/css';
      document.head.appendChild(link);
    }
  });
}

// Aguarda os componentes estarem disponíveis globalmente
function waitForComponents() {
  return new Promise((resolve) => {
    let attempts = 0;
    const check = setInterval(() => {
      if (window.HealthScoreWidget && window.DashboardGreeting && window.HealthScoreInsights && window.FinanceOverview) {
        clearInterval(check);
        resolve();
      }
      if (attempts++ > 50) {
        // Timeout após 5s, procede mesmo assim
        clearInterval(check);
        resolve();
      }
    }, 100);
  });
}

// Inicializa os componentes
async function initializeComponents() {
  injectStyles();

  await waitForComponents();

  // Aguarda o DOM estar pronto
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboardComponents);
  } else {
    initDashboardComponents();
  }
}

function initDashboardComponents() {
  // Encontra a seção do dashboard
  const dashboard = document.querySelector('.modern-dashboard');
  if (!dashboard) return;

  // Greeting Component
  if (typeof window.DashboardGreeting !== 'undefined') {
    const greetingDiv = document.createElement('div');
    greetingDiv.id = 'greetingContainer';
    dashboard.insertBefore(greetingDiv, dashboard.firstChild);

    const greeting = new window.DashboardGreeting();
    greeting.render();
  }

  // Health Score Component
  if (typeof window.HealthScoreWidget !== 'undefined') {
    const healthDiv = document.createElement('div');
    healthDiv.id = 'healthScoreContainer';

    // Insere após KPI grid
    const kpiGrid = dashboard.querySelector('.kpi-grid');
    if (kpiGrid) {
      kpiGrid.insertAdjacentElement('afterend', healthDiv);
    } else {
      dashboard.insertBefore(healthDiv, dashboard.children[1]);
    }

    const healthScore = new window.HealthScoreWidget();
    healthScore.render();
    healthScore.load();

    // Health Score Insights — container separado, fora do card
    if (typeof window.HealthScoreInsights !== 'undefined') {
      const insightsDiv = document.createElement('div');
      insightsDiv.id = 'healthScoreInsights';
      insightsDiv.className = 'health-score-insights-section';
      healthDiv.insertAdjacentElement('afterend', insightsDiv);
      window.healthScoreInsights = new window.HealthScoreInsights();
    }
  }

  // Finance Overview (Orçamento + Metas)
  if (typeof window.FinanceOverview !== 'undefined') {
    const foDiv = document.createElement('div');
    foDiv.id = 'financeOverviewContainer';

    // Insere após insights, ou após health score, ou antes de provisão
    const insightsEl = document.getElementById('healthScoreInsights');
    const provisao = dashboard.querySelector('.provisao-section');
    if (insightsEl) {
      insightsEl.insertAdjacentElement('afterend', foDiv);
    } else if (provisao) {
      provisao.insertAdjacentElement('beforebegin', foDiv);
    } else {
      dashboard.appendChild(foDiv);
    }

    const fo = new window.FinanceOverview();
    fo.render();
    fo.load();
  }

  // Atualiza ícones
  if (typeof window.lucide !== 'undefined') {
    window.lucide.createIcons();
  }
}

// Inicia quando o script é carregado
initializeComponents();
