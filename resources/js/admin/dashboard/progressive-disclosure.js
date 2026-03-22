import { apiGet } from '../shared/api.js';

const ONBOARDING_STORAGE_PREFIX = `lk_user_${window.__LK_CONFIG?.userId ?? 'anon'}_`;

function storageKey(name) {
  return ONBOARDING_STORAGE_PREFIX + name;
}

/**
 * =====================================================================
 * PROGRESSIVE DISCLOSURE for First-Time Users
 * Mostra apenas o essencial na primeira visita
 * Gradualmente revela mais seções conforme usuário interage
 * ===================================================================== */

class ProgressiveDisclosure {
  constructor(config = {}) {
    this.config = {
      isFirstTime: config.isFirstTime || window.__lkFirstVisit || false,
      minTransactionsToUnlock: config.minTransactionsToUnlock || 1,
      ...config
    };

    this.state = {
      transactionCount: 0,
      hiddenSections: []
    };

    this.HIDDEN_SECTIONS_KEY = 'lk_hidden_sections_shown';
    this.init();
  }

  init() {
    if (!this.config.isFirstTime) return;

    // Get transaction count from API
    this.checkTransactionCount();

    // Listen untuk transaction-added events
    document.addEventListener('lukrato:transaction-added', () => {
      this.onTransactionAdded();
    });

    // Listen untuk data changes
    document.addEventListener('lukrato:data-changed', (e) => {
      if (e.detail?.resource === 'transactions') {
        this.checkTransactionCount();
      }
    });
  }

  async checkTransactionCount() {
    try {
      const json = await apiGet(`${window.BASE_URL || '/'}api/dashboard/metrics`, {
        month: this.getCurrentMonth()
      });
      const data = json?.data ?? json;

      if (data && typeof data.count !== 'undefined') {
        this.state.transactionCount = parseInt(data.count) || 0;
        this.updateHiddenSections();
      }
    } catch (err) {
      console.error('Error checking transaction count:', err);
    }
  }

  updateHiddenSections() {
    // Show sections based on transaction count
    const sections = [
      { id: 'provisaoSection', threshold: 0, label: 'Previsão' },
      { id: 'chart-section', threshold: 2, label: 'Gráfico' },
      { id: 'table-section', threshold: 0, label: 'Transações' } // sempre mostrar
    ];

    sections.forEach(section => {
      const el = document.querySelector(`#${section.id}, .${section.id}`);
      if (!el) return;

      const shouldShow = this.state.transactionCount >= section.threshold;

      if (shouldShow && el.classList.contains('progressive-hidden')) {
        // Reveal com animation
        this.revealSection(el, section.label);
      } else if (!shouldShow && !el.classList.contains('progressive-hidden')) {
        // Hide section
        el.classList.add('progressive-hidden');
        el.style.opacity = '0.5';
        el.style.pointerEvents = 'none';
      }
    });
  }

  revealSection(el, label) {
    el.classList.remove('progressive-hidden');

    // Animation
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)';
    el.style.pointerEvents = 'auto';

    setTimeout(() => {
      el.style.opacity = '1';
      el.style.transform = 'translateY(0)';
    }, 100);

    // Toast notification
    if (window.LK?.toast) {
      window.LK.toast.success(`✨ Nova seção desbloqueada: ${label}`);
    }

    // Mark as shown
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

  /**
   * Método público para forçar reveal de todas as seções
   * (útil quando usuário completa onboarding)
   */
  revealAll() {
    const sections = document.querySelectorAll('.progressive-hidden');
    sections.forEach((el, i) => {
      setTimeout(() => {
        el.classList.remove('progressive-hidden');
        el.style.opacity = '1';
        el.style.pointerEvents = 'auto';
      }, i * 100);
    });

    localStorage.setItem(this.HIDDEN_SECTIONS_KEY, JSON.stringify([
      'Previsão',
      'Gráfico',
      'Transações'
    ]));
  }
}

// ─── Export & Initialize ──────────────────────────────────────────────
window.ProgressiveDisclosure = ProgressiveDisclosure;

// Auto-init na primeira visita
document.addEventListener('DOMContentLoaded', () => {
  const isFirstTime = window.__lkFirstVisit || localStorage.getItem(storageKey('lukrato_onboarding_completed')) !== 'true';
  if (isFirstTime) {
    window.progressiveDisclosure = new ProgressiveDisclosure({ isFirstTime: true });
  }
});
