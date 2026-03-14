/**
 * Financial Health Score Component
 * Exibe um gauge circular mostrando a saúde financeira do usuário (0-100)
 * Calcula baseado em: taxa de poupança, consistência, diversificação, saldo positivo
 */

class HealthScoreWidget {
  constructor(containerId = 'healthScoreContainer') {
    this.container = document.getElementById(containerId);
    this.healthScore = 0;
    this.maxScore = 100;
    this.animationDuration = 1200;
  }

  /**
   * Renderiza o widget HTML
   */
  render() {
    if (!this.container) return;

    this.container.innerHTML = `
      <div class="health-score-widget" data-aos="fade-up" data-aos-duration="500">
        <div class="health-score-header">
          <div>
            <h3 class="health-score-title">
              <i data-lucide="heart-handshake" style="width:20px;height:20px;"></i>
              Saúde Financeira
            </h3>
            <p class="health-score-subtitle">Seu score de bem-estar financeiro</p>
          </div>
        </div>

        <div class="health-score-body">
          <div class="health-score-gauge-wrapper">
            <svg class="health-score-gauge" viewBox="0 0 200 200" width="200" height="200">
              <!-- Background circle -->
              <circle cx="100" cy="100" r="90" class="gauge-bg" />
              <!-- Progress circle -->
              <circle
                cx="100"
                cy="100"
                r="90"
                class="gauge-progress"
                style="--score: 0; --total: ${this.maxScore};"
                id="gaugeCircle"
              />
              <!-- Center content -->
              <g class="gauge-center-content">
                <text x="100" y="95" class="gauge-value" id="gaugeValue">0</text>
                <text x="100" y="110" class="gauge-label">/100</text>
              </g>
            </svg>
            <div class="health-score-indicator" id="healthIndicator">
              <span class="indicator-dot"></span>
              <span class="indicator-text">Carregando...</span>
            </div>
          </div>

          <div class="health-score-breakdown">
            <div class="breakdown-item">
              <div class="breakdown-icon" style="color: #10b981;">
                <i data-lucide="piggy-bank" style="width:16px;height:16px;"></i>
              </div>
              <div class="breakdown-content">
                <span class="breakdown-label">Taxa de Poupança</span>
                <span class="breakdown-value" id="savingsRate">--%</span>
              </div>
            </div>

            <div class="breakdown-item">
              <div class="breakdown-icon" style="color: #3b82f6;">
                <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
              </div>
              <div class="breakdown-content">
                <span class="breakdown-label">Consistência</span>
                <span class="breakdown-value" id="consistency">--</span>
              </div>
            </div>

            <div class="breakdown-item">
              <div class="breakdown-icon" style="color: #f59e0b;">
                <i data-lucide="layers" style="width:16px;height:16px;"></i>
              </div>
              <div class="breakdown-content">
                <span class="breakdown-label">Diversificação</span>
                <span class="breakdown-value" id="diversification">--</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;

    this.updateIcons();
  }

  /**
   * Carrega dados da API e atualiza o widget
   */
  async load() {
    try {
      const response = await fetch(`${window.BASE_URL || '/'}api/dashboard/health-score`);
      if (!response.ok) throw new Error('Failed to fetch health score');

      const data = await response.json();

      if (data.success && data.data) {
        this.updateScore(data.data);
      }
    } catch (error) {
      console.error('Error loading health score:', error);
      this.showError();
    }
  }

  /**
   * Atualiza o score e anima a mudança
   */
  updateScore(data) {
    const {
      score = 50,
      savingsRate = 0,
      consistency = 'Ótima',
      categories = 0,
    } = data;

    // Anima o gauge circular
    this.animateGauge(score);

    // Atualiza breakdown
    this.updateBreakdown({
      savingsRate,
      consistency,
      categories,
    });

    // Atualiza indicador de status
    this.updateStatusIndicator(score);
  }

  /**
   * Anima o gauge SVG do score
   */
  animateGauge(targetScore) {
    const circle = document.getElementById('gaugeCircle');
    const valueText = document.getElementById('gaugeValue');
    if (!circle || !valueText) return;

    let currentScore = 0;
    const increment = targetScore / (this.animationDuration / 16);

    const animate = () => {
      currentScore += increment;
      if (currentScore >= targetScore) {
        currentScore = targetScore;
      }

      circle.style.setProperty('--score', Math.round(currentScore));
      valueText.textContent = Math.round(currentScore);

      if (currentScore < targetScore) {
        requestAnimationFrame(animate);
      }
    };

    animate();
  }

  /**
   * Atualiza a breakdown de fatores
   */
  updateBreakdown(data) {
    const savingsEl = document.getElementById('savingsRate');
    const consistencyEl = document.getElementById('consistency');
    const diversificationEl = document.getElementById('diversification');

    if (savingsEl) {
      savingsEl.textContent = `${Math.round(data.savingsRate)}%`;
      savingsEl.className = 'breakdown-value ' + this.getColorClass(data.savingsRate, 'savings');
    }

    if (consistencyEl) {
      consistencyEl.textContent = data.consistency;
      consistencyEl.className = 'breakdown-value ' + this.getColorClass(data.consistency, 'consistency');
    }

    if (diversificationEl) {
      diversificationEl.textContent = `${data.categories} categoria${data.categories !== 1 ? 's' : ''}`;
      diversificationEl.className = 'breakdown-value ' + this.getColorClass(data.categories, 'categories');
    }
  }

  /**
   * Atualiza o indicador de status baseado no score
   */
  updateStatusIndicator(score) {
    const indicator = document.getElementById('healthIndicator');
    if (!indicator) return;

    let status, message, color;

    if (score >= 70) {
      status = 'excellent';
      message = '✨ Excelente!';
      color = '#10b981';
    } else if (score >= 50) {
      status = 'good';
      message = '👍 No caminho certo';
      color = '#3b82f6';
    } else if (score >= 30) {
      status = 'warning';
      message = '⚠️ Precisa de atenção';
      color = '#f59e0b';
    } else {
      status = 'critical';
      message = '🚨 Crítico';
      color = '#ef4444';
    }

    indicator.className = `health-score-indicator status-${status}`;
    indicator.innerHTML = `
      <span class="indicator-dot" style="background: ${color};"></span>
      <span class="indicator-text">${message}</span>
    `;
  }

  /**
   * Retorna classe de cor baseada em valores
   */
  getColorClass(value, type) {
    if (type === 'savings') {
      if (value >= 20) return 'color-success';
      if (value >= 10) return 'color-warning';
      return 'color-danger';
    } else if (type === 'consistency') {
      if (value === 'Excelente' || value === 'Ótima') return 'color-success';
      if (value === 'Boa') return 'color-warning';
      return 'color-danger';
    } else if (type === 'categories') {
      if (value >= 5) return 'color-success';
      if (value >= 3) return 'color-warning';
      return 'color-muted';
    }
    return '';
  }

  /**
   * Atualiza os ícones com lucide
   */
  updateIcons() {
    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }

  /**
   * Mostra erro na UI
   */
  showError() {
    const indicator = document.getElementById('healthIndicator');
    if (indicator) {
      indicator.className = 'health-score-indicator status-error';
      indicator.innerHTML = `
        <span class="indicator-dot" style="background: #6b7280;"></span>
        <span class="indicator-text">Erro ao carregar</span>
      `;
    }
  }
}

// Export para uso em otro lugar
window.HealthScoreWidget = HealthScoreWidget;
