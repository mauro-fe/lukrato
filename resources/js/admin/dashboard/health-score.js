/**
 * Financial Health Score Component
 * Gauge compacto + métricas de lançamentos, orçamento e metas
 */

class HealthScoreWidget {
  constructor(containerId = 'healthScoreContainer') {
    this.container = document.getElementById(containerId);
    this.healthScore = 0;
    this.maxScore = 100;
    this.animationDuration = 1200;
  }

  render() {
    if (!this.container) return;

    const circumference = 339.29;

    this.container.innerHTML = `
      <div class="health-score-widget" data-aos="fade-up" data-aos-duration="500">
        <div class="hs-main">
          <div class="hs-gauge-area">
            <svg class="hs-gauge" viewBox="0 0 120 120">
              <defs>
                <linearGradient id="gaugeGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stop-color="#e67e22"/>
                  <stop offset="100%" stop-color="#f39c12"/>
                </linearGradient>
              </defs>
              <circle cx="60" cy="60" r="54" class="hs-gauge-track"/>
              <circle cx="60" cy="60" r="54" class="hs-gauge-fill"
                id="gaugeCircle"
                stroke-dasharray="${circumference}"
                stroke-dashoffset="${circumference}"
              />
              <text x="60" y="56" class="hs-gauge-value" id="gaugeValue">0</text>
              <text x="60" y="72" class="hs-gauge-label">de 100</text>
            </svg>
          </div>
          <div class="hs-info">
            <div class="hs-title-row">
              <h3 class="hs-title">Saúde Financeira</h3>
              <div class="hs-badge" id="healthIndicator">
                <span class="hs-badge-dot"></span>
                <span class="hs-badge-text">Carregando</span>
              </div>
            </div>
            <div class="hs-breakdown">
              <div class="hs-metric">
                <span class="hs-metric-label">Lançamentos</span>
                <span class="hs-metric-value" id="hsLancamentos">--</span>
              </div>
              <div class="hs-metric-divider"></div>
              <div class="hs-metric">
                <span class="hs-metric-label">Orçamento</span>
                <span class="hs-metric-value" id="hsOrcamento">--</span>
              </div>
              <div class="hs-metric-divider"></div>
              <div class="hs-metric">
                <span class="hs-metric-label">Metas</span>
                <span class="hs-metric-value" id="hsMetas">--</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;

    this.circumference = circumference;
    this.updateIcons();
  }

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

    if (!this._listeningDataChanged) {
      this._listeningDataChanged = true;
      document.addEventListener('lukrato:data-changed', () => this.load());
    }
  }

  updateScore(data) {
    const { score = 0 } = data;

    this.animateGauge(score);
    this.updateBreakdown(data);
    this.updateStatusIndicator(score);
  }

  animateGauge(targetScore) {
    const circle = document.getElementById('gaugeCircle');
    const valueText = document.getElementById('gaugeValue');
    if (!circle || !valueText) return;

    const circumference = this.circumference || 339.29;
    let currentScore = 0;
    const increment = targetScore / (this.animationDuration / 16);

    const animate = () => {
      currentScore += increment;
      if (currentScore >= targetScore) currentScore = targetScore;

      const offset = circumference - (circumference * currentScore / this.maxScore);
      circle.setAttribute('stroke-dashoffset', offset);
      valueText.textContent = Math.round(currentScore);

      if (currentScore < targetScore) {
        requestAnimationFrame(animate);
      }
    };

    animate();
  }

  updateBreakdown(data) {
    const lancEl = document.getElementById('hsLancamentos');
    const orcEl = document.getElementById('hsOrcamento');
    const metasEl = document.getElementById('hsMetas');

    // Lançamentos do mês
    if (lancEl) {
      const count = data.lancamentos ?? 0;
      lancEl.textContent = `${count} este mês`;
      if (count >= 10) {
        lancEl.className = 'hs-metric-value color-success';
      } else if (count >= 5) {
        lancEl.className = 'hs-metric-value color-warning';
      } else {
        lancEl.className = 'hs-metric-value color-muted';
      }
    }

    // Orçamento: X de Y no limite
    if (orcEl) {
      const total = data.orcamentos ?? 0;
      const ok = data.orcamentos_ok ?? 0;
      if (total === 0) {
        orcEl.textContent = 'Não definido';
        orcEl.className = 'hs-metric-value color-muted';
      } else {
        orcEl.textContent = `${ok}/${total} no limite`;
        if (ok === total) {
          orcEl.className = 'hs-metric-value color-success';
        } else if (ok >= total / 2) {
          orcEl.className = 'hs-metric-value color-warning';
        } else {
          orcEl.className = 'hs-metric-value color-danger';
        }
      }
    }

    // Metas ativas
    if (metasEl) {
      const ativas = data.metas_ativas ?? 0;
      const concluidas = data.metas_concluidas ?? 0;
      if (ativas === 0) {
        metasEl.textContent = 'Nenhuma';
        metasEl.className = 'hs-metric-value color-muted';
      } else if (concluidas > 0) {
        metasEl.textContent = `${ativas} ativa${ativas !== 1 ? 's' : ''} · ${concluidas} concluída${concluidas !== 1 ? 's' : ''}`;
        metasEl.className = 'hs-metric-value color-success';
      } else {
        metasEl.textContent = `${ativas} ativa${ativas !== 1 ? 's' : ''}`;
        metasEl.className = 'hs-metric-value color-warning';
      }
    }
  }

  updateStatusIndicator(score) {
    const indicator = document.getElementById('healthIndicator');
    if (!indicator) return;

    let status, label;

    if (score >= 70) {
      status = 'excellent';
      label = 'Excelente';
    } else if (score >= 50) {
      status = 'good';
      label = 'Bom';
    } else if (score >= 30) {
      status = 'warning';
      label = 'Atenção';
    } else {
      status = 'critical';
      label = 'Crítico';
    }

    indicator.className = `hs-badge hs-badge--${status}`;
    indicator.innerHTML = `
      <span class="hs-badge-dot"></span>
      <span class="hs-badge-text">${label}</span>
    `;
  }

  updateIcons() {
    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }

  showError() {
    const indicator = document.getElementById('healthIndicator');
    if (indicator) {
      indicator.className = 'hs-badge hs-badge--error';
      indicator.innerHTML = `
        <span class="hs-badge-dot"></span>
        <span class="hs-badge-text">Erro</span>
      `;
    }
  }
}

window.HealthScoreWidget = HealthScoreWidget;
