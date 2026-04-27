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
      alertsSection: document.getElementById('sectionAlertas'),
      quickStart: document.getElementById('dashboardQuickStart'),
      quickStartEyebrow: document.getElementById('dashboardQuickStartEyebrow'),
      quickStartTitle: document.getElementById('dashboardQuickStartTitle'),
      quickStartSummary: document.getElementById('dashboardQuickStartSummary'),
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

    const shouldShowQuickStart = this.shouldShowQuickStart();

    this.toggleQuickStart(shouldShowQuickStart);
    this.syncPrimaryActionCopy(actionCopy);
    this.syncDisplayNamePrompt();
    this.togglePrimaryActionFocus(shouldShowQuickStart);

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

  shouldShowQuickStart() {
    return this.state.isDemo === true && Number(this.state.transactionCount ?? 0) === 0;
  }

  syncPrimaryActionCopy(copy) {
    if (!copy) {
      return;
    }

    const onboardingContent = this.buildQuickStartContent(copy);

    if (this.elements.quickStartEyebrow) {
      this.elements.quickStartEyebrow.textContent = onboardingContent.eyebrow;
    }

    if (this.elements.quickStartTitle) {
      this.elements.quickStartTitle.textContent = onboardingContent.title;
    }

    if (this.elements.quickStartSummary) {
      this.elements.quickStartSummary.textContent = onboardingContent.summary;
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

  buildQuickStartContent(copy) {
    if (copy?.action?.actionType === 'create_transaction') {
      return {
        eyebrow: 'Próxima ação',
        title: 'Registre a primeira transação',
        summary: 'Com a conta pronta, registre a primeira movimentação para transformar o painel inicial em acompanhamento real do período.',
      };
    }

    return {
      eyebrow: 'Configuração inicial',
      title: 'Cadastre sua primeira conta',
      summary: 'Comece pela base do seu fluxo financeiro. Assim que a conta for criada, o painel passa a refletir a sua operação.',
    };
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
    return true;
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
      this.elements.previewLearnMore.hidden = !shouldShowPreview;
    }

    if (this.elements.displayNameForm) {
      this.elements.displayNameForm.hidden = !shouldShowName;
    }

    this.elements.displayNameCard.hidden = !shouldShowName;
    this.elements.displayNameCard.classList.toggle('is-name-only', shouldShowName);

    this.syncStackVisibility();
  }

  syncStackVisibility() {
    if (!this.elements.firstRunStack) {
      return;
    }

    const hasQuickStart = this.elements.quickStart && !this.elements.quickStart.hidden;
    const hasDisplayNameBar = this.elements.displayNameCard && !this.elements.displayNameCard.hidden;

    this.elements.firstRunStack.hidden = !(hasQuickStart || hasDisplayNameBar);

    document.body.classList.toggle('dashboard-demo-preview-active', this.state.isDemo === true);
    document.body.classList.toggle('dashboard-first-use-active', Boolean(hasQuickStart));
    document.body.classList.toggle('dashboard-onboarding-active', Boolean(hasQuickStart));

    if (this.elements.alertsSection) {
      this.elements.alertsSection.classList.toggle('dashboard-alerts--suppressed', Boolean(hasQuickStart));
    }
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
    if (window.Swal?.fire) {
      const actionLabel = this.elements.primaryActionCta?.textContent?.trim() || 'Continuar';
      const canOfferTour = this.hasTourAction();
      const result = await window.Swal.fire({
        title: 'O que é esta prévia?',
        html: `
          <div class="dash-preview-modal__content">
            <p class="dash-preview-modal__intro">
              Estes números servem só para mostrar como o Lukrato organiza suas finanças antes do primeiro uso real.
            </p>
            <ul class="dash-preview-modal__list">
              <li>Os valores exibidos aqui são apenas de exemplo.</li>
              <li>Nada dessa prévia entra no seu histórico real.</li>
              <li>Assim que você criar sua primeira conta e começar a usar, a demonstração some.</li>
            </ul>
            <p class="dash-preview-modal__footnote">
              O próximo passo é só um: começar seu painel com dados seus.
            </p>
          </div>
        `,
        showConfirmButton: true,
        confirmButtonText: actionLabel,
        showDenyButton: canOfferTour,
        denyButtonText: 'Ver tour',
        showCancelButton: true,
        cancelButtonText: 'Fechar',
        reverseButtons: false,
        focusConfirm: true,
        customClass: {
          popup: 'lk-swal-popup dash-preview-modal',
          confirmButton: 'dash-preview-modal__confirm',
          denyButton: 'dash-preview-modal__deny',
          cancelButton: 'dash-preview-modal__cancel',
        },
      });

      if (result.isConfirmed) {
        this.openPrimaryAction();
        return;
      }

      if (result.isDenied) {
        this.startTour();
      }

      return;
    }

    window.alert('Estes números são apenas de exemplo. Assim que você começar a usar, a prévia some.');
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

    if (this.shouldShowQuickStart()) {
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
