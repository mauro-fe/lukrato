import { bootAccountPage } from '../perfil/account-page.js';
import { initConfiguracoesMode } from './mode-configuracoes.js';

export function bootConfiguracoesPage() {
    const { pageReadyPromise } = bootAccountPage({
        mode: 'configuracoes',
        initMode: initConfiguracoesMode,
    });

    void pageReadyPromise;
}
