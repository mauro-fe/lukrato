/**
 * ============================================================================
 * LUKRATO - Theme Toggle
 * ============================================================================
 * Alternancia de tema claro/escuro com persistencia no localStorage e banco.
 * ============================================================================
 */

import { apiPost } from '../shared/api.js';

(() => {
    'use strict';

    const root = document.documentElement;
    const themeBtn = document.getElementById('topNavThemeToggle');
    const STORAGE_KEY = 'lukrato-theme';
    const THEME_EVENT = 'lukrato:theme-changed';

    function getTheme() {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved === 'light' || saved === 'dark') return saved;

        const attr = root.getAttribute('data-theme');
        if (attr === 'light' || attr === 'dark') return attr;

        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function updateThemeIcon(theme) {
        if (!themeBtn) return;
        themeBtn.classList.toggle('dark', theme === 'dark');
    }

    async function saveThemeToDatabase(theme) {
        try {
            const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
            await apiPost(baseUrl + 'api/perfil/tema', { theme });
        } catch (error) {
            console.warn('[Theme] Erro ao salvar tema:', error);
        }
    }

    function applyTheme(theme, saveToDb = true) {
        root.setAttribute('data-theme', theme);
        localStorage.setItem(STORAGE_KEY, theme);
        updateThemeIcon(theme);
        document.dispatchEvent(new CustomEvent(THEME_EVENT, {
            detail: { theme }
        }));

        if (saveToDb) {
            saveThemeToDatabase(theme);
        }
    }

    function toggleTheme() {
        const current = getTheme();
        const next = current === 'dark' ? 'light' : 'dark';
        applyTheme(next);
    }

    const htmlTheme = root.getAttribute('data-theme');
    if (htmlTheme && (htmlTheme === 'light' || htmlTheme === 'dark')) {
        localStorage.setItem(STORAGE_KEY, htmlTheme);
        updateThemeIcon(htmlTheme);
    } else {
        updateThemeIcon(getTheme());
    }

    if (themeBtn) {
        themeBtn.addEventListener('click', toggleTheme);
    }

    document.addEventListener(THEME_EVENT, (e) => {
        updateThemeIcon(e.detail.theme);
    });
})();
