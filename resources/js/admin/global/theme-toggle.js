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
    resolveAppliedTheme,
    THEME_EVENT,
    storeAppliedTheme,
} from './theme-preferences.js';
import { resolveThemePreferenceEndpoint } from '../api/endpoints/preferences.js';
import { apiPost } from '../shared/api.js';

(() => {
    'use strict';

    const root = document.documentElement;
    const themeBtn = document.getElementById('topNavThemeToggle');
    const systemThemeMediaQuery = createSystemThemeMediaQuery();
    let currentThemePreference = null;
    let hasLocalThemeInteraction = false;

    function getTheme() {
        const attr = root.getAttribute('data-theme');
        if (attr === 'light' || attr === 'dark') return attr;

        return getInitialAppliedTheme({ root });
    }

    function updateThemeIcon(theme) {
        if (!themeBtn) return;
        themeBtn.classList.toggle('dark', theme === 'dark');
    }

    async function saveThemeToDatabase(theme) {
        try {
            await apiPost(resolveThemePreferenceEndpoint(), { theme });
        } catch (error) {
            console.warn('[Theme] Erro ao salvar tema:', error);
        }
    }

    function applyTheme(theme, options = {}) {
        const {
            saveToDb = true,
            dispatchEvent = true,
            syncStorage = true,
        } = options;
        const appliedTheme = theme === 'dark' ? 'dark' : 'light';
        const previousTheme = root.getAttribute('data-theme');

        root.setAttribute('data-theme', appliedTheme);
        if (syncStorage) {
            storeAppliedTheme(appliedTheme);
        }
        updateThemeIcon(appliedTheme);

        if (dispatchEvent && previousTheme !== appliedTheme) {
            document.dispatchEvent(new CustomEvent(THEME_EVENT, {
                detail: { theme: appliedTheme }
            }));
        }

        if (saveToDb) {
            saveThemeToDatabase(appliedTheme);
        }
    }

    function applyThemePreference(themePreference, options = {}) {
        currentThemePreference = themePreference;
        const appliedTheme = resolveAppliedTheme(themePreference, {
            fallbackTheme: getTheme(),
        });

        applyTheme(appliedTheme, {
            saveToDb: options.saveToDb ?? false,
            dispatchEvent: options.dispatchEvent ?? true,
            syncStorage: options.syncStorage ?? true,
        });
    }

    function toggleTheme() {
        hasLocalThemeInteraction = true;
        const current = getTheme();
        const next = current === 'dark' ? 'light' : 'dark';
        currentThemePreference = next;
        applyTheme(next);
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

    function handleSystemThemeChange(event) {
        if (currentThemePreference !== 'system') {
            return;
        }

        applyTheme(event?.matches ? 'dark' : 'light', {
            saveToDb: false,
            dispatchEvent: true,
            syncStorage: true,
        });
    }

    applyTheme(getInitialAppliedTheme({ root }), {
        saveToDb: false,
        dispatchEvent: false,
        syncStorage: true,
    });

    if (themeBtn) {
        themeBtn.addEventListener('click', toggleTheme);
    }

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
