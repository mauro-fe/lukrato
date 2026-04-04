const PAGE_SCOPE = 'page';
const APP_SCOPE = 'app';

const activeElements = {
    [PAGE_SCOPE]: new Set(),
    [APP_SCOPE]: new Set(),
};

const overlayObservers = new WeakMap();
const CHROME_BLOCK_SELECTORS = ['.sidebar', '.top-navbar', '#edgeMenuBtn', '.fab-container', '.lk-support-button'];
const DIALOG_SURFACE_SELECTOR = [
    '[role="dialog"]',
    '[aria-modal="true"]',
    '.modal-dialog',
    '.modal-container',
    '.fin-modal',
    '.lkh-modal-content',
    '.lk-modal-modern',
    '.payment-modal__content',
    '.modal-fatura-container',
    '.parcelas-modal',
    '.card-detail-modal',
    '[class*="customize-modal"]',
    '.lk-session-dialog',
].join(', ');

function resolveElement(reference) {
    if (!reference) return null;

    if (typeof reference === 'string') {
        return document.querySelector(reference);
    }

    return reference instanceof Element ? reference : null;
}

function normalizeScope(scope) {
    return scope === APP_SCOPE ? APP_SCOPE : PAGE_SCOPE;
}

function getPageRoot() {
    return document.getElementById('lk-page-modal-root');
}

function getAppRoot() {
    return document.getElementById('lk-app-modal-root');
}

function getRoot(scope) {
    const normalizedScope = normalizeScope(scope);

    if (normalizedScope === APP_SCOPE) {
        return getAppRoot() || document.body;
    }

    return getPageRoot() || getAppRoot() || document.body;
}

function getElementScope(element) {
    return normalizeScope(element?.dataset?.lkModalScope);
}

function setInertState(elements, active) {
    elements.forEach((element) => {
        if (!(element instanceof HTMLElement)) return;

        if (active) {
            element.setAttribute('inert', '');
        } else {
            element.removeAttribute('inert');
        }
    });
}

function getDialogSurface(element) {
    if (!(element instanceof Element)) return null;

    if (element.matches('.modal, [role="dialog"], [aria-modal="true"]')) {
        return element;
    }

    return element.querySelector(DIALOG_SURFACE_SELECTOR) || element;
}

function ensureDialogAccessibility(element) {
    if (!(element instanceof Element)) return null;

    const dialogSurface = getDialogSurface(element);
    if (!(dialogSurface instanceof HTMLElement)) return null;

    if (!dialogSurface.getAttribute('role')) {
        dialogSurface.setAttribute('role', 'dialog');
    }

    dialogSurface.setAttribute('aria-modal', 'true');

    if (!dialogSurface.hasAttribute('tabindex')
        && !dialogSurface.matches('a, button, input, select, textarea, summary, [tabindex]')) {
        dialogSurface.tabIndex = -1;
    }

    return dialogSurface;
}

function updateBlockedRegions(pageOpen, appOpen) {
    const chromeElements = CHROME_BLOCK_SELECTORS.flatMap((selector) => Array.from(document.querySelectorAll(selector)));
    const pageContent = document.getElementById('lk-page-content');
    const pageShell = document.getElementById('lk-page-shell');

    setInertState(chromeElements, pageOpen || appOpen);
    setInertState(pageContent ? [pageContent] : [], pageOpen);
    setInertState(pageShell ? [pageShell] : [], appOpen);
}

function updateScopeState() {
    const pageOpen = activeElements[PAGE_SCOPE].size > 0;
    const appOpen = activeElements[APP_SCOPE].size > 0;
    const anyOpen = pageOpen || appOpen;

    document.body.classList.toggle('lk-page-modal-open', pageOpen);
    document.body.classList.toggle('lk-app-modal-open', appOpen);
    document.body.classList.toggle('lk-any-modal-open', anyOpen);

    const pageRoot = getPageRoot();
    if (pageRoot) {
        pageRoot.classList.toggle('is-active', pageOpen);
        pageRoot.setAttribute('aria-hidden', pageOpen ? 'false' : 'true');
    }

    const appRoot = getAppRoot();
    if (appRoot) {
        appRoot.classList.toggle('is-active', appOpen);
        appRoot.setAttribute('aria-hidden', appOpen ? 'false' : 'true');
    }

    const pageShell = document.getElementById('lk-page-shell');
    if (pageShell) {
        pageShell.classList.toggle('lk-page-shell--modal-open', pageOpen);
    }

    updateBlockedRegions(pageOpen, appOpen);
}

