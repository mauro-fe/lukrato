/**
 * Sprint 2 Dashboard Components
 * Carrega Health Score, Greeting e Finance Overview.
 */

function injectStyles() {
  // CSS now bundled by Vite via dashboard/index.css — no runtime injection needed
}

function waitForComponents() {
  return new Promise((resolve) => {
    let attempts = 0;
    const check = setInterval(() => {
      if (window.HealthScoreWidget && window.DashboardGreeting && window.HealthScoreInsights && window.FinanceOverview && window.EvolucaoCharts) {
        clearInterval(check);
        resolve();
      }

      if (attempts++ > 50) {
        clearInterval(check);
        resolve();
      }
    }, 100);
  });
}

function ensureContainer(id, fallbackFactory) {
  const existing = document.getElementById(id);
  if (existing) return existing;
  return fallbackFactory();
}

async function initializeComponents() {
  injectStyles();
  await waitForComponents();

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboardComponents);
  } else {
    initDashboardComponents();
  }
}

function initDashboardComponents() {
  const dashboard = document.querySelector('.modern-dashboard');
  if (!dashboard) return;

  if (typeof window.DashboardGreeting !== 'undefined') {
    ensureContainer('greetingContainer', () => {
      const greetingDiv = document.createElement('div');
      greetingDiv.id = 'greetingContainer';
      dashboard.insertBefore(greetingDiv, dashboard.firstChild);
      return greetingDiv;
    });

    const greeting = new window.DashboardGreeting();
    greeting.render();
  }

  if (typeof window.HealthScoreWidget !== 'undefined') {
    const healthDiv = document.getElementById('healthScoreContainer');
    if (healthDiv) {
      const healthScore = new window.HealthScoreWidget();
      healthScore.render();
      healthScore.load();
    }

    if (typeof window.HealthScoreInsights !== 'undefined') {
      const insightsDiv = document.getElementById('healthScoreInsights');
      if (insightsDiv) {
        window.healthScoreInsights = new window.HealthScoreInsights();
      }
    }
  }

  // AI Tip Card
  if (typeof window.AiTipCard !== 'undefined') {
    const aiTipDiv = document.getElementById('aiTipContainer');
    if (aiTipDiv) {
      const aiTip = new window.AiTipCard();
      aiTip.init();
    }
  }

  // Evolução Financeira — always init the widget; visibility is controlled by customize.js
  if (typeof window.EvolucaoCharts !== 'undefined') {
    const evoDiv = document.getElementById('evolucaoChartsContainer');
    if (evoDiv) {
      const evo = new window.EvolucaoCharts();
      evo.init();
    }
  }

  if (typeof window.FinanceOverview !== 'undefined') {
    ensureContainer('financeOverviewContainer', () => {
      const foDiv = document.createElement('div');
      foDiv.id = 'financeOverviewContainer';
      const provisao = dashboard.querySelector('.provisao-section');
      if (provisao) {
        provisao.insertAdjacentElement('afterend', foDiv);
      } else {
        dashboard.appendChild(foDiv);
      }
      return foDiv;
    });

    const fo = new window.FinanceOverview();
    fo.render();
    fo.load();
  }

  if (typeof window.lucide !== 'undefined') {
    window.lucide.createIcons();
  }
}

initializeComponents();
