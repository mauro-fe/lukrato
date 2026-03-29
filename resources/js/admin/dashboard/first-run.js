import '../../../css/admin/dashboard/_first-run.css';

import { apiPost, getErrorMessage, logClientError } from '../shared/api.js';
import { getDashboardPrimaryActionCopy, openPrimaryAction } from '../shared/primary-actions.js';
import { CONFIG } from './state.js';

const STORAGE_PREFIX = `lk_user_${window.__LK_CONFIG?.userId ?? 'anon'}_`;

function storageKey(name) {
  return STORAGE_PREFIX + name;
}

const STORAGE = {
  DISPLAY_NAME_DISMISSED: storageKey('display_name_prompt_dismissed_v1'),
  TOUR_PROMPT_DISMISSED: storageKey('dashboard_tour_prompt_dismissed_v1'),
  FIRST_ACTION_TOAST: storageKey('dashboard_first_action_toast_v1'),
};

class DashboardFirstRunExperience {
  constructor() {
    this.state = {
      accountCount: 0,
      primaryAction: 'create_transaction',
      transactionCount: null,
      promptScheduled: false,
      tourPromptVisible: false,
      awaitingFirstActionFeedback: false,
    };

    this.elements = {
      displayNameCard: document.getElementById('dashboardDisplayNamePrompt'),
      displayNameForm: document.getElementById('dashboardDisplayNameForm'),
      displayNameInput: document.getElementById('dashboardDisplayNameInput'),
      displayNameSubmit: document.getElementById('dashboardDisplayNameSubmit'),
      displayNameDismiss: document.getElementById('dashboardDisplayNameDismiss'),
      displayNameFeedback: document.getElementById('dashboardDisplayNameFeedback'),
      quickStart: document.getElementById('dashboardQuickStart'),
      quickStartTitle: document.querySelector('#dashboardQuickStart .dash-quick-start__header h2'),
      quickStartDescription: document.querySelector('#dashboardQuickStart .dash-quick-start__header p'),
      quickStartNotes: Array.from(document.querySelectorAll('#dashboardQuickStart .dash-quick-start__notes span')),
      firstTransactionCta: document.getElementById('dashboardFirstTransactionCta'),
      openTourPrompt: document.getElementById('dashboardOpenTourPrompt'),
      emptyStateTitle: document.querySelector('#emptyState p'),
      emptyStateDescription: document.querySelector('#emptyState .dash-empty__subtext'),
      emptyStateCta: document.getElementById('dashboardEmptyStateCta'),
      fabButton: document.getElementById('fabButton'),
    };
  }

  init() {
    if (!window.LKHelpCenter?.isManagingAutoOffers?.()) {
      this.createTourPrompt();
    }

    this.bindEvents();
    this.syncDisplayNamePrompt();
  }

  bindEvents() {
    this.elements.firstTransactionCta?.addEventListener('click', () => this.openPrimaryAction());
    this.elements.emptyStateCta?.addEventListener('click', () => this.openPrimaryAction());
    this.elements.openTourPrompt?.addEventListener('click', () => this.startTour());
    this.elements.displayNameDismiss?.addEventListener('click', () => this.dismissDisplayNamePrompt());
    this.elements.displayNameForm?.addEventListener('submit', (event) => this.handleDisplayNameSubmit(event));

    this.tourPrompt?.querySelector('[data-tour-action="start"]')?.addEventListener('click', () => this.startTour());
    this.tourPrompt?.querySelector('[data-tour-action="dismiss"]')?.addEventListener('click', () => {
      localStorage.setItem(STORAGE.TOUR_PROMPT_DISMISSED, '1');
      this.hideTourPrompt();
      this.focusPrimaryAction();
    });

    document.addEventListener('lukrato:dashboard-overview-rendered', (event) => {
      this.handleOverviewUpdate(event.detail || {});
    });

    document.addEventListener('lukrato:data-changed', (event) => {
      if (event.detail?.resource === 'transactions' && event.detail?.action === 'create') {
        this.state.awaitingFirstActionFeedback = true;
      }
    });
  }

  createTourPrompt() {
    const prompt = document.createElement('div');
    prompt.className = 'dash-tour-offer';
    prompt.id = 'dashboardTourOffer';
    prompt.innerHTML = `
      <div class="dash-tour-offer__inner surface-card">
        <div class="dash-tour-offer__icon">
          <i data-lucide="sparkles"></i>
        </div>
        <div class="dash-tour-offer__copy">
          <span class="dash-tour-offer__eyebrow">Tour opcional</span>
          <strong>Quer um tour rápido de 30 segundos?</strong>
          <p>Eu te mostro só o essencial para começar sem travar sua navegação.</p>
        </div>
        <div class="dash-tour-offer__actions">
          <button type="button" class="dash-btn dash-btn--primary" data-tour-action="start">Sim</button>
          <button type="button" class="dash-btn dash-btn--ghost" data-tour-action="dismiss">Agora não</button>
        </div>
      </div>
    `;

    document.body.appendChild(prompt);
    this.tourPrompt = prompt;

    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }

