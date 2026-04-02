import { createPerfilContext } from './context.js';
import { initCustomize } from './customize.js';
import { initPerfilMode } from './mode-perfil.js';
import { setupAvatarHandlers } from './profile-common.js';
import { initTabs } from './tabs.js';

export function bootPerfilPage() {
    const context = createPerfilContext('perfil');

    initCustomize();
    initTabs('perfil');
    setupAvatarHandlers(context);
    void initPerfilMode(context);
}