function hasOpenModal() {
    return activeElements[PAGE_SCOPE].size > 0 || activeElements[APP_SCOPE].size > 0;
}

function hasBlockingDialog() {
    if (hasOpenModal()) {
        return true;
    }

    return Boolean(document.querySelector('.swal2-container:not(.swal2-backdrop-hide), .swal2-popup.swal2-show'));
}

function setElementActive(element, active) {
    if (!(element instanceof Element)) return;

    const scope = getElementScope(element);
    const scopedSet = activeElements[scope];

    if (active) {
        scopedSet.add(element);
    } else {
        scopedSet.delete(element);
    }

    updateScopeState();
}

function placeInScope(reference, options = {}) {
    const element = resolveElement(reference);
    if (!element) return null;

    const scope = normalizeScope(options.scope ?? element.dataset.lkModalScope);
    const root = getRoot(scope);

    element.dataset.lkModalScope = scope;

    if (root && element.parentElement !== root) {
        root.appendChild(element);
    }

    ensureDialogAccessibility(element);
    if (!element.hasAttribute('aria-hidden')) {
        element.setAttribute('aria-hidden', 'true');
    }

    return element;
}

function moveBootstrapBackdrop(element) {
    if (!(element instanceof Element)) return null;

    const scope = getElementScope(element);
    const root = getRoot(scope);
    if (!root) return null;

    const backdrops = Array.from(document.querySelectorAll('.modal-backdrop'));
    const backdrop = backdrops[backdrops.length - 1] || null;

    if (!backdrop) return null;

    backdrop.dataset.lkModalScope = scope;
    backdrop.dataset.lkModalOwner = element.id || '';

    if (backdrop.parentElement !== root) {
        root.appendChild(backdrop);
    }

    return backdrop;
}

function prepareBootstrapModal(reference, options = {}) {
    const element = placeInScope(reference, options);
    if (!element) return null;

    if (element.dataset.lkBootstrapModalPrepared === '1') {
        return element;
    }

    element.dataset.lkBootstrapModalPrepared = '1';

    element.addEventListener('show.bs.modal', () => {
        ensureDialogAccessibility(element);
        element.setAttribute('aria-hidden', 'false');
        setElementActive(element, true);
    });

    element.addEventListener('shown.bs.modal', () => {
        moveBootstrapBackdrop(element);
    });

    element.addEventListener('hidden.bs.modal', () => {
        element.setAttribute('aria-hidden', 'true');
        setElementActive(element, false);
    });

    if (element.classList.contains('show')) {
        element.setAttribute('aria-hidden', 'false');
        setElementActive(element, true);
        moveBootstrapBackdrop(element);
    }

    return element;
}

function syncOverlayState(element) {
    if (!(element instanceof Element)) return;

    const isActive = element.classList.contains('active') || element.classList.contains('show');
    ensureDialogAccessibility(element);
    element.setAttribute('aria-hidden', isActive ? 'false' : 'true');
    setElementActive(element, isActive);
}

function prepareOverlay(reference, options = {}) {
    const element = placeInScope(reference, options);
    if (!element) return null;

    if (!overlayObservers.has(element)) {
        const observer = new MutationObserver(() => {
            syncOverlayState(element);
        });

        observer.observe(element, {
            attributes: true,
            attributeFilter: ['class'],
        });

        overlayObservers.set(element, observer);
    }

    syncOverlayState(element);
    return element;
}

window.LK = window.LK || {};
window.LK.modalSystem = {
    PAGE_SCOPE,
    APP_SCOPE,
    getRoot,
    placeInScope,
    hasOpenModal,
    hasBlockingDialog,
    prepareBootstrapModal,
    prepareOverlay,
};

document.addEventListener('DOMContentLoaded', updateScopeState);