import { bootAccountPage } from './account-page.js';
import { initPerfilMode } from './mode-perfil.js';
import { initProfileDisplayName } from './quick-display-name.js';

export function bootPerfilPage() {
    const { context, pageReadyPromise } = bootAccountPage({
        mode: 'perfil',
        initMode: initPerfilMode,
        afterInit: (resolvedContext, profileReadyPromise) => {
            initProfileDisplayName(resolvedContext, profileReadyPromise);
        },
    });

    void context;
    void pageReadyPromise;
}
