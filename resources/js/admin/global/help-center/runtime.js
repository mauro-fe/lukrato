import {
    clearOfferSessionCache as clearOfferSessionCacheStore,
    getOfferSessionKey as getOfferSessionKeyStore,
    markOfferShownThisSession as markOfferShownThisSessionStore,
    normalizeHelpPreferences,
    persistHelpPreference,
    wasOfferShownThisSession as wasOfferShownThisSessionStore,
} from './persistence.js';
import {
    DEFAULT_VERSION,
    MOBILE_VIEWPORT_MAX,
    NAVIGATION_VERSION,
    NAVIGATION_TOUR_CONFIG,
    PAGE_LABELS,
    PAGE_ROUTE_MAP,
    TOUR_CONFIGS,
    TUTORIAL_TYPES,
    TUTORIAL_VARIANTS,
} from './tour-configs.js';
import {
    bindMenuRuntime,
    renderMenuStateRuntime,
    toggleMenuRuntime,
} from './menu-runtime.js';
import {
    createOfferRuntime,
    hideOfferRuntime,
    scheduleOfferRuntime,
    shouldOfferRuntime,
    showOfferRuntime,
} from './offer-runtime.js';
import {
    buildStepsRuntime,
    clearTourTargetRuntime,
    closeMobileSidebarIfNeededRuntime,
    closeTourRuntime,
    createTourElementsRuntime,
    getTourPopoverPositionRuntime,
    goToTourStepRuntime,
    highlightPrimaryActionRuntime,
    openMobileSidebarIfNeededRuntime,
    positionTourPopoverRuntime,
    renderTourPopoverRuntime,
    setTourTargetRuntime,
    showCurrentPageTipsRuntime,
    startCurrentPageTutorialRuntime,
    startNavigationTutorialRuntime,
    startTutorialRuntime,
    syncNavigationUIForStepRuntime,
    syncTourSpotlightRuntime,
    teardownTourRefreshGuardsRuntime,
} from './tour-runtime.js';

class HelpCenter {
    constructor() {
        this.currentPage = this.getCurrentPage();
        this.preferences = normalizeHelpPreferences(window.__LK_CONFIG?.helpCenter);
        this.tour = null;
        this.activeTourTarget = null;
        this.completedCurrentRun = false;
        this.offerVisible = false;
        this.offerElement = null;
        this.tourRefreshCleanup = null;

        this.elements = {
            helpToggle: document.getElementById('topNavHelpToggle'),
            helpMenu: document.getElementById('topNavHelpMenu'),
            helpCurrentPage: document.getElementById('topNavHelpCurrentPage'),
            helpStatus: document.getElementById('topNavHelpStatus'),
            helpTourBtn: document.getElementById('topNavHelpTourBtn'),
            helpNavigationTourBtn: document.getElementById('topNavHelpNavigationTourBtn'),
            helpTipsBtn: document.getElementById('topNavHelpTipsBtn'),
            helpAutoOfferBtn: document.getElementById('topNavHelpAutoOfferBtn'),
            helpResetBtn: document.getElementById('topNavHelpResetBtn'),
        };
    }

    init() {
        if (!document.body) {
            return;
        }

        this.createOffer();
        this.bindMenu();
        this.renderMenuState();
        this.scheduleOffer();
    }

    getCurrentPage() {
        const configPage = String(window.__LK_CONFIG?.currentMenu || '').trim().toLowerCase();
        if (configPage) {
            return configPage;
        }

        const path = window.location.pathname.toLowerCase();
        for (const [fragment, page] of Object.entries(PAGE_ROUTE_MAP)) {
            if (path.includes(fragment)) {
                return page;
            }
        }

        return 'dashboard';
    }

    getPageLabel(page = this.currentPage) {
        return PAGE_LABELS[page] || 'Esta tela';
    }

    isMobileViewport() {
        return window.matchMedia(`(max-width: ${MOBILE_VIEWPORT_MAX}px)`).matches;
    }

    getViewportVariant() {
        return this.isMobileViewport() ? TUTORIAL_VARIANTS.MOBILE : TUTORIAL_VARIANTS.DESKTOP;
    }

    getPageTourConfig(page = this.currentPage) {
        return TOUR_CONFIGS[page] || null;
    }

    getPageTutorialTarget(page = this.currentPage) {
        const config = this.getPageTourConfig(page);
        if (!config) {
            return null;
        }

        const variant = this.getViewportVariant();
        return {
            type: TUTORIAL_TYPES.PAGE,
            page,
            variant,
            key: `${page}.${variant}`,
            baseKey: page,
            label: config.label || this.getPageLabel(page),
            version: config.version || DEFAULT_VERSION,
            config,
        };
    }

    getNavigationTutorialTarget() {
        if (!NAVIGATION_TOUR_CONFIG) {
            return null;
        }

        const variant = this.getViewportVariant();
        return {
            type: TUTORIAL_TYPES.NAVIGATION,
            page: 'navigation',
            variant,
            key: `navigation.${variant}`,
            baseKey: 'navigation',
            label: NAVIGATION_TOUR_CONFIG.label || 'Navegacao',
            version: NAVIGATION_TOUR_CONFIG.version || NAVIGATION_VERSION,
            config: NAVIGATION_TOUR_CONFIG,
        };
    }

    getCurrentConfig() {
        return this.getPageTourConfig(this.currentPage);
    }

    getCurrentVersion() {
        return this.getPageTutorialTarget()?.version || DEFAULT_VERSION;
    }

    hasTutorial(page = this.currentPage) {
        return Boolean(this.getPageTourConfig(page));
    }

    hasNavigationTutorial() {
        return Boolean(NAVIGATION_TOUR_CONFIG);
    }

