export function initTabs(mode = 'perfil') {
    const tabs = document.querySelectorAll('.profile-tab');
    const panels = document.querySelectorAll('.profile-tab-panel');

    if (!tabs.length || !panels.length) {
        return;
    }

    const tabStorageKey = mode === 'configuracoes' ? 'configuracoes_tab' : 'perfil_tab';
    const availableTabs = Array.from(tabs)
        .map((tab) => tab.dataset.tab || '')
        .filter(Boolean);

    function switchTab(tabId) {
        tabs.forEach((tab) => {
            const isActive = tab.dataset.tab === tabId;
            tab.classList.toggle('active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        panels.forEach((panel) => {
            panel.classList.toggle('active', panel.id === `panel-${tabId}`);
        });

        try {
            localStorage.setItem(tabStorageKey, tabId);
        } catch (error) {
            void error;
        }

        history.replaceState(null, '', `#${tabId}`);
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => switchTab(tab.dataset.tab));
    });

    if (!availableTabs.length) {
        return;
    }

    const hash = location.hash.replace('#', '');
    let initialTab = availableTabs[0];

    if (hash && availableTabs.includes(hash)) {
        initialTab = hash;
    } else {
        try {
            const storedTab = localStorage.getItem(tabStorageKey);
            if (storedTab && availableTabs.includes(storedTab)) {
                initialTab = storedTab;
            }
        } catch (error) {
            void error;
        }
    }

    switchTab(initialTab);
}
