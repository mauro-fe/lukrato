/**
 * ============================================================================
 * LUKRATO - Theme Toggle
 * ============================================================================
 * Alternancia de tema claro/escuro com persistencia no localStorage e banco.
 * ============================================================================
 */

import {
    createSystemThemeMediaQuery,
    fetchThemePreference,
    getInitialAppliedTheme,
    normalizeThemePreference,
    readStoredThemePreference,
    resolveAppliedTheme,
    THEME_EVENT,
    storeThemePreference,
} from './theme-preferences.js';
import { resolveThemePreferenceEndpoint } from '../api/endpoints/preferences.js';
import { apiPost } from '../shared/api.js';

(() => {
    'use strict';

    const root = document.documentElement;
    const quickThemeButtons = Array.from(document.querySelectorAll('[data-theme-toggle], #topNavThemeToggle'));
    const themeChoiceButtons = Array.from(document.querySelectorAll('[data-theme-choice]'));
    const systemThemeMediaQuery = createSystemThemeMediaQuery();
    let currentThemePreference = readStoredThemePreference() ?? 'system';
    let hasLocalThemeInteraction = false;

    function getAppliedTheme() {
        const attr = root.getAttribute('data-theme');
        if (attr === 'light' || attr === 'dark') return attr;

        return getInitialAppliedTheme({ root });
    }

    function getThemeLabels(theme) {
        const appliedTheme = theme === 'dark' ? 'dark' : 'light';
        const nextTheme = appliedTheme === 'dark' ? 'light' : 'dark';
        const currentLabel = appliedTheme === 'dark' ? 'Dark' : 'Light';
        const nextLabel = nextTheme === 'dark' ? 'Dark' : 'Light';

        return {
            currentLabel,
            nextLabel,
            title: `Tema atual: ${currentLabel}`,
        };
    }

    function updateThemeLabel(button, theme) {
        const labels = getThemeLabels(theme);
        const currentLabel = button.querySelector('[data-theme-current-label]');
        const nextLabel = button.querySelector('[data-theme-next-label]');

        if (currentLabel) {
            currentLabel.textContent = labels.currentLabel;
        }

        if (nextLabel) {
            nextLabel.textContent = labels.nextText;
        }

        button.setAttribute('aria-label', labels.ariaLabel);
        button.setAttribute('title', labels.title);
    }

    function updateThemeIcon(theme) {
        if (quickThemeButtons.length === 0) {
            return;
        }

        quickThemeButtons.forEach((button) => {
            button.classList.toggle('dark', theme === 'dark');
            updateThemeLabel(button, theme);
        });
    }

    function syncThemeChoiceButtons(themePreference) {
        if (themeChoiceButtons.length === 0) {
            return;
        }

        const activePreference = normalizeThemePreference(
            themePreference,
            currentThemePreference ?? readStoredThemePreference() ?? 'system',
        ) ?? 'system';

        themeChoiceButtons.forEach((button) => {
            const buttonPreference = normalizeThemePreference(button.dataset.themeChoice, null);
            const isActive = buttonPreference !== null && buttonPreference === activePreference;

            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    async function saveThemePreferenceToDatabase(themePreference) {
        try {
            await apiPost(resolveThemePreferenceEndpoint(), { theme: themePreference });
        } catch (error) {
            console.warn('[Theme] Erro ao salvar tema:', error);
        }
    }

    function applyResolvedTheme(theme, options = {}) {
        const {
            dispatchEvent = true,
        } = options;
        const appliedTheme = theme === 'dark' ? 'dark' : 'light';
        const previousTheme = root.getAttribute('data-theme');

        root.setAttribute('data-theme', appliedTheme);
        updateThemeIcon(appliedTheme);

        if (dispatchEvent && previousTheme !== appliedTheme) {
            document.dispatchEvent(new CustomEvent(THEME_EVENT, {
                detail: { theme: appliedTheme }
            }));
        }
    }

    function applyThemePreference(themePreference, options = {}) {
        const normalizedPreference = normalizeThemePreference(
            themePreference,
            currentThemePreference ?? readStoredThemePreference() ?? 'system',
        ) ?? 'system';

        currentThemePreference = normalizedPreference;
        const appliedTheme = resolveAppliedTheme(normalizedPreference, {
            fallbackTheme: getAppliedTheme(),
        });

        applyResolvedTheme(appliedTheme, {
            dispatchEvent: options.dispatchEvent ?? true,
        });

        if (options.syncStorage ?? true) {
            storeThemePreference(normalizedPreference);
        }

        syncThemeChoiceButtons(normalizedPreference);

        if (options.saveToDb ?? false) {
            void saveThemePreferenceToDatabase(normalizedPreference);
        }
    }

    function toggleTheme() {
        hasLocalThemeInteraction = true;
        const currentAppliedTheme = getAppliedTheme();
        const nextPreference = currentAppliedTheme === 'dark' ? 'light' : 'dark';

        applyThemePreference(nextPreference, {
            saveToDb: true,
            dispatchEvent: true,
            syncStorage: true,
        });
    }

    function handleThemeChoiceClick(event) {
        const nextPreference = normalizeThemePreference(event.currentTarget?.dataset?.themeChoice, null);
        if (!nextPreference) {
            return;
        }

        hasLocalThemeInteraction = true;
        applyThemePreference(nextPreference, {
            saveToDb: true,
            dispatchEvent: true,
            syncStorage: true,
        });
    }

    async function hydrateThemePreference() {
        const result = await fetchThemePreference();
        if (!result.ok || !result.theme || hasLocalThemeInteraction) {
            return;
        }

        applyThemePreference(result.theme, {
            saveToDb: false,
            dispatchEvent: true,
            syncStorage: true,
        });
    }

    function handleSystemThemeChange() {
        if (currentThemePreference !== 'system') {
            return;
        }

        applyThemePreference('system', {
            saveToDb: false,
            dispatchEvent: true,
            syncStorage: true,
        });
    }

    applyResolvedTheme(getInitialAppliedTheme({ root }), {
        dispatchEvent: false,
    });
    syncThemeChoiceButtons(currentThemePreference);

    quickThemeButtons.forEach((button) => {
        button.addEventListener('click', toggleTheme);
    });

    themeChoiceButtons.forEach((button) => {
        button.addEventListener('click', handleThemeChoiceClick);
    });

    if (typeof systemThemeMediaQuery?.addEventListener === 'function') {
        systemThemeMediaQuery.addEventListener('change', handleSystemThemeChange);
    } else if (typeof systemThemeMediaQuery?.addListener === 'function') {
        systemThemeMediaQuery.addListener(handleSystemThemeChange);
    }

    void hydrateThemePreference();

    document.addEventListener(THEME_EVENT, (e) => {
        updateThemeIcon(e.detail.theme);
    });
})();
