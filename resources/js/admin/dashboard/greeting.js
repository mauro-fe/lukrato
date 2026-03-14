/**
 * Dashboard Greeting Component
 * Saudação contextual baseada em hora, com insight dinâmico
 */

class DashboardGreeting {
  constructor(containerId = 'greetingContainer') {
    this.container = document.getElementById(containerId);
    this.userName = document.body.getAttribute('data-user-name') || 'Usuário';
  }

  /**
   * Renderiza o componente de saudação
   */
  render() {
    if (!this.container) return;

    const greeting = this.getGreeting();

    this.container.innerHTML = `
      <div class="dashboard-greeting" data-aos="fade-right" data-aos-duration="500">
        <div class="greeting-content">
          <h1 class="greeting-title">
            <span class="greeting-emoji" id="greetingEmoji">${greeting.emoji}</span>
            ${greeting.title}
          </h1>
          <p class="greeting-subtitle" id="greetingSubtitle">
            Carregando seu resumo do dia...
          </p>
        </div>
        <div class="greeting-insight" id="greetingInsight">
          <div class="insight-skeleton">
            <div class="skeleton-line" style="width: 70%;"></div>
            <div class="skeleton-line" style="width: 50%; margin-top: 0.5rem;"></div>
          </div>
        </div>
      </div>
    `;

    // Anima o emoji
    this.animateEmoji();

    // Carrega insight dinâmico
    this.loadInsight();
  }

  /**
   * Retorna saudação baseada na hora
   */
  getGreeting() {
    const hour = new Date().getHours();

    if (hour >= 5 && hour < 12) {
      return {
        title: `Bom dia, ${this.userName}!`,
        emoji: '🌅',
      };
    } else if (hour >= 12 && hour < 18) {
      return {
        title: `Boa tarde, ${this.userName}!`,
        emoji: '☀️',
      };
    } else if (hour >= 18 && hour < 21) {
      return {
        title: `Boa noite, ${this.userName}!`,
        emoji: '🌆',
      };
    } else {
      return {
        title: `Olá, ${this.userName}!`,
        emoji: '🌙',
      };
    }
  }

  /**
   * Anima o emoji com rotação suave
   */
  animateEmoji() {
    const emoji = document.getElementById('greetingEmoji');
    if (!emoji) return;

    // Pulse animation
    emoji.style.animation = 'greeting-pulse 2s ease-in-out infinite';
  }

  /**
   * Carrega insight dinâmico da API
   */
  async loadInsight() {
    try {
      const response = await fetch(`${window.BASE_URL || '/'}api/dashboard/greeting-insight`);
      if (!response.ok) throw new Error('Failed to fetch insight');

      const data = await response.json();

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

  /**
   * Exibe o insight com animação
   */
  displayInsight(data) {
    const container = document.getElementById('greetingInsight');
    const subtitle = document.getElementById('greetingSubtitle');

    if (!container) return;

    const { message, icon, color } = data;

    // Anima fade-in do insight
    container.style.opacity = '0';
    container.innerHTML = `
      <div class="insight-content">
        <div class="insight-icon" style="color: ${color || 'var(--color-primary)'};">
          <i data-lucide="${icon || 'trending-up'}" style="width:20px;height:20px;"></i>
        </div>
        <div class="insight-text">
          <p class="insight-message">${message}</p>
        </div>
      </div>
    `;

    // Atualiza subtitle
    if (subtitle) {
      subtitle.style.opacity = '0';
      subtitle.textContent = message;

      // Fade in do subtitle
      setTimeout(() => {
        subtitle.style.transition = 'opacity 0.4s ease';
        subtitle.style.opacity = '1';
      }, 100);
    }

    // Fade in do insight
    setTimeout(() => {
      container.style.transition = 'opacity 0.4s ease';
      container.style.opacity = '1';
    }, 100);

    // Atualiza ícones
    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }

  /**
   * Exibe insight padrão quando API falha
   */
  displayFallbackInsight() {
    const container = document.getElementById('greetingInsight');
    const subtitle = document.getElementById('greetingSubtitle');

    if (container) {
      container.innerHTML = `
        <div class="insight-content">
          <div class="insight-icon">
            <i data-lucide="sparkles"></i>
          </div>
          <div class="insight-text">
            <p class="insight-message">Bem-vindo ao seu painel de controle financeiro</p>
          </div>
        </div>
      `;

      if (typeof window.lucide !== 'undefined') {
        window.lucide.createIcons();
      }
    }

    if (subtitle) {
      subtitle.textContent = 'Bem-vindo ao seu painel de controle financeiro';
    }
  }
}

// Export para uso
window.DashboardGreeting = DashboardGreeting;
