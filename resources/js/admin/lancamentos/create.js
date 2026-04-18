import '../../../css/admin/lancamentos/create.css';

function readWizardOptions(root) {
    return {
        source: root.dataset.source === 'contas' ? 'contas' : 'global',
        presetAccountId: root.dataset.presetAccountId || null,
        tipo: root.dataset.tipo || null,
    };
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('modalLancamentoGlobalOverlay');
    const manager = window.lancamentoGlobalManager;

    if (!root || !manager?.openModal) {
        return;
    }

    window.setTimeout(() => {
        manager.openModal(readWizardOptions(root));
    }, 0);
});
