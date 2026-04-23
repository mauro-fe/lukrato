import '../../../css/admin/dashboard/_first-run.css';

import { apiPost, getErrorMessage, logClientError } from '../shared/api.js';
import { resolveDisplayNameEndpoint } from '../api/endpoints/preferences.js';
import { getDashboardPrimaryActionCopy, openPrimaryAction } from '../shared/primary-actions.js';
import {
  applyRuntimeConfig,
  ensureRuntimeConfig,
  getRuntimeConfig,
  onRuntimeConfigUpdate,
} from '../global/runtime-config.js';

function storageKey(name) {
  return `lk_user_${getRuntimeConfig().userId ?? 'anon'}_${name}`;
}

const STORAGE = {
  DISPLAY_NAME_DISMISSED: () => storageKey('display_name_prompt_dismissed_v1'),
  FIRST_ACTION_TOAST: () => storageKey('dashboard_first_action_toast_v1'),
};

class DashboardFirstRunExperience {
  constructor() {
    this.state = {
      accountCount: 0,
      primaryAction: 'create_transaction',
      transactionCount: null,
      isDemo: false,
      awaitingFirstActionFeedback: false,
    };

    this.elements = {
      firstRunStack: document.getElementById('dashboardFirstRunStack'),
      displayNameCard: document.getElementById('dashboardDisplayNamePrompt'),
      previewNotice: document.getElementById('dashboardPreviewNotice'),
      previewLearnMore: document.getElementById('dashboardPreviewLearnMore'),
      displayNameForm: document.getElementById('dashboardDisplayNameForm'),
      displayNameInput: document.getElementById('dashboardDisplayNameInput'),
      displayNameSubmit: document.getElementById('dashboardDisplayNameSubmit'),
      displayNameDismiss: document.getElementById('dashboardDisplayNameDismiss'),
      displayNameFeedback: document.getElementById('dashboardDisplayNameFeedback'),
      quickStart: document.getElementById('dashboardQuickStart'),
      journeySteps: Array.from(document.querySelectorAll('[data-journey-step]')),
      primaryActionCta: document.getElementById('dashboardFirstTransactionCta'),
      openTourPrompt: document.getElementById('dashboardOpenTourPrompt'),
      emptyStateTitle: document.querySelector('#emptyState p'),
      emptyStateDescription: document.querySelector('#emptyState .dash-empty__subtext'),
      emptyStateCta: document.getElementById('dashboardEmptyStateCta'),
      fabButton: document.getElementById('fabButton'),
    };
  }

  init() {
    this.bindEvents();
    this.syncDisplayNamePrompt();
    this.syncStackVisibility();

    onRuntimeConfigUpdate(() => {
      this.syncDisplayNamePrompt();
    });

    void ensureRuntimeConfig({}, { silent: true }).then(() => {
      this.syncDisplayNamePrompt();
    });
  }

  bindEvents() {
    this.elements.primaryActionCta?.addEventListener('click', () => this.openPrimaryAction());
    this.elements.emptyStateCta?.addEventListener('click', () => this.openPrimaryAction());
    this.elements.openTourPrompt?.addEventListener('click', () => this.startTour());
    this.elements.previewLearnMore?.addEventListener('click', () => {
      void this.openPreviewHelp();
    });
    this.elements.displayNameDismiss?.addEventListener('click', () => this.dismissDisplayNamePrompt());
    this.elements.displayNameForm?.addEventListener('submit', (event) => this.handleDisplayNameSubmit(event));

    document.addEventListener('lukrato:dashboard-overview-rendered', (event) => {
      this.handleOverviewUpdate(event.detail || {});
    });

    document.addEventListener('lukrato:data-changed', (event) => {
      if (event.detail?.resource === 'transactions' && event.detail?.action === 'create') {
        this.state.awaitingFirstActionFeedback = true;
      }
    });
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

    this.state.accountCount = Math.max(0, Number(detail.accountCount ?? actionCopy.action.accountCount ?? 0) || 0);
    this.state.primaryAction = actionCopy.action.actionType;
    this.state.transactionCount = currentCount;
    this.state.isDemo = detail.isDemo === true;

    this.toggleQuickStart(currentCount === 0);
    this.syncPrimaryActionCopy(actionCopy);
    this.syncJourneySteps();
    this.syncDisplayNamePrompt();
    this.togglePrimaryActionFocus(currentCount === 0);

    if (!isFirstPayload && previousCount === 0 && currentCount > 0) {
      this.handleFirstActionCompleted();
    } else if (this.state.awaitingFirstActionFeedback && currentCount > 0) {
      this.handleFirstActionCompleted();
    }
  }

  toggleQuickStart(shouldShow) {
    if (!this.elements.quickStart) {
      return;
    }

    this.elements.quickStart.hidden = !shouldShow;

    if (shouldShow) {
      this.suppressHelpCenterOffer();
    }

    this.syncStackVisibility();
  }

  syncPrimaryActionCopy(copy) {
    if (!copy) {
      return;
    }

    if (this.elements.primaryActionCta) {
      this.elements.primaryActionCta.innerHTML = `<i data-lucide="plus"></i> ${this.getPrimaryCtaLabel(copy)}`;
    }

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
      this.elements.openTourPrompt.hidden = !this.hasTourAction(copy);
    }

