/**
 * Sprint 2 Dashboard Components
 * Carrega Health Score, Greeting e Finance Overview.
 */

function injectStyles() {
  const base = window.BASE_URL || window.__LK_CONFIG?.baseUrl || '/';
  const styles = [
    'assets/css/pages/admin-dashboard/health-score.css',
    'assets/css/pages/admin-dashboard/greeting.css',
    'assets/css/pages/admin-dashboard/health-score-insights.css'
  ];

  styles.forEach((path) => {
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

function waitForComponents() {
  return new Promise((resolve) => {
    let attempts = 0;
    const check = setInterval(() => {
      if (window.HealthScoreWidget && window.DashboardGreeting && window.HealthScoreInsights && window.FinanceOverview) {
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
