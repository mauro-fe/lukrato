import { OFFER_DELAY } from './tour-configs.js';

export function shouldOfferRuntime(helpCenter) {
    const target = helpCenter.getPageTutorialTarget();
    if (!target) {
        return false;
    }

    if (!helpCenter.preferences.settings.auto_offer) {
        return false;
    }

    if (helpCenter.isCompleted(target) || helpCenter.isDismissed(target)) {
        return false;
    }

    if (helpCenter.wasOfferShownThisSession(target)) {
        return false;
    }

    const availableSteps = helpCenter.buildSteps(target);
    return availableSteps.length > 1;
}

export function createOfferRuntime(helpCenter) {
    const offer = document.createElement('div');
    offer.className = 'lk-help-offer';
    offer.id = 'lkHelpOffer';
    offer.innerHTML = `
        <div class="lk-help-offer__card surface-card surface-card--clip">
            <div class="lk-help-offer__icon">
                <i data-lucide="sparkles"></i>
            </div>
            <div class="lk-help-offer__content">
                <span class="lk-help-offer__eyebrow">Tour opcional</span>
                <strong>Quer um tour rápido desta tela?</strong>
                <p>Em menos de 30 segundos eu te mostro onde agir primeiro, sem travar sua navegação.</p>
            </div>
            <div class="lk-help-offer__actions">
                <button type="button" class="lk-help-btn lk-help-btn--primary" data-help-offer="start">Ver agora</button>
                <button type="button" class="lk-help-btn lk-help-btn--ghost" data-help-offer="tips">Ver dicas</button>
                <button type="button" class="lk-help-btn lk-help-btn--subtle" data-help-offer="dismiss">Agora nao</button>
            </div>
        </div>
    `;

    document.body.appendChild(offer);
    helpCenter.offerElement = offer;

    offer.querySelector('[data-help-offer="start"]')?.addEventListener('click', () => {
        helpCenter.hideOffer();
        helpCenter.startCurrentPageTutorial({ source: 'offer' });
    });

    offer.querySelector('[data-help-offer="tips"]')?.addEventListener('click', async () => {
        await helpCenter.markDismissed(helpCenter.getPageTutorialTarget());
        helpCenter.hideOffer();
        helpCenter.showCurrentPageTips();
    });

    offer.querySelector('[data-help-offer="dismiss"]')?.addEventListener('click', async () => {
        await helpCenter.markDismissed(helpCenter.getPageTutorialTarget());
        helpCenter.hideOffer();
        helpCenter.renderMenuState();
        helpCenter.highlightPrimaryAction();
    });

    window.LK?.refreshIcons?.(offer);
}

export function scheduleOfferRuntime(helpCenter, force = false) {
    const target = helpCenter.getPageTutorialTarget();
    if (!target) {
        return;
    }

    if (helpCenter.buildSteps(target).length <= 1) {
        return;
    }

    if (!force && !helpCenter.shouldOffer()) {
        return;
    }

    window.setTimeout(() => {
        if (helpCenter.buildSteps(target).length <= 1) {
            return;
        }

        if (!force && !helpCenter.shouldOffer()) {
            return;
        }

        helpCenter.showOffer(target);
    }, OFFER_DELAY);
}

export function showOfferRuntime(helpCenter, target = helpCenter.getPageTutorialTarget()) {
    if (!helpCenter.offerElement || helpCenter.offerVisible) {
        return;
    }

    helpCenter.markOfferShownThisSession(target);
    helpCenter.offerVisible = true;
    helpCenter.offerElement.classList.add('is-visible');
}

export function hideOfferRuntime(helpCenter) {
    if (!helpCenter.offerElement) {
        return;
    }

    helpCenter.offerVisible = false;
    helpCenter.offerElement.classList.remove('is-visible');
}