    if (this.elements.previewLearnMore) {
      this.elements.previewLearnMore.hidden = !this.hasPreviewHelp();
    }

    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }

  getPrimaryCtaLabel(copy) {
    if (copy?.action?.actionType === 'create_account') {
      return 'Criar primeira conta';
    }

    if (copy?.action?.actionType === 'create_transaction') {
      return 'Registrar primeira transação';
    }

    return String(copy?.quickStartButton || 'Continuar').trim();
  }

  hasTourAction(copy = null) {
    const canTour = Boolean(
      window.LKHelpCenter?.startCurrentPageTutorial
      || window.LKHelpCenter?.showCurrentPageTips
    );

    if (!canTour) {
      return false;
    }

    if (copy && copy.shouldOfferTour === false && !this.state.isDemo) {
      return false;
    }

    return true;
  }

  hasPreviewHelp() {
    return Boolean(
      window.LKHelpCenter?.showCurrentPageTips
      || window.LKHelpCenter?.startCurrentPageTutorial
    );
  }

  syncJourneySteps() {
    if (!this.elements.journeySteps.length) {
      return;
    }

    const transactionCount = Math.max(0, Number(this.state.transactionCount ?? 0) || 0);
    const accountCount = Math.max(0, Number(this.state.accountCount ?? 0) || 0);
    const stepStates = {
      create_account: 'pending',
      create_transaction: 'pending',
      done: 'pending',
    };

    if (transactionCount > 0) {
      stepStates.create_account = 'completed';
      stepStates.create_transaction = 'completed';
      stepStates.done = 'completed';
    } else if (accountCount > 0) {
      stepStates.create_account = 'completed';
      stepStates.create_transaction = 'active';
    } else {
      stepStates.create_account = 'active';
    }

    this.elements.journeySteps.forEach((element) => {
      const stepKey = element.dataset.journeyStep;
      const nextState = stepStates[stepKey] || 'pending';

      element.dataset.state = nextState;

      if (nextState === 'active') {
        element.setAttribute('aria-current', 'step');
      } else {
        element.removeAttribute('aria-current');
      }
    });
  }

  syncDisplayNamePrompt() {
    if (!this.elements.displayNameCard) {
      return;
    }

    const shouldShowPreview = this.state.isDemo;
    const shouldShowName = Boolean(getRuntimeConfig().needsDisplayNamePrompt)
      && localStorage.getItem(STORAGE.DISPLAY_NAME_DISMISSED()) !== '1';

    if (this.elements.previewNotice) {
      this.elements.previewNotice.hidden = !shouldShowPreview;
    }

    if (this.elements.previewLearnMore) {
      this.elements.previewLearnMore.hidden = !shouldShowPreview || !this.hasPreviewHelp();
    }

    if (this.elements.displayNameForm) {
      this.elements.displayNameForm.hidden = !shouldShowName;
    }

    const shouldShowBar = shouldShowPreview || shouldShowName;

    this.elements.displayNameCard.hidden = !shouldShowBar;
    this.elements.displayNameCard.classList.toggle('is-preview-only', shouldShowPreview && !shouldShowName);
    this.elements.displayNameCard.classList.toggle('is-name-only', shouldShowName && !shouldShowPreview);
    this.elements.displayNameCard.classList.toggle('is-dual', shouldShowPreview && shouldShowName);

    this.syncStackVisibility();
  }

  syncStackVisibility() {
    if (!this.elements.firstRunStack) {
      return;
    }

    const hasQuickStart = this.elements.quickStart && !this.elements.quickStart.hidden;
    const hasDisplayNameBar = this.elements.displayNameCard && !this.elements.displayNameCard.hidden;

    this.elements.firstRunStack.hidden = !(hasQuickStart || hasDisplayNameBar);
  }

  dismissDisplayNamePrompt() {
    localStorage.setItem(STORAGE.DISPLAY_NAME_DISMISSED(), '1');
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
      const response = await apiPost(resolveDisplayNameEndpoint(), {
        display_name: value,
      });

      if (response?.success === false) {
        throw response;
      }

      const payload = response?.data || {};
      const displayName = String(payload.display_name || value).trim();
      const firstName = String(payload.first_name || displayName).trim();

      applyRuntimeConfig({
        username: displayName,
        needsDisplayNamePrompt: false,
      }, {
        source: 'display-name',
      });
      localStorage.removeItem(STORAGE.DISPLAY_NAME_DISMISSED());

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
      this.elements.displayNameSubmit.textContent = 'Salvar';
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

  async openPreviewHelp() {
    if (window.LKHelpCenter?.showCurrentPageTips) {
      await window.LKHelpCenter.showCurrentPageTips();
      return;
    }

    this.startTour();
  }

  startTour() {
    if (window.LKHelpCenter?.startCurrentPageTutorial) {
      this.suppressHelpCenterOffer();
      window.LKHelpCenter.startCurrentPageTutorial({ source: 'dashboard-first-run' });
      return;
    }

    if (window.LKHelpCenter?.showCurrentPageTips) {
      void window.LKHelpCenter.showCurrentPageTips();
      return;
    }

    window.LK?.toast?.info('Tutorial indisponível no momento.');
  }

  suppressHelpCenterOffer() {
    const helpCenter = window.LKHelpCenter;
    if (!helpCenter?.getPageTutorialTarget) {
      return;
    }

    const target = helpCenter.getPageTutorialTarget();
    if (!target) {
      return;
    }

    helpCenter.markOfferShownThisSession?.(target);
    helpCenter.hideOffer?.();
  }

  togglePrimaryActionFocus(shouldHighlight) {
    const targets = [
      this.elements.fabButton,
      this.elements.primaryActionCta,
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

    if (localStorage.getItem(STORAGE.FIRST_ACTION_TOAST()) !== '1') {
      if (window.LK?.toast) {
        window.LK.toast.success('Boa! Você já começou a controlar suas finanças.');
      }

      localStorage.setItem(STORAGE.FIRST_ACTION_TOAST(), '1');
    }

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
