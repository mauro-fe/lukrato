export function bindMenuRuntime(helpCenter) {
    helpCenter.elements.helpToggle?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        const isOpen = helpCenter.elements.helpMenu?.hasAttribute('hidden') === false;
        helpCenter.toggleMenu(!isOpen);
    });

    helpCenter.elements.helpTourBtn?.addEventListener('click', () => {
        helpCenter.toggleMenu(false);
        helpCenter.startCurrentPageTutorial({ source: 'menu' });
    });

    helpCenter.elements.helpNavigationTourBtn?.addEventListener('click', () => {
        helpCenter.toggleMenu(false);
        helpCenter.startNavigationTutorial({ source: 'menu' });
    });

    helpCenter.elements.helpTipsBtn?.addEventListener('click', () => {
        helpCenter.toggleMenu(false);
        helpCenter.showCurrentPageTips();
    });

    helpCenter.elements.helpAutoOfferBtn?.addEventListener('click', async () => {
        const nextValue = !helpCenter.preferences.settings.auto_offer;

        helpCenter.preferences.settings.auto_offer = nextValue;
        helpCenter.renderMenuState();

        const success = await helpCenter.persistPreference('set_auto_offer', {
            value: nextValue,
        });

        if (!success) {
            helpCenter.preferences.settings.auto_offer = !nextValue;
            helpCenter.renderMenuState();
            return;
        }

        if (window.LK?.toast) {
            window.LK.toast.success(nextValue
                ? 'Convites de tutorial reativados.'
                : 'Convites automaticos pausados.');
        }
    });

    helpCenter.elements.helpResetBtn?.addEventListener('click', async () => {
        const confirmed = await (window.LK?.confirm
            ? window.LK.confirm({
                title: 'Recomecar tutoriais?',
                text: 'Isso libera novamente tours e dicas das telas principais.',
                confirmText: 'Recomecar',
                cancelText: 'Cancelar',
            })
            : Promise.resolve(window.confirm('Recomecar tutoriais desta conta?')));

        if (!confirmed) {
            return;
        }

        const previousPreferences = JSON.parse(JSON.stringify(helpCenter.preferences));

        helpCenter.preferences.tour_completed = {};
        helpCenter.preferences.offer_dismissed = {};
        helpCenter.preferences.tips_seen = {};
        helpCenter.renderMenuState();

        const success = await helpCenter.persistPreference('reset_all');
        if (!success) {
            helpCenter.preferences = previousPreferences;
            helpCenter.renderMenuState();
            return;
        }

        window.FirstVisitTooltips?.resetVisitedPages?.();
        helpCenter.clearOfferSessionCache();

        if (window.LK?.toast) {
            window.LK.toast.success('Tutoriais liberados novamente.');
        }

        helpCenter.toggleMenu(false);
        helpCenter.scheduleOffer(true);
    });

    document.addEventListener('click', (event) => {
        if (!helpCenter.elements.helpMenu || !helpCenter.elements.helpToggle) {
            return;
        }

        const target = event.target;
        if (!(target instanceof Node)) {
            return;
        }

        if (helpCenter.elements.helpMenu.contains(target) || helpCenter.elements.helpToggle.contains(target)) {
            return;
        }

        helpCenter.toggleMenu(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            helpCenter.toggleMenu(false);
            helpCenter.hideOffer();
        }
    });
}

export function renderMenuStateRuntime(helpCenter) {
    const pageTarget = helpCenter.getPageTutorialTarget();
    const navigationTarget = helpCenter.getNavigationTutorialTarget();
    const label = helpCenter.getPageLabel();
    const tutorialAvailable = Boolean(pageTarget);
    const navigationTutorialAvailable = Boolean(navigationTarget);
    const tipsAvailable = helpCenter.hasTips();

    if (helpCenter.elements.helpCurrentPage) {
        helpCenter.elements.helpCurrentPage.textContent = tutorialAvailable
            ? `Tutorial de ${label}`
            : `Ajuda de ${label}`;
    }

    if (helpCenter.elements.helpStatus) {
        let status = 'Disponivel';

        if (!tutorialAvailable && !tipsAvailable) {
            status = 'Sem guia';
        } else if (helpCenter.isCompleted(pageTarget)) {
            status = 'Concluido';
        } else if (!helpCenter.preferences.settings.auto_offer) {
            status = 'Manual';
        }

        helpCenter.elements.helpStatus.textContent = status;
    }

    if (helpCenter.elements.helpTourBtn) {
        helpCenter.elements.helpTourBtn.disabled = !tutorialAvailable;
        helpCenter.elements.helpTourBtn.classList.toggle('is-disabled', !tutorialAvailable);
    }

    if (helpCenter.elements.helpNavigationTourBtn) {
        helpCenter.elements.helpNavigationTourBtn.disabled = !navigationTutorialAvailable;
        helpCenter.elements.helpNavigationTourBtn.classList.toggle('is-disabled', !navigationTutorialAvailable);
    }

    if (helpCenter.elements.helpTipsBtn) {
        helpCenter.elements.helpTipsBtn.disabled = !tipsAvailable;
        helpCenter.elements.helpTipsBtn.classList.toggle('is-disabled', !tipsAvailable);
    }

    if (helpCenter.elements.helpAutoOfferBtn) {
        const icon = helpCenter.preferences.settings.auto_offer ? 'bell' : 'bell-off';
        const text = helpCenter.preferences.settings.auto_offer
            ? 'Desativar convite automatico'
            : 'Ativar convite automatico';

        helpCenter.elements.helpAutoOfferBtn.innerHTML = `
            <i data-lucide="${icon}"></i>
            <span>${text}</span>
        `;
    }

    window.LK?.refreshIcons?.(helpCenter.elements.helpMenu);
}

export function toggleMenuRuntime(helpCenter, shouldOpen) {
    if (!helpCenter.elements.helpMenu || !helpCenter.elements.helpToggle) {
        return;
    }

    if (shouldOpen) {
        helpCenter.elements.helpMenu.removeAttribute('hidden');
        helpCenter.elements.helpToggle.setAttribute('aria-expanded', 'true');
    } else {
        helpCenter.elements.helpMenu.setAttribute('hidden', 'hidden');
        helpCenter.elements.helpToggle.setAttribute('aria-expanded', 'false');
    }
}
