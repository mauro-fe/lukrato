/**
 * Floating Action Button for quick transaction creation.
 */

import { getBaseUrl } from '../shared/api.js';
import { ensureRuntimeConfig, getRuntimeConfig } from '../global/runtime-config.js';

class FloatingActionButton {
  constructor(config = {}) {
    this.config = {
      baseURL: config.baseURL || getRuntimeConfig().baseUrl || getBaseUrl(),
      firstTime: config.firstTime || false,
      ...config,
    };

    this.container = null;
    this.isExpanded = false;
    this.init();
  }

  init() {
    this.hideOldFab();
    this.render();
    this.attachEventListeners();

    if (this.config.firstTime) {
      this.activateFirstTimeMode();
    }
  }

  hideOldFab() {
    const oldFab = document.getElementById('fabButton');
    if (!oldFab) return;

    const oldContainer = oldFab.closest('.fab-container');
    if (oldContainer) {
      oldContainer.style.display = 'none';
    }
  }

  render() {
    this.container = document.createElement('div');
    this.container.className = 'fab-container';
    this.container.id = 'fabContainer';

    this.container.innerHTML = `
      <button class="fab-main" id="fabMain" title="Adicionar transacao" aria-label="Menu de acoes">
        <div class="fab-icon">
          <i data-lucide="plus" style="width:32px;height:32px;"></i>
        </div>
      </button>

      <div class="fab-menu" id="fabMenu">
        <button class="fab-item fab-receita" data-action="receita" title="Adicionar receita">
          <div class="fab-label">Receita</div>
          <i data-lucide="arrow-down" style="width:24px;height:24px;"></i>
        </button>
        <button class="fab-item fab-despesa" data-action="despesa" title="Adicionar despesa">
          <div class="fab-label">Despesa</div>
          <i data-lucide="arrow-up" style="width:24px;height:24px;"></i>
        </button>
        <button class="fab-item fab-transferencia" data-action="transferencia" title="Adicionar transferencia">
          <div class="fab-label">Transferencia</div>
          <i data-lucide="arrow-right-left" style="width:24px;height:24px;"></i>
        </button>
      </div>
    `;

    const backdrop = document.createElement('div');
    backdrop.className = 'fab-backdrop';
    backdrop.id = 'fabBackdrop';

    document.body.appendChild(this.container);
    document.body.appendChild(backdrop);

    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }

  attachEventListeners() {
    const mainBtn = document.getElementById('fabMain');
    const backdrop = document.getElementById('fabBackdrop');
    const items = document.querySelectorAll('.fab-item');

    mainBtn?.addEventListener('click', () => this.toggle());
    backdrop?.addEventListener('click', () => this.close());

    items.forEach((item) => {
      item.addEventListener('click', (event) => {
        event.preventDefault();
        const action = item.getAttribute('data-action');
        this.handleAction(action);
        this.close();
      });
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && this.isExpanded) {
        this.close();
      }
    });
  }

  toggle() {
    if (this.isExpanded) {
      this.close();
    } else {
      this.open();
    }
  }

  open() {
    if (!this.container) return;
    this.container.classList.add('expanded');
    this.isExpanded = true;
    document.getElementById('fabMain')?.setAttribute('aria-expanded', 'true');
  }

  close() {
    if (!this.container) return;
    this.container.classList.remove('expanded');
    this.isExpanded = false;
    document.getElementById('fabMain')?.setAttribute('aria-expanded', 'false');
  }

  handleAction(action) {
    const urls = {
      receita: `${this.config.baseURL}lancamentos/novo?tipo=receita`,
      despesa: `${this.config.baseURL}lancamentos/novo?tipo=despesa`,
      transferencia: `${this.config.baseURL}lancamentos/novo?tipo=transferencia`,
    };

    if (!urls[action]) return;

    if (window.LK?.modals?.openLancamentoModal) {
      window.LK.modals.openLancamentoModal({ tipo: action });
      return;
    }

    window.location.href = urls[action];
  }

  activateFirstTimeMode() {
    if (!this.container) return;
    this.container.classList.add('first-time');

    const tooltip = document.createElement('div');
    tooltip.style.cssText = `
      position: absolute;
      bottom: 76px;
      right: 0;
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      padding: 12px 16px;
      border-radius: 12px;
      font-size: 13px;
      color: var(--color-text);
      z-index: 1000;
      backdrop-filter: blur(10px);
      max-width: 200px;
      white-space: normal;
      animation: fadeInUp 0.4s ease;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    `;

    tooltip.innerHTML = `
      <div style="display:flex;gap:8px;align-items:flex-start;">
        <span style="display:flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:999px;background:rgba(230,126,34,0.16);color:var(--color-primary);flex-shrink:0;">
          <i data-lucide="sparkles" style="width:14px;height:14px;"></i>
        </span>
        <div>
          <strong>Comece aqui</strong><br>
          <small>Clique para adicionar sua primeira transacao</small>
        </div>
      </div>
    `;

    this.container.appendChild(tooltip);

    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }

    setTimeout(() => {
      tooltip.style.opacity = '0';
      tooltip.style.transform = 'translateY(8px)';
      tooltip.style.transition = 'all 0.3s ease';
      setTimeout(() => tooltip.remove(), 300);
    }, 8000);
  }

  celebrate() {
    if (!this.container) return;

    this.container.classList.add('celebrating');

    if (typeof confetti === 'function') {
      confetti({
        particleCount: 40,
        spread: 60,
        origin: { x: 0.95, y: 0.9 },
      });
    }

    setTimeout(() => {
      this.container?.classList.remove('celebrating');
    }, 600);
  }

  showSuccess() {
    if (!this.container) return;

    const mainBtn = document.getElementById('fabMain');
    mainBtn?.classList.add('fab-success');

    setTimeout(() => {
      mainBtn?.classList.remove('fab-success');
    }, 2000);
  }
}

window.FloatingActionButton = FloatingActionButton;

document.addEventListener('DOMContentLoaded', () => {
  void ensureRuntimeConfig({}, { silent: true }).finally(() => {
    if (document.getElementById('fabContainer')) {
      return;
    }

    const firstTime = Boolean(window.__lkFirstVisit)
      || getRuntimeConfig().needsDisplayNamePrompt === true;

    window.fab = new FloatingActionButton({ firstTime });
  });
});

document.addEventListener('lukrato:transaction-added', () => {
  if (!window.fab) return;
  window.fab.celebrate();
  window.fab.showSuccess();
});
