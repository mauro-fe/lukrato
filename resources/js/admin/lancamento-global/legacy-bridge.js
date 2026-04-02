export function registerLancamentoGlobalBridge(manager) {
    window.lancamentoGlobalManager = manager;
    window.LK = window.LK || {};
    window.LK.modals = window.LK.modals || {};
    window.LK.modals.openLancamentoModal = (options = {}) => manager.openModal(options);
}

export function bootLancamentoGlobalManager(manager) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => manager.init());
        return;
    }

    manager.init();
}
