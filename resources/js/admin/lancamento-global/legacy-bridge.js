export function registerLancamentoGlobalBridge(manager) {
    window.lancamentoGlobalManager = manager;
    window.LK = window.LK || {};
    window.LK.modals = window.LK.modals || {};
    window.LK.modals.openLancamentoModal = (options = {}) => manager.openModal(options);
}

export function bootLancamentoGlobalManager(manager) {
    const init = () => {
        window.LK?.modalSystem?.prepareOverlay('#modalLancamentoGlobalOverlay', { scope: 'app' });
        manager.init();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
        return;
    }

    init();
}