  handleOverviewUpdate(detail) {
    const previousCount = Number(this.state.transactionCount ?? 0);
    const currentCount = Number(detail.transactionCount || 0);
    const isFirstPayload = this.state.transactionCount === null;
    const actionCopy = getDashboardPrimaryActionCopy(detail, {
      accountCount: Number(detail.accountCount ?? 0),
      actionType: detail.primaryAction,
      ctaLabel: detail.ctaLabel,
      ctaUrl: detail.ctaUrl,
    });

    this.state.accountCount = Number(actionCopy.action.accountCount || 0);
    this.state.primaryAction = actionCopy.action.actionType;
    this.state.transactionCount = currentCount;

    this.toggleQuickStart(currentCount === 0 && !detail.isDemo);
    this.togglePrimaryActionFocus(currentCount === 0);
    this.syncPrimaryActionCopy(actionCopy);
    this.syncDisplayNamePrompt();

    if (!this.state.promptScheduled && this.shouldOfferTour()) {
      this.state.promptScheduled = true;
      window.setTimeout(() => {
        if (this.shouldOfferTour()) {
          this.showTourPrompt();
        }
      }, 1600);
    }

    if (!isFirstPayload && previousCount === 0 && currentCount > 0) {
      this.handleFirstActionCompleted();
    } else if (this.state.awaitingFirstActionFeedback && currentCount > 0) {
      this.handleFirstActionCompleted();
    }
  }

  shouldOfferTour() {
    return !window.LKHelpCenter?.isManagingAutoOffers?.()
      && localStorage.getItem(STORAGE.TOUR_PROMPT_DISMISSED) !== '1'
      && window.__LK_CONFIG?.tourCompleted !== true
      && this.state.primaryAction === 'create_transaction'
      && Number(this.state.transactionCount ?? 0) === 0;
  }

  showTourPrompt() {
    if (!this.tourPrompt || this.state.tourPromptVisible) {
      return;
    }

    this.state.tourPromptVisible = true;
    this.tourPrompt.classList.add('is-visible');
  }

  hideTourPrompt() {
    if (!this.tourPrompt) {
      return;
    }

    this.state.tourPromptVisible = false;
    this.tourPrompt.classList.remove('is-visible');
  }

  toggleQuickStart(shouldShow) {
    if (!this.elements.quickStart) {
      return;
    }

    this.elements.quickStart.style.display = shouldShow ? '' : 'none';
  }

  syncPrimaryActionCopy(copy) {
    if (!copy) {
      return;
    }

    if (this.elements.quickStartTitle) {
      this.elements.quickStartTitle.textContent = copy.quickStartTitle;
    }

    if (this.elements.quickStartDescription) {
      this.elements.quickStartDescription.textContent = copy.quickStartDescription;
    }

    if (this.elements.firstTransactionCta) {
      this.elements.firstTransactionCta.innerHTML = `<i data-lucide="plus"></i> ${copy.quickStartButton}`;
    }

    this.elements.quickStartNotes.forEach((element, index) => {
      if (!element) {
        return;
      }

      const note = copy.quickStartNotes[index] || '';
      const iconMarkup = element.querySelector('i, svg')?.outerHTML || '';
      element.innerHTML = `${iconMarkup} ${note}`;
    });

    if (this.elements.emptyStateTitle) {
      this.elements.emptyStateTitle.textContent = copy.emptyStateTitle;
    }

    if (this.elements.emptyStateDescription) {
      this.elements.emptyStateDescription.textContent = copy.emptyStateDescription;
    }

    if (this.elements.emptyStateCta) {
      this.elements.emptyStateCta.innerHTML = `<i data-lucide="plus"></i> ${copy.emptyStateButton}`;
    }

    if (this.elements.openTourPrompt) {
      this.elements.openTourPrompt.style.display = copy.shouldOfferTour ? '' : 'none';
    }

    if (!copy.shouldOfferTour) {
      this.hideTourPrompt();
    }

    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }

  syncDisplayNamePrompt() {
    if (!this.elements.displayNameCard) {
      return;
    }

    const shouldShow = Boolean(window.__LK_CONFIG?.needsDisplayNamePrompt)
      && localStorage.getItem(STORAGE.DISPLAY_NAME_DISMISSED) !== '1';

    this.elements.displayNameCard.style.display = shouldShow ? '' : 'none';
  }

  dismissDisplayNamePrompt() {
    localStorage.setItem(STORAGE.DISPLAY_NAME_DISMISSED, '1');
    this.syncDisplayNamePrompt();
  }

