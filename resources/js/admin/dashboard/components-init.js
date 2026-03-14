/**
 * Dashboard Components Initialization
 * Carrega Health Score e Greeting componentes
 * Arquivo de inicialização principal do dashboard melhorado (Sprint 2)
 */

document.addEventListener('DOMContentLoaded', async () => {
  // ============ GREETING COMPONENT ============
  // Cria container se não existir
  const greetingContainer = document.getElementById('greetingContainer') || createContainer('greetingContainer', 'before:kpiGrid');

  if (greetingContainer) {
    const greeting = new window.DashboardGreeting('greetingContainer');
    greeting.render();
    await new Promise(r => setTimeout(r, 100)); // Deixa renderizar antes de carregar dados
    greeting.loadInsight();
  }

  // ============ HEALTH SCORE COMPONENT ============
  // Cria container se não existir
  const healthContainer = document.getElementById('healthScoreContainer') || createContainer('healthScoreContainer', 'after:greetingContainer');

  if (healthContainer) {
    const healthScore = new window.HealthScoreWidget('healthScoreContainer');
    healthScore.render();
    await new Promise(r => setTimeout(r, 100));
    healthScore.load();
  }

  // Inicializa ícones (Lucide)
  if (typeof window.lucide !== 'undefined') {
    window.lucide.createIcons();
  }

  // ============ KEYBOARD SHORTCUTS ============
  // Adiciona atalhos (será usado em Sprint 11)
  setupKeyboardShortcuts();
});

/**
 * Helper function para criar containers
 */
function createContainer(id, position) {
  const existing = document.getElementById(id);
  if (existing) return existing;

  const container = document.createElement('div');
  container.id = id;
  container.className = 'dynamic-component-container';

  if (position.startsWith('before:')) {
    const anchor = document.querySelector(`.${position.split(':')[1]}`);
    if (anchor) {
      anchor.parentNode.insertBefore(container, anchor);
    } else {
      document.querySelector('.modern-dashboard')?.prepend(container);
    }
  } else if (position.startsWith('after:')) {
    const anchor = document.getElementById(position.split(':')[1]);
    if (anchor) {
      anchor.insertAdjacentElement('afterend', container);
    }
  }

  return container;
}

/**
 * Setup de atalhos de teclado (placeholder para Sprint 11)
 */
function setupKeyboardShortcuts() {
  document.addEventListener('keydown', (e) => {
    // N = novo lançamento
    if (e.key === 'n' && !isInputFocused()) {
      // Será implementado em Sprint 11
    }

    // ? = ajuda/atalhos
    if (e.key === '?' && !isInputFocused()) {
      // Será implementado em Sprint 11
    }
  });
}

/**
 * Verifica se um input está em foco
 */
function isInputFocused() {
  return document.activeElement?.tagName === 'INPUT' || document.activeElement?.tagName === 'TEXTAREA';
}

// Export para debugging
window.DashboardInit = {
  greeting: window.DashboardGreeting,
  healthScore: window.HealthScoreWidget,
};
