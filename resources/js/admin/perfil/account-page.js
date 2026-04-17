import { createPerfilContext } from './context.js';
import { initCustomize } from './customize.js';
import { setupAvatarHandlers } from './profile-common.js';
import { initTabs } from './tabs.js';

export function bootAccountPage({ mode, initMode, afterInit = null }) {
    const context = createPerfilContext(mode);

    initCustomize();
    initTabs(mode);
    setupAvatarHandlers(context);

    const pageReadyPromise = Promise.resolve(typeof initMode === 'function' ? initMode(context) : null);

    if (typeof afterInit === 'function') {
        afterInit(context, pageReadyPromise);
    }

    return {
        context,
        pageReadyPromise,
    };
}