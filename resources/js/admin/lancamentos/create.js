import '../../../css/admin/lancamentos/create.css';

function readWizardOptions(root) {
    return {
        source: root.dataset.source === 'contas' ? 'contas' : 'global',
        presetAccountId: root.dataset.presetAccountId || null,
        tipo: root.dataset.tipo || null,
    };
}

function releaseWizardBoot(root) {
    const page = root?.closest('.lancamento-create-page')
        || document.querySelector('.lancamento-create-page[data-wizard-booting="true"]');

    root?.removeAttribute('data-wizard-booting');
    page?.removeAttribute('data-wizard-booting');
}

document.addEventListener('DOMContentLoaded', async () => {
    const root = document.getElementById('modalLancamentoGlobalOverlay');
    const manager = window.lancamentoGlobalManager;

    if (!root || !manager?.openModal) {
        releaseWizardBoot(root);
        return;
    }

    try {
        await manager.openModal(readWizardOptions(root));
    } finally {
        releaseWizardBoot(root);
    }
});
