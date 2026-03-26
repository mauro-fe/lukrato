import { logClientError } from '../shared/api.js';
import { getDashboardOverview, invalidateDashboardOverview } from './dashboard-data.js';

/**
 * Financial Health Score Component — Compact
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

    const r = 45;
    this.circumference = 2 * Math.PI * r;
    const circ = this.circumference;

    this.container.innerHTML = `
      <div class="health-score-widget" data-aos="fade-up" data-aos-duration="400">
        <div class="hs-header">
          <h2 class="hs-title">Saude financeira</h2>
          <div class="hs-badge" id="healthIndicator">
            <span class="hs-badge-dot"></span>
            <span class="hs-badge-text">...</span>
          </div>
        </div>

        <div class="hs-gauge-area">
          <svg class="hs-gauge" viewBox="0 0 100 100">
            <defs>
              <linearGradient id="gaugeGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#10b981"/>
                <stop offset="100%" stop-color="#3b82f6"/>
              </linearGradient>
            </defs>
            <circle cx="50" cy="50" r="${r}" class="hs-gauge-track"/>
            <circle cx="50" cy="50" r="${r}" class="hs-gauge-fill"
              id="gaugeCircle"
              stroke-dasharray="${circ}"
              stroke-dashoffset="${circ}"
            />
            <text x="50" y="47" class="hs-gauge-value" id="gaugeValue">0</text>
            <text x="50" y="60" class="hs-gauge-label">de 100</text>
          </svg>
        </div>

        <p class="hs-message" id="healthMessage">Carregando...</p>

        <div class="hs-breakdown">
          <div class="hs-metric">
            <span class="hs-metric-label">Registros</span>
            <span class="hs-metric-value" id="hsLancamentos">--</span>
          </div>
          <div class="hs-metric">
            <span class="hs-metric-label">Limites</span>
            <span class="hs-metric-value" id="hsOrcamento">--</span>
          </div>
          <div class="hs-metric">
            <span class="hs-metric-label">Metas</span>
            <span class="hs-metric-value" id="hsMetas">--</span>
          </div>
        </div>
      </div>
    `;

    this.updateIcons();
  }

  async load({ force = false } = {}) {
    try {
      const response = await getDashboardOverview(undefined, { force });
      const data = response?.data ?? response;

      if (data?.health_score) {
        this.updateScore(data.health_score);
      }
    } catch (error) {
      logClientError('Error loading health score', error, 'Falha ao carregar health score');
      this.showError();
    }

    if (!this._listeningDataChanged) {
      this._listeningDataChanged = true;
      document.addEventListener('lukrato:data-changed', () => {
        invalidateDashboardOverview();
        this.load({ force: true });
      });
      document.addEventListener('lukrato:month-changed', () => {
        invalidateDashboardOverview();
        this.load({ force: true });
      });
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

    const circumference = this.circumference || (2 * Math.PI * 45);
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

    if (lancEl) {
      const count = data.lancamentos ?? 0;
      lancEl.textContent = `${count}`;
      if (count >= 10) {
        lancEl.className = 'hs-metric-value color-success';
      } else if (count >= 5) {
        lancEl.className = 'hs-metric-value color-warning';
      } else {
        lancEl.className = 'hs-metric-value color-muted';
      }
    }

    if (orcEl) {
      const total = data.orcamentos ?? 0;
      const ok = data.orcamentos_ok ?? 0;
      if (total === 0) {
        orcEl.textContent = '--';
        orcEl.className = 'hs-metric-value color-muted';
      } else {
        orcEl.textContent = `${ok}/${total}`;
        if (ok === total) {
          orcEl.className = 'hs-metric-value color-success';
        } else if (ok >= total / 2) {
          orcEl.className = 'hs-metric-value color-warning';
        } else {
          orcEl.className = 'hs-metric-value color-danger';
        }
      }
    }

    if (metasEl) {
      const ativas = data.metas_ativas ?? 0;
      const concluidas = data.metas_concluidas ?? 0;
      if (ativas === 0) {
        metasEl.textContent = '--';
        metasEl.className = 'hs-metric-value color-muted';
      } else if (concluidas > 0) {
        metasEl.textContent = `${ativas}+${concluidas}`;
        metasEl.className = 'hs-metric-value color-success';
      } else {
        metasEl.textContent = `${ativas}`;
        metasEl.className = 'hs-metric-value color-warning';
      }
    }
  }

  updateStatusIndicator(score) {
    const indicator = document.getElementById('healthIndicator');
    const messageEl = document.getElementById('healthMessage');
    if (!indicator) return;

    let status = 'critical';
    let label = 'CRITICA';
    let message = 'Ajustes rapidos podem evitar aperto financeiro.';

    if (score >= 70) {
      status = 'excellent';
      label = 'BOA';
      message = 'Voce esta no controle. Continue assim!';
    } else if (score >= 50) {
      status = 'good';
      label = 'ESTAVEL';
      message = 'Controle bom, mas ha espaco para melhorar.';
    } else if (score >= 30) {
      status = 'warning';
      label = 'ATENCAO';
      message = 'Alguns sinais pedem cuidado neste mes.';
    }

    indicator.className = `hs-badge hs-badge--${status}`;
    indicator.innerHTML = `
      <span class="hs-badge-dot"></span>
      <span class="hs-badge-text">${label}</span>
    `;

    if (messageEl) {
      messageEl.textContent = message;
    }
  }

  updateIcons() {
    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }

  showError() {
    const indicator = document.getElementById('healthIndicator');
    const messageEl = document.getElementById('healthMessage');

    if (indicator) {
      indicator.className = 'hs-badge hs-badge--error';
      indicator.innerHTML = `
        <span class="hs-badge-dot"></span>
        <span class="hs-badge-text">Erro</span>
      `;
    }

    if (messageEl) {
      messageEl.textContent = 'Nao foi possivel carregar.';
    }
  }
}

window.HealthScoreWidget = HealthScoreWidget;
