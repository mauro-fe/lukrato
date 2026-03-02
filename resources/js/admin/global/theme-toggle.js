/**
 * ============================================================================
 * LUKRATO — Theme Toggle
 * ============================================================================
 * Alternância de tema claro/escuro com persistência no localStorage e banco.
 * Extraído de: views/admin/partials/top-navbar.php
 * ============================================================================
 */

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
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            if (!csrfToken) {
                console.warn('[Theme] CSRF token não encontrado');
                return;
            }

            const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
            const url = baseUrl + 'api/perfil/tema';

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    theme: theme,
                    csrf_token: csrfToken
                }),
                credentials: 'same-origin'
            });

            if (!response.ok) {
                console.warn('[Theme] Falha ao salvar tema:', response.status);
            }
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

    // Initialize — sincronizar com tema do servidor
    const htmlTheme = root.getAttribute('data-theme');

    if (htmlTheme && (htmlTheme === 'light' || htmlTheme === 'dark')) {
        localStorage.setItem(STORAGE_KEY, htmlTheme);
        updateThemeIcon(htmlTheme);
    } else {
        updateThemeIcon(getTheme());
    }

    if (themeBtn) themeBtn.addEventListener('click', toggleTheme);

    // Listen for theme changes from other components
    document.addEventListener(THEME_EVENT, (e) => {
        updateThemeIcon(e.detail.theme);
    });
})();
