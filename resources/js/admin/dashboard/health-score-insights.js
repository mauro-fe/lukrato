import { apiGet } from '../shared/api.js';

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
    this.renderSkeleton();
    this.loadInsights();

    setInterval(() => this.loadInsights(), 300000);
    document.addEventListener('lukrato:data-changed', () => this.loadInsights());
  }

  renderSkeleton() {
    this.container.innerHTML = `
      <div class="hsi-list">
        <div class="hsi-skeleton"></div>
        <div class="hsi-skeleton"></div>
      </div>
    `;
  }

  async loadInsights() {
    try {
      const data = await apiGet(`${this.baseURL}api/dashboard/health-score/insights`);

      if (data.success && data.data) {
        this.renderInsights(data.data);
      } else {
        this.renderEmpty();
      }
    } catch (error) {
      console.error('Error loading health score insights:', error);
      this.renderEmpty();
    }
  }

  renderInsights(data) {
    const { insights = [], total_possible_improvement = '' } = data;

    if (insights.length === 0) {
      this.renderEmpty();
      return;
    }

    const cards = insights.map((insight, i) => `
      <a href="${this.baseURL}${insight.action.url}" class="hsi-card hsi-card--${insight.priority}" style="animation-delay: ${i * 80}ms;">
        <div class="hsi-card-icon hsi-icon--${insight.priority}">
          <i data-lucide="${this.getIconForType(insight.type)}" style="width:16px;height:16px;"></i>
        </div>
        <div class="hsi-card-body">
          <span class="hsi-card-title">${insight.title}</span>
          <span class="hsi-card-desc">${insight.message}</span>
        </div>
        <div class="hsi-card-meta">
          <span class="hsi-impact">${insight.impact}</span>
          <i data-lucide="chevron-right" style="width:14px;height:14px;" class="hsi-arrow"></i>
        </div>
      </a>
    `).join('');

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
