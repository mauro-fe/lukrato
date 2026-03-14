/**
 * =====================================================================
 * FLOATING ACTION BUTTON (FAB) Component
 * Componente acionável flutuante para "Adicionar Transação"
 * Menu expandível com ícones para Receita, Despesa, Transferência
 * ===================================================================== */

class FloatingActionButton {
  constructor(config = {}) {
    this.config = {
      baseURL: config.baseURL || window.BASE_URL || '/',
      firstTime: config.firstTime || false,
      ...config
    };

    this.container = null;
    this.isExpanded = false;
    this.init();
  }

  init() {
    this.render();
    this.attachEventListeners();

    if (this.config.firstTime) {
      this.activateFirstTimeMode();
    }
  }

  render() {
    // Criar container
    this.container = document.createElement('div');
    this.container.className = 'fab-container';
    this.container.id = 'fabContainer';

    // HTML principal
    this.container.innerHTML = `
      <button class="fab-main" id="fabMain" title="Adicionar Transação" aria-label="Menu de ações">
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
        <button class="fab-item fab-transferencia" data-action="transferencia" title="Adicionar transferência">
          <div class="fab-label">Transferência</div>
          <i data-lucide="arrow-right-left" style="width:24px;height:24px;"></i>
        </button>
      </div>
    `;

    // Criar backdrop
    const backdrop = document.createElement('div');
    backdrop.className = 'fab-backdrop';
    backdrop.id = 'fabBackdrop';

    document.body.appendChild(this.container);
    document.body.appendChild(backdrop);

    // Atualizar ícones Lucide
    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }

  attachEventListeners() {
    const mainBtn = document.getElementById('fabMain');
    const backdrop = document.getElementById('fabBackdrop');
    const items = document.querySelectorAll('.fab-item');

    // Toggle menu ao clicar no botão principal
    mainBtn?.addEventListener('click', () => this.toggle());

    // Fechar menu ao clicar no backdrop
    backdrop?.addEventListener('click', () => this.close());

    // Actions dos items
    items.forEach(item => {
      item.addEventListener('click', (e) => {
        e.preventDefault();
        const action = item.getAttribute('data-action');
        this.handleAction(action);
        this.close();
      });
    });

    // Fechar com ESC
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.isExpanded) {
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

    // A11y
    const mainBtn = document.getElementById('fabMain');
    mainBtn?.setAttribute('aria-expanded', 'true');
  }

  close() {
    if (!this.container) return;
    this.container.classList.remove('expanded');
    this.isExpanded = false;

    // A11y
    const mainBtn = document.getElementById('fabMain');
    mainBtn?.setAttribute('aria-expanded', 'false');
  }

  handleAction(action) {
    const urls = {
      receita: `${this.config.baseURL}lancamentos?tipo=receita`,
      despesa: `${this.config.baseURL}lancamentos?tipo=despesa`,
      transferencia: `${this.config.baseURL}lancamentos?tipo=transferencia`
    };

    if (urls[action]) {
      // Trigger custom event for modal if available
      if (window.LK?.modals?.openLancamentoModal) {
        window.LK.modals.openLancamentoModal({ tipo: action });
      } else {
        // Fallback: navigate to page
        window.location.href = urls[action];
      }
    }
  }

  activateFirstTimeMode() {
    if (!this.container) return;
    this.container.classList.add('first-time');

    // Mostrar tooltip
    const tooltip = document.createElement('div');
    tooltip.style.cssText = `
      position: fixed;
      bottom: 100px;
      right: 24px;
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
      <div style="display: flex; gap: 8px; align-items: flex-start;">
        <span style="font-size: 16px;">👋</span>
        <div>
          <strong>Comece aqui!</strong><br>
          <small>Clique para adicionar sua primeira transação</small>
        </div>
      </div>
    `;

    document.body.appendChild(tooltip);

    // Remover tooltip após 8 segundos
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

    // Confetti
    if (typeof confetti === 'function') {
      confetti({
        particleCount: 40,
        spread: 60,
        origin: { x: 0.95, y: 0.9 }
      });
    }

    // Remove celebrating class
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

// ─── Export & Initialize ──────────────────────────────────────────────
window.FloatingActionButton = FloatingActionButton;

// Auto-init se elemento existir no DOM
document.addEventListener('DOMContentLoaded', () => {
  // Só iniciar se não existir ainda
  if (!document.getElementById('fabContainer')) {
    const firstTime = window.__lkFirstVisit || localStorage.getItem('lukrato_onboarding_completed') !== 'true';
    window.fab = new FloatingActionButton({ firstTime });
  }
});

// Listener para celebrate quando transação adicionada
document.addEventListener('lukrato:transaction-added', () => {
  if (window.fab) {
    window.fab.celebrate();
    window.fab.showSuccess();
  }
});
