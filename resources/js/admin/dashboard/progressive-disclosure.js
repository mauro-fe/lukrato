import { logClientError } from '../shared/api.js';
import { getDashboardOverview, invalidateDashboardOverview } from './dashboard-data.js';

/**
 * Progressive disclosure for first-time users.
 * Shows only the essentials and reveals more sections as the user engages.
 */
class ProgressiveDisclosure {
  constructor(config = {}) {
    this.config = {
      isFirstTime: config.isFirstTime || window.__lkFirstVisit || false,
      minTransactionsToUnlock: config.minTransactionsToUnlock || 1,
      ...config,
    };

    this.state = {
      transactionCount: 0,
      hiddenSections: [],
    };

    this.HIDDEN_SECTIONS_KEY = 'lk_hidden_sections_shown';
    this.init();
  }

  init() {
    if (!this.config.isFirstTime) return;

    this.checkTransactionCount();

    document.addEventListener('lukrato:transaction-added', () => {
      this.onTransactionAdded();
    });

    document.addEventListener('lukrato:data-changed', (e) => {
      if (e.detail?.resource === 'transactions') {
        invalidateDashboardOverview();
        this.checkTransactionCount({ force: true });
      }
    });
  }

  async checkTransactionCount({ force = false } = {}) {
    try {
      const response = await getDashboardOverview(this.getCurrentMonth(), { force });
      const data = (response?.data ?? response)?.metrics || null;

      if (data && typeof data.count !== 'undefined') {
        this.state.transactionCount = parseInt(data.count, 10) || 0;
        this.updateHiddenSections();
      }
    } catch (error) {
      logClientError('Error checking transaction count', error, 'Falha ao verificar transacoes');
    }
  }

  updateHiddenSections() {
    const sections = [
      { id: 'sectionPrevisao', threshold: 0, label: 'Previsao' },
      { id: 'chart-section', threshold: 2, label: 'Grafico' },
      { id: 'table-section', threshold: 0, label: 'Transacoes' },
    ];

    sections.forEach((section) => {
      const el = document.querySelector(`#${section.id}, .${section.id}`);
      if (!el) return;

      const shouldShow = this.state.transactionCount >= section.threshold;

      if (shouldShow && el.classList.contains('progressive-hidden')) {
        this.revealSection(el, section.label);
      } else if (!shouldShow && !el.classList.contains('progressive-hidden')) {
        el.classList.add('progressive-hidden');
        el.style.opacity = '0.5';
        el.style.pointerEvents = 'none';
      }
    });
  }

  revealSection(el, label) {
    el.classList.remove('progressive-hidden');
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)';
    el.style.pointerEvents = 'auto';

    setTimeout(() => {
      el.style.opacity = '1';
      el.style.transform = 'translateY(0)';
    }, 100);

    if (window.LK?.toast) {
      window.LK.toast.success(`Nova secao desbloqueada: ${label}`);
    }

    const shown = JSON.parse(localStorage.getItem(this.HIDDEN_SECTIONS_KEY) || '[]');
    if (!shown.includes(label)) {
      shown.push(label);
      localStorage.setItem(this.HIDDEN_SECTIONS_KEY, JSON.stringify(shown));
    }
  }

  onTransactionAdded() {
    this.checkTransactionCount();
  }

  getCurrentMonth() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    return `${year}-${month}`;
  }

  revealAll() {
    const sections = document.querySelectorAll('.progressive-hidden');
    sections.forEach((el, index) => {
      setTimeout(() => {
        el.classList.remove('progressive-hidden');
        el.style.opacity = '1';
        el.style.pointerEvents = 'auto';
      }, index * 100);
    });

    localStorage.setItem(this.HIDDEN_SECTIONS_KEY, JSON.stringify([
      'Previsao',
      'Grafico',
      'Transacoes',
    ]));
  }
}

window.ProgressiveDisclosure = ProgressiveDisclosure;

document.addEventListener('DOMContentLoaded', () => {
  const isFirstTime = Boolean(window.__lkFirstVisit)
    || window.__LK_CONFIG?.needsDisplayNamePrompt === true;

  if (isFirstTime) {
    window.progressiveDisclosure = new ProgressiveDisclosure({ isFirstTime: true });
  }
});
