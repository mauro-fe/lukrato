import { createPerfilContext } from '../perfil/context.js';
import { initCustomize } from '../perfil/customize.js';
import { setupAvatarHandlers } from '../perfil/profile-common.js';
import { initTabs } from '../perfil/tabs.js';
import { initConfiguracoesMode } from './mode-configuracoes.js';

export function bootConfiguracoesPage() {
    const context = createPerfilContext('configuracoes');

    initCustomize();
    initTabs('configuracoes');
    setupAvatarHandlers(context);
    void initConfiguracoesMode(context);
}
