function initCardPage() {
    window.lucide?.createIcons?.();
}

export function bootSiteCardPage() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCardPage);
    } else {
        initCardPage();
    }
}