  async handleDisplayNameSubmit(event) {
    event.preventDefault();

    if (!this.elements.displayNameInput || !this.elements.displayNameSubmit) {
      return;
    }

    const value = this.elements.displayNameInput.value.trim();
    if (value.length < 2) {
      this.showDisplayNameFeedback('Use pelo menos 2 caracteres.', true);
      return;
    }

    this.elements.displayNameSubmit.disabled = true;
    this.elements.displayNameSubmit.textContent = 'Salvando...';

    try {
      const response = await apiPost(`${CONFIG.BASE_URL}api/user/display-name`, {
        display_name: value,
      });

      if (response?.success === false) {
        throw response;
      }

      const payload = response?.data || {};
      const displayName = String(payload.display_name || value).trim();
      const firstName = String(payload.first_name || displayName).trim();

      window.__LK_CONFIG.username = displayName;
      window.__LK_CONFIG.needsDisplayNamePrompt = false;
      localStorage.removeItem(STORAGE.DISPLAY_NAME_DISMISSED);

      this.updateGlobalIdentity(displayName, firstName);
      this.showDisplayNameFeedback('Perfeito. Agora o Lukrato já fala com você do jeito certo.');
      window.setTimeout(() => this.syncDisplayNamePrompt(), 900);

      if (window.LK?.toast) {
        window.LK.toast.success('Nome de exibição salvo.');
      }
    } catch (error) {
      logClientError('Erro ao salvar nome de exibição', error, 'Falha ao salvar nome de exibição');
      this.showDisplayNameFeedback(getErrorMessage(error, 'Não foi possível salvar agora.'), true);
    } finally {
      this.elements.displayNameSubmit.disabled = false;
      this.elements.displayNameSubmit.textContent = 'Salvar nome';
    }
  }

  showDisplayNameFeedback(message, isError = false) {
    if (!this.elements.displayNameFeedback) {
      return;
    }

    this.elements.displayNameFeedback.hidden = false;
    this.elements.displayNameFeedback.textContent = message;
    this.elements.displayNameFeedback.classList.toggle('is-error', isError);
  }

  updateGlobalIdentity(displayName, firstName) {
    const firstNameSafe = firstName || displayName || 'U';
    const initial = firstNameSafe.charAt(0).toUpperCase();

    document.querySelectorAll('.greeting-name strong').forEach((element) => {
      element.textContent = firstNameSafe;
    });

    document.querySelectorAll('.avatar-initials-sm, .avatar-initials-xs').forEach((element) => {
      element.textContent = initial;
    });

    const supportToggle = document.getElementById('lkSupportToggle');
    if (supportToggle) {
      supportToggle.dataset.supportName = displayName;
    }

    const supportName = document.getElementById('sfName');
    if (supportName) {
      supportName.textContent = displayName;
    }

    if (this.elements.displayNameInput) {
      this.elements.displayNameInput.value = displayName;
    }
  }

  startTour() {
    if (!window.LKHelpCenter?.startCurrentPageTutorial) {
      window.LK?.toast?.info('Tutorial indisponível no momento.');
      return;
    }

    localStorage.setItem(STORAGE.TOUR_PROMPT_DISMISSED, '1');
    this.hideTourPrompt();
    window.LKHelpCenter.startCurrentPageTutorial({ source: 'dashboard-first-run' });
  }

  togglePrimaryActionFocus(shouldHighlight) {
    const targets = [
      this.elements.fabButton,
      this.elements.firstTransactionCta,
      document.getElementById('dashboardEmptyStateCta'),
      document.getElementById('dashboardChartEmptyCta'),
    ];

    targets.forEach((element) => {
      if (!element) {
        return;
      }

      element.classList.toggle('dash-primary-cta-highlight', shouldHighlight);
    });
  }

  focusPrimaryAction() {
    this.togglePrimaryActionFocus(true);

    if (this.state.transactionCount === 0) {
      this.elements.quickStart?.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
      });
    }
  }

  handleFirstActionCompleted() {
    this.state.awaitingFirstActionFeedback = false;

    if (localStorage.getItem(STORAGE.FIRST_ACTION_TOAST) !== '1') {
      if (window.LK?.toast) {
        window.LK.toast.success('Boa! Você já começou a controlar suas finanças.');
      }

      localStorage.setItem(STORAGE.FIRST_ACTION_TOAST, '1');
    }

    this.hideTourPrompt();
    this.togglePrimaryActionFocus(false);
  }

  openPrimaryAction() {
    openPrimaryAction({
      primary_action: this.state.primaryAction,
      real_account_count: this.state.accountCount,
    });
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const dashboard = document.querySelector('.modern-dashboard');
  if (!dashboard) {
    return;
  }

  if (!window.dashboardFirstRunExperience) {
    window.dashboardFirstRunExperience = new DashboardFirstRunExperience();
    window.dashboardFirstRunExperience.init();
  }
});
