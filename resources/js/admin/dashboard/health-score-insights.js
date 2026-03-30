import { logClientError } from '../shared/api.js';
import { getDashboardOverview, invalidateDashboardOverview } from './dashboard-data.js';

/**
 * Health Score Insights Component
 * Cards compactos com sugestões acionáveis
 */

class HealthScoreInsights {
  constructor(containerId = 'healthScoreInsights') {
    this.container = document.getElementById(containerId);
    this.baseURL = window.BASE_URL || '/';
    this.init();
  }

  init() {
    if (!this.container) return;
    if (this._initialized) return;
    this._initialized = true;

    this.renderSkeleton();
    this.loadInsights();

    this._intervalId = setInterval(() => this.loadInsights({ force: true }), 300000);
    document.addEventListener('lukrato:data-changed', () => {
      invalidateDashboardOverview();
      this.loadInsights({ force: true });
    });
    document.addEventListener('lukrato:month-changed', () => {
      invalidateDashboardOverview();
      this.loadInsights({ force: true });
    });
  }

  renderSkeleton() {
    this.container.innerHTML = `
      <div class="hsi-list">
        <div class="hsi-skeleton"></div>
        <div class="hsi-skeleton"></div>
      </div>
    `;
  }

  async loadInsights({ force = false } = {}) {
    try {
      const response = await getDashboardOverview(undefined, { force });
      const data = response?.data ?? response;

      if (data?.health_score_insights) {
        this.renderInsights(data.health_score_insights);
      } else {
        this.renderEmpty();
      }
    } catch (error) {
      logClientError('Error loading health score insights', error, 'Falha ao carregar insights');
      this.renderEmpty();
    }
  }

  renderInsights(data) {
    const insights = Array.isArray(data) ? data : (data?.insights || []);
    const total_possible_improvement = Array.isArray(data) ? '' : (data?.total_possible_improvement || '');

    if (insights.length === 0) {
      this.renderEmpty();
      return;
    }

    const cards = insights.map((insight, i) => {
      const normalized = this.normalizeInsight(insight);
      return `
      <a href="${this.baseURL}${normalized.action.url}" class="hsi-card hsi-card--${normalized.priority}" style="animation-delay: ${i * 80}ms;">
        <div class="hsi-card-icon hsi-icon--${normalized.priority}">
          <i data-lucide="${this.getIconForType(normalized.type)}" style="width:16px;height:16px;"></i>
        </div>
        <div class="hsi-card-body">
          <span class="hsi-card-title">${normalized.title}</span>
          <span class="hsi-card-desc">${normalized.message}</span>
        </div>
        <div class="hsi-card-meta">
          <span class="hsi-impact">${normalized.impact}</span>
          <i data-lucide="chevron-right" style="width:14px;height:14px;" class="hsi-arrow"></i>
        </div>
      </a>
    `;
    }).join('');

    this.container.innerHTML = `
      <div class="hsi-list">${cards}</div>
      ${total_possible_improvement ? `
        <div class="hsi-summary">
          <i data-lucide="trending-up" style="width:14px;height:14px;"></i>
          <span>Potencial: <strong>${total_possible_improvement}</strong></span>
        </div>
      ` : ''}
    `;

    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }

  normalizeInsight(insight) {
    const defaults = {
      negative_balance: {
        title: 'Seu saldo ficou negativo',
        impact: 'Aja agora',
        action: { url: 'lancamentos?tipo=despesa' }
      },
      low_activity: {
        title: 'Registre mais movimentações',
        impact: 'Mais controle',
        action: { url: 'lancamentos' }
      },
      low_categories: {
        title: 'Use mais categorias',
        impact: 'Mais clareza',
        action: { url: 'categorias' }
      },
      no_goals: {
        title: 'Defina uma meta financeira',
        impact: 'Mais direcao',
        action: { url: 'financas#metas' }
      },
    };

    const preset = defaults[insight.type] || {
      title: 'Insight do mes',
      impact: 'Ver detalhe',
      action: { url: 'dashboard' }
    };

    return {
      priority: insight.priority || 'medium',
      type: insight.type || 'generic',
      title: insight.title || preset.title,
      message: insight.message || '',
      impact: insight.impact || preset.impact,
      action: insight.action || preset.action,
    };
  }

  renderEmpty() {
    this.container.innerHTML = '';
  }

  getIconForType(type) {
    const icons = {
      'savings_rate': 'piggy-bank',
      'consistency': 'calendar-check',
      'diversification': 'layers',
      'negative_balance': 'alert-triangle',
      'low_balance': 'wallet',
      'no_income': 'alert-circle',
      'no_goals': 'target',
    };
    return icons[type] || 'lightbulb';
  }
}

window.HealthScoreInsights = HealthScoreInsights;