    hasTips(page = this.currentPage) {
        return Boolean(window.FirstVisitTooltips?.hasTooltipsForPage?.(page));
    }

    getOfferSessionKey(target = this.getPageTutorialTarget()) {
        return getOfferSessionKeyStore(target, {
            currentPage: this.currentPage,
            defaultVersion: DEFAULT_VERSION,
        });
    }

    wasOfferShownThisSession(target = this.getPageTutorialTarget()) {
        return wasOfferShownThisSessionStore(target, {
            currentPage: this.currentPage,
            defaultVersion: DEFAULT_VERSION,
        });
    }

    markOfferShownThisSession(target = this.getPageTutorialTarget()) {
        markOfferShownThisSessionStore(target, {
            currentPage: this.currentPage,
            defaultVersion: DEFAULT_VERSION,
        });
    }

    clearOfferSessionCache() {
        clearOfferSessionCacheStore();
    }

    isCompleted(target = this.getPageTutorialTarget()) {
        if (!target) {
            return false;
        }

        const completed = this.preferences.tour_completed || {};
        return completed[target.key] === target.version
            || completed[target.baseKey] === target.version;
    }

    isDismissed(target = this.getPageTutorialTarget()) {
        if (!target) {
            return false;
        }

        const dismissed = this.preferences.offer_dismissed || {};
        return dismissed[target.key] === target.version
            || dismissed[target.baseKey] === target.version;
    }

    shouldOffer() {
        return shouldOfferRuntime(this);
    }

    createOffer() {
        createOfferRuntime(this);
    }

    bindMenu() {
        bindMenuRuntime(this);
    }

    renderMenuState() {
        renderMenuStateRuntime(this);
    }

    toggleMenu(shouldOpen) {
        toggleMenuRuntime(this, shouldOpen);
    }

    scheduleOffer(force = false) {
        scheduleOfferRuntime(this, force);
    }

    showOffer(target = this.getPageTutorialTarget()) {
        showOfferRuntime(this, target);
    }

    hideOffer() {
        hideOfferRuntime(this);
    }

    buildSteps(target = this.getPageTutorialTarget()) {
        return buildStepsRuntime(this, target);
    }

    teardownTourRefreshGuards() {
        teardownTourRefreshGuardsRuntime(this);
    }

    clearTourTarget() {
        clearTourTargetRuntime(this);
    }

    setTourTarget(element) {
        setTourTargetRuntime(this, element);
    }

    createTourElements() {
        return createTourElementsRuntime();
    }

    syncTourSpotlight(state) {
        syncTourSpotlightRuntime(this, state);
    }

    getTourPopoverPosition(targetRect, popoverRect, options = {}) {
        return getTourPopoverPositionRuntime(targetRect, popoverRect, options);
    }

    positionTourPopover(state) {
        positionTourPopoverRuntime(this, state);
    }

    renderTourPopover(state) {
        renderTourPopoverRuntime(this, state);
    }

    openMobileSidebarIfNeeded() {
        openMobileSidebarIfNeededRuntime(this);
    }

    closeMobileSidebarIfNeeded() {
        closeMobileSidebarIfNeededRuntime(this);
    }

    syncNavigationUIForStep(state, step) {
        syncNavigationUIForStepRuntime(this, state, step);
    }

    goToTourStep(index) {
        goToTourStepRuntime(this, index);
    }

    async closeTour(state = this.tour, options = {}) {
        return closeTourRuntime(this, state, options);
    }

    async startTutorial(target) {
        return startTutorialRuntime(this, target);
    }

    async startCurrentPageTutorial(_options = {}) {
        return startCurrentPageTutorialRuntime(this, _options);
    }

    async startNavigationTutorial(_options = {}) {
        return startNavigationTutorialRuntime(this, _options);
    }

    async showCurrentPageTips() {
        return showCurrentPageTipsRuntime(this);
    }

    highlightPrimaryAction(scrollIntoView = false) {
        highlightPrimaryActionRuntime(this, scrollIntoView);
    }

    async markCompleted(target = this.getPageTutorialTarget()) {
        if (!target) {
            return;
        }

        this.preferences.tour_completed[target.key] = target.version;
        delete this.preferences.offer_dismissed[target.key];
        this.renderMenuState();

        await this.persistPreference('complete_tour', {
            page: target.key,
            version: target.version,
        }, { silent: true });
    }

    async markDismissed(target = this.getPageTutorialTarget()) {
        if (!target) {
            return;
        }

        this.preferences.offer_dismissed[target.key] = target.version;
        this.renderMenuState();

        await this.persistPreference('dismiss_offer', {
            page: target.key,
            version: target.version,
        }, { silent: true });
    }

    async markTipsSeen() {
        const pageTarget = this.getPageTutorialTarget();
        if (!pageTarget) {
            return;
        }

        this.preferences.tips_seen[pageTarget.baseKey] = pageTarget.version;
        await this.persistPreference('view_tips', {
            page: pageTarget.baseKey,
            version: pageTarget.version,
        }, { silent: true });
    }

    async persistPreference(action, extra = {}, options = {}) {
        const result = await persistHelpPreference(action, extra, options);
        if (!result?.ok) {
            return false;
        }

        if (result.preferences) {
            this.preferences = result.preferences;
            window.__LK_CONFIG.helpCenter = this.preferences;
        }

        return true;
    }

    isManagingAutoOffers() {
        return true;
    }
}

export function bootHelpCenter() {
    if (window.LKHelpCenter) {
        return;
    }

    window.LKHelpCenter = new HelpCenter();
    window.LKHelpCenter.init();
}

export { HelpCenter };
