import { logClientError } from '../shared/api.js';
import { getDashboardOverview, invalidateDashboardOverview } from './dashboard-data.js';

/**
 * Dashboard Greeting Component
 * Versao compacta para apoiar o resumo financeiro principal.
 */

class DashboardGreeting {
  constructor(containerId = 'greetingContainer') {
    this.container = document.getElementById(containerId);
    const fullName = window.__LK_CONFIG?.username || 'Usuario';
    this.userName = fullName.split(' ')[0];
    this._listeningDataChanged = false;
  }

  render() {
    if (!this.container) return;

    const greeting = this.getGreeting();
    const today = new Date();
    const dateStr = today.toLocaleDateString('pt-BR', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
    });

    this.container.innerHTML = `
      <div class="dashboard-greeting dashboard-greeting--compact" data-aos="fade-right" data-aos-duration="500">
        <p class="greeting-date">${dateStr}</p>
        <p class="greeting-title">${greeting.title}</p>
        <div class="greeting-insight" id="greetingInsight">
          <div class="insight-skeleton">
            <div class="skeleton-line" style="width: 70%;"></div>
          </div>
        </div>
      </div>
    `;

    this.loadInsight();
  }

  getGreeting() {
    const hour = new Date().getHours();

    if (hour >= 5 && hour < 12) {
      return { title: `Bom dia, ${this.userName}.` };
    }

    if (hour >= 12 && hour < 18) {
      return { title: `Boa tarde, ${this.userName}.` };
    }

    if (hour >= 18 && hour < 24) {
      return { title: `Boa noite, ${this.userName}.` };
    }

    return { title: `Boa madrugada, ${this.userName}.` };
  }

  async loadInsight({ force = false } = {}) {
    try {
      const response = await getDashboardOverview(undefined, { force });
      const data = response?.data ?? response;

      if (data?.greeting_insight) {
        this.displayInsight(data.greeting_insight);
      } else {
        this.displayFallbackInsight();
      }
    } catch (error) {
      logClientError('Error loading greeting insight', error, 'Falha ao carregar insight');
      this.displayFallbackInsight();
    }

    if (!this._listeningDataChanged) {
      this._listeningDataChanged = true;
      document.addEventListener('lukrato:data-changed', () => {
        invalidateDashboardOverview();
        this.loadInsight({ force: true });
      });
      document.addEventListener('lukrato:month-changed', () => {
        invalidateDashboardOverview();
        this.loadInsight({ force: true });
      });
    }
  }

  displayInsight(data) {
    const container = document.getElementById('greetingInsight');
    if (!container) return;

    const { message, icon, color } = data;

    container.innerHTML = `
      <div class="insight-content">
        <div class="insight-icon" style="color: ${color || 'var(--color-primary)'};">
          <i data-lucide="${icon || 'sparkles'}" style="width:16px;height:16px;"></i>
        </div>
        <p class="insight-message">${message}</p>
      </div>
    `;

    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }

  displayFallbackInsight() {
    const container = document.getElementById('greetingInsight');
    if (!container) return;

    container.innerHTML = `
      <div class="insight-content">
        <div class="insight-icon">
          <i data-lucide="sparkles" style="width:16px;height:16px;"></i>
        </div>
        <p class="insight-message">Seu resumo financeiro do mes aparece logo abaixo.</p>
      </div>
    `;

    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }
}

window.DashboardGreeting = DashboardGreeting;
