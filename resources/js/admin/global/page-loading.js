/**
 * Lukrato Page Loading
 *
 * Controls the admin content-area loading state (inside .lk-main).
 * Keeps sidebar, top-navbar and footer visible.
 */
(function () {
    'use strict';

    const DEFAULT_DELAY_MS = 140;
    const MIN_VISIBLE_MS = 240;
    const DEFAULT_MESSAGE = 'Carregando...';
    const DEFAULT_SUBTITLE = 'Preparando seus dados';
    const BOOT_MAX_WAIT_MS = 15000;

    const state = {
        activeCount: 0,
        showTimer: null,
        hideTimer: null,
        shownAt: 0,
        anchorRaf: null,
        viewportTrackingBound: false,
        onViewportChange: null,
        boot: {
            started: false,
            loadDone: document.readyState === 'complete',
            pending: 0,
            release: null,
            fallbackTimer: null,
            finished: false,
        },
    };

    function getElements() {
        return {
            shell: document.getElementById('lk-page-shell'),
            loader: document.getElementById('lk-page-loader'),
            content: document.getElementById('lk-page-content'),
            title: document.getElementById('lk-page-loader-title'),
            subtitle: document.getElementById('lk-page-loader-subtitle'),
        };
    }

    function clearTimers() {
        if (state.showTimer) {
            clearTimeout(state.showTimer);
            state.showTimer = null;
        }
        if (state.hideTimer) {
            clearTimeout(state.hideTimer);
            state.hideTimer = null;
        }
    }

    function setCopy(elements, message, subtitle) {
        if (elements.title) {
            elements.title.textContent = String(message || DEFAULT_MESSAGE);
        }
        if (elements.subtitle) {
            elements.subtitle.textContent = String(subtitle || DEFAULT_SUBTITLE);
        }
    }

    function getVisibleCenter(shellRect) {
        const visibleLeft = Math.max(0, shellRect.left);
        const visibleRight = Math.min(window.innerWidth, shellRect.right);
        const visibleTop = Math.max(0, shellRect.top);
        const visibleBottom = Math.min(window.innerHeight, shellRect.bottom);

        const centerX = visibleRight > visibleLeft
            ? (visibleLeft + visibleRight) / 2
            : Math.max(24, Math.min(window.innerWidth - 24, shellRect.left + (shellRect.width / 2)));

        const centerY = visibleBottom > visibleTop
            ? (visibleTop + visibleBottom) / 2
            : Math.max(24, Math.min(window.innerHeight - 24, shellRect.top + (shellRect.height / 2)));

        return {
            x: Math.round(centerX),
            y: Math.round(centerY),
        };
    }

    function applyLoaderAnchor(elements = getElements()) {
        if (!elements.shell) {
            return;
        }

        const rect = elements.shell.getBoundingClientRect();
        const center = getVisibleCenter(rect);
        elements.shell.style.setProperty('--lk-page-loader-x', `${center.x}px`);
        elements.shell.style.setProperty('--lk-page-loader-y', `${center.y}px`);
    }

    function queueAnchorUpdate() {
        if (state.anchorRaf) {
            return;
        }

        state.anchorRaf = requestAnimationFrame(() => {
            state.anchorRaf = null;
            const elements = getElements();
            if (elements.shell?.dataset.pageLoadingState === 'active') {
                applyLoaderAnchor(elements);
            }
        });
    }

    function bindViewportTracking() {
        if (state.viewportTrackingBound) {
            return;
        }

        if (!state.onViewportChange) {
            state.onViewportChange = () => queueAnchorUpdate();
        }

        state.viewportTrackingBound = true;
        window.addEventListener('scroll', state.onViewportChange, true);
        window.addEventListener('resize', state.onViewportChange);
    }

    function unbindViewportTracking() {
        if (!state.viewportTrackingBound || !state.onViewportChange) {
            return;
        }

        state.viewportTrackingBound = false;
        window.removeEventListener('scroll', state.onViewportChange, true);
        window.removeEventListener('resize', state.onViewportChange);

        if (state.anchorRaf) {
            cancelAnimationFrame(state.anchorRaf);
            state.anchorRaf = null;
        }
    }

    function setVisible(visible, message = DEFAULT_MESSAGE, subtitle = DEFAULT_SUBTITLE) {
        const elements = getElements();
        if (!elements.shell || !elements.loader || !elements.content) {
            return;
        }

        if (visible) {
            setCopy(elements, message, subtitle);
            elements.shell.dataset.pageLoadingState = 'active';
            elements.shell.setAttribute('aria-busy', 'true');
            elements.loader.hidden = false;
            elements.loader.setAttribute('aria-hidden', 'false');
            bindViewportTracking();
            applyLoaderAnchor(elements);
            state.shownAt = Date.now();

            if (typeof window.__LK_RELEASE_PREBOOT__ === 'function') {
                window.__LK_RELEASE_PREBOOT__();
            }

            return;
        }

        elements.shell.dataset.pageLoadingState = 'idle';
        elements.shell.setAttribute('aria-busy', 'false');
        elements.loader.hidden = true;
        elements.loader.setAttribute('aria-hidden', 'true');
        elements.shell.style.removeProperty('--lk-page-loader-x');
        elements.shell.style.removeProperty('--lk-page-loader-y');
        unbindViewportTracking();
    }

    function scheduleHide() {
        clearTimeout(state.showTimer);
        state.showTimer = null;

        const elements = getElements();
        if (!elements.shell || elements.shell.dataset.pageLoadingState !== 'active') {
            setVisible(false);
            return;
        }

        const elapsed = Date.now() - state.shownAt;
        const wait = Math.max(0, MIN_VISIBLE_MS - elapsed);

        if (state.hideTimer) {
            clearTimeout(state.hideTimer);
        }
        state.hideTimer = setTimeout(() => {
            state.hideTimer = null;
            if (state.activeCount === 0) {
                setVisible(false);
            }
        }, wait);
    }

    function normalizeOptions(options) {
        if (typeof options === 'string') {
            return { message: options };
        }
        return options && typeof options === 'object' ? options : {};
    }

    function start(options = {}) {
        const opts = normalizeOptions(options);
        const message = opts.message || DEFAULT_MESSAGE;
        const subtitle = opts.subtitle || DEFAULT_SUBTITLE;
        const delay = Number.isFinite(opts.delay) ? Math.max(0, opts.delay) : DEFAULT_DELAY_MS;
        let released = false;

        state.activeCount += 1;

        if (state.activeCount === 1) {
            if (state.hideTimer) {
                clearTimeout(state.hideTimer);
                state.hideTimer = null;
            }
            if (state.showTimer) {
                clearTimeout(state.showTimer);
            }
            if (delay === 0) {
                setVisible(true, message, subtitle);
            } else {
                state.showTimer = setTimeout(() => {
                    state.showTimer = null;
                    if (state.activeCount > 0) {
                        setVisible(true, message, subtitle);
                    }
                }, delay);
            }
        } else {
            const elements = getElements();
            if (elements.shell?.dataset.pageLoadingState === 'active') {
                setCopy(elements, message, subtitle);
                queueAnchorUpdate();
            }
        }

        return function release() {
            if (released) {
                return;
            }
            released = true;
            state.activeCount = Math.max(0, state.activeCount - 1);
            if (state.activeCount === 0) {
                scheduleHide();
            }
        };
    }

    function show(message = DEFAULT_MESSAGE, options = {}) {
        const opts = normalizeOptions(options);
        return start({
            ...opts,
            message,
            delay: opts.delay ?? 0,
        });
    }

    function hide() {
        state.activeCount = 0;
        clearTimers();
        scheduleHide();
    }

    async function withLoading(task, options = {}) {
        const release = start(options);
        try {
            if (typeof task === 'function') {
                return await task();
            }
            return await task;
        } finally {
            release();
        }
    }

    function setSectionLoading(target, isLoading = true) {
        const element = typeof target === 'string' ? document.querySelector(target) : target;
        if (!element) {
            return;
        }

        if (isLoading) {
            element.classList.add('lk-section-loading');
            element.setAttribute('aria-busy', 'true');
            return;
        }

        element.classList.remove('lk-section-loading');
        element.setAttribute('aria-busy', 'false');
    }

    function startNavigationLoading(options = {}) {
        return start({
            message: 'Carregando pagina...',
            subtitle: 'Preparando conteudo',
            delay: 0,
            ...normalizeOptions(options),
        });
    }

    function dispatchReadyEvent() {
        if (typeof window.CustomEvent === 'function') {
            window.dispatchEvent(new CustomEvent('lk:page-ready'));
            return;
        }

        const event = document.createEvent('Event');
        event.initEvent('lk:page-ready', true, true);
        window.dispatchEvent(event);
    }

    function finishBootLoading() {
        const boot = state.boot;
        if (!boot.started || boot.finished) {
            return;
        }

        boot.finished = true;
        boot.started = false;
        boot.pending = 0;

        if (boot.fallbackTimer) {
            clearTimeout(boot.fallbackTimer);
            boot.fallbackTimer = null;
        }

        if (typeof boot.release === 'function') {
            boot.release();
        }
        boot.release = null;

        window.__LK_INITIAL_PAGE_READY__ = true;

        if (typeof window.__LK_RELEASE_PREBOOT__ === 'function') {
            window.__LK_RELEASE_PREBOOT__();
        }

        dispatchReadyEvent();
    }

    function maybeFinishBootLoading() {
        const boot = state.boot;
        if (!boot.started || boot.finished) {
            return;
        }
        if (!boot.loadDone) {
            return;
        }
        if (boot.pending > 0) {
            return;
        }
        finishBootLoading();
    }

    function bootRequestStart() {
        const boot = state.boot;
        if (!boot.started || boot.finished) {
            return null;
        }

        boot.pending += 1;
        let released = false;

        return function releaseBootRequest() {
            if (released) {
                return;
            }
            released = true;
            boot.pending = Math.max(0, boot.pending - 1);
            maybeFinishBootLoading();
        };
    }

    function initBootLoading() {
        const boot = state.boot;
        window.__LK_INITIAL_PAGE_READY__ = false;

        if (boot.loadDone) {
            boot.finished = true;
            window.__LK_INITIAL_PAGE_READY__ = true;

            if (typeof window.__LK_RELEASE_PREBOOT__ === 'function') {
                window.__LK_RELEASE_PREBOOT__();
            }

            return;
        }

        boot.started = true;
        boot.finished = false;
        boot.pending = 0;
        boot.release = startNavigationLoading({
            message: 'Carregando página...',
            subtitle: 'Preparando conteúdo',
            delay: 0,
        });

        window.addEventListener('load', () => {
            boot.loadDone = true;
            maybeFinishBootLoading();
        }, { once: true });

        boot.fallbackTimer = setTimeout(() => {
            boot.loadDone = true;
            finishBootLoading();
        }, BOOT_MAX_WAIT_MS);
    }

    window.LKPageLoading = {
        start,
        show,
        hide,
        withLoading,
        setSectionLoading,
        startNavigationLoading,
        bootRequestStart,
    };

    window.LK = window.LK || {};
    window.LK.pageLoading = show;
    window.LK.hidePageLoading = hide;
    window.LK.withPageLoading = withLoading;
    window.LK.sectionLoading = setSectionLoading;
    window.LK.startNavigationLoading = startNavigationLoading;

    initBootLoading();
})();

