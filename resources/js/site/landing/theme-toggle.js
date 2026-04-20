/**
 * Theme Toggle — Landing
 * Reads/writes localStorage('lukrato-theme'), toggles data-theme on <html>.
 */

const STORAGE_KEY = 'lukrato-theme';
const THEME_EVENT = 'lukrato:theme-changed';

function syncThemeImages(theme) {
    document.querySelectorAll('img[data-theme-image-light][data-theme-image-dark]').forEach(img => {
        const lightSrc = img.getAttribute('data-theme-image-light');
        const darkSrc = img.getAttribute('data-theme-image-dark');
        const nextSrc = theme === 'dark' ? darkSrc : lightSrc;

        if (nextSrc && img.getAttribute('src') !== nextSrc) {
            img.setAttribute('src', nextSrc);
        }
    });
}

function getTheme() {
    const root = document.documentElement;
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved === 'light' || saved === 'dark') return saved;
    const attr = root.getAttribute('data-theme');
    if (attr === 'light' || attr === 'dark') return attr;
    // Padrão sempre light — não segue prefers-color-scheme do SO
    return 'light';
}

function updateToggleIcons(theme) {
    document.querySelectorAll('.lk-theme-toggle').forEach(btn => {
        btn.classList.toggle('dark', theme === 'dark');
    });
}

function applyTheme(theme) {
    const root = document.documentElement;
    root.setAttribute('data-theme', theme);
    localStorage.setItem(STORAGE_KEY, theme);
    updateToggleIcons(theme);
    syncThemeImages(theme);

    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', theme === 'dark' ? '#092741' : '#e67e22');

    document.dispatchEvent(new CustomEvent(THEME_EVENT, { detail: { theme } }));

    if (window.lucide) {
        requestAnimationFrame(() => lucide.createIcons());
    }
}

function toggleTheme() {
    const current = getTheme();
    applyTheme(current === 'dark' ? 'light' : 'dark');
}

export function init() {
    const currentTheme = getTheme();
    // Aplica tema completo (inclusive salva no localStorage) para garantir consistência
    applyTheme(currentTheme);

    document.querySelectorAll('.lk-theme-toggle').forEach(btn => {
        btn.addEventListener('click', toggleTheme);
    });

    document.addEventListener(THEME_EVENT, (e) => {
        updateToggleIcons(e.detail.theme);
    });
}
