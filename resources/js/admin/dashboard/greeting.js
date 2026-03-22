import { apiGet } from '../shared/api.js';

/**
 * Dashboard Greeting Component
 * Saudação contextual baseada em hora, com insight dinâmico
 */

class DashboardGreeting {
  constructor(containerId = 'greetingContainer') {
    this.container = document.getElementById(containerId);
    const fullName = window.__LK_CONFIG?.username || 'Usuário';
    this.userName = fullName.split(' ')[0];
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
      <div class="dashboard-greeting" data-aos="fade-right" data-aos-duration="500">
        <div class="greeting-content">
          <p class="greeting-date">${dateStr}</p>
          <h1 class="greeting-title">
            ${greeting.title}
          </h1>
          <div class="greeting-insight" id="greetingInsight">
            <div class="insight-skeleton">
              <div class="skeleton-line" style="width: 65%;"></div>
              <div class="skeleton-line" style="width: 40%; margin-top: 0.5rem;"></div>
            </div>
          </div>
        </div>
        <div class="greeting-visual">
          <span class="greeting-emoji" id="greetingEmoji">${greeting.emoji}</span>
        </div>
      </div>
    `;

    this.animateEmoji();
    this.loadInsight();
  }

  getGreeting() {
    const hour = new Date().getHours();

    if (hour >= 5 && hour < 12) {
      return { title: `Bom dia, ${this.userName}!`, emoji: '🌅' };

    } else if (hour >= 12 && hour < 18) {
      return { title: `Boa tarde, ${this.userName}!`, emoji: '☀️' };

    } else if (hour >= 18 && hour < 24) {
      return { title: `Boa noite, ${this.userName}!`, emoji: '🌆' };

    } else {
      return { title: `Boa madrugada, ${this.userName}!`, emoji: '🌙' };
    }
  }

  animateEmoji() {
    const emoji = document.getElementById('greetingEmoji');
    if (!emoji) return;
    emoji.style.animation = 'greeting-pulse 2s ease-in-out infinite';
  }

  async loadInsight() {
    try {
      const data = await apiGet(`${window.BASE_URL || '/'}api/dashboard/greeting-insight`);

      if (data.success && data.data) {
        this.displayInsight(data.data);
      } else {
        this.displayFallbackInsight();
      }
    } catch (error) {
      console.error('Error loading insight:', error);
      this.displayFallbackInsight();
    }
  }

  displayInsight(data) {
    const container = document.getElementById('greetingInsight');
    if (!container) return;

    const { message, icon, color } = data;

    container.style.opacity = '0';
    container.innerHTML = `
      <div class="insight-content">
        <div class="insight-icon" style="color: ${color || 'var(--color-primary)'};">
          <i data-lucide="${icon || 'trending-up'}" style="width:18px;height:18px;"></i>
        </div>
        <p class="insight-message">${message}</p>
      </div>
    `;

    setTimeout(() => {
      container.style.transition = 'opacity 0.4s ease';
      container.style.opacity = '1';
    }, 100);

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
          <i data-lucide="sparkles" style="width:18px;height:18px;"></i>
        </div>
        <p class="insight-message">Bem-vindo ao seu painel financeiro</p>
      </div>
    `;

    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }
}

window.DashboardGreeting = DashboardGreeting;
